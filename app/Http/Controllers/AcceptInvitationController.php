<?php

namespace App\Http\Controllers;

use App\Models\CompanyInvitation;
use App\Models\User;
use Illuminate\Http\Request;

class AcceptInvitationController extends Controller
{
    public function __invoke(Request $request, string $token)
    {
        $invitation = CompanyInvitation::where('token', $token)->firstOrFail();

        if ($invitation->isAccepted()) {
            return redirect('/panel')->with('notification', 'This invitation has already been accepted.');
        }

        $existingUser = User::where('email', $invitation->email)->first();

        if ($existingUser) {
            // Existing user — accept immediately if logged in, otherwise redirect to login
            if (auth()->check() && auth()->id() === $existingUser->id) {
                $invitation->accept($existingUser);
                return redirect('/panel')->with('notification', "You have joined {$invitation->company->title}.");
            }

            // Log them in if they're logged in as someone else, or redirect to login
            if (auth()->check()) {
                auth()->logout();
            }

            redirect()->setIntendedUrl(route('invitation.accept', $token));
            return redirect('/panel/login');
        }

        // New user — show registration form
        return redirect("/panel/invitation/{$token}/register");
    }
}
