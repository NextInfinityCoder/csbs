<?php

use App\Models\Station;
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
        Schema::table('users', function (Blueprint $table) {
            $table->string('type')->default('cashier')->after('email')->index();
            $table->foreignIdFor(Station::class)->nullable()->after('type')->constrained()->nullOnDelete();
        });

        DB::table('users')->update(['type' => 'admin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Station::class);
            $table->dropColumn('type');
        });
    }
};
