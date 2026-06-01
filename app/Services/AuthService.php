<?php

namespace App\Services;

use App\Models\User;
use App\Models\Patient;
use App\Models\Doctor;
use App\Enums\UserRole;
use App\Enums\PatientGender;
use App\Enums\BloodType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(protected WhatsAppService $whatsAppService) {}

    /**
     * Register a new user, create conditional profile, store the OTP, and send the OTP via SMS.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, profile: \Illuminate\Database\Eloquent\Model|null, otp: string}
     */
    public function register(array $data): array
    {
        $data['password'] = Hash::make($data['password']);
        $data['phone'] = $this->normalizePhone($data['phone']);

        if (isset($data['role'])) {
            $data['role'] = UserRole::from($data['role']);
        }

        return DB::transaction(function () use ($data) {
            // Step 1: Create the user record
            $user = User::create($data);

            // Step 2: Create conditional profile based on role
            $profile = null;
            $role = $user->role;

            if ($role === UserRole::PATIENT) {
                $profile = $this->createPatientProfile($user, $data);
            } elseif ($role === UserRole::DOCTOR) {
                $profile = $this->createDoctorProfile($user, $data);
            }

            // Send verification notification and generate OTP
            $user->sendEmailVerificationNotification();
            $otp = $this->generateOtp($user);

            return [
                'user' => $user,
                'profile' => $profile,
                'otp' => $otp,
            ];
        });
    }

    /**
     * Create a patient profile for the registered user.
     *
     * @param  \App\Models\User  $user
     * @param  array<string, mixed>  $data
     * @return \App\Models\Patient
     */
    protected function createPatientProfile(User $user, array $data): Patient
    {
        return $user->patient()->create([
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => isset($data['gender']) ? PatientGender::from($data['gender']) : null,
            'blood_type' => isset($data['blood_type']) ? BloodType::from($data['blood_type']) : null,
            'national_id' => $data['national_id'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
            'allergies' => $data['allergies'] ?? [],
            'chronic_conditions' => $data['chronic_conditions'] ?? [],
        ]);
    }

    /**
     * Create a doctor profile for the registered user.
     *
     * @param  \App\Models\User  $user
     * @param  array<string, mixed>  $data
     * @return \App\Models\Doctor
     */
    protected function createDoctorProfile(User $user, array $data): Doctor
    {
        return $user->doctor()->create([
            'specialty' => $data['specialty'] ?? null,
            'license_number' => $data['license_number'] ?? null,
            'bio' => $data['bio'] ?? null,
            'years_experience' => $data['years_experience'] ?? 0,
            'consultation_fee' => $data['consultation_fee'] ?? 0,
            'clinic_id' => $data['clinic_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'ai_summary_enabled' => false,
        ]);
    }

    /**
     * Authenticate the user and generate a Sanctum token.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are invalid.'],
            ]);
        }

        if (! $user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => ['Your email address must be verified before login.'],
            ]);
        }

        if (! $user->phone_verified_at) {
            throw ValidationException::withMessages([
                'phone' => ['Your phone number must be verified before login.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Create or authenticate a user using Google OAuth data.
     *
     * @param  string  $name
     * @param  string  $email
     * @param  string|null  $avatar
     * @return array{user: User, token: string}
     */
    public function loginOrCreateSocialUser(string $name, string $email, ?string $avatar = null): array
    {
        return DB::transaction(function () use ($name, $email, $avatar) {
            $user = User::firstOrNew(['email' => $email]);

            $user->forceFill([
                'name' => $name,
                'email' => $email,
                'avatar' => $avatar,
                'password' => $user->password ?? Hash::make(uniqid()),
                'email_verified_at' => now(),
                'last_login_at' => now(),
            ])->save();

            $token = $user->createToken('api-token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        });
    }

    /**
     * Verify the submitted OTP and issue a Sanctum token.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function verifyOtp(array $data): array
    {
        $phone = $this->normalizePhone($data['phone']);

        if (Cache::has($this->otpLockCacheKey($phone))) {
            throw ValidationException::withMessages([
                'phone' => ['This phone number is temporarily locked due to multiple failed OTP attempts. Try again later.'],
            ]);
        }

        $user = User::where('phone', $phone)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => ['The phone number is not registered.'],
            ]);
        }

        if (! $user->otp_code || ! hash_equals($user->otp_code, (string) $data['code'])) {
            $attempts = $this->incrementOtpAttempt($user->phone);

            if ($attempts >= 3) {
                Cache::put($this->otpLockCacheKey($user->phone), true, now()->addMinutes(15));
            }

            throw ValidationException::withMessages([
                'code' => ['The provided OTP is invalid.'],
            ]);
        }

        if (! $user->otp_expires_at || $user->otp_expires_at->isPast()) {
            $this->clearOtpData($user);

            throw ValidationException::withMessages([
                'code' => ['The OTP has expired. Please request a new code.'],
            ]);
        }

        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'phone_verified_at' => now(),
                'otp_code' => null,
                'otp_expires_at' => null,
            ])->save();

            $this->clearOtpAttempts($user->phone);
        });

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Revoke the current API token for the authenticated user.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /**
     * Generate a one-time password for a user and dispatch an SMS.
     */
    public function generateOtp(User $user): string
    {
        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ])->save();

        $this->clearOtpAttempts($user->phone);

        $this->whatsAppService->sendOtp($user->phone, $otp);

        return $otp;
    }

    /**
     * Increment a failed OTP attempt counter.
     */
    protected function incrementOtpAttempt(string $phone): int
    {
        $cacheKey = $this->otpAttemptCacheKey($phone);
        $attempts = Cache::get($cacheKey, 0) + 1;

        Cache::put($cacheKey, $attempts, now()->addMinutes(15));

        return $attempts;
    }

    /**
     * Clear OTP attempt counters for the given phone.
     */
    protected function clearOtpAttempts(string $phone): void
    {
        Cache::forget($this->otpAttemptCacheKey($phone));
        Cache::forget($this->otpLockCacheKey($phone));
    }

    /**
     * Clear OTP data from the user record.
     */
    protected function clearOtpData(User $user): void
    {
        $user->forceFill([
            'otp_code' => null,
            'otp_expires_at' => null,
        ])->save();
    }

    /**
     * Normalize a phone number for storage and UltraMsg delivery.
     */
    protected function normalizePhone(string $phone): string
    {
        $formatted = preg_replace('/\D+/', '', $phone);

        if (! $formatted || ! preg_match('/^[1-9]\d{9,14}$/', $formatted)) {
            throw ValidationException::withMessages([
                'phone' => ['The phone number must be in international format without a plus sign.'],
            ]);
        }

        return $formatted;
    }

    /**
     * Build the cache key for OTP attempt tracking.
     */
    protected function otpAttemptCacheKey(string $phone): string
    {
        return "auth.otp_attempts:{$phone}";
    }

    /**
     * Build the cache key for OTP lock tracking.
     */
    protected function otpLockCacheKey(string $phone): string
    {
        return "auth.otp_locked:{$phone}";
    }
}
