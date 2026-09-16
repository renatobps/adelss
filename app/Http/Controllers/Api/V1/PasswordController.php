<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Members\MemberUserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $rules = [
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)],
        ];
        if (! $user->must_change_password) {
            $rules['current_password'] = ['required', 'string'];
        } else {
            $rules['current_password'] = ['nullable', 'string'];
        }

        $validated = $request->validate($rules, [
            'current_password.required' => 'Informe a senha atual.',
            'password.required' => 'Informe a nova senha.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'password.min' => 'A nova senha deve ter pelo menos 8 caracteres.',
        ]);

        if (Hash::check($validated['password'], $user->password)
            || $validated['password'] === MemberUserService::DEFAULT_PASSWORD) {
            return ApiResponse::error('Escolha uma senha diferente da atual e da senha padrão.', 422);
        }

        if (! $user->must_change_password) {
            if (! Hash::check((string) $validated['current_password'], $user->password)) {
                return ApiResponse::error('Senha atual inválida.', 422);
            }
        } elseif (! empty($validated['current_password'])
            && ! Hash::check((string) $validated['current_password'], $user->password)) {
            return ApiResponse::error('Senha atual inválida.', 422);
        }

        $user->password = Hash::make($validated['password']);
        $user->must_change_password = false;
        $user->save();

        $currentId = $user->currentAccessToken()?->id;
        $user->tokens()->when($currentId, fn ($query) => $query->where('id', '!=', $currentId))->delete();

        return ApiResponse::success(['must_change_password' => false]);
    }

    public function forgot(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        Password::sendResetLink([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        return ApiResponse::success([
            'sent' => true,
        ], [
            'message' => 'Se o e-mail existir, enviamos o link para redefinir a senha.',
        ]);
    }
}
