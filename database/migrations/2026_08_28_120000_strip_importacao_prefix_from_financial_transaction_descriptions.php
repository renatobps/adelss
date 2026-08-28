<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('financial_transactions')
            ->where('description', 'like', '[Importação%')
            ->orderBy('id')
            ->select('id', 'description')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $clean = preg_replace('/^\[Importação(?:\s+\d{4})?\]\s*/iu', '', (string) $row->description);
                    $clean = trim((string) $clean);

                    if ($clean === '' || $clean === $row->description) {
                        continue;
                    }

                    DB::table('financial_transactions')
                        ->where('id', $row->id)
                        ->update(['description' => $clean]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
