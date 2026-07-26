<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodeMemberAddresses extends Command
{
    protected $signature = 'members:geocode-addresses {--limit=30}';

    protected $description = 'Geocodifica endereços de membros (Nominatim/OpenStreetMap) para o mapa';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $members = Member::query()
            ->whereNull('latitude')
            ->whereNull('geocoded_at')
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNotNull('address')->where('address', '!=', '');
                })->orWhere(function ($inner) {
                    $inner->whereNotNull('city')->where('city', '!=', '');
                });
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($members->isEmpty()) {
            $this->info('Nenhum endereço pendente de geocodificação.');

            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;

        foreach ($members as $member) {
            $query = $member->fullAddress();
            if ($query === '') {
                continue;
            }

            try {
                // Nominatim exige User-Agent identificável e rate-limit ~1 req/s
                sleep(1);
                $res = Http::withHeaders([
                    'User-Agent' => 'ADELSS-MembersMap/1.0 (contato@adelss.com.br)',
                    'Accept-Language' => 'pt-BR',
                ])->timeout(20)->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query . ', Brasil',
                    'format' => 'json',
                    'limit' => 1,
                ]);

                $row = $res->json()[0] ?? null;
                if (!$res->successful() || !$row) {
                    $member->forceFill(['geocoded_at' => now()])->save();
                    $fail++;
                    $this->warn("Falha: #{$member->id} {$member->name}");
                    continue;
                }

                $member->forceFill([
                    'latitude' => (float) $row['lat'],
                    'longitude' => (float) $row['lon'],
                    'geocoded_at' => now(),
                ])->save();
                $ok++;
                $this->line("OK: #{$member->id} {$member->name}");
            } catch (\Throwable $e) {
                Log::warning('Geocode membro falhou', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage(),
                ]);
                $fail++;
            }
        }

        $this->info("Concluído: {$ok} ok, {$fail} falhas.");

        return self::SUCCESS;
    }
}
