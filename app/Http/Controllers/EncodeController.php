<?php

namespace App\Http\Controllers;

use App\Actions\EncodeFight;
use App\EventStatus;
use App\Http\Requests\EncodeFightRequest;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EncodeController extends Controller
{
    /**
     * Display the ongoing events, which are the only ones that can be encoded.
     */
    public function index(): Response
    {
        return Inertia::render('encode/index', [
            'events' => Event::query()
                ->where('status', EventStatus::Ongoing)
                ->withCount('fights')
                ->latest('date')
                ->latest('id')
                ->paginate(10)
                ->onEachSide(1),
        ]);
    }

    /**
     * Display the encoding form and the encoded fights of the event.
     */
    public function show(Event $event): Response
    {
        $this->ensureEventIsOngoing($event);

        $fights = $event->fights()
            ->withCount('bets')
            ->orderByDesc('fight_number')
            ->get();

        return Inertia::render('encode/show', [
            'event' => $event,
            'fights' => $fights,
            'nextFightNumber' => (int) $fights->max('fight_number') + 1,
            'defaultTimeOpen' => $fights->first()?->bet_closed?->format('H:i'),
            'today' => now()->toDateString(),
            'hasCashiers' => User::query()->cashiers()->exists(),
        ]);
    }

    /**
     * Encode a fight and generate its bets.
     */
    public function store(EncodeFightRequest $request, Event $event, EncodeFight $encodeFight): RedirectResponse
    {
        $fight = $encodeFight->handle($event, $request->validated(), User::query()->cashiers()->get());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Fight #:number encoded with :count bets.', [
                'number' => $fight->fight_number,
                'count' => $fight->bets()->count(),
            ]),
        ]);

        return to_route('encode.show', $event);
    }

    /**
     * Only ongoing events can be encoded.
     */
    private function ensureEventIsOngoing(Event $event): void
    {
        abort_unless($event->status === EventStatus::Ongoing, 403, __('Only ongoing events can be encoded.'));
    }
}
