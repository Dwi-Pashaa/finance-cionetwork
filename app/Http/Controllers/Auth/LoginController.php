<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    public function index() 
    {
        return view("auth.login");    
    }

    public function login(Request $request) 
    {
        $request->validate([
            "username" => "required|exists:users,username",
            "password" => "required"
        ]);    

        $user = User::where('username', $request->username)->first();

        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                Auth::login($user);
                return redirect()->route('dashboard');
            } else {
                return back()->with('warning', 'Username/Password salah.');
            }
        }
        
        return back()->with('error', 'Akun tidak terdaftar.');

    }

    public function logout() 
    {
        Auth::logout();
        Session::flush();
        
        return redirect()->route('login');
    }
}
