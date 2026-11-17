<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Fight;
use Inertia\Inertia;
use Inertia\Response;

class FightController extends Controller
{
    /**
     * Display a paginated listing of the fights.
     */
    public function index(): Response
    {
        return Inertia::render('fights/index', [
            'fights' => Fight::query()
                ->with('event:id,name')
                ->orderByDesc(
                    Event::select('date')->whereColumn('events.id', 'fights.event_id')
                )
                ->orderByDesc('fight_number')
                ->paginate(10)
                ->onEachSide(1),
        ]);
    }
}
