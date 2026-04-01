<?php

namespace App\Http\Controllers;

use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AcceptInvitationRegisterController extends Controller
{
    public function show(string $token)
    {
        $invitation = CompanyInvitation::where('token', $token)->firstOrFail();

        if ($invitation->isAccepted()) {
            return redirect('/panel')->with('notification', 'This invitation has already been accepted.');
        }

        if (User::where('email', $invitation->email)->exists()) {
            return redirect("/panel/invitation/{$token}/accept");
        }

        return view('auth.accept-invitation', [
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }

    public function store(Request $request, string $token)
    {
        $invitation = CompanyInvitation::where('token', $token)->firstOrFail();

        if ($invitation->isAccepted()) {
            return redirect('/panel');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $invitation->email,
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $invitation->accept($user);

        Auth::login($user);

        return redirect('/panel');
    }
}
