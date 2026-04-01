<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonateController extends Controller
{
    public function start(User $user)
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);
        abort_if(Session::has('impersonator_id'), 403, 'Already impersonating.');

        $impersonatorId = Auth::id();

        Auth::guard('web')->login($user);

        // Re-set after login (login regenerates session)
        Session::put('impersonator_id', $impersonatorId);

        return redirect('/panel');
    }

    public function stop()
    {
        $impersonatorId = Session::pull('impersonator_id');

        abort_unless($impersonatorId, 403);

        Auth::guard('web')->login(User::findOrFail($impersonatorId));

        return redirect('/panel');
    }
}
