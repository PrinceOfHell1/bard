<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use App\Jobs\SendEmailJob;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PasswordReset;
use App\Jobs\ForgotPasswordJob;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Google.
     */
    public function google(Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'url' => Socialite::driver('google')->stateless()->redirect()->getTargetUrl(),
            ]);
        }
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google Action.
     */
    public function googleAPI(Request $request)
    {
        try {
            // Authenticate users through Google using Socialite
            $google = Socialite::driver('google')->stateless()->user();
            dd($google);
            $findUser = User::where('email', $google->email)->first();

            if (!$findUser) {
                // If the user doesn't exist, create a new user
                $user = User::create([
                    'photo' => $google->avatar,
                    'name' => $google->name,
                    'email' => $google->email,
                    'password' => Hash::make('12345678'), // You can generate a random password
                    'authenticated' => 'verified',
                    'login' => 'google'
                ]);
                $user->markEmailAsVerified();
            } else {
                // If the user exists, use the existing user
                $user = $findUser;
            }

            // Generate a JWT token for the user
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Google login successfully',
                'data'  => [
                    'name' => $user->name,
                    'token' => $token,
                ]
            ]);
        } catch (JWTException $e) {
            // Handle JWT errors
            return response()->json(['success' => false, 'message' => 'Failed to create JWT token'], 500);
        } catch (Exception $e) {
            // Handle other errors
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Access token from google.
     */
    public static function accessToken($access_token)
    {
        $ret = self::requestData(
            'https://people.googleapis.com/v1/people/me?personFields=phoneNumbers,emailAddresses,names,photos',
            'GET',
            null,
            'Bearer ' . $access_token
        );

        return $ret;
    }

    /**
     * Request Data from access token.
     */
    public static function requestData($url, $method, $data = null, $Authorization = null)
    {
        $options = array(
            'http' => array(
                'method' => $method,
                'header' => "Content-Type: application/json\r\n" .
                    "Authorization: " . $Authorization,
            )
        );

        if ($data) {
            $options['http']['content'] = json_encode($data);
        }

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve data.',
            ];
        } else {
            $data = json_decode($response);

            $email = $data->emailAddresses[0]->value;

            // Check whether the email already exists in the database
            $user = User::where('email', $email)->first();

            if (!$user) {
                // If the email is not registered, create a new user
                $user = User::create([
                    'photo' => $data->photos[0]->url,
                    'name' => $data->names[0]->displayName,
                    'email' => $email,
                    'password' => Hash::make('12345678'), // You should use a more secure method to generate passwords
                    'authenticated' => 'verified',
                    'provider' => 'google',
                    'email_verified_at' => now()
                ]);
            }

            $credentials = [
                'email' => $email,
                'password' => '12345678' // Make sure you use the password entered by the user
            ];

            $token = JWTAuth::attempt($credentials);

            return [
                'success' => true,
                'message' => 'Login Google Successfully',
                'data' => [
                    'name' => $user->name,
                    'token' => $token
                ],
            ];
        }
    }

    /**
     * Login.
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if ($token = JWTAuth::attempt($credentials)) {
                $user =  $request->user();

                // check if user login with manual
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
                            'name' => $user->name,
                            'token' => $token,
                        ]
                    ]);
                } else {
                    //if login with google
                    return response()->json([
                        'success' => false,
                        'message' => 'The email address is already in use on Google',
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
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token',
            ]);
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

        $email = $request->email;
        $user = User::create([
            'name' => $request->name,
            'email' => $email,
            'password' => Hash::make($request->password),
            'verified' => Str::random(50),
        ]);
        SendEmailJob::dispatch($email, $user);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful and email sent successfully',
        ]);
    }

    /**
     * Verify Email.
     */
    public function verifyEmail($verified)
    {
        //check if verified token same or not
        $user = User::where('verified', $verified)->first();

        //if account verified update for authenticated
        if ($user) {
            $user->markEmailAsVerified();
            $user->update([
                'authenticated' => 'verified'
            ]);
            return view('admin.vertifyEmailSuccess');
        } else {
            //display if account not found
            return response()->json([
                'success' => false,
                'message' => 'Account not found'
            ]);
        }
    }

    /**
     * Send email for forget password.
     */
    public function sendEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email']
        ]);

        //check if email not found
        $email = $request->email;
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found, please register first'
            ]);
        }

        //create reset password
        $otp = mt_rand(1000, 9999);
        PasswordReset::create([
            'email' => $email,
            'otp' => $otp,
        ]);

        //send token to email user
        ForgotPasswordJob::dispatch($email, $otp);
        return response()->json([
            'success' => true,
            'message' => 'Token successfully sent to ' . $email,
            // 'token' => $token,
        ]);
    }

    /**
     * Repeat send token.
     */
    public function resendOTP($email)
    {
        //create reset password
        $otp = mt_rand(1000, 9999);
        PasswordReset::create([
            'email' => $email,
            'token' => $otp,
        ]);

        //send token to email user
        ForgotPasswordJob::dispatch($email, $otp);
        return response()->json([
            'success' => true,
            'message' => 'Token successfully sent to ' . $email,
        ]);
    }

    /**
     * Check the token obtained from the email.
     */
    public function checkOTP(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'numeric']
        ]);

        $otpInput = $request->otp;
        $otpSend = PasswordReset::where('otp', $otpInput)->first();

        if (!$otpSend) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP valid',
        ], 200);
    }

    /**
     * Forget Password Action.
     */
    public function forgetPassword(Request $request, $otp)
    {
        $request->validate([
            'password' => ['required'],
            'confirm_password' => ['required', 'same:password'],
        ]);

        $resetPassword = PasswordReset::where('otp', $otp)->first();
        $user = User::where('email', $resetPassword->email)->first();

        if ($user) {
            // Verify that the new password is not the same as the old one
            if (Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The new password cannot be the same as the old one.'
                ]);
            }

            // If the new password is valid, then update the password
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reset Password Success'
        ]);
    }

    /**
     * Profile.
     */
    public function profile(Request $request)
    {
        $user =  $request->user();
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
    public function logout(Request $request)
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json([
            'success' => true,
            'message' => 'Logout Successfully',
        ], 200);
    }

    /**
     * Delete Account.
     */
    public function delete(Request $request)
    {
        $user =  $request->user();
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
