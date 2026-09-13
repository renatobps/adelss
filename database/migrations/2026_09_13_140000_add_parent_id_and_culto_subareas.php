<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_areas')) {
            return;
        }

        Schema::table('service_areas', function (Blueprint $table) {
            if (! Schema::hasColumn('service_areas', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('id')
                    ->constrained('service_areas')->nullOnDelete();
            }
            if (! Schema::hasColumn('service_areas', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('min_quantity');
            }
        });

        $now = now();
        $cultoId = $this->ensureArea('Culto', [
            'description' => 'Equipe que conduz o culto (abertura, profético, oferta, apoio e recepção).',
            'min_quantity' => 1,
            'sort_order' => 1,
        ]);

        $this->ensureArea('Intercessão', [
            'description' => 'Interceder durante o culto.',
            'min_quantity' => 4,
            'sort_order' => 2,
        ]);

        $this->setRootOrder('Portaria', 3);
        $this->setRootOrder('Sala das Crianças', 4);
        $this->setRootOrder('Preletor', 5);
        $this->setRootOrder('Zeladoria', 6);
        $this->setRootOrder('Limpeza', 6);

        $subareas = [
            ['names' => ['Abertura do culto', 'Direção de Culto', 'Direcao de Culto'], 'name' => 'Abertura do culto', 'quantity' => 1, 'order' => 1],
            ['names' => ['Momento profético', 'Momento profetico'], 'name' => 'Momento profético', 'quantity' => 1, 'order' => 2],
            ['names' => ['Palavra de Oferta', 'Palavra de oferta'], 'name' => 'Palavra de Oferta', 'quantity' => 1, 'order' => 3],
            ['names' => ['Apoio', 'Apoio Geral'], 'name' => 'Apoio', 'quantity' => 1, 'order' => 4],
            ['names' => ['Recepção', 'Recepcao'], 'name' => 'Recepção', 'quantity' => 2, 'order' => 5],
        ];

        $childIds = [];
        foreach ($subareas as $subarea) {
            $childIds[] = $this->ensureChildArea($cultoId, $subarea);
        }

        if ($cultoId && $childIds !== []) {
            $rows = DB::table('volunteer_service_areas')
                ->whereIn('service_area_id', $childIds)
                ->get(['volunteer_id']);

            foreach ($rows as $row) {
                $exists = DB::table('volunteer_service_areas')
                    ->where('volunteer_id', $row->volunteer_id)
                    ->where('service_area_id', $cultoId)
                    ->exists();

                if (! $exists) {
                    DB::table('volunteer_service_areas')->insert([
                        'volunteer_id' => $row->volunteer_id,
                        'service_area_id' => $cultoId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('service_areas') && Schema::hasColumn('service_areas', 'parent_id')) {
            Schema::table('service_areas', function (Blueprint $table) {
                $table->dropConstrainedForeignId('parent_id');
                if (Schema::hasColumn('service_areas', 'sort_order')) {
                    $table->dropColumn('sort_order');
                }
            });
        }
    }

    private function ensureArea(string $name, array $attributes): ?int
    {
        $existing = DB::table('service_areas')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->orderByRaw('deleted_at IS NULL DESC')
            ->orderBy('id')
            ->first();

        if (! $existing) {
            $existing = DB::table('service_areas')
                ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($name).'%'])
                ->whereNull('parent_id')
                ->orderByRaw('deleted_at IS NULL DESC')
                ->orderBy('id')
                ->first();
        }

        $payload = array_merge([
            'name' => $name,
            'status' => 'ativo',
            'parent_id' => null,
            'updated_at' => now(),
        ], $attributes);

        if ($existing) {
            DB::table('service_areas')->where('id', $existing->id)->update(array_merge($payload, [
                'deleted_at' => null,
            ]));

            return (int) $existing->id;
        }

        $payload['created_at'] = now();
        if (! isset($payload['allowed_audience']) && Schema::hasColumn('service_areas', 'allowed_audience')) {
            $payload['allowed_audience'] = 'ambos';
        }

        return (int) DB::table('service_areas')->insertGetId($payload);
    }

    private function ensureChildArea(int $parentId, array $subarea): int
    {
        $existing = null;
        foreach ($subarea['names'] as $name) {
            $existing = DB::table('service_areas')
                ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($name).'%'])
                ->orderByRaw('deleted_at IS NULL DESC')
                ->orderBy('id')
                ->first();
            if ($existing) {
                break;
            }
        }

        $payload = [
            'name' => $subarea['name'],
            'parent_id' => $parentId,
            'status' => 'ativo',
            'min_quantity' => $subarea['quantity'],
            'sort_order' => $subarea['order'],
            'deleted_at' => null,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('service_areas')->where('id', $existing->id)->update($payload);

            return (int) $existing->id;
        }

        $payload['created_at'] = now();
        if (Schema::hasColumn('service_areas', 'allowed_audience')) {
            $payload['allowed_audience'] = 'ambos';
        }

        return (int) DB::table('service_areas')->insertGetId($payload);
    }

    private function setRootOrder(string $nameLike, int $order): void
    {
        DB::table('service_areas')
            ->whereNull('parent_id')
            ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($nameLike).'%'])
            ->update(['sort_order' => $order, 'updated_at' => now()]);
    }
};
