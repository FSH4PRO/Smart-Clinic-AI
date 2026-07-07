<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProfileController extends BaseController
{
    public function me()
    {
        $user = Auth::user();
        $user->load(['doctor', 'patient']);

        return $this->successResponse(UserResource::make($user), 'User profile retrieved successfully');
    }
}
