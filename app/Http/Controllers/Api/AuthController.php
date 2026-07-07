<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Exceptions\WhatsAppServiceException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    public function __construct(protected AuthService $authService) {}

    /**
     * Register a new user and dispatch an OTP.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());

            // Eager-load the profile relationship based on role for complete response
            $user = $result['user'];
            if ($user->role->value === 'patient') {
                $user->load('patient');
            } elseif ($user->role->value === 'doctor') {
                $user->load('doctor');
            }
        } catch (WhatsAppServiceException $exception) {
            return $this->errorResponse(null, $exception->getMessage(), 500);
        }

        $response = [
            'user' => new UserResource($user),
            'message' => 'Registration successful. Verify your phone with the OTP sent to your phone.',
        ];

        if (app()->environment('local')) {
            $response['otp'] = $result['otp'];
        }

        return $this->successResponse($response, 'Registration successful.', 201);
    }

    /**
     * Authenticate the user and return a token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Login successful.');
    }

    /**
     * Verify OTP and issue an API token.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->authService->verifyOtp($request->validated());

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'OTP verified successfully.');
    }

    /**
     * Resend the email verification link to the authenticated user.
     */
    public function resendEmailVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->errorResponse(null, 'Unauthorized.', 401);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse(null, 'Email address is already verified.');
        }

        $user->sendEmailVerificationNotification();

        return $this->successResponse(null, 'Verification email resent.');
    }

    /**
     * Verify the user's email using a signed verification link.
     */
    public function verifyEmail(Request $request, string $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return $this->errorResponse(null, 'Invalid verification link.', 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse(null, 'Email already verified.');
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return $this->successResponse(null, 'Email verified successfully.');
    }

    /**
     * Revoke the currently authenticated user's API token.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(null, 'Logged out successfully.');
    }
}
