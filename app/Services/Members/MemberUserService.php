<?php

namespace App\Services\Members;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MemberUserService
{
    public const DEFAULT_PASSWORD = '123456';

    public function normalizeEmail(?string $email): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        return strtolower(trim($email));
    }

    public function syncFromMember(Member $member, ?string $password = null, bool $forceDefaultPassword = false): ?User
    {
        $email = $this->normalizeEmail($member->email);

        if ($email === null) {
            return null;
        }

        if ($member->email !== $email) {
            $member->forceFill(['email' => $email])->save();
        }

        $user = $member->user ?: new User();
        $user->member_id = $member->id;
        $user->name = $member->name;
        $user->email = $email;

        if ($password !== null && $password !== '') {
            $user->password = Hash::make($password);
            $user->must_change_password = false;
        } elseif (! $user->exists || $forceDefaultPassword) {
            $user->password = Hash::make(self::DEFAULT_PASSWORD);
            $user->must_change_password = true;
        }

        if ($user->is_admin === null) {
            $user->is_admin = false;
        }

        $user->save();

        return $user->fresh();
    }
}
