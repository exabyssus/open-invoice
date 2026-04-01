<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    public function start(User $user)
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);
        abort_if(session()->has('impersonator_id'), 403, 'Already impersonating.');

        session()->put('impersonator_id', Auth::id());

        Auth::login($user);

        return redirect('/panel');
    }

    public function stop()
    {
        $impersonatorId = session()->pull('impersonator_id');

        abort_unless($impersonatorId, 403);

        Auth::login(User::findOrFail($impersonatorId));

        return redirect('/panel');
    }
}
