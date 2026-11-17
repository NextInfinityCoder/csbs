<?php

namespace App\Http\Controllers;

use App\Http\Requests\StationRequest;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StationController extends Controller
{
    /**
     * Display a paginated listing of the stations.
     */
    public function index(): Response
    {
        return Inertia::render('stations/index', [
            'stations' => Station::query()
                ->withCount('cashiers')
                ->orderBy('name')
                ->paginate(10)
                ->onEachSide(1),
        ]);
    }

    /**
     * Store a newly created station.
     */
    public function store(StationRequest $request): RedirectResponse
    {
        Station::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Station created.')]);

        return back();
    }

    /**
     * Update the given station.
     */
    public function update(StationRequest $request, Station $station): RedirectResponse
    {
        $station->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Station updated.')]);

        return back();
    }
}
