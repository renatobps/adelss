<?php

namespace App\Http\Controllers;

use App\Models\Song;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class RepertorioController extends Controller
{
    /**
     * Exibe a página principal do Repertório
     */
    public function index()
    {
        $songs = Song::orderBy('order')->orderBy('title')->get();
        $folders = Folder::whereNull('parent_id')->orderBy('order')->orderBy('name')->get();
        
        // Contar músicas e pastas
        $songsCount = $songs->count();
        $foldersCount = $folders->count();
        
        // Buscar artistas únicos (para a aba Artistas - apenas visualização)
        $artists = Song::whereNotNull('artist')
            ->distinct()
            ->pluck('artist')
            ->sort()
            ->values();
        $artistsCount = $artists->count();
        
        return view('moriah.repertorio.index', compact('songs', 'folders', 'artists', 'songsCount', 'foldersCount', 'artistsCount'));
    }

    /**
     * Retorna os detalhes de uma música em JSON
     */
    public function showSong(Song $song)
    {
        $song->load('folder');
        
        // Formatar duração
        $duration = '';
        if ($song->duration_hours > 0) {
            $duration .= $song->duration_hours . ':';
        }
        $duration .= str_pad($song->duration_minutes ?? 0, 2, '0', STR_PAD_LEFT) . ':';
        $duration .= str_pad($song->duration_seconds ?? 0, 2, '0', STR_PAD_LEFT);
        
        return response()->json([
            'song' => [
                'id' => $song->id,
                'title' => $song->title,
                'version_name' => $song->version_name,
                'artist' => $song->artist,
                'genre' => $song->genre,
                'key' => $song->key,
                'bpm' => $song->bpm,
                'duration' => $duration,
                'duration_hours' => $song->duration_hours ?? 0,
                'duration_minutes' => $song->duration_minutes ?? 0,
                'duration_seconds' => $song->duration_seconds ?? 0,
                'observations' => $song->observations,
                'thumbnail_url' => $song->thumbnail_url ? Storage::url($song->thumbnail_url) : null,
                'has_lyrics' => $song->has_lyrics,
                'has_chords' => $song->has_chords,
                'has_audio' => $song->has_audio,
                'has_video' => $song->has_video,
                'link_letra' => $song->link_letra,
                'link_cifra' => $song->link_cifra,
                'cifra_pdf_url' => $song->cifra_pdf_url ? Storage::url($song->cifra_pdf_url) : null,
                'link_audio' => $song->link_audio,
                'link_video' => $song->link_video,
                'folder' => $song->folder ? $song->folder->name : null,
            ]
        ]);
    }

    /**
     * Store a newly created song
     */
    public function storeSong(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'version_name' => 'nullable|string|max:50',
            'observations' => 'nullable|string|max:150',
            'artist' => 'nullable|string|max:255',
            'genre' => 'required|string|max:255',
            'key' => 'nullable|string|max:10',
            'bpm' => 'nullable|integer|min:40|max:240',
            'duration_hours' => 'nullable|integer|min:0|max:23',
            'duration_minutes' => 'nullable|integer|min:0|max:59',
            'duration_seconds' => 'nullable|integer|min:0|max:59',
            'folder_id' => 'nullable|exists:folders,id',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'has_lyrics' => 'boolean',
            'has_chords' => 'boolean',
            'has_audio' => 'boolean',
            'has_video' => 'boolean',
            'lyrics' => 'nullable|string',
            'chords' => 'nullable|string',
            'link_letra' => 'nullable|url|max:255',
            'link_cifra' => 'nullable|url|max:255',
            'cifra_pdf' => 'nullable|file|mimes:pdf|max:10240',
            'link_audio' => 'nullable|url|max:255',
            'link_video' => 'nullable|url|max:255',
            'audio' => 'nullable|file|mimes:mp3,wav,ogg|max:10240',
            'video' => 'nullable|file|mimes:mp4,avi,mov|max:51200',
        ]);

        $data = $request->only([
            'title', 'version_name', 'observations', 'artist', 'genre', 'key', 'bpm',
            'duration_hours', 'duration_minutes', 'duration_seconds', 'folder_id',
            'has_lyrics', 'has_chords', 'has_audio', 'has_video',
            'lyrics', 'chords', 'link_letra', 'link_cifra', 'link_audio', 'link_video'
        ]);
        
        // Se title não foi fornecido, usar version_name
        if (empty($data['title']) && !empty($data['version_name'])) {
            $data['title'] = $data['version_name'];
        }
        
        // Garantir que os campos de duração tenham valores padrão (não podem ser null)
        $data['duration_hours'] = isset($data['duration_hours']) && $data['duration_hours'] !== '' ? (int)$data['duration_hours'] : 0;
        $data['duration_minutes'] = isset($data['duration_minutes']) && $data['duration_minutes'] !== '' ? (int)$data['duration_minutes'] : 0;
        $data['duration_seconds'] = isset($data['duration_seconds']) && $data['duration_seconds'] !== '' ? (int)$data['duration_seconds'] : 0;

        // Upload thumbnail
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail_url'] = $request->file('thumbnail')->store('repertorio/thumbnails', 'public');
        }
        
        // Upload PDF da cifra
        if ($request->hasFile('cifra_pdf')) {
            $data['cifra_pdf_url'] = $request->file('cifra_pdf')->store('repertorio/cifras', 'public');
            $data['has_chords'] = true;
        }

        // Upload audio
        if ($request->hasFile('audio')) {
            $data['audio_url'] = $request->file('audio')->store('repertorio/audio', 'public');
            $data['has_audio'] = true;
        }

        // Upload video
        if ($request->hasFile('video')) {
            $data['video_url'] = $request->file('video')->store('repertorio/video', 'public');
            $data['has_video'] = true;
        }

        $song = Song::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Música criada com sucesso!',
            'song' => $song
        ]);
    }

    /**
     * Update the specified song
     */
    public function updateSong(Request $request, Song $song)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'version_name' => 'nullable|string|max:50',
            'observations' => 'nullable|string|max:150',
            'artist' => 'nullable|string|max:255',
            'genre' => 'required|string|max:255',
            'key' => 'nullable|string|max:10',
            'bpm' => 'nullable|integer|min:40|max:240',
            'duration_hours' => 'nullable|integer|min:0|max:23',
            'duration_minutes' => 'nullable|integer|min:0|max:59',
            'duration_seconds' => 'nullable|integer|min:0|max:59',
            'folder_id' => 'nullable|exists:folders,id',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'has_lyrics' => 'boolean',
            'has_chords' => 'boolean',
            'has_audio' => 'boolean',
            'has_video' => 'boolean',
            'lyrics' => 'nullable|string',
            'chords' => 'nullable|string',
            'link_letra' => 'nullable|url|max:255',
            'link_cifra' => 'nullable|url|max:255',
            'cifra_pdf' => 'nullable|file|mimes:pdf|max:10240',
            'link_audio' => 'nullable|url|max:255',
            'link_video' => 'nullable|url|max:255',
            'audio' => 'nullable|file|mimes:mp3,wav,ogg|max:10240',
            'video' => 'nullable|file|mimes:mp4,avi,mov|max:51200',
        ]);

        $data = $request->only([
            'title', 'version_name', 'observations', 'artist', 'genre', 'key', 'bpm',
            'duration_hours', 'duration_minutes', 'duration_seconds', 'folder_id',
            'has_lyrics', 'has_chords', 'has_audio', 'has_video',
            'lyrics', 'chords', 'link_letra', 'link_cifra', 'link_audio', 'link_video'
        ]);
        
        // Se title não foi fornecido, usar version_name
        if (empty($data['title']) && !empty($data['version_name'])) {
            $data['title'] = $data['version_name'];
        }
        
        // Garantir que os campos de duração tenham valores padrão (não podem ser null)
        $data['duration_hours'] = isset($data['duration_hours']) && $data['duration_hours'] !== '' ? (int)$data['duration_hours'] : 0;
        $data['duration_minutes'] = isset($data['duration_minutes']) && $data['duration_minutes'] !== '' ? (int)$data['duration_minutes'] : 0;
        $data['duration_seconds'] = isset($data['duration_seconds']) && $data['duration_seconds'] !== '' ? (int)$data['duration_seconds'] : 0;

        // Upload thumbnail
        if ($request->hasFile('thumbnail')) {
            // Deletar thumbnail antigo
            if ($song->thumbnail_url) {
                Storage::disk('public')->delete($song->thumbnail_url);
            }
            $data['thumbnail_url'] = $request->file('thumbnail')->store('repertorio/thumbnails', 'public');
        }
        
        // Upload PDF da cifra
        if ($request->hasFile('cifra_pdf')) {
            // Deletar PDF antigo se existir
            if ($song->cifra_pdf_url) {
                Storage::disk('public')->delete($song->cifra_pdf_url);
            }
            $data['cifra_pdf_url'] = $request->file('cifra_pdf')->store('repertorio/cifras', 'public');
            $data['has_chords'] = true;
        }

        // Upload audio
        if ($request->hasFile('audio')) {
            // Deletar audio antigo
            if ($song->audio_url) {
                Storage::disk('public')->delete($song->audio_url);
            }
            $data['audio_url'] = $request->file('audio')->store('repertorio/audio', 'public');
            $data['has_audio'] = true;
        }

        // Upload video
        if ($request->hasFile('video')) {
            // Deletar video antigo
            if ($song->video_url) {
                Storage::disk('public')->delete($song->video_url);
            }
            $data['video_url'] = $request->file('video')->store('repertorio/video', 'public');
            $data['has_video'] = true;
        }

        $song->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Música atualizada com sucesso!',
            'song' => $song
        ]);
    }

    /**
     * Remove the specified song
     */
    public function destroySong(Song $song)
    {
        // Deletar arquivos
        if ($song->thumbnail_url) {
            Storage::disk('public')->delete($song->thumbnail_url);
        }
        if ($song->audio_url) {
            Storage::disk('public')->delete($song->audio_url);
        }
        if ($song->video_url) {
            Storage::disk('public')->delete($song->video_url);
        }

        $song->delete();

        return response()->json([
            'success' => true,
            'message' => 'Música excluída com sucesso!'
        ]);
    }

    /**
     * Store a newly created folder
     */
    public function storeFolder(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:folders,id',
        ]);

        $folder = Folder::create($request->only(['name', 'description', 'parent_id']));

        return response()->json([
            'success' => true,
            'message' => 'Pasta criada com sucesso!',
            'folder' => $folder
        ]);
    }

    /**
     * Update the specified folder
     */
    public function updateFolder(Request $request, Folder $folder)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:folders,id',
        ]);

        $folder->update($request->only(['name', 'description', 'parent_id']));

        return response()->json([
            'success' => true,
            'message' => 'Pasta atualizada com sucesso!',
            'folder' => $folder
        ]);
    }

    /**
     * Remove the specified folder
     */
    public function destroyFolder(Folder $folder)
    {
        $folder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pasta excluída com sucesso!'
        ]);
    }

    /**
     * Exibe a página de importação de músicas
     */
    public function import()
    {
        return view('moriah.repertorio.import');
    }

    /**
     * Gera e baixa o template de importação (CSV)
     */
    public function downloadTemplate()
    {
        $headers = [
            'nomeMusica',
            'nomeArtista',
            'genero',
            'tom',
            'letra',
            'cifra',
            'linkLetra',
            'linkCifra',
            'linkAudio',
            'linkVideo',
            'referencias'
        ];

        // Exemplos
        $examples = [
            ['Exemplo Música 1', 'Exemplo Artista', 'Louvor', 'C', 'Letra da música exemplo...', 'Cifra da música exemplo...', 'https://exemplo.com/letra', 'https://exemplo.com/cifra', 'https://exemplo.com/audio', 'https://exemplo.com/video', '[Descrição](https://link.com); [Outra](https://link2.com)'],
            ['Exemplo Música 2', 'Exemplo Artista 2', 'Adoração', 'D', '', '', '', '', '', '', ''],
        ];

        $fileName = 'template_importacao_musicas_' . date('Y-m-d') . '.csv';
        
        $callback = function() use ($headers, $examples) {
            $file = fopen('php://output', 'w');
            
            // Adicionar BOM para UTF-8 (para Excel reconhecer corretamente)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Escrever cabeçalhos
            fputcsv($file, $headers, ';');
            
            // Escrever exemplos
            foreach ($examples as $row) {
                fputcsv($file, $row, ';');
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ]);
    }

    /**
     * Processa a importação do arquivo
     */
    public function processImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
            'autofill' => 'boolean',
        ]);

        $autofill = $request->has('autofill') && $request->autofill;

        try {
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());

            // Por enquanto, suportar apenas CSV
            // Para Excel, o usuário pode salvar como CSV
            if ($extension === 'csv') {
                $data = $this->readCsv($file);
            } else {
                return redirect()->route('moriah.repertorio.import')
                    ->with('error', 'Por favor, salve o arquivo Excel como CSV (.csv) e tente novamente.');
            }

            $imported = 0;
            $errors = [];

            foreach ($data as $index => $row) {
                $rowNumber = $index + 2; // +2 porque começa na linha 2 (linha 1 é cabeçalho)

                // Validar campos obrigatórios
                if (empty($row['nomeMusica']) || empty($row['nomeArtista'])) {
                    $errors[] = "Linha {$rowNumber}: Nome da música e nome do artista são obrigatórios.";
                    continue;
                }

                // Preparar dados
                $songData = [
                    'title' => trim($row['nomeMusica']),
                    'artist' => trim($row['nomeArtista']),
                    'genre' => $autofill && empty($row['genero']) ? 'Louvor' : trim($row['genero'] ?? 'Louvor'),
                    'key' => trim($row['tom'] ?? ''),
                    'lyrics' => trim($row['letra'] ?? ''),
                    'chords' => trim($row['cifra'] ?? ''),
                    'has_lyrics' => !empty($row['letra']) || !empty($row['linkLetra']),
                    'has_chords' => !empty($row['cifra']) || !empty($row['linkCifra']),
                    'has_audio' => !empty($row['linkAudio']),
                    'has_video' => !empty($row['linkVideo']),
                ];

                // Processar referências (links customizados)
                if (!empty($row['referencias'])) {
                    $references = $this->parseReferences($row['referencias']);
                    // Adicionar referências aos links se não estiverem preenchidos
                    if (empty($songData['lyrics']) && isset($references['letra'])) {
                        $songData['lyrics'] = $references['letra'];
                        $songData['has_lyrics'] = true;
                    }
                    if (empty($songData['chords']) && isset($references['cifra'])) {
                        $songData['chords'] = $references['cifra'];
                        $songData['has_chords'] = true;
                    }
                    if (empty($songData['has_audio']) && isset($references['audio'])) {
                        $songData['has_audio'] = true;
                    }
                    if (empty($songData['has_video']) && isset($references['video'])) {
                        $songData['has_video'] = true;
                    }
                }

                // Criar música
                try {
                    Song::create($songData);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Linha {$rowNumber}: Erro ao criar música - " . $e->getMessage();
                }
            }

            $message = "Importação concluída! {$imported} música(s) importada(s) com sucesso.";
            if (count($errors) > 0) {
                $message .= " " . count($errors) . " erro(s) encontrado(s).";
            }

            return redirect()->route('moriah.repertorio.index')
                ->with('success', $message)
                ->with('errors', $errors);

        } catch (\Exception $e) {
            return redirect()->route('moriah.repertorio.import')
                ->with('error', 'Erro ao processar arquivo: ' . $e->getMessage());
        }
    }

    /**
     * Lê arquivo CSV
     */
    private function readCsv($file)
    {
        $data = [];
        $handle = fopen($file->getRealPath(), 'r');
        
        // Detectar delimitador (pode ser ; ou ,)
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
        
        // Ler cabeçalhos
        $headers = fgetcsv($handle, 0, $delimiter);
        
        // Limpar BOM se existir
        if (!empty($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }
        
        // Normalizar headers (remover espaços e converter para minúsculas)
        $headers = array_map(function($h) {
            return trim($h);
        }, $headers);

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) >= count($headers)) {
                // Combinar headers com valores
                $rowData = [];
                foreach ($headers as $index => $header) {
                    $rowData[$header] = isset($row[$index]) ? trim($row[$index]) : '';
                }
                
                // Só adicionar se tiver pelo menos nome da música ou artista
                if (!empty($rowData['nomeMusica']) || !empty($rowData['nomeArtista'])) {
                    $data[] = $rowData;
                }
            }
        }

        fclose($handle);
        return $data;
    }


    /**
     * Parse referências no formato [descricao](link)
     */
    private function parseReferences($references)
    {
        $parsed = [];
        $pattern = '/\[([^\]]+)\]\(([^\)]+)\)/';
        preg_match_all($pattern, $references, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $description = strtolower(trim($match[1]));
            $link = trim($match[2]);

            if (strpos($description, 'letra') !== false || strpos($description, 'lyrics') !== false) {
                $parsed['letra'] = $link;
            } elseif (strpos($description, 'cifra') !== false || strpos($description, 'chord') !== false) {
                $parsed['cifra'] = $link;
            } elseif (strpos($description, 'audio') !== false || strpos($description, 'som') !== false) {
                $parsed['audio'] = $link;
            } elseif (strpos($description, 'video') !== false || strpos($description, 'vídeo') !== false) {
                $parsed['video'] = $link;
            }
        }

        return $parsed;
    }

    /**
     * Preencher automaticamente campos a partir de link do YouTube
     */
    public function preencherYoutube(Request $request)
    {
        $request->validate([
            'youtube_url' => 'required|string'
        ]);

        // Extrair ID do vídeo
        preg_match(
            '/(youtu\.be\/|v=)([a-zA-Z0-9_-]{11})/',
            $request->youtube_url,
            $matches
        );

        if (!isset($matches[2])) {
            return response()->json([
                'error' => 'Link do YouTube inválido'
            ], 422);
        }

        $videoId = $matches[2];
        $apiKey = config('services.youtube.key');

        if (!$apiKey) {
            return response()->json([
                'error' => 'Chave da API do YouTube não configurada. Adicione YOUTUBE_API_KEY no arquivo .env'
            ], 500);
        }

        // YouTube Data API v3
        $response = Http::get(
            'https://www.googleapis.com/youtube/v3/videos',
            [
                'part' => 'snippet,contentDetails',
                'id'   => $videoId,
                'key'  => $apiKey,
            ]
        );

        if (!$response->ok() || empty($response->json()['items'])) {
            return response()->json([
                'error' => 'Vídeo não encontrado ou erro na API do YouTube'
            ], 404);
        }

        $video = $response->json()['items'][0];
        $snippet = $video['snippet'];
        $contentDetails = $video['contentDetails'];

        // Normalização do título
        $tituloCompleto = $snippet['title'];
        $titulo = $tituloCompleto;
        $artista = null;

        // Tentar extrair artista do título (formato comum: "Título | Artista" ou "Artista - Título")
        if (str_contains($tituloCompleto, '|')) {
            $parts = explode('|', $tituloCompleto);
            $titulo = trim($parts[0]);
            $artista = isset($parts[1]) ? trim($parts[1]) : null;
        } elseif (str_contains($tituloCompleto, '-')) {
            $parts = explode('-', $tituloCompleto, 2);
            if (count($parts) === 2) {
                $titulo = trim($parts[1]);
                $artista = trim($parts[0]);
            }
        }

        // Converter duração ISO 8601 para horas, minutos e segundos
        $duration = $contentDetails['duration'] ?? '';
        $duracaoSegundos = $this->parseDuration($duration);
        $duracaoHoras = floor($duracaoSegundos / 3600);
        $duracaoMinutos = floor(($duracaoSegundos % 3600) / 60);
        $duracaoSegundosResto = $duracaoSegundos % 60;

        // Thumbnail
        $thumbnail = $snippet['thumbnails']['high']['url'] ?? 
                     $snippet['thumbnails']['medium']['url'] ?? 
                     $snippet['thumbnails']['default']['url'] ?? null;

        // Gerar links de busca
        $query = urlencode(trim(($titulo . ' ' . ($artista ?? ''))));
        $cifraUrl = "https://www.cifraclub.com.br/?q={$query}";
        $letraUrl = "https://www.letras.mus.br/?q={$query}";

        return response()->json([
            'version_name' => $titulo,
            'artist' => $artista,
            'thumbnail_url' => $thumbnail,
            'duration_hours' => $duracaoHoras,
            'duration_minutes' => $duracaoMinutos,
            'duration_seconds' => $duracaoSegundosResto,
            'link_cifra' => $cifraUrl,
            'link_letra' => $letraUrl,
            'link_video' => $request->youtube_url,
        ]);
    }

    /**
     * Converter duração ISO 8601 para segundos
     */
    private function parseDuration($duration)
    {
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches);
        $hours = isset($matches[1]) ? (int)$matches[1] : 0;
        $minutes = isset($matches[2]) ? (int)$matches[2] : 0;
        $seconds = isset($matches[3]) ? (int)$matches[3] : 0;
        return $hours * 3600 + $minutes * 60 + $seconds;
    }
}
