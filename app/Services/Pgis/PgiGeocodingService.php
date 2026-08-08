<?php

namespace App\Services\Pgis;

use App\Models\Pgi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PgiGeocodingService
{
    /**
     * Coordenadas do endereço do PGI, geocodificando sob demanda quando ainda
     * não foram resolvidas.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function resolve(Pgi $pgi): ?array
    {
        if ($pgi->hasCoordinates()) {
            return ['lat' => (float) $pgi->latitude, 'lng' => (float) $pgi->longitude];
        }

        if ($pgi->fullAddress() === '') {
            return null;
        }

        // Uma tentativa por dia evita repetir chamadas para endereços que o
        // Nominatim não reconhece a cada abertura da página.
        if ($pgi->geocoded_at && $pgi->geocoded_at->greaterThan(now()->subDay())) {
            return null;
        }

        return $this->geocode($pgi);
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function geocode(Pgi $pgi): ?array
    {
        foreach ($this->candidates($pgi) as $index => $query) {
            if ($index > 0) {
                // Nominatim pede no máximo 1 requisição por segundo.
                usleep(1_100_000);
            }

            $row = $this->search($query);

            if ($row) {
                $pgi->forceFill([
                    'latitude' => (float) $row['lat'],
                    'longitude' => (float) $row['lon'],
                    'geocoded_at' => now(),
                ])->save();

                return ['lat' => (float) $pgi->latitude, 'lng' => (float) $pgi->longitude];
            }
        }

        $pgi->forceFill(['geocoded_at' => now()])->save();

        return null;
    }

    /**
     * Consultas da mais específica para a mais ampla: endereços informais de
     * chácara raramente existem no Nominatim, mas o bairro quase sempre existe.
     *
     * @return array<int, string>
     */
    private function candidates(Pgi $pgi): array
    {
        $context = trim((string) config('pgis.geocoding_context', ''));
        $suffix = $context !== '' ? ', ' . $context : ', Brasil';

        $address = trim((string) $pgi->address);
        $number = trim((string) $pgi->number);
        $neighborhood = trim((string) $pgi->neighborhood);

        $candidates = [];

        if ($address !== '' && $number !== '' && $neighborhood !== '') {
            $candidates[] = "{$address}, {$number}, {$neighborhood}";
        }
        if ($address !== '' && $neighborhood !== '') {
            $candidates[] = "{$address}, {$neighborhood}";
        }
        if ($address !== '') {
            $candidates[] = $address;
        }
        if ($neighborhood !== '') {
            $candidates[] = $neighborhood;
        }

        return collect($candidates)
            ->unique()
            ->map(fn (string $candidate) => $candidate . $suffix)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function search(string $query): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'ADELSS-PgiMap/1.0 (contato@adelss.com.br)',
                'Accept-Language' => 'pt-BR',
            ])->timeout(15)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'json',
                'limit' => 1,
            ]);

            return $response->successful() ? ($response->json()[0] ?? null) : null;
        } catch (\Throwable $e) {
            Log::warning('Geocode do PGI falhou', ['query' => $query, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
