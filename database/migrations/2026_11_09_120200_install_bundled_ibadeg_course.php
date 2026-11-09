<?php

use App\Services\IbadegCourseImporter;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(IbadegCourseImporter::class)->import();
    }

    public function down(): void
    {
        // O rollback do deploy preserva o curso e as respostas dos alunos.
    }
};
