<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    //


    public function register(Request $request)
    {
        $fields =  $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('users', 'name')],
            'email' => ['required', 'string', 'email', 'max:255',, Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8']
        ]);
        $fields['password'] = bcrypt($fields['password']);
        $user = User::create($fields);
        auth()->login($user);
        return redirect('/');
    }
}
