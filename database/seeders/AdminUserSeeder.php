<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar ou criar o membro
        $member = \App\Models\Member::firstOrNew(['email' => 'renato.bps@hotmail.com']);
        if (!$member->exists) {
            $member->name = 'Renato Bento Pereira de Souza';
            $member->status = 'ativo';
            $member->save();
        }

        // Criar ou atualizar o usuário admin
        $user = User::firstOrNew(['email' => 'renato.bps@hotmail.com']);
        $user->name = 'Renato Bento';
        $user->password = Hash::make('Altruismo1@');
        $user->is_admin = true;
        $user->member_id = $member->id;
        $user->save();

        $this->command->info('Usuário admin criado/atualizado com sucesso!');
        $this->command->info('Email: renato.bps@hotmail.com');
        $this->command->info('Senha: Altruismo1@');
        $this->command->info('Membro vinculado: ' . $member->name . ' (ID: ' . $member->id . ')');
    }
}
