<?php

namespace App\Console\Commands;

use App\Support\Cp437Utf8MojibakeFixer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixDatabaseEncoding extends Command
{
    protected $signature = 'db:fix-encoding {--dry-run : Só mostra o que seria corrigido}';

    protected $description = 'Corrige textos UTF-8 gravados como CP437 em todas as tabelas';

    /**
     * @var list<string>
     */
    private array $skipTables = [
        'migrations',
        'sessions',
        'jobs',
        'failed_jobs',
        'job_batches',
        'cache',
        'cache_locks',
        'password_reset_tokens',
        'password_resets',
        'personal_access_tokens',
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
    ];

    /**
     * @var list<string>
     */
    private array $skipColumns = [
        'password',
        'remember_token',
        'token',
        'secret',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'payload',
        'exception',
        'qr_code_base64',
        'qr_code_text',
        'raw_payload',
        'webhook_payload',
    ];

    public function handle(Cp437Utf8MojibakeFixer $fixer): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $examples = [];
        $byTable = [];

        foreach ($this->discoverTargets() as $target) {
            $select = array_values(array_unique(array_merge($target['keys'], $target['columns'])));
            $rows = DB::table($target['table'])->select($select)->get();
            $tableCount = 0;

            foreach ($rows as $row) {
                $changes = [];
                foreach ($target['columns'] as $column) {
                    $current = (string) ($row->{$column} ?? '');
                    $fixed = $fixer->repair($current);
                    if ($fixed !== $current) {
                        $changes[$column] = $fixed;
                        if (count($examples) < 40) {
                            $examples[] = $target['table'].' '.$column.': '.$current.' → '.$fixed;
                        }
                    }
                }

                if ($changes === []) {
                    continue;
                }

                $updated++;
                $tableCount++;
                if (! $dryRun) {
                    $query = DB::table($target['table']);
                    foreach ($target['keys'] as $key) {
                        $query->where($key, $row->{$key});
                    }
                    $query->update($changes);
                }
            }

            if ($tableCount > 0) {
                $byTable[$target['table']] = $tableCount;
            }
        }

        foreach ($examples as $example) {
            $this->line($example);
        }
        foreach ($byTable as $table => $count) {
            $this->line("  {$table}: {$count}");
        }

        $this->info(($dryRun ? 'Seriam corrigidos' : 'Corrigidos').": {$updated} registro(s).");

        return self::SUCCESS;
    }

    /**
     * @return list<array{table: string, columns: list<string>, keys: list<string>}>
     */
    private function discoverTargets(): array
    {
        $targets = [];

        foreach ($this->listTables() as $table) {
            if (in_array($table, $this->skipTables, true) || str_starts_with($table, 'telescope_')) {
                continue;
            }

            $listing = Schema::getColumnListing($table);
            $keys = array_values(array_filter($listing, fn (string $name) => $name === 'id' || str_ends_with($name, '_id')));
            if (! in_array('id', $keys, true)) {
                continue;
            }

            $columns = [];
            foreach ($listing as $column) {
                if (in_array($column, $this->skipColumns, true) || in_array($column, $keys, true)) {
                    continue;
                }
                if (! $this->isTextColumn($table, $column)) {
                    continue;
                }
                $columns[] = $column;
            }

            if ($columns !== []) {
                $targets[] = [
                    'table' => $table,
                    'columns' => $columns,
                    'keys' => ['id'],
                ];
            }
        }

        return $targets;
    }

    /**
     * @return list<string>
     */
    private function listTables(): array
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            return array_map(
                fn ($row) => (string) array_values((array) $row)[0],
                DB::select('SHOW TABLES')
            );
        }

        if ($driver === 'sqlite') {
            return collect(DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"))
                ->pluck('name')
                ->all();
        }

        return Schema::getTableListing();
    }

    private function isTextColumn(string $table, string $column): bool
    {
        try {
            $type = strtolower((string) Schema::getColumnType($table, $column));
        } catch (\Throwable) {
            return false;
        }

        return str_contains($type, 'char')
            || str_contains($type, 'text')
            || $type === 'string';
    }
}
