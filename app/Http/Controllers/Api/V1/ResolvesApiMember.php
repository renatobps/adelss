<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Member;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ResolvesApiMember
{
    protected function requireMember(Request $request): Member|JsonResponse
    {
        $user = $request->user();
        if ($user && ! $user->relationLoaded('member')) {
            $user->load('member');
        }

        $member = $user?->member;
        if (! $member) {
            return ApiResponse::error('Esta conta não está vinculada a um membro.', 403, [
                'code' => 'member_required',
            ]);
        }

        return $member;
    }
}
