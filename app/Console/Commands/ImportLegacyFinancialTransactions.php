<?php

namespace App\Console\Commands;

use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ImportLegacyFinancialTransactions extends Command
{
    protected $signature = 'financial:import-legacy
                            {caminho? : Caminho do CSV}
                            {--dry-run : Processa e mostra o resumo sem gravar no banco}';

    protected $description = 'Importa a carga histórica financeira de 2025 a partir do CSV normalizado';

    private const DESCRIPTION_PREFIX = '[Importação 2025]';

    private const RECEIVED_FROM_OTHER_LABEL = 'Importação 2025';

    private const EXPECTED_RECEITAS_CENTS = 7351263;

    private const EXPECTED_DESPESAS_CENTS = 7348241;

    public function handle(): int
    {
        $path = $this->resolvePath();
        $dryRun = (bool) $this->option('dry-run');

        if (! is_file($path)) {
            $this->error("Arquivo CSV não encontrado: {$path}");

            return self::FAILURE;
        }

        if (! Schema::hasColumn('financial_transactions', 'external_ref')) {
            $this->error('Coluna external_ref ausente. Rode as migrations antes de importar.');

            return self::FAILURE;
        }

        try {
            $rows = $this->parseCsv($path);
        } catch (Throwable $e) {
            $this->error('Falha ao ler o CSV: '.$e->getMessage());

            return self::FAILURE;
        }

        $createdBy = User::query()->orderBy('id')->value('id');

        try {
            $result = $dryRun
                ? $this->process($rows, $createdBy, true)
                : DB::transaction(function () use ($rows, $createdBy) {
                    return $this->process($rows, $createdBy, false);
                });
        } catch (Throwable $e) {
            $this->error('Importação abortada e revertida: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->renderReport($path, $dryRun, $result);
        $this->writeLog($dryRun, $result);

        return self::SUCCESS;
    }

    private function resolvePath(): string
    {
        $argument = $this->argument('caminho');

        if (is_string($argument) && $argument !== '') {
            return $this->isAbsolutePath($argument)
                ? $argument
                : base_path($argument);
        }

        return storage_path('imports/financial_import_2025.csv');
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || (strlen($path) > 2 && ctype_alpha($path[0]) && $path[1] === ':');
    }

    /**
     * @return list<array{
     *     line: int,
     *     mes_aba: string,
     *     data: string,
     *     descricao: string,
     *     valor_cents: int,
     *     tipo: string,
     *     categoria: string,
     *     status_import: string,
     *     external_ref: string
     * }>
     */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException("Não foi possível abrir {$path}");
        }

        try {
            $headerLine = fgetcsv($handle, 0, ',');
            if ($headerLine === false || $headerLine === [null]) {
                throw new RuntimeException('CSV vazio ou sem cabeçalho.');
            }

            $header = array_map(function ($column) {
                $column = (string) $column;
                $column = preg_replace('/^\xEF\xBB\xBF/', '', $column) ?? $column;

                return trim($column);
            }, $headerLine);

            $required = ['mes_aba', 'data', 'descricao', 'valor', 'tipo', 'categoria', 'status_import'];
            foreach ($required as $column) {
                if (! in_array($column, $header, true)) {
                    throw new RuntimeException("Coluna obrigatória ausente no CSV: {$column}");
                }
            }

            $rows = [];
            $fileLine = 1;
            $dataIndex = 0;

            while (($raw = fgetcsv($handle, 0, ',')) !== false) {
                $fileLine++;

                if ($this->isEmptyCsvRow($raw)) {
                    continue;
                }

                $dataIndex++;
                $assoc = [];
                foreach ($header as $i => $key) {
                    $assoc[$key] = trim((string) ($raw[$i] ?? ''));
                }

                $tipo = Str::lower($assoc['tipo']);
                if (! in_array($tipo, ['receita', 'despesa'], true)) {
                    throw new RuntimeException("Tipo inválido na linha {$fileLine}: {$assoc['tipo']}");
                }

                $mesAba = $assoc['mes_aba'];
                $rows[] = [
                    'line' => $fileLine,
                    'mes_aba' => $mesAba,
                    'data' => $assoc['data'],
                    'descricao' => $assoc['descricao'],
                    'valor_cents' => $this->parseAmountToCents($assoc['valor'], $fileLine),
                    'tipo' => $tipo,
                    'categoria' => $assoc['categoria'],
                    'status_import' => Str::upper($assoc['status_import']),
                    'external_ref' => 'legacy2025:'.$mesAba.':'.$dataIndex,
                ];
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<string|null>  $row
     */
    private function isEmptyCsvRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseAmountToCents(string $raw, int $line): int
    {
        $normalized = str_replace(["\u{00A0}", ' '], '', $raw);
        $normalized = str_replace(',', '.', $normalized);

        if ($normalized === '' || ! is_numeric($normalized)) {
            throw new RuntimeException("Valor inválido na linha {$line}: {$raw}");
        }

        return (int) round(((float) $normalized) * 100);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function process(array $rows, ?int $createdBy, bool $dryRun): array
    {
        $categoriesByKey = $this->loadCategoryIndex();
        $existingRefs = FinancialTransaction::withTrashed()
            ->whereNotNull('external_ref')
            ->pluck('external_ref')
            ->flip()
            ->all();

        $pending = [];
        $imported = 0;
        $alreadyExisted = 0;
        $skippedReview = 0;
        $okCount = 0;
        $revenueCents = 0;
        $expenseCents = 0;
        $skippedExpenseCents = 0;
        $categoriesToCreate = [];

        foreach ($rows as $row) {
            if ($row['status_import'] === 'REVISAR' || $row['categoria'] === '') {
                $skippedReview++;
                if ($row['tipo'] === 'despesa') {
                    $skippedExpenseCents += $row['valor_cents'];
                }
                $pending[] = $row;

                continue;
            }

            if ($row['status_import'] !== 'OK') {
                throw new RuntimeException("status_import inválido na linha {$row['line']}: {$row['status_import']}");
            }

            $okCount++;

            if ($row['tipo'] === 'receita') {
                $revenueCents += $row['valor_cents'];
            } else {
                $expenseCents += $row['valor_cents'];
            }

            if (isset($existingRefs[$row['external_ref']])) {
                $alreadyExisted++;

                continue;
            }

            $categoryKey = $this->categoryKey($row['tipo'], $row['categoria']);
            if (! isset($categoriesByKey[$categoryKey])) {
                $categoriesToCreate[$categoryKey] = [
                    'name' => $row['categoria'],
                    'type' => $row['tipo'],
                ];

                if ($dryRun) {
                    $categoriesByKey[$categoryKey] = 0;
                } else {
                    $category = $this->createCategory($row['categoria'], $row['tipo']);
                    $categoriesByKey[$categoryKey] = $category->id;
                }
            }

            if ($dryRun) {
                $imported++;

                continue;
            }

            $categoryId = $categoriesByKey[$categoryKey];
            if (! $categoryId) {
                throw new RuntimeException("Categoria não resolvida na linha {$row['line']}: {$row['categoria']}");
            }

            $this->createTransaction($row, (int) $categoryId, $createdBy);
            $existingRefs[$row['external_ref']] = true;
            $imported++;
        }

        return [
            'processed' => count($rows),
            'ok' => $okCount,
            'imported' => $imported,
            'already_existed' => $alreadyExisted,
            'skipped_review' => $skippedReview,
            'categories_created' => count($categoriesToCreate),
            'categories_to_create' => array_values($categoriesToCreate),
            'revenue_cents' => $revenueCents,
            'expense_cents' => $expenseCents,
            'skipped_expense_cents' => $skippedExpenseCents,
            'pending' => $pending,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function loadCategoryIndex(): array
    {
        $index = [];

        foreach (FinancialCategory::query()->get(['id', 'name', 'type']) as $category) {
            $index[$this->categoryKey($category->type, $category->name)] = $category->id;
        }

        return $index;
    }

    private function categoryKey(string $type, string $name): string
    {
        return $type.'|'.Str::lower(Str::ascii(trim($name)));
    }

    private function createCategory(string $name, string $type): FinancialCategory
    {
        $normalized = Str::lower(Str::ascii($name));
        $sendsReceipt = $type === 'receita'
            && (str_contains($normalized, 'dizim') || str_contains($normalized, 'oferta'));

        return FinancialCategory::create([
            'name' => $name,
            'slug' => Str::slug($name) ?: 'categoria',
            'type' => $type,
            'sends_receipt' => $sendsReceipt,
            'description' => 'Criada automaticamente na importação histórica de 2025.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function createTransaction(array $row, int $categoryId, ?int $createdBy): void
    {
        $date = $this->parseDate($row['data'], $row['line']);
        $amount = number_format($row['valor_cents'] / 100, 2, '.', '');
        $originalDescription = trim((string) $row['descricao']);
        $description = $originalDescription !== '' ? $originalDescription : $row['categoria'];
        $description = Str::limit($description, 255, '');

        $notes = self::DESCRIPTION_PREFIX.' Aba '.$row['mes_aba'];
        if ($originalDescription !== '') {
            $notes .= ' — '.$originalDescription;
        }

        $isReceita = $row['tipo'] === 'receita';

        FinancialTransaction::create([
            'type' => $row['tipo'],
            'transaction_date' => $date,
            'due_date' => $date,
            'competence_date' => $date,
            'description' => $description,
            'amount' => $amount,
            'is_paid' => true,
            'status' => $isReceita ? 'recebido' : 'pago',
            'member_id' => null,
            'received_from_other' => $isReceita ? self::RECEIVED_FROM_OTHER_LABEL : null,
            'category_id' => $categoryId,
            'payment_type' => 'unico',
            'external_ref' => $row['external_ref'],
            'notes' => $notes,
            'created_by' => $createdBy,
        ]);
    }

    private function parseDate(string $raw, int $line): string
    {
        try {
            $date = Carbon::createFromFormat('Y-m-d', $raw);
        } catch (Throwable $e) {
            throw new RuntimeException("Data inválida na linha {$line}: {$raw}");
        }

        if ($date === false || $date->format('Y-m-d') !== $raw) {
            throw new RuntimeException("Data inválida na linha {$line}: {$raw}");
        }

        return $date->format('Y-m-d');
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function renderReport(string $path, bool $dryRun, array $result): void
    {
        $this->newLine();
        $this->info('Importação financeira legado 2025');
        $this->line('Arquivo: '.$path);
        $this->line($dryRun ? 'Modo: DRY-RUN (nada foi gravado no banco)' : 'Modo: GRAVAÇÃO');
        $this->newLine();

        $importedLabel = $dryRun ? 'A importar' : 'Importadas agora';

        $this->table(
            ['Métrica', 'Quantidade'],
            [
                ['Linhas processadas', $result['processed']],
                ['Linhas OK', $result['ok']],
                ['Linhas REVISAR (puladas)', $result['skipped_review']],
                [$importedLabel, $result['imported']],
                ['Já existentes (idempotência)', $result['already_existed']],
                [$dryRun ? 'Categorias a criar' : 'Categorias criadas', $result['categories_created']],
            ]
        );

        $importedExpenses = $result['expense_cents'];
        $skippedExpenses = $result['skipped_expense_cents'];
        $totalExpenses = $importedExpenses + $skippedExpenses;
        $importedRevenues = $result['revenue_cents'];
        $importedBalance = $importedRevenues - $importedExpenses;
        $planilhaBalance = self::EXPECTED_RECEITAS_CENTS - self::EXPECTED_DESPESAS_CENTS;

        $this->newLine();
        $this->info($dryRun ? 'Totais a importar (somente linhas OK)' : 'Totais importados (linhas OK, novas + já existentes)');
        $this->table(
            ['', 'Valor'],
            [
                ['Receitas', $this->formatMoney($importedRevenues)],
                ['Despesas', $this->formatMoney($importedExpenses)],
                ['Saldo', $this->formatMoney($importedBalance)],
            ]
        );

        $receitasOk = $importedRevenues === self::EXPECTED_RECEITAS_CENTS;
        $despesasOk = $totalExpenses === self::EXPECTED_DESPESAS_CENTS;

        $this->newLine();
        $this->info('Conferência com a planilha original');
        $this->table(
            ['', 'Planilha', 'Deste arquivo', 'Status'],
            [
                [
                    'Receitas',
                    $this->formatMoney(self::EXPECTED_RECEITAS_CENTS),
                    $this->formatMoney($importedRevenues),
                    $receitasOk ? 'OK' : 'DIVERGE',
                ],
                [
                    'Despesas (OK + REVISAR)',
                    $this->formatMoney(self::EXPECTED_DESPESAS_CENTS),
                    $this->formatMoney($importedExpenses).' + '.$this->formatMoney($skippedExpenses).' = '.$this->formatMoney($totalExpenses),
                    $despesasOk ? 'OK' : 'DIVERGE',
                ],
                [
                    'Saldo (com pendências)',
                    $this->formatMoney($planilhaBalance),
                    $this->formatMoney($importedRevenues - $totalExpenses),
                    ($importedRevenues - $totalExpenses) === $planilhaBalance ? 'OK' : 'DIVERGE',
                ],
            ]
        );

        $this->line('As 17 linhas REVISAR não entram no saldo importado até receberem categoria (tela de Transações ou CSV corrigido).');

        if ($result['categories_to_create'] !== []) {
            $this->newLine();
            $this->info($dryRun ? 'Categorias que seriam criadas' : 'Categorias novas');
            $this->table(
                ['Tipo', 'Nome'],
                array_map(fn (array $c) => [$c['type'], $c['name']], $result['categories_to_create'])
            );
        }

        if ($result['pending'] !== []) {
            $this->newLine();
            $this->warn('Linhas puladas (status_import = REVISAR) — definir categoria manualmente depois:');
            $this->table(
                ['Linha', 'Mês', 'Data', 'Descrição', 'Valor', 'Tipo'],
                array_map(fn (array $row) => [
                    $row['line'],
                    $row['mes_aba'],
                    $row['data'],
                    $row['descricao'] !== '' ? $row['descricao'] : '—',
                    $this->formatMoney($row['valor_cents']),
                    $row['tipo'],
                ], $result['pending'])
            );
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function writeLog(bool $dryRun, array $result): void
    {
        $lines = [
            'Importação financeira legado 2025 — '.now()->toDateTimeString(),
            'Modo: '.($dryRun ? 'DRY-RUN' : 'GRAVAÇÃO'),
            'Processadas: '.$result['processed'],
            'OK: '.$result['ok'],
            'REVISAR: '.$result['skipped_review'],
            'Importadas: '.$result['imported'],
            'Já existentes: '.$result['already_existed'],
            'Categorias criadas: '.$result['categories_created'],
            'Receitas: '.$this->formatMoney($result['revenue_cents']),
            'Despesas OK: '.$this->formatMoney($result['expense_cents']),
            'Despesas REVISAR: '.$this->formatMoney($result['skipped_expense_cents']),
            '',
            'Pendências REVISAR:',
        ];

        foreach ($result['pending'] as $row) {
            $lines[] = sprintf(
                '  linha %d | %s | %s | %s | %s | %s',
                $row['line'],
                $row['mes_aba'],
                $row['data'],
                $row['descricao'] !== '' ? $row['descricao'] : '—',
                $this->formatMoney($row['valor_cents']),
                $row['tipo']
            );
        }

        File::put(storage_path('logs/import-financeiro-2025.log'), implode(PHP_EOL, $lines).PHP_EOL);
        $this->newLine();
        $this->line('Relatório salvo em storage/logs/import-financeiro-2025.log');
    }

    private function formatMoney(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';

        return $sign.'R$ '.number_format(abs($cents) / 100, 2, ',', '.');
    }
}
