<?php

namespace App\Http\Controllers\Midia;

use App\Http\Controllers\Controller;
use App\Models\GoogleDriveSetting;
use App\Models\InstagramSetting;
use App\Models\MediaFile;
use App\Models\MediaFolder;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function __construct(private GoogleDriveService $drive) {}

    public function index(Request $request)
    {
        $folderId = $request->integer('pasta') ?: null;
        $currentFolder = $folderId ? MediaFolder::with('parent')->findOrFail($folderId) : null;

        $foldersQuery = MediaFolder::query()
            ->where('parent_folder_id', $folderId)
            ->orderBy('name');

        $filesQuery = MediaFile::query()
            ->with('uploader:id,name')
            ->where('media_folder_id', $folderId)
            ->latest();

        if ($category = $request->input('categoria')) {
            if (in_array($category, [MediaFile::CATEGORY_PHOTO, MediaFile::CATEGORY_DOCUMENT], true)) {
                $filesQuery->where('category', $category);
            }
        }

        if ($search = trim((string) $request->input('q', ''))) {
            $foldersQuery->where('name', 'like', "%{$search}%");
            $filesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('original_filename', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $viewMode = $request->input('view', 'grade');
        if (!in_array($viewMode, ['grade', 'lista'], true)) {
            $viewMode = 'grade';
        }

        return view('midia.arquivos.index', [
            'driveConnected' => GoogleDriveSetting::current()->isConnected(),
            'currentFolder' => $currentFolder,
            'folders' => $foldersQuery->withCount(['files', 'children'])->get(),
            'files' => $filesQuery->paginate(36)->withQueryString(),
            'filters' => [
                'q' => $search,
                'categoria' => $request->input('categoria', ''),
                'view' => $viewMode,
                'pasta' => $folderId,
            ],
            'allFolders' => MediaFolder::orderBy('name')->get(['id', 'name', 'parent_folder_id']),
        ]);
    }

    public function store(Request $request)
    {
        if (!GoogleDriveSetting::current()->isConnected()) {
            return back()->with('error', 'Conecte o Google Drive nas configurações de Mídia.');
        }

        $validated = $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'file|max:20480',
            'media_folder_id' => 'nullable|exists:media_folders,id',
            'description' => 'nullable|string|max:2000',
            'module_reference' => 'nullable|string|max:60',
            'reference_id' => 'nullable|integer',
        ]);

        $folder = !empty($validated['media_folder_id'])
            ? MediaFolder::find($validated['media_folder_id'])
            : null;
        $parentDriveId = $folder?->google_drive_folder_id;

        $created = 0;
        $uploadError = null;

        foreach ($request->file('files', []) as $file) {
            if (!$file->isValid()) {
                continue;
            }

            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $category = str_starts_with($mime, 'image/')
                ? MediaFile::CATEGORY_PHOTO
                : MediaFile::CATEGORY_DOCUMENT;

            try {
                $uploaded = $this->drive->uploadFile(
                    $file,
                    $file->getClientOriginalName(),
                    $parentDriveId
                );
            } catch (\Throwable $e) {
                Log::error('Falha no upload para o Google Drive', [
                    'file' => $file->getClientOriginalName(),
                    'media_folder_id' => $folder?->id,
                    'drive_parent_id' => $parentDriveId,
                    'error' => $e->getMessage(),
                ]);
                $uploadError = $this->driveErrorMessage($e);
                break;
            }

            MediaFile::create([
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $uploaded['mime'],
                'size' => $uploaded['size'],
                'google_drive_file_id' => $uploaded['id'],
                'media_folder_id' => $folder?->id,
                'category' => $category,
                'module_reference' => $validated['module_reference'] ?? null,
                'reference_id' => $validated['reference_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'uploaded_by' => Auth::id(),
            ]);
            $created++;
        }

        if ($uploadError !== null) {
            $prefix = $created > 0 ? $created . ' arquivo(s) enviado(s), mas houve falha: ' : '';

            return back()->with('error', $prefix . $uploadError);
        }

        return back()->with('success', $created . ' arquivo(s) enviado(s) ao Google Drive.');
    }

    public function createFolder(Request $request)
    {
        if (!GoogleDriveSetting::current()->isConnected()) {
            return back()->with('error', 'Conecte o Google Drive nas configurações de Mídia.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'parent_folder_id' => 'nullable|exists:media_folders,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $parent = !empty($validated['parent_folder_id'])
            ? MediaFolder::find($validated['parent_folder_id'])
            : null;

        try {
            $driveFolderId = $this->drive->createFolder(
                $validated['name'],
                $parent?->google_drive_folder_id
            );
        } catch (\Throwable $e) {
            Log::error('Falha ao criar pasta no Google Drive', [
                'name' => $validated['name'],
                'parent_folder_id' => $parent?->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', $this->driveErrorMessage($e));
        }

        MediaFolder::create([
            'name' => $validated['name'],
            'google_drive_folder_id' => $driveFolderId,
            'parent_folder_id' => $parent?->id,
            'department_id' => $validated['department_id'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Pasta criada no Google Drive.');
    }

    public function download(MediaFile $mediaFile): StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        try {
            $contents = $this->drive->downloadContents($mediaFile->google_drive_file_id);
        } catch (\Throwable $e) {
            Log::error('Falha no download do Google Drive', [
                'media_file_id' => $mediaFile->id,
                'drive_file_id' => $mediaFile->google_drive_file_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', $this->driveErrorMessage($e));
        }

        return response()->streamDownload(function () use ($contents) {
            echo $contents;
        }, $mediaFile->original_filename, [
            'Content-Type' => $mediaFile->mime_type,
        ]);
    }

    public function destroy(MediaFile $mediaFile)
    {
        try {
            $this->drive->deleteFile($mediaFile->google_drive_file_id);
        } catch (\Throwable $e) {
            // segue com exclusão local se o arquivo já não existir no Drive
        }

        $mediaFile->delete();

        return back()->with('success', 'Arquivo removido.');
    }

    public function move(Request $request, MediaFile $mediaFile)
    {
        $validated = $request->validate([
            'media_folder_id' => 'nullable|exists:media_folders,id',
        ]);

        $target = !empty($validated['media_folder_id'])
            ? MediaFolder::find($validated['media_folder_id'])
            : null;

        $newParent = $target?->google_drive_folder_id
            ?: GoogleDriveSetting::current()->root_folder_id;

        $oldParent = $mediaFile->folder?->google_drive_folder_id
            ?: GoogleDriveSetting::current()->root_folder_id;

        if ($newParent) {
            try {
                $this->drive->moveFile($mediaFile->google_drive_file_id, $newParent, $oldParent);
            } catch (\Throwable $e) {
                Log::error('Falha ao mover arquivo no Google Drive', [
                    'media_file_id' => $mediaFile->id,
                    'drive_file_id' => $mediaFile->google_drive_file_id,
                    'error' => $e->getMessage(),
                ]);

                return back()->with('error', $this->driveErrorMessage($e));
            }
        }

        $mediaFile->update(['media_folder_id' => $target?->id]);

        return back()->with('success', 'Arquivo movido.');
    }

    public function preview(MediaFile $mediaFile)
    {
        if (!$mediaFile->isPhoto()) {
            abort(404);
        }

        try {
            $contents = $this->drive->downloadContents($mediaFile->google_drive_file_id);
        } catch (\Throwable $e) {
            Log::warning('Preview do Drive indisponível', [
                'media_file_id' => $mediaFile->id,
                'drive_file_id' => $mediaFile->google_drive_file_id,
                'error' => $e->getMessage(),
            ]);
            abort(404);
        }

        return response($contents, 200, [
            'Content-Type' => $mediaFile->mime_type,
            'Cache-Control' => 'private, max-age=300',
            'Content-Disposition' => 'inline; filename="' . Str::ascii($mediaFile->original_filename) . '"',
        ]);
    }

    /**
     * Miniatura autenticada (proxy do thumbnailLink/iconLink do Drive).
     * Usar no HTML em vez de thumbnailLink direto — a URL do Google exige token.
     */
    public function thumbnail(MediaFile $mediaFile)
    {
        try {
            $thumb = $this->drive->downloadThumbnailContents($mediaFile->google_drive_file_id);
        } catch (\Throwable $e) {
            Log::warning('Miniatura do Drive indisponível', [
                'media_file_id' => $mediaFile->id,
                'drive_file_id' => $mediaFile->google_drive_file_id,
                'error' => $e->getMessage(),
            ]);
            abort(404);
        }

        if ($thumb === null) {
            abort(404);
        }

        return response($thumb['contents'], 200, [
            'Content-Type' => $thumb['contentType'],
            'Cache-Control' => 'private, max-age=600',
            'Content-Disposition' => 'inline; filename="thumb-' . Str::ascii($mediaFile->original_filename) . '"',
        ]);
    }

    /**
     * Listagem JSON para o modal de seleção (navegação de pastas + filtros).
     */
    public function browse(Request $request)
    {
        $folderId = $request->integer('pasta') ?: null;
        $currentFolder = $folderId
            ? MediaFolder::with('parent')->findOrFail($folderId)
            : null;

        $foldersQuery = MediaFolder::query()
            ->where('parent_folder_id', $folderId)
            ->orderBy('name');

        $filesQuery = MediaFile::query()
            ->where('media_folder_id', $folderId)
            ->latest();

        // Instagram / seletor de mídia: só imagem e vídeo.
        if ($request->boolean('only_media', false)) {
            $filesQuery->where(function ($q) {
                $q->where('mime_type', 'like', 'image/%')
                    ->orWhere('mime_type', 'like', 'video/%');
            });
        }

        if ($category = $request->input('categoria')) {
            if (in_array($category, [MediaFile::CATEGORY_PHOTO, MediaFile::CATEGORY_DOCUMENT], true)) {
                $filesQuery->where('category', $category);
            }
        }

        if ($search = trim((string) $request->input('q', ''))) {
            $foldersQuery->where('name', 'like', "%{$search}%");
            $filesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('original_filename', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $breadcrumb = [];
        if ($currentFolder) {
            foreach ($currentFolder->breadcrumb() as $crumb) {
                $breadcrumb[] = [
                    'id' => $crumb->id,
                    'name' => $crumb->name,
                ];
            }
        }

        $folders = $foldersQuery->get(['id', 'name', 'parent_folder_id'])->map(fn (MediaFolder $f) => [
            'id' => $f->id,
            'name' => $f->name,
            'parent_folder_id' => $f->parent_folder_id,
        ]);

        $files = $filesQuery->limit(100)->get()->map(function (MediaFile $file) {
            $kind = $file->mediaKind();
            $hasThumb = $file->isPhoto() || $file->isVideo();

            return [
                'id' => $file->id,
                'name' => $file->name,
                'original_filename' => $file->original_filename,
                'mime_type' => $file->mime_type,
                'size' => $file->size,
                'formatted_size' => $file->formattedSize(),
                'category' => $file->category,
                'media_kind' => $kind,
                'is_photo' => $file->isPhoto(),
                'is_video' => $file->isVideo(),
                'selectable' => $file->isPhoto() || $file->isVideo(),
                'thumbnail_url' => $hasThumb
                    ? route('midia.thumbnail', $file)
                    : null,
                'preview_url' => $file->isPhoto()
                    ? route('midia.preview', $file)
                    : null,
            ];
        });

        return response()->json([
            'current_folder_id' => $folderId,
            'breadcrumb' => $breadcrumb,
            'folders' => $folders,
            'files' => $files,
            'filters' => [
                'q' => $search,
                'categoria' => $request->input('categoria', ''),
                'pasta' => $folderId,
            ],
        ]);
    }

    public function settings()
    {
        return view('midia.settings', [
            'google' => GoogleDriveSetting::current(),
            'instagram' => InstagramSetting::current(),
        ]);
    }

    /**
     * Converte erros da API do Google Drive em mensagens acionáveis para o usuário.
     */
    private function driveErrorMessage(\Throwable $e): string
    {
        $msg = $e->getMessage();

        if (str_contains($msg, 'ACCESS_TOKEN_SCOPE_INSUFFICIENT') || stripos($msg, 'insufficientScopes') !== false) {
            return 'A autorização do Google Drive está com permissões desatualizadas (escopo insuficiente). '
                . 'Desconecte e conecte novamente a conta em Mídia > Configurações — será pedido um novo consentimento ao Google.';
        }

        if (str_contains($msg, 'invalid_grant') || str_contains($msg, 'Reconecte a conta')) {
            return 'A conexão com o Google Drive expirou ou foi revogada. Reconecte a conta em Mídia > Configurações.';
        }

        if (str_contains($msg, 'File not found') || str_contains($msg, 'notFound')) {
            return 'A pasta não está acessível na conta do Google Drive conectada. '
                . 'Se a conta do Drive foi trocada ou reconectada, as pastas criadas antes ficam inacessíveis — '
                . 'crie a pasta novamente ou reconecte a conta original em Mídia > Configurações.';
        }

        if (stripos($msg, 'insufficient') !== false || stripos($msg, 'permission') !== false) {
            return 'Sem permissão no Google Drive para esta operação. Reconecte a conta em Mídia > Configurações.';
        }

        if (str_contains($msg, 'storageQuotaExceeded') || stripos($msg, 'quota') !== false) {
            return 'A conta do Google Drive conectada está sem espaço ou excedeu a cota de uso.';
        }

        return 'Falha ao comunicar com o Google Drive: ' . $msg;
    }
}
