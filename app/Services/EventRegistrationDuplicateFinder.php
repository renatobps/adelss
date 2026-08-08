<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Support\Collection;

/**
 * Agrupa inscrições do mesmo evento que compartilham e-mail ou telefone.
 *
 * Nem todo grupo é lixo: é comum uma mãe inscrever dois filhos com o próprio
 * contato. Por isso o resultado é sempre apresentado para decisão humana —
 * quem decide o que remover é a interface ou o comando, nunca este serviço.
 */
class EventRegistrationDuplicateFinder
{
    /**
     * Grupos com dois ou mais registros do mesmo contato, ordenados do mais antigo
     * para o mais novo dentro de cada grupo.
     *
     * @return Collection<int, Collection<int, EventRegistration>>
     */
    public function groupsForEvent(Event|int $event): Collection
    {
        $eventId = $event instanceof Event ? $event->id : (int) $event;

        $registrations = EventRegistration::query()
            ->where('event_id', $eventId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return $this->group($registrations);
    }

    /**
     * @param  Collection<int, EventRegistration>  $registrations
     * @return Collection<int, Collection<int, EventRegistration>>
     */
    public function group(Collection $registrations): Collection
    {
        $parent = [];
        $find = function (int $id) use (&$parent, &$find): int {
            while (($parent[$id] ?? $id) !== $id) {
                $id = $parent[$id];
            }

            return $id;
        };
        $union = function (int $a, int $b) use (&$parent, $find): void {
            $rootA = $find($a);
            $rootB = $find($b);
            if ($rootA !== $rootB) {
                // O menor id vence: o registro mais antigo permanece como raiz do grupo.
                $parent[max($rootA, $rootB)] = min($rootA, $rootB);
            }
        };

        $seen = [];
        foreach ($registrations as $registration) {
            $parent[$registration->id] ??= $registration->id;
            foreach ($registration->duplicateKeys() as $key) {
                if (isset($seen[$key])) {
                    $union($registration->id, $seen[$key]);
                } else {
                    $seen[$key] = $registration->id;
                }
            }
        }

        return $registrations
            ->groupBy(fn (EventRegistration $r) => $find($r->id))
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->map(fn (Collection $group) => $group->sortBy([['created_at', 'asc'], ['id', 'asc']])->values())
            ->values();
    }

    /** Ids de todos os registros que participam de algum grupo duplicado. */
    public function duplicateIdsForEvent(Event|int $event): array
    {
        return $this->groupsForEvent($event)
            ->flatten(1)
            ->pluck('id')
            ->all();
    }

    /**
     * Ids sugeridos para remoção: tudo menos o registro mais antigo de cada grupo,
     * que é o legítimo.
     */
    public function redundantIdsForEvent(Event|int $event): array
    {
        return $this->groupsForEvent($event)
            ->flatMap(fn (Collection $group) => $group->skip(1)->pluck('id'))
            ->all();
    }
}
