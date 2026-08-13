<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Padrão true para manter o comportamento de quem já usa o comprovante.
            $table->boolean('send_receipt_pdf')
                ->default(true)
                ->after('registration_success_message');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('send_receipt_pdf');
        });
    }
};
