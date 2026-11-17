<?php

namespace App\Http\Controllers;

use App\Actions\GenerateEventSummary;
use App\Actions\GenerateEventZReport;
use App\Http\Requests\EventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * Display a paginated listing of the events.
     */
    public function index(): Response
    {
        return Inertia::render('events/index', [
            'events' => Event::query()
                ->latest('date')
                ->latest('id')
                ->paginate(10)
                ->onEachSide(1),
        ]);
    }

    /**
     * Display the event with its summary report.
     */
    public function show(
        Event $event,
        GenerateEventSummary $generateEventSummary,
        GenerateEventZReport $generateEventZReport,
    ): Response {
        return Inertia::render('events/show', [
            'event' => $event,
            ...$generateEventSummary->handle($event),
            'zReport' => $generateEventZReport->handle($event),
        ]);
    }

    /**
     * Store a newly created event.
     */
    public function store(EventRequest $request): RedirectResponse
    {
        Event::create($request->eventAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event created.')]);

        return to_route('events.index');
    }

    /**
     * Update the given event.
     */
    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $event->update($request->eventAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event updated.')]);

        return back();
    }
}
