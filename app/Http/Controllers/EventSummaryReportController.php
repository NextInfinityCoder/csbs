<?php

namespace App\Http\Controllers;

use App\Actions\GenerateEventSummary;
use App\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

class EventSummaryReportController extends Controller
{
    /**
     * Display the printable summary report of the event.
     */
    public function __invoke(Event $event, GenerateEventSummary $generateEventSummary): Response
    {
        return Inertia::render('print/event-summary', [
            'event' => $event,
            ...$generateEventSummary->handle($event),
            'businessName' => config('app.name'),
            'generatedAt' => now()->toIso8601String(),
        ]);
    }
}
