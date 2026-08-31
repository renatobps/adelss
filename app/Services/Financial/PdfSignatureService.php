<?php

namespace App\Services\Financial;

use App\Models\Member;
use App\Support\PdfText;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PdfSignatureService
{
    public const ROLE_PASTOR = 'pastor';

    public const ROLE_TESOUREIRO = 'tesoureiro';

    private const DISK = 'public';

    private const DIR = 'financial/signatures';

    /**
     * @return array{tesoureiroNome:?string,tesoureiroAssinaturaSrc:?string,pastorNome:?string,pastorAssinaturaSrc:?string}
     */
    public function forPdf(): array
    {
        return [
            'tesoureiroNome' => $this->tesoureiroNome(),
            'tesoureiroAssinaturaSrc' => $this->imageSrc(self::ROLE_TESOUREIRO),
            'pastorNome' => $this->pastorNome(),
            'pastorAssinaturaSrc' => $this->imageSrc(self::ROLE_PASTOR),
        ];
    }

    public function imagePath(string $role): ?string
    {
        $role = $this->normalizeRole($role);
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $relative = self::DIR.'/'.$role.'.'.$ext;
            if (Storage::disk(self::DISK)->exists($relative)) {
                $absolute = Storage::disk(self::DISK)->path($relative);
                if (is_file($absolute)) {
                    return $absolute;
                }
            }
        }

        return null;
    }

    public function imageSrc(string $role): ?string
    {
        $path = $this->imagePath($role);
        if (! $path) {
            return null;
        }

        $mime = @mime_content_type($path) ?: 'image/png';
        $contents = @file_get_contents($path);
        if ($contents === false || $contents === '') {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    public function store(string $role, UploadedFile $file): void
    {
        $role = $this->normalizeRole($role);
        $this->delete($role);
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            $ext = 'png';
        }
        $file->storeAs(self::DIR, $role.'.'.$ext, self::DISK);
    }

    public function delete(string $role): void
    {
        $role = $this->normalizeRole($role);
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            Storage::disk(self::DISK)->delete(self::DIR.'/'.$role.'.'.$ext);
        }
    }

    public function pastorNome(): ?string
    {
        return $this->namesForRole(['Pastor%', 'Pastora%', '%Pastor(a)%']);
    }

    public function tesoureiroNome(): ?string
    {
        $members = $this->membersWithRoleLike(['%Tesoureiro%']);
        if ($members->isEmpty()) {
            return null;
        }

        $first = $members->filter(fn (Member $member) => $this->isFirstTreasurerRole((string) ($member->role->name ?? '')));
        if ($first->isNotEmpty()) {
            return $this->formatMemberNames($first);
        }

        $withoutSecond = $members->reject(fn (Member $member) => $this->isSecondTreasurerRole((string) ($member->role->name ?? '')));

        return $this->formatMemberNames($withoutSecond);
    }

    private function normalizeRole(string $role): string
    {
        return $role === self::ROLE_PASTOR ? self::ROLE_PASTOR : self::ROLE_TESOUREIRO;
    }

    private function isFirstTreasurerRole(string $roleName): bool
    {
        return (bool) preg_match('/(?:^|[\s])(?:1[ºo°]?|primeiro)\s*tesoureir/iu', $roleName);
    }

    private function isSecondTreasurerRole(string $roleName): bool
    {
        return (bool) preg_match('/(?:^|[\s])(?:2[ºo°]?|segundo)\s*tesoureir/iu', $roleName);
    }

    /**
     * @param  list<string>  $namePatterns
     */
    private function namesForRole(array $namePatterns): ?string
    {
        return $this->formatMemberNames($this->membersWithRoleLike($namePatterns));
    }

    /**
     * @param  list<string>  $namePatterns
     * @return Collection<int, Member>
     */
    private function membersWithRoleLike(array $namePatterns): Collection
    {
        if (! Schema::hasTable('members') || ! Schema::hasTable('member_roles')) {
            return collect();
        }

        $query = Member::query()
            ->with('role')
            ->whereHas('role', function ($q) use ($namePatterns) {
                $q->where('is_active', true)
                    ->where(function ($inner) use ($namePatterns) {
                        foreach ($namePatterns as $index => $pattern) {
                            if ($index === 0) {
                                $inner->where('name', 'like', $pattern);
                            } else {
                                $inner->orWhere('name', 'like', $pattern);
                            }
                        }
                    });
            })
            ->orderBy('name');

        $members = (clone $query)->where('status', Member::STATUS_ATIVO)->get();

        return $members->isNotEmpty() ? $members : $query->get();
    }

    /**
     * @param  Collection<int, Member>  $members
     */
    private function formatMemberNames(Collection $members): ?string
    {
        if ($members->isEmpty()) {
            return null;
        }

        $names = $members
            ->pluck('name')
            ->filter()
            ->map(fn ($name) => PdfText::stripEmoji((string) $name))
            ->unique()
            ->values();

        return $names->isEmpty() ? null : $names->implode(' / ');
    }
}
