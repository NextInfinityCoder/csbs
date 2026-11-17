<?php

use App\Models\Event;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fights', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Event::class)->constrained();
            $table->unsignedInteger('fight_number');
            $table->decimal('meron_amount', 15, 2)->default(0);
            $table->decimal('wala_amount', 15, 2)->default(0);
            $table->decimal('draw_amount', 15, 2)->default(0);
            $table->decimal('meron_odds', 8, 2)->default(0);
            $table->decimal('wala_odds', 8, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->decimal('commission', 15, 2)->default(0);
            $table->string('result')->default('pending')->index();
            $table->dateTime('bet_open')->nullable();
            $table->dateTime('bet_closed')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'fight_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fights');
    }
};
