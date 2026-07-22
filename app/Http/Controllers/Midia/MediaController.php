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
            'folders' => $foldersQuery->get(),
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
        foreach ($request->file('files', []) as $file) {
            if (!$file->isValid()) {
                continue;
            }

            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $category = str_starts_with($mime, 'image/')
                ? MediaFile::CATEGORY_PHOTO
                : MediaFile::CATEGORY_DOCUMENT;

            $uploaded = $this->drive->uploadFile(
                $file,
                $file->getClientOriginalName(),
                $parentDriveId
            );

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

        $driveFolderId = $this->drive->createFolder(
            $validated['name'],
            $parent?->google_drive_folder_id
        );

        MediaFolder::create([
            'name' => $validated['name'],
            'google_drive_folder_id' => $driveFolderId,
            'parent_folder_id' => $parent?->id,
            'department_id' => $validated['department_id'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'Pasta criada no Google Drive.');
    }

    public function download(MediaFile $mediaFile): StreamedResponse
    {
        $contents = $this->drive->downloadContents($mediaFile->google_drive_file_id);

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
            $this->drive->moveFile($mediaFile->google_drive_file_id, $newParent, $oldParent);
        }

        $mediaFile->update(['media_folder_id' => $target?->id]);

        return back()->with('success', 'Arquivo movido.');
    }

    public function preview(MediaFile $mediaFile)
    {
        if (!$mediaFile->isPhoto()) {
            abort(404);
        }

        $contents = $this->drive->downloadContents($mediaFile->google_drive_file_id);

        return response($contents, 200, [
            'Content-Type' => $mediaFile->mime_type,
            'Cache-Control' => 'private, max-age=300',
            'Content-Disposition' => 'inline; filename="' . Str::ascii($mediaFile->original_filename) . '"',
        ]);
    }

    public function settings()
    {
        return view('midia.settings', [
            'google' => GoogleDriveSetting::current(),
            'instagram' => InstagramSetting::current(),
        ]);
    }
}
