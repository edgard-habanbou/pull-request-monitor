<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    //
    public function login(Request $request)
    {
        $fields = $request->validate([
            'loginEmail' => ['required', 'string', 'email'],
            'loginPassword' => ['required', 'string']
        ]);

        if (!auth()->attempt(['email' => $fields['loginEmail'], 'password' => $fields['loginPassword']])) {
            return back()->with('error', 'Invalid credentials');
        }
        return redirect('/');
    }

    public function register(Request $request)
    {
        $fields =  $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('users', 'name')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8']
        ]);
        $fields['password'] = bcrypt($fields['password']);
        $user = User::create($fields);
        auth()->login($user);
        return redirect('/');
    }

    public function logout()
    {
        auth()->logout();
        return redirect('/');
    }
}
