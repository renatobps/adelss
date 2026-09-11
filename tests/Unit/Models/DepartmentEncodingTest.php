<?php

namespace Tests\Unit\Models;

use App\Models\Department;
use Tests\TestCase;

class DepartmentEncodingTest extends TestCase
{
    public function test_repara_acentos_quebrados_na_descricao_e_no_nome(): void
    {
        $department = new Department([
            'name' => "Crian\u{251C}\u{00BA}as",
            'description' => "Formando uma gera\u{251C}\u{00BA}\u{251C}\u{00FA}o comprometida com Cristo, Seu Reino e Sua miss\u{251C}\u{00FA}o.",
        ]);

        $this->assertSame('Crianças', $department->name);
        $this->assertSame(
            'Formando uma geração comprometida com Cristo, Seu Reino e Sua missão.',
            $department->description
        );

        $department->description = "Conduzindo a igreja \u{251C}\u{00E1} adora\u{251C}\u{00BA}\u{251C}\u{00FA}o e servindo com excel\u{251C}\u{00AC}ncia para a gl\u{251C}\u{2502}ria de Deus.";
        $this->assertSame(
            'Conduzindo a igreja à adoração e servindo com excelência para a glória de Deus.',
            $department->description
        );
    }

    public function test_remove_sufixo_invalido_da_descricao(): void
    {
        $department = new Department([
            'name' => 'Jovens',
            'description' => "Formando uma gera\u{251C}\u{00BA}\u{251C}\u{00FA}o comprometida com Cristo.\u{00AD}\u{0192}\u{00F6}\u{00D1}",
        ]);

        $this->assertSame(
            'Formando uma geração comprometida com Cristo.',
            $department->description
        );
    }

    public function test_mantem_texto_ja_correto(): void
    {
        $department = new Department([
            'name' => 'Intercessão',
            'description' => 'Responsável pela intercessão',
        ]);

        $this->assertSame('Intercessão', $department->name);
        $this->assertSame('Responsável pela intercessão', $department->description);
    }

    public function test_aceita_descricao_nula(): void
    {
        $department = new Department(['name' => 'Louvor', 'description' => null]);

        $this->assertNull($department->description);
    }
}
