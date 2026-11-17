<?php

namespace App\Http\Controllers;

use App\Actions\GenerateDailyAnalysis;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The number of days the dashboard can analyse.
     *
     * @var list<int>
     */
    public const array RANGES = [1, 7, 14, 30, 90];

    public const int DEFAULT_RANGE = 1;

    /**
     * Display the daily analysis of bets, commission and payouts.
     */
    public function __invoke(Request $request, GenerateDailyAnalysis $generateDailyAnalysis): Response
    {
        $range = in_array($request->integer('range'), self::RANGES, true)
            ? $request->integer('range')
            : self::DEFAULT_RANGE;

        return Inertia::render('dashboard', [
            'range' => $range,
            'ranges' => self::RANGES,
            'days' => $generateDailyAnalysis->handle($range),
        ]);
    }
}
