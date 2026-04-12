<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('incentive_type', ['percentage', 'fixed', 'profit_based'])->nullable()->after('shift_id');
            $table->decimal('incentive_value', 15, 4)->nullable()->default(0)->after('incentive_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['incentive_type', 'incentive_value']);
        });
    }
};
