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
            'device_name' => ['nullable', 'string', 'max:191'],
            'fcm_token' => ['nullable', 'string', 'max:512'],
            'platform' => ['nullable', 'in:android,ios,web'],
        ], [
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'Informe a senha.',
        ]);

        $email = strtolower(trim($credentials['email']));
        $password = $credentials['password'];

        try {
            $user = \App\Models\User::query()->where('email', $email)->first();
            if (! $user || ! Hash::check($password, $user->password)) {
                return ApiResponse::error('E-mail ou senha inválidos.', 401);
            }

            $deviceName = trim((string) ($credentials['device_name'] ?? '')) ?: 'android';
            if (strlen($deviceName) > 80) {
                $deviceName = substr($deviceName, 0, 80);
            }

            $token = $user->createToken($deviceName)->plainTextToken;
            try {
                $this->rememberDevice($request, $user->id, $deviceName);
            } catch (\Throwable $e) {
                report($e);
            }

            try {
                $payload = $profile->for($user);
            } catch (\Throwable $e) {
                report($e);
                $payload = [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_admin' => (bool) $user->is_admin,
                        'must_change_password' => (bool) ($user->getAttributes()['must_change_password'] ?? false),
                    ],
                    'permissions' => [],
                    'member' => null,
                    'modules' => [],
                ];
            }

            $payload['token'] = $token;
            $payload['token_type'] = 'Bearer';

            return ApiResponse::success($payload);
        } catch (\Throwable $e) {
            report($e);

            return ApiResponse::error(
                config('app.debug') ? $e->getMessage() : 'Não foi possível entrar.',
                500
            );
        }
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
