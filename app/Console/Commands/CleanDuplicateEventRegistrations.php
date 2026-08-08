<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\AuditLogger;
use App\Services\EventRegistrationDuplicateFinder;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class CleanDuplicateEventRegistrations extends Command
{
    protected $signature = 'inscricoes:limpar-duplicadas
                            {--event= : Limita a um evento específico (id)}
                            {--force : Remove sem perguntar (para uso em script)}';

    protected $description = 'Identifica inscrições duplicadas por evento + contato, mantém a mais antiga e remove as demais';

    public function handle(EventRegistrationDuplicateFinder $finder): int
    {
        $events = Event::query()
            ->when($this->option('event'), fn ($q) => $q->whereKey((int) $this->option('event')))
            ->whereHas('registrations')
            ->orderBy('id')
            ->get();

        if ($events->isEmpty()) {
            $this->info('Nenhum evento com inscrições encontrado.');

            return self::SUCCESS;
        }

        $toRemove = [];
        $totalGroups = 0;

        foreach ($events as $event) {
            $groups = $finder->groupsForEvent($event);
            if ($groups->isEmpty()) {
                continue;
            }

            $totalGroups += $groups->count();
            $this->newLine();
            $this->line("<comment>Evento #{$event->id} — {$event->title}</comment>");

            foreach ($groups as $group) {
                $this->renderGroup($group);
                foreach ($group->skip(1) as $duplicate) {
                    $toRemove[] = $duplicate;
                }
            }
        }

        if ($toRemove === []) {
            $this->newLine();
            $this->info('Nenhuma inscrição duplicada encontrada.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn(count($toRemove) . ' inscrição(ões) em ' . $totalGroups . ' grupo(s) serão removidas (soft delete).');
        $this->line('A mais antiga de cada grupo é mantida — ela é a legítima.');

        if (! $this->option('force') && ! $this->confirm('Confirmar a remoção?', false)) {
            $this->info('Nada foi removido.');

            return self::SUCCESS;
        }

        $removed = 0;
        foreach ($toRemove as $registration) {
            if ($registration->loadMissing('payment')->hasConfirmedPayment()) {
                $this->warn("Ignorada #{$registration->id} ({$registration->name}): possui pagamento confirmado — cancele em vez de excluir.");
                continue;
            }
            $registration->delete();
            $removed++;
        }

        AuditLogger::log(
            'agenda',
            'inscricoes.limpar-duplicadas',
            "Remoção de {$removed} inscrição(ões) duplicada(s) via comando.",
            ['event_id' => $this->option('event'), 'removed' => $removed]
        );

        $this->info("{$removed} inscrição(ões) removida(s). Use o filtro \"Excluídas\" na tela de inscrições para restaurar, se necessário.");

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, EventRegistration>  $group
     */
    private function renderGroup(Collection $group): void
    {
        $rows = $group->map(fn (EventRegistration $r, int $i) => [
            $i === 0 ? 'MANTER' : 'remover',
            $r->id,
            $r->registration_number ?: '—',
            $r->name,
            $r->email ?: '—',
            $r->phone ?: '—',
            $r->created_at?->format('d/m/Y H:i:s'),
        ])->all();

        $this->table(['Ação', 'Id', 'Inscrição', 'Nome', 'E-mail', 'Telefone', 'Criada em'], $rows);
    }
}
