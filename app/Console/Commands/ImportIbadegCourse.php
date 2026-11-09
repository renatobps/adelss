<?php

namespace App\Console\Commands;

use App\Services\IbadegCourseImporter;
use Illuminate\Console\Command;

class ImportIbadegCourse extends Command
{
    protected $signature = 'ensino:import-ibadeg {source? : Diretório contendo LIVROS, PROVAS e SITE PROVAS; por padrão usa o curso incluído no projeto}';

    protected $description = 'Importa o curso IBADEG com apostilas, provas e gabaritos, preservando tentativas existentes';

    public function handle(IbadegCourseImporter $importer): int
    {
        $study = $importer->import($this->argument('source'));
        $this->info('Curso disponível: '.$study->name);
        $this->info('Disciplinas: '.$study->forms()->count());
        $this->line('/ensino/estudos/'.$study->id);

        return self::SUCCESS;
    }
}
