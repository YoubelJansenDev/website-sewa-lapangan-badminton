<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Password;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // role SELALU 'user' di sini, tidak pernah dari input request
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => 'user',
        ]);

        $token = auth('api')->login($user);

        return response()->json(['user' => $this->userData($user)], 201)
            ->withCookie($this->tokenCookie($token));
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        return response()->json(['user' => $this->userData(auth('api')->user())])
            ->withCookie($this->tokenCookie($token));
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userData($request->user())]);
    }

    public function refresh(): JsonResponse
    {
        try {
            $token = auth('api')->refresh();
        } catch (JWTException $e) {
            return response()->json(['message' => 'Sesi tidak valid, silakan login kembali.'], 401);
        }

        return response()->json(['message' => 'Token diperbarui.'])
            ->withCookie($this->tokenCookie($token));
    }

    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json(['message' => 'Berhasil logout.'])
            ->withCookie(Cookie::forget('token'));
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        // Balasan selalu sama agar email terdaftar tidak bisa ditebak
        return response()->json(['message' => 'Jika email terdaftar, link reset password sudah dikirim.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 422);
        }

        return response()->json(['message' => 'Password berhasil diubah. Silakan login.']);
    }

    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
        ];
    }

    // Cookie hidup selama masa refresh; JWT-nya sendiri kedaluwarsa lebih cepat (JWT_TTL)
    private function tokenCookie(string $token)
    {
        return cookie(
            'token',
            $token,
            (int) config('jwt.refresh_ttl'),
            '/',
            null,
            app()->isProduction(), // secure hanya di production (HTTPS)
            true,                  // httpOnly: tidak bisa dibaca JavaScript
            false,
            'Lax'
        );
    }

        private function guard(): JWTGuard
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        return $guard;
    }
}