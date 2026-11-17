<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CashierStatusController extends Controller
{
    /**
     * Activate or deactivate a cashier. Inactive cashiers are left out of encoding.
     */
    public function __invoke(Request $request, User $cashier): RedirectResponse
    {
        abort_unless($cashier->isCashier(), 404);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $cashier->update(['is_active' => $validated['is_active']]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $cashier->is_active
                ? __(':name is now active.', ['name' => $cashier->name])
                : __(':name is now inactive.', ['name' => $cashier->name]),
        ]);

        return back();
    }
}
