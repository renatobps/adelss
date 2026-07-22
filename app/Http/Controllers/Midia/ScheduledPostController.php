<?php

namespace App\Http\Controllers\Midia;

use App\Http\Controllers\Controller;
use App\Models\InstagramSetting;
use App\Models\MediaFile;
use App\Models\ScheduledPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ScheduledPostController extends Controller
{
    public function index(Request $request)
    {
        $query = ScheduledPost::query()
            ->with(['mediaFile', 'creator:id,name'])
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
        return view('midia.instagram.form', [
            'post' => null,
            'photos' => MediaFile::query()
                ->where('category', MediaFile::CATEGORY_PHOTO)
                ->latest()
                ->limit(100)
                ->get(),
            'instagramConnected' => InstagramSetting::current()->isConnected(),
        ]);
    }

    public function store(Request $request)
    {
        if (!InstagramSetting::current()->isConnected()) {
            return back()->with('error', 'Conecte o Instagram nas configurações de Mídia.')->withInput();
        }

        $validated = $request->validate([
            'media_file_id' => 'nullable|exists:media_files,id',
            'image' => 'nullable|image|max:10240',
            'caption' => 'nullable|string|max:2200',
            'scheduled_for' => 'required|date|after:now',
        ]);

        if (empty($validated['media_file_id']) && !$request->hasFile('image')) {
            return back()->with('error', 'Selecione uma foto da Mídia ou faça upload.')->withInput();
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('instagram-uploads', 'public');
        }

        ScheduledPost::create([
            'media_file_id' => $validated['media_file_id'] ?? null,
            'image_path' => $imagePath,
            'caption' => $validated['caption'] ?? '',
            'scheduled_for' => $validated['scheduled_for'],
            'status' => ScheduledPost::STATUS_SCHEDULED,
            'created_by' => Auth::id(),
        ]);

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

    public function retry(ScheduledPost $scheduledPost)
    {
        if (!$scheduledPost->canRetry()) {
            return back()->with('error', 'Somente posts com erro podem ser reenviados.');
        }

        $scheduledPost->update([
            'status' => ScheduledPost::STATUS_SCHEDULED,
            'error_message' => null,
            'scheduled_for' => now()->addMinute(),
        ]);

        return back()->with('success', 'Publicação recolocada na fila.');
    }
}
