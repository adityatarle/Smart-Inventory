<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request, ActivityLogger $logger)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user->update($data);
        $logger->log('profile.update', 'Updated profile details', 'settings', $user);

        return back()->with('success', 'Profile updated.');
    }

    public function passwordForm()
    {
        return view('profile.password');
    }

    public function updatePassword(Request $request, ActivityLogger $logger)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        Auth::user()->update([
            'password' => $request->password,
        ]);

        $logger->log('profile.password', 'Changed account password', 'settings');

        return back()->with('success', 'Password changed successfully.');
    }
}
