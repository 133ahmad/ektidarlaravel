<?php

namespace App\Http\Controllers;

use App\Models\Verification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    // Send verification code
    public function sendCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->email;
        $code = rand(100000, 999999);

        // Store or update verification code in DB
        Verification::updateOrCreate(
            ['email' => $email],
            [
                'code' => $code,
                'verified' => false,
                'expires_at' => Carbon::now()->addMinutes(10),
            ]
        );

        // Send email
        Mail::raw("Your verification code is: $code", function ($message) use ($email) {
            $message->to($email)->subject('Email Verification Code');
        });

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent!'
        ]);
    }

    // Resend verification code
    public function resendCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->email;
        $code = rand(100000, 999999);

        // Update code and expiry
        Verification::updateOrCreate(
            ['email' => $email],
            [
                'code' => $code,
                'verified' => false,
                'expires_at' => Carbon::now()->addMinutes(10),
            ]
        );

        Mail::raw("Your new verification code is: $code", function ($message) use ($email) {
            $message->to($email)->subject('Resend Verification Code');
        });

        return response()->json([
            'success' => true,
            'message' => 'New verification code sent!'
        ]);
    }

    // Verify code
    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6'
        ]);

        $verification = Verification::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($verification) {
            $verification->update(['verified' => true]);
            return response()->json(['verified' => true]);
        }

        return response()->json([
            'verified' => false,
            'message' => 'Invalid or expired code'
        ]);
    }
}
