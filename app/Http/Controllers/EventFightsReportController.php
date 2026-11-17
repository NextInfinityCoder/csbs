<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

class EventFightsReportController extends Controller
{
    /**
     * Display the printable list of every fight in the event.
     */
    public function __invoke(Event $event): Response
    {
        return Inertia::render('print/event-fights', [
            'event' => $event,
            'fights' => $event->fights()->orderBy('fight_number')->get(),
            'businessName' => config('app.name'),
            'generatedAt' => now()->toIso8601String(),
        ]);
    }
}
