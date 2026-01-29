<?php

namespace App\Http\Controllers;

use App\Models\Song;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
     * Store a newly created song
     */
    public function storeSong(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'artist' => 'nullable|string|max:255',
            'genre' => 'nullable|string|max:255',
            'key' => 'nullable|string|max:10',
            'folder_id' => 'nullable|exists:folders,id',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'has_lyrics' => 'boolean',
            'has_chords' => 'boolean',
            'has_audio' => 'boolean',
            'has_video' => 'boolean',
            'lyrics' => 'nullable|string',
            'chords' => 'nullable|string',
            'audio' => 'nullable|file|mimes:mp3,wav,ogg|max:10240',
            'video' => 'nullable|file|mimes:mp4,avi,mov|max:51200',
        ]);

        $data = $request->only([
            'title', 'artist', 'genre', 'key', 'folder_id',
            'has_lyrics', 'has_chords', 'has_audio', 'has_video',
            'lyrics', 'chords'
        ]);

        // Upload thumbnail
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail_url'] = $request->file('thumbnail')->store('repertorio/thumbnails', 'public');
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
            'title' => 'required|string|max:255',
            'artist' => 'nullable|string|max:255',
            'genre' => 'nullable|string|max:255',
            'key' => 'nullable|string|max:10',
            'folder_id' => 'nullable|exists:folders,id',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'has_lyrics' => 'boolean',
            'has_chords' => 'boolean',
            'has_audio' => 'boolean',
            'has_video' => 'boolean',
            'lyrics' => 'nullable|string',
            'chords' => 'nullable|string',
            'audio' => 'nullable|file|mimes:mp3,wav,ogg|max:10240',
            'video' => 'nullable|file|mimes:mp4,avi,mov|max:51200',
        ]);

        $data = $request->only([
            'title', 'artist', 'genre', 'key', 'folder_id',
            'has_lyrics', 'has_chords', 'has_audio', 'has_video',
            'lyrics', 'chords'
        ]);

        // Upload thumbnail
        if ($request->hasFile('thumbnail')) {
            // Deletar thumbnail antigo
            if ($song->thumbnail_url) {
                Storage::disk('public')->delete($song->thumbnail_url);
            }
            $data['thumbnail_url'] = $request->file('thumbnail')->store('repertorio/thumbnails', 'public');
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
}
