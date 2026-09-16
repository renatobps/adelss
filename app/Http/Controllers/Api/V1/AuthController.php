<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Services\Api\UserApiProfile;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request, UserApiProfile $profile): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
            'fcm_token' => ['nullable', 'string', 'max:512'],
            'platform' => ['nullable', 'in:android,ios'],
        ], [
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'Informe a senha.',
        ]);

        $email = strtolower(trim($credentials['email']));
        $password = $credentials['password'];

        $user = \App\Models\User::query()->where('email', $email)->first();
        if (! $user || ! Hash::check($password, $user->password)) {
            return ApiResponse::error('E-mail ou senha inválidos.', 401);
        }

        $deviceName = trim((string) ($credentials['device_name'] ?? '')) ?: 'android';
        $token = $user->createToken($deviceName)->plainTextToken;

        $this->rememberDevice($request, $user->id, $deviceName);

        $payload = $profile->for($user);
        $payload['token'] = $token;
        $payload['token_type'] = 'Bearer';

        return ApiResponse::success($payload);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $plain = (string) $request->bearerToken();

        if ($user && str_contains($plain, '|')) {
            $id = explode('|', $plain, 2)[0];
            $user->tokens()->whereKey($id)->delete();
        } elseif ($user?->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return ApiResponse::success(['logged_out' => true]);
    }

    private function rememberDevice(Request $request, int $userId, string $deviceName): void
    {
        $fcm = trim((string) $request->input('fcm_token', ''));
        if ($fcm === '') {
            return;
        }

        DeviceToken::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'token' => $fcm,
            ],
            [
                'platform' => (string) $request->input('platform', DeviceToken::PLATFORM_ANDROID),
                'device_name' => $deviceName,
                'last_used_at' => now(),
            ]
        );
    }
}
