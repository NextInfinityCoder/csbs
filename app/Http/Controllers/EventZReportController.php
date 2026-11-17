<?php

namespace App\Http\Controllers;

use App\Actions\GenerateEventZReport;
use App\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

class EventZReportController extends Controller
{
    /**
     * Display the printable Z-report of the event.
     */
    public function __invoke(Event $event, GenerateEventZReport $generateEventZReport): Response
    {
        return Inertia::render('print/event-z-report', [
            'event' => $event,
            'zReport' => $generateEventZReport->handle($event),
            'businessName' => config('app.name'),
            'generatedAt' => now()->toIso8601String(),
        ]);
    }
}
