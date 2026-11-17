<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->unsignedBigInteger('station_sequence')->nullable()->after('station_id');
        });

        $this->numberExistingBets();

        Schema::table('bets', function (Blueprint $table) {
            $table->unique(['station_id', 'station_sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->dropUnique(['station_id', 'station_sequence']);
            $table->dropColumn('station_sequence');
        });
    }

    /**
     * Number the existing bets of every station in the order they were placed.
     */
    private function numberExistingBets(): void
    {
        $currentStationId = null;
        $sequence = 0;

        $bets = DB::table('bets')
            ->whereNotNull('station_id')
            ->orderBy('station_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->select(['id', 'station_id'])
            ->cursor();

        foreach ($bets as $bet) {
            if ($bet->station_id !== $currentStationId) {
                $currentStationId = $bet->station_id;
                $sequence = 0;
            }

            DB::table('bets')->where('id', $bet->id)->update(['station_sequence' => ++$sequence]);
        }
    }
};
