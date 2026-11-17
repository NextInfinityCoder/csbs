<?php

namespace App\Http\Controllers;

use App\Models\Bet;
use Inertia\Inertia;
use Inertia\Response;

class BetController extends Controller
{
    /**
     * Display a paginated listing of the bets.
     */
    public function index(): Response
    {
        return Inertia::render('bets/index', [
            'bets' => Bet::query()
                ->with([
                    'event:id,name',
                    'fight:id,fight_number',
                    'user:id,name',
                    'station:id,name',
                    'claimedFrom:id,name',
                ])
                ->latest()
                ->latest('id')
                ->paginate(10)
                ->onEachSide(1),
        ]);
    }
}
