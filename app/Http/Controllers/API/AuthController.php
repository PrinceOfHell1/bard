<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\PasswordReset;
use App\Mail\VerifyMail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Login.
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            $user = Auth::user();

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
                    'token' => $user->createToken('myApp')->plainTextToken,
                    'name' => $user->name
                ]
            ]);
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

        Mail::to($request->email)->send(new VerifyMail($user));
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
        }

        //display if account not found
        return response()->json([
            'success' => false,
            'message' => 'Account not found'
        ]);
    }

    /**
     * Profile.
     */
    public function profile()
    {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'data' => [
                'photo' => url('storage/'.$user->photo),
                'name' => $user->name,
                'email' => $user->email,
            ]
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

    public function forgetPassword(Request $request)
    {
        try{

            $user = User::where('email', $request->email)->get();
             if(count($user) > 0 ){
                $token = Str::random(50);
                $domain = URL::to('/');
                $url = $domain.'/reset-password?token='.$token;

                $data['url'] = $url;
                $data['email'] = $request->email;
                $data['title'] = "Password Reset";
                $data['body'] = "Please click the button below to reset your password";

                Mail::send('forgetPasswordMail',['data'=>$data],function($message) use ($data){
                    $message->to($data['email'])->subject($data['title']); 
                });
                $datetime = Carbon::now()->format('Y-m-d H:i:s');
                PasswordReset::updateOrCreate(
                    ['email' => $request->email],
                    [
                        'email' => $request->email,
                        'token' => $token,
                        'created_at' => $datetime
                    ]
                    );

                return response()->json(['success' => true, 'message' => 'Email sent successfully']);
             }
             else {
                return response()->json(['success' => false, 'message' => 'Email not found']);
             }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' =>$e->getMessage()]);
        }
    }

    public function resetPasswordLoad(Request $request)
    {
        $resetData = PasswordReset::where('token',$request->token)->get();
        if(isset($request->token) && count($resetData) > 0){
            $user = User::where('email',$resetData[0]['email'])->get();
            return view('resetPassword',compact('user'));
        }
        else{
            return view('404');
        }
    }

    public function resetPassword(Request $request){

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::find($request->id);
        $user->password = $request->password;
        $user->save();

        return "<h1>Password Reset Successfully</h1>";
    }
}
