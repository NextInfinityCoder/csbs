<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashierRequest;
use App\Models\Station;
use App\Models\User;
use App\UserType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CashierController extends Controller
{
    /**
     * Display a paginated listing of the cashiers.
     */
    public function index(): Response
    {
        return Inertia::render('cashiers/index', [
            'cashiers' => User::query()
                ->cashiers()
                ->with('station:id,name')
                ->orderBy('name')
                ->paginate(10)
                ->onEachSide(1),
            'stations' => Station::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created cashier.
     */
    public function store(CashierRequest $request): RedirectResponse
    {
        User::create([
            ...$request->safe()->only(['name', 'email', 'password', 'station_id']),
            'type' => UserType::Cashier,
        ])->markEmailAsVerified();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cashier created.')]);

        return back();
    }

    /**
     * Update the given cashier.
     */
    public function update(CashierRequest $request, User $cashier): RedirectResponse
    {
        abort_unless($cashier->isCashier(), 404);

        $cashier->fill($request->safe()->only(['name', 'email', 'station_id']));

        if ($request->filled('password')) {
            $cashier->password = $request->validated('password');
        }

        $cashier->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cashier updated.')]);

        return back();
    }
}
