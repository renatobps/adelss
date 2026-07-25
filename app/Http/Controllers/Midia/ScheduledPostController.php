<?php

namespace App\Http\Controllers\Midia;

use App\Http\Controllers\Controller;
use App\Models\InstagramSetting;
use App\Models\MediaFile;
use App\Models\ScheduledPost;
use App\Models\ScheduledPostDestination;
use App\Services\WhatsAppService;
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
        $user = Auth::user();
        $selectedId = old('media_file_id');
        $selectedMediaFile = $selectedId
            ? MediaFile::query()->find($selectedId)
            : null;

        return view('midia.instagram.form', [
            'post' => null,
            'selectedMediaFile' => $selectedMediaFile,
            'instagramConnected' => InstagramSetting::current()->isConnected(),
            'browseUrl' => route('midia.files.browse'),
            'canScheduleInstagram' => $user?->is_admin || $user?->hasPermission('midia.instagram.schedule'),
            'canScheduleWhatsApp' => $user?->is_admin || $user?->hasPermission('midia.whatsapp.schedule'),
            'whatsappGroupsUrl' => route('midia.whatsapp.grupos'),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $canIg = $user?->is_admin || $user?->hasPermission('midia.instagram.schedule');
        $canWa = $user?->is_admin || $user?->hasPermission('midia.whatsapp.schedule');

        $validated = $request->validate([
            'media_file_id' => 'nullable|exists:media_files,id',
            'media' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,mp4,mov,m4v|max:102400',
            'caption' => 'nullable|string|max:2200',
            'event_name' => 'nullable|string|max:180',
            'remove_after_days' => 'nullable|integer|min:1|max:365',
            'scheduled_for' => 'required|date|after:now',
            'destinations' => 'required|array|min:1',
            'destinations.*' => Rule::in(array_keys(ScheduledPostDestination::DESTINATIONS)),
            'whatsapp_group_jid' => 'nullable|string|max:80',
            'whatsapp_group_name' => 'nullable|string|max:180',
        ], [
            'destinations.required' => 'Selecione pelo menos um destino.',
            'destinations.min' => 'Selecione pelo menos um destino.',
        ]);

        if (empty($validated['media_file_id']) && !$request->hasFile('media')) {
            return back()->with('error', 'Selecione um arquivo da Mídia ou faça upload.')->withInput();
        }

        $mediaKind = $this->resolveMediaKind($request, $validated['media_file_id'] ?? null);
        $destinations = array_values(array_unique($validated['destinations']));

        $instagramDests = array_values(array_intersect(
            $destinations,
            array_keys(ScheduledPostDestination::INSTAGRAM_DESTINATIONS)
        ));
        $hasWhatsApp = in_array(ScheduledPostDestination::DEST_GRUPO, $destinations, true);

        if ($instagramDests !== [] && !$canIg) {
            throw ValidationException::withMessages([
                'destinations' => 'Sem permissão para agendar no Instagram.',
            ]);
        }
        if ($hasWhatsApp && !$canWa) {
            throw ValidationException::withMessages([
                'destinations' => 'Sem permissão para agendar no Grupo do WhatsApp.',
            ]);
        }

        if ($instagramDests !== [] && !InstagramSetting::current()->isConnected()) {
            return back()->with('error', 'Conecte o Instagram nas configurações de Mídia.')->withInput();
        }

        if (in_array(ScheduledPostDestination::DEST_REELS, $instagramDests, true)
            && $mediaKind !== ScheduledPost::KIND_VIDEO) {
            throw ValidationException::withMessages([
                'destinations' => 'Reels exige vídeo — remova essa opção ou envie um vídeo.',
            ]);
        }

        $whatsappGroupJid = trim((string) ($validated['whatsapp_group_jid'] ?? ''));
        $whatsappGroupName = trim((string) ($validated['whatsapp_group_name'] ?? ''));
        if ($hasWhatsApp) {
            if ($whatsappGroupJid === '' || !str_contains($whatsappGroupJid, '@g.us')) {
                throw ValidationException::withMessages([
                    'whatsapp_group_jid' => 'Selecione o grupo do WhatsApp.',
                ]);
            }
        }

        $hasPermanent = (bool) array_intersect($instagramDests, [
            ScheduledPostDestination::DEST_FEED,
            ScheduledPostDestination::DEST_REELS,
        ]);
        $removeAfterDays = $hasPermanent && filled($validated['remove_after_days'] ?? null)
            ? (int) $validated['remove_after_days']
            : null;

        $imagePath = null;
        if ($request->hasFile('media')) {
            $imagePath = $request->file('media')->store('instagram-uploads', 'public');
        }

        DB::transaction(function () use (
            $validated,
            $destinations,
            $instagramDests,
            $hasWhatsApp,
            $whatsappGroupJid,
            $whatsappGroupName,
            $mediaKind,
            $imagePath,
            $removeAfterDays
        ) {
            $post = ScheduledPost::create([
                'media_file_id' => $validated['media_file_id'] ?? null,
                'image_path' => $imagePath,
                'caption' => $validated['caption'] ?? '',
                'event_name' => filled($validated['event_name'] ?? null)
                    ? trim((string) $validated['event_name'])
                    : null,
                'media_kind' => $mediaKind,
                'scheduled_for' => $validated['scheduled_for'],
                'status' => ScheduledPost::STATUS_SCHEDULED,
                'created_by' => Auth::id(),
            ]);

            foreach ($instagramDests as $destination) {
                $isPermanent = in_array($destination, [
                    ScheduledPostDestination::DEST_FEED,
                    ScheduledPostDestination::DEST_REELS,
                ], true);

                ScheduledPostDestination::create([
                    'scheduled_post_id' => $post->id,
                    'channel' => ScheduledPostDestination::CHANNEL_INSTAGRAM,
                    'destination' => $destination,
                    'status' => ScheduledPostDestination::STATUS_PENDING,
                    'remove_after_days' => $isPermanent ? $removeAfterDays : null,
                    'removal_status' => ScheduledPostDestination::REMOVAL_NONE,
                ]);
            }

            if ($hasWhatsApp) {
                ScheduledPostDestination::create([
                    'scheduled_post_id' => $post->id,
                    'channel' => ScheduledPostDestination::CHANNEL_WHATSAPP,
                    'destination' => ScheduledPostDestination::DEST_GRUPO,
                    'target_id' => $whatsappGroupJid,
                    'target_name' => $whatsappGroupName !== '' ? $whatsappGroupName : null,
                    'status' => ScheduledPostDestination::STATUS_PENDING,
                    'remove_after_days' => null,
                    'removal_status' => ScheduledPostDestination::REMOVAL_NONE,
                ]);
            }
        });

        return redirect()
            ->route('midia.instagram.posts.index')
            ->with('success', 'Publicação agendada com sucesso.');
    }

    public function listWhatsAppGroups(WhatsAppService $whatsapp)
    {
        $user = Auth::user();
        if (!$user?->is_admin && !$user?->hasPermission('midia.whatsapp.schedule')) {
            abort(403, 'Sem permissão para listar grupos do WhatsApp.');
        }

        $result = $whatsapp->listGroups();
        if (!($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Falha ao listar grupos.',
                'groups' => [],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'groups' => $result['groups'] ?? [],
        ]);
    }

    public function destroy(ScheduledPost $scheduledPost)
    {
        if (!$scheduledPost->canDeleteLocally()) {
            return back()->with('error', 'Não é possível excluir enquanto a publicação está em andamento.');
        }

        // Exclusão manual: apenas registro local. Remoção no Instagram só via comando agendado.
        if ($scheduledPost->image_path && !$scheduledPost->media_file_id) {
            Storage::disk('public')->delete($scheduledPost->image_path);
        }

        $scheduledPost->delete();

        return back()->with('success', 'Registro removido do ADELSS (a publicação no Instagram, se existir, permanece intacta).');
    }

    public function retryDestination(ScheduledPostDestination $destination)
    {
        if (!$destination->canRetry()) {
            return back()->with('error', 'Somente destinos com erro podem ser reenviados.');
        }

        $user = Auth::user();
        if ($destination->isWhatsApp()) {
            if (!$user?->is_admin && !$user?->hasPermission('midia.whatsapp.schedule')) {
                return back()->with('error', 'Sem permissão para reenviar ao WhatsApp.');
            }
        } elseif (!$user?->is_admin && !$user?->hasPermission('midia.instagram.schedule')) {
            return back()->with('error', 'Sem permissão para reenviar ao Instagram.');
        }

        $destination->update([
            'status' => ScheduledPostDestination::STATUS_PENDING,
            'error_message' => null,
            'instagram_media_id' => null,
            'published_at' => null,
            'remove_at' => null,
            'removed_at' => null,
            'removal_status' => ScheduledPostDestination::REMOVAL_NONE,
            'removal_error' => null,
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
