<?php

namespace App\Http\Controllers\Midia;

use App\Http\Controllers\Controller;
use App\Models\InstagramSetting;
use App\Models\MediaFile;
use App\Models\ScheduledPost;
use App\Models\ScheduledPostDestination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ScheduledPostController extends Controller
{
    public function index(Request $request)
    {
        $query = ScheduledPost::query()
            ->with(['mediaFile', 'creator:id,name', 'destinations'])
            ->latest('scheduled_for');

        if ($status = $request->input('status')) {
            if (array_key_exists($status, ScheduledPost::STATUSES)) {
                $query->where('status', $status);
            }
        }

        return view('midia.instagram.index', [
            'posts' => $query->paginate(20)->withQueryString(),
            'instagramConnected' => InstagramSetting::current()->isConnected(),
            'instagram' => InstagramSetting::current(),
            'filters' => ['status' => $request->input('status', '')],
        ]);
    }

    public function create()
    {
        $selectedId = old('media_file_id');
        $selectedMediaFile = $selectedId
            ? MediaFile::query()->find($selectedId)
            : null;

        return view('midia.instagram.form', [
            'post' => null,
            'selectedMediaFile' => $selectedMediaFile,
            'instagramConnected' => InstagramSetting::current()->isConnected(),
            'browseUrl' => route('midia.files.browse'),
        ]);
    }

    public function store(Request $request)
    {
        if (!InstagramSetting::current()->isConnected()) {
            return back()->with('error', 'Conecte o Instagram nas configurações de Mídia.')->withInput();
        }

        $validated = $request->validate([
            'media_file_id' => 'nullable|exists:media_files,id',
            'media' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,mp4,mov,m4v|max:102400',
            'caption' => 'nullable|string|max:2200',
            'scheduled_for' => 'required|date|after:now',
            'destinations' => 'required|array|min:1',
            'destinations.*' => Rule::in(array_keys(ScheduledPostDestination::DESTINATIONS)),
        ], [
            'destinations.required' => 'Selecione pelo menos um destino (Feed, Reels ou Stories).',
            'destinations.min' => 'Selecione pelo menos um destino (Feed, Reels ou Stories).',
        ]);

        if (empty($validated['media_file_id']) && !$request->hasFile('media')) {
            return back()->with('error', 'Selecione um arquivo da Mídia ou faça upload.')->withInput();
        }

        $mediaKind = $this->resolveMediaKind($request, $validated['media_file_id'] ?? null);
        $destinations = array_values(array_unique($validated['destinations']));

        if (in_array(ScheduledPostDestination::DEST_REELS, $destinations, true)
            && $mediaKind !== ScheduledPost::KIND_VIDEO) {
            throw ValidationException::withMessages([
                'destinations' => 'Reels exige vídeo — remova essa opção ou envie um vídeo.',
            ]);
        }

        $imagePath = null;
        if ($request->hasFile('media')) {
            $imagePath = $request->file('media')->store('instagram-uploads', 'public');
        }

        DB::transaction(function () use ($validated, $destinations, $mediaKind, $imagePath) {
            $post = ScheduledPost::create([
                'media_file_id' => $validated['media_file_id'] ?? null,
                'image_path' => $imagePath,
                'caption' => $validated['caption'] ?? '',
                'media_kind' => $mediaKind,
                'scheduled_for' => $validated['scheduled_for'],
                'status' => ScheduledPost::STATUS_SCHEDULED,
                'created_by' => Auth::id(),
            ]);

            foreach ($destinations as $destination) {
                ScheduledPostDestination::create([
                    'scheduled_post_id' => $post->id,
                    'destination' => $destination,
                    'status' => ScheduledPostDestination::STATUS_PENDING,
                ]);
            }
        });

        return redirect()
            ->route('midia.instagram.posts.index')
            ->with('success', 'Publicação agendada com sucesso.');
    }

    public function destroy(ScheduledPost $scheduledPost)
    {
        if (!$scheduledPost->canCancel()) {
            return back()->with('error', 'Só é possível cancelar posts agendados ou com erro.');
        }

        if ($scheduledPost->image_path && !$scheduledPost->media_file_id) {
            Storage::disk('public')->delete($scheduledPost->image_path);
        }

        $scheduledPost->delete();

        return back()->with('success', 'Publicação cancelada.');
    }

    public function retryDestination(ScheduledPostDestination $destination)
    {
        if (!$destination->canRetry()) {
            return back()->with('error', 'Somente destinos com erro podem ser reenviados.');
        }

        $destination->update([
            'status' => ScheduledPostDestination::STATUS_PENDING,
            'error_message' => null,
            'instagram_media_id' => null,
            'published_at' => null,
        ]);

        $post = $destination->post;
        $post->update([
            'status' => ScheduledPost::STATUS_SCHEDULED,
            'scheduled_for' => now()->subMinute(),
        ]);

        return back()->with('success', 'Destino ' . $destination->destination_label . ' recolocado na fila.');
    }

    private function resolveMediaKind(Request $request, ?int $mediaFileId): string
    {
        if ($request->hasFile('media')) {
            $mime = (string) $request->file('media')->getMimeType();

            return str_starts_with($mime, 'video/')
                ? ScheduledPost::KIND_VIDEO
                : ScheduledPost::KIND_PHOTO;
        }

        if ($mediaFileId) {
            $file = MediaFile::find($mediaFileId);
            if ($file && str_starts_with((string) $file->mime_type, 'video/')) {
                return ScheduledPost::KIND_VIDEO;
            }
        }

        return ScheduledPost::KIND_PHOTO;
    }
}
