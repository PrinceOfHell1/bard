<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use App\Mail\VerifyMail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{

    /**
     * Google.
     */
    public function google(Request $request)
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google Action.
     */
    public function googleAPI(Request $request)
    {
        try {
            //authenticate users through Google using Socialite
            $google = Socialite::driver('google')->user();

            $findUser = User::where('email', $google->email)->first();
            if (!$findUser) {
                $googleRegister = User::create([
                    'photo' => $google->avatar,
                    'name' => $google->name,
                    'email' => $google->email,
                    'password' => Hash::make($google->password),
                    'authenticated' => 'verified',
                    'login' => 'google'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Google login Successfully',
                    'data'  => [
                        'token' => $googleRegister->createToken('myApp')->plainTextToken,
                        'name' => $googleRegister->name,
                        'google' => [
                            'client_id' => env('GOOGLE_CLIENT_ID'),
                            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                        ],
                    ]
                ]);
            } else {
                $googleLogin = User::where('email', $google->email)->where('login', 'google')->first();
                if ($googleLogin) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Google login Successfully',
                        'data'  => [
                            'token' => $googleLogin->createToken('myApp')->plainTextToken,
                            'name' => $googleLogin->name,
                            'google' => [
                                'client_id' => env('GOOGLE_CLIENT_ID'),
                                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                            ],
                        ]
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Account already exists',
                    ]);
                }
            }
        } catch (Exception $e) {
            //retrieve error message
            return response()->json($e->getMessage());
        }
    }

    /**
     * Login.
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user->login == 'manual') {
                // check if account has not been verified
                if ($user->authenticated != 'verified') {
                    return response()->json([
                        'success' => false,
                        'message' => 'The account has not been verified'
                    ]);
                }

                // if account has been verified
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'token' => $user->createToken($user->email)->plainTextToken,
                        'name' => $user->name
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'The account is registered as a Google account'
                ]);
            }
        } else {
            // Check if the user account exists by email
            $user = User::where('email', $credentials['email'])->first();

            if ($user) {
                // The email exists, but the password is incorrect
                return response()->json([
                    'success' => false,
                    'message' => 'The password you entered is incorrect',
                ]);
            } else {
                // The user account doesn't exist
                return response()->json([
                    'success' => false,
                    'message' => 'Account not found, please register first',
                ]);
            }
        }
    }

    /**
     * Register.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required'],
        ]);

        //create a new account
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'verified' => Str::random(50),
        ]);

        Mail::to($user->email)->send(new VerifyMail($user));
        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
        ]);
    }

    /**
     * Verify Email.
     */
    public function verifyEmail($verified)
    {
        //check if verified same or not
        $user = User::where('verified', $verified)->first();

        //if account verified update for authenticated
        if ($user) {
            $user->markEmailAsVerified();
            $user->update([
                'authenticated' => 'verified'
            ]);
            return view('admin.vertifyEmailSuccess');
        } else{
            //display if account not found
            return response()->json([
                'success' => false,
                'message' => 'Account not found'
            ]);
        }
    }

    /**
     * Profile.
     */
    public function profile()
    {
        $user = Auth::user();
        $data = [
            'photo' => ($user->login == 'google') ? $user->photo : url($user->photo),
            'name' => $user->name,
            'email' => $user->email,
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Logout.
     */
    public function logout()
    {
        $user = Auth::user();
        $user->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logout Successfully',
        ], 200);
    }

    /**
     * Delete Account.
     */
    public function delete()
    {
        $user = Auth::user();
        $user->delete();
        return response()->json([
            'success' => true,
            'message' => 'Account Deleted Success',
        ]);
    }

    /**
     * Restore Account.
     */
    public function restore(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        // Check if the user account exists by email
        $user = User::withTrashed()->where('email', $request->email)->first();
        if (!$user) {
            // this is if account not found
            return response()->json([
                'success' => false,
                'message' => 'Account not found, please register first',
            ]);
        } else {
            // this is if have account
            $user->restore();
            return response()->json([
                'success' => true,
                'message' => 'Account Restore Success'
            ]);
        }
    }
}
