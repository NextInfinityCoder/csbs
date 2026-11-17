<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeRedirectController extends Controller
{
    /**
     * Send the user to the start page for their user type; guests are sent to the login page by the auth middleware.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        return match (true) {
            $request->user()->isAdmin() => to_route('dashboard'),
            $request->user()->isEncoder() => to_route('encode.index'),
            default => to_route('events.index'),
        };
    }
}
