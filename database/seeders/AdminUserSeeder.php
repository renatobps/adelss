<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Dados do administrador inicial
        $adminEmail = 'renato.bps@hotmail.com';

        // Buscar membro correspondente
        $member = Member::where('email', $adminEmail)->first();

        if (!$member) {
            $this->command?->warn("Membro com e-mail {$adminEmail} não encontrado. Crie o membro antes de rodar este seeder.");
            return;
        }

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $member->name,
                'password' => Hash::make('123456'), // Senha inicial (pode ser alterada depois)
                'is_admin' => true,
                'member_id' => $member->id,
            ]
        );

        $this->command?->info('Usuário administrador criado/atualizado com sucesso (email: ' . $adminEmail . ', senha: 123456).');
    }
}

