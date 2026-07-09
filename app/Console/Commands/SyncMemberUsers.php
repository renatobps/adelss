<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Services\Members\MemberUserService;
use Illuminate\Console\Command;

class SyncMemberUsers extends Command
{
    protected $signature = 'members:sync-users {--dry-run : Apenas lista membros sem usuário}';

    protected $description = 'Cria usuários de acesso para membros que têm e-mail mas ainda não possuem login';

    public function handle(MemberUserService $memberUserService): int
    {
        $members = Member::whereNotNull('email')
            ->where('email', '!=', '')
            ->whereDoesntHave('user')
            ->orderBy('name')
            ->get();

        if ($members->isEmpty()) {
            $this->info('Nenhum membro pendente de usuário de acesso.');

            return self::SUCCESS;
        }

        $this->info("Membros com e-mail sem usuário: {$members->count()}");

        if ($this->option('dry-run')) {
            foreach ($members as $member) {
                $this->line("- [{$member->id}] {$member->name} <{$member->email}>");
            }

            return self::SUCCESS;
        }

        $created = 0;

        foreach ($members as $member) {
            $memberUserService->syncFromMember($member, forceDefaultPassword: true);
            $created++;
        }

        $this->info("Usuários criados: {$created}. Senha inicial: 123456");

        return self::SUCCESS;
    }
}
