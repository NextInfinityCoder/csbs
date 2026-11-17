<?php

use App\Models\Event;
use App\Models\Fight;
use App\Models\Station;
use App\Models\User;
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
        Schema::create('bets', function (Blueprint $table) {
            $table->id();
            $table->char('barcode', 12)->unique();
            $table->foreignIdFor(Event::class)->constrained();
            $table->foreignIdFor(Fight::class)->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(Station::class)->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('bet_side');
            $table->decimal('claim_amount', 15, 2)->nullable();
            $table->foreignId('claim_from_user_id')->nullable()->constrained('users');
            $table->dateTime('claimed_at')->nullable();
            $table->timestamps();

            $table->index(['fight_id', 'bet_side']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};
