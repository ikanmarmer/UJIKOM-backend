<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // Register dengan Email Manual
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate verification code
        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => 'user',
            'verification_code' => $verificationCode,
            'verification_code_expires_at' => Carbon::now()->addMinutes(10),
            'is_verified' => false,
        ]);

        // Send verification code via email
        Mail::raw("Your StayEasy verification code is: {$verificationCode}\n\nThis code will expire in 10 minutes.", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('StayEasy - Email Verification Code');
        });

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please check your email for verification code.',
            'data' => [
                'email' => $user->email,
                'requires_verification' => true,
            ]
        ], 201);
    }

    // Verify Email Code
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        if ($user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Email already verified.'
            ], 400);
        }

        if ($user->verification_code !== $request->code) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code.'
            ], 400);
        }

        if (Carbon::now()->greaterThan($user->verification_code_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code has expired.'
            ], 400);
        }

        $user->update([
            'is_verified' => true,
            'email_verified_at' => Carbon::now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ], 200);
    }

    // Resend Verification Code
    public function resendVerificationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        if ($user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Email already verified.'
            ], 400);
        }

        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'verification_code' => $verificationCode,
            'verification_code_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        Mail::raw("Your StayEasy verification code is: {$verificationCode}\n\nThis code will expire in 10 minutes.", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('StayEasy - Email Verification Code');
        });

        return response()->json([
            'success' => true,
            'message' => 'Verification code has been resent to your email.'
        ], 200);
    }

    // Login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.'
            ], 401);
        }

        if (!$user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Email not verified. Please verify your email first.',
                'requires_verification' => true,
                'email' => $user->email,
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ], 200);
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.'
        ], 200);
    }

    // Get Current User
    public function me(Request $request)
    {
        $user = $request->user();
        $user->has_password = !is_null($user->password);

        // Add full avatar URL if exists
        if ($user->avatar && !filter_var($user->avatar, FILTER_VALIDATE_URL)) {
            $user->avatar_url = asset('storage/' . $user->avatar);
        } else {
            $user->avatar_url = $user->avatar;
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ], 200);
    }

    // Update Profile
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // Base validation rules
        $rules = [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'avatar' => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        // Password validation based on user type
        if ($user->google_id && !$user->password) {
            // Google user setting password for first time (optional)
            $rules['password'] = 'sometimes|nullable|string|min:8|confirmed';
        } else {
            // Regular user changing password (requires current password)
            $rules['current_password'] = 'required_with:password|string';
            $rules['password'] = 'sometimes|nullable|string|min:8|confirmed';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = [];

        // Update name
        if ($request->has('name')) {
            $data['name'] = $request->name;
        }

        // Update phone
        if ($request->has('phone')) {
            $data['phone'] = $request->phone;
        }

        // Update avatar
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists and not from Google
            if ($user->avatar && !filter_var($user->avatar, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Store new avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $avatarPath;
        }

        // Handle password update
        if ($request->filled('password')) {
            // Google user setting password for first time
            if ($user->google_id && !$user->password) {
                $data['password'] = Hash::make($request->password);
            }
            // Regular user changing password
            else {
                // Verify current password
                if (!$request->filled('current_password')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current password is required to change password'
                    ], 422);
                }

                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Current password is incorrect'
                    ], 422);
                }

                $data['password'] = Hash::make($request->password);
            }
        }

        // Update user
        $user->update($data);
        $user->refresh();

        // Add has_password flag
        $user->has_password = !is_null($user->password) && !empty($user->password);

        // Add full avatar URL
        if ($user->avatar && !filter_var($user->avatar, FILTER_VALIDATE_URL)) {
            $user->avatar_url = asset('storage/' . $user->avatar);
        } else {
            $user->avatar_url = $user->avatar;
        }

        // Custom success message based on what was updated
        $message = 'Profile updated successfully';
        if ($request->filled('password')) {
            if ($user->google_id && !$user->getOriginal('password')) {
                $message = 'Password set successfully! You can now login with email and password.';
            } else {
                $message = 'Password changed successfully!';
            }
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $user
        ], 200);
    }

    // Google OAuth Redirect
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    // Helper function to download and save Google avatar
    private function saveGoogleAvatar($avatarUrl)
    {
        try {
            // Download image from Google
            $imageContent = file_get_contents($avatarUrl);

            if ($imageContent === false) {
                return null;
            }

            // Generate unique filename
            $filename = 'avatars/' . uniqid() . '_google.jpg';

            // Save to storage
            Storage::disk('public')->put($filename, $imageContent);

            return $filename;
        } catch (\Exception $e) {
            \Log::error('Failed to save Google avatar: ' . $e->getMessage());
            return null;
        }
    }

    // Google OAuth Callback
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();

            $user = User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            $avatarPath = null;
            if ($googleUser->avatar) {
                $avatarPath = $this->saveGoogleAvatar($googleUser->avatar);
            }

            if ($user) {
                // Update existing user
                $updateData = [
                    'google_id' => $googleUser->id,
                    'is_verified' => true,
                    'email_verified_at' => $user->email_verified_at ?? Carbon::now(),
                ];

                // Only update avatar if we successfully saved it
                if ($avatarPath) {
                    // Delete old avatar if exists and not a URL
                    if ($user->avatar && !filter_var($user->avatar, FILTER_VALIDATE_URL)) {
                        Storage::disk('public')->delete($user->avatar);
                    }
                    $updateData['avatar'] = $avatarPath;
                }

                $user->update($updateData);
            } else {
                // Create new user
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar' => $avatarPath ?? $googleUser->avatar,
                    'role' => 'user',
                    'is_verified' => true,
                    'email_verified_at' => Carbon::now(),
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            $frontendUrl = config('app.frontend_url', 'http://127.0.0.1:3000');

            return redirect()->away("{$frontendUrl}/auth/google/callback?token={$token}");

        } catch (\Exception $e) {
            \Log::error('Google OAuth Error: ' . $e->getMessage());

            $frontendUrl = config('app.frontend_url', 'http://127.0.0.1:3000');
            return redirect()->away("{$frontendUrl}/auth/google/callback?error=authentication_failed");
        }
    }
}
