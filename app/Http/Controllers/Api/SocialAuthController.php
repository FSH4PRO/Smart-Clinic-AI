<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;

class SocialAuthController extends BaseController
{
    public function __construct(
        protected SocialiteFactory $socialite,
        protected AuthService $authService
    ) {}

    /**
     * Redirect the user to the Google OAuth consent screen.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return $this->socialite->driver('google')
            ->redirectUrl(config('services.google.redirect'))
            ->stateless()
            ->redirect();
    }

    /**
     * Return the configured Google redirect URL for diagnostics.
     */
    public function googleRedirectUrl(): JsonResponse
    {
        return $this->successResponse([
            'redirect_url' => config('services.google.redirect'),
        ], 'Google redirect URL loaded.');
    }

    /**
     * Handle the OAuth callback from Google.
     */
    public function handleGoogleCallback(Request $request): JsonResponse
    {
        if ($request->missing('code')) {
            $errorMessage = $request->input('error_description', $request->input('error', 'Missing Google authorization code.'));

            return $this->errorResponse([
                'exception' => $errorMessage,
            ], 'Google authentication failed.', 400);
        }

        try {
            $socialUser = $this->socialite->driver('google')
                ->redirectUrl(config('services.google.redirect'))
                ->stateless()
                ->user();
        } catch (\Throwable $exception) {
            return $this->errorResponse([
                'exception' => $exception->getMessage(),
            ], 'Google authentication failed.', 400);
        }

        if (! $socialUser->getEmail()) {
            return $this->errorResponse(null, 'Google account did not return an email address.', 422);
        }

        $result = $this->authService->loginOrCreateSocialUser(
            $socialUser->getName() ?? $socialUser->getNickname() ?? 'Google User',
            $socialUser->getEmail(),
            $socialUser->getAvatar()
        );

        return $this->successResponse([
            'token' => $result['token'],
            'user' => new UserResource($result['user']),
        ], 'Google login successful.');
    }
}
