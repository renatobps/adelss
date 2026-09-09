<?php

namespace App\Console\Commands;

use App\Support\Cp437Utf8MojibakeFixer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixDatabaseEncoding extends Command
{
    protected $signature = 'db:fix-encoding {--dry-run : Só mostra o que seria corrigido}';

    protected $description = 'Corrige textos UTF-8 gravados como CP437 (Dízimo, Oferta, categorias)';

    /**
     * @var array<int, array{table: string, columns: list<string>}>
     */
    private array $targets = [
        ['table' => 'financial_transactions', 'columns' => ['description', 'received_from_other', 'notes']],
        ['table' => 'financial_categories', 'columns' => ['name', 'description']],
        ['table' => 'financial_contacts', 'columns' => ['name']],
        ['table' => 'members', 'columns' => ['name']],
    ];

    public function handle(Cp437Utf8MojibakeFixer $fixer): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $examples = [];

        foreach ($this->targets as $target) {
            if (! Schema::hasTable($target['table'])) {
                continue;
            }

            $columns = array_values(array_filter(
                $target['columns'],
                fn (string $column) => Schema::hasColumn($target['table'], $column)
            ));
            if ($columns === []) {
                continue;
            }

            $select = array_merge(['id'], $columns);
            $rows = DB::table($target['table'])->select($select)->get();

            foreach ($rows as $row) {
                $changes = [];
                foreach ($columns as $column) {
                    $current = (string) ($row->{$column} ?? '');
                    $fixed = $fixer->repair($current);
                    if ($fixed !== $current) {
                        $changes[$column] = $fixed;
                        if (count($examples) < 12) {
                            $examples[] = $target['table'].'#'.$row->id.' '.$column.': '.$current.' → '.$fixed;
                        }
                    }
                }

                if ($changes === []) {
                    continue;
                }

                $updated++;
                if (! $dryRun) {
                    DB::table($target['table'])->where('id', $row->id)->update($changes);
                }
            }
        }

        foreach ($examples as $example) {
            $this->line($example);
        }

        $this->info(($dryRun ? 'Seriam corrigidos' : 'Corrigidos').": {$updated} registro(s).");

        return self::SUCCESS;
    }
}
