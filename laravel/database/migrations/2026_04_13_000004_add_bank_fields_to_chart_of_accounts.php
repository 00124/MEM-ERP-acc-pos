<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->string('account_number')->nullable()->after('account_name');
            $table->string('branch_name')->nullable()->after('account_number');
            $table->decimal('opening_balance', 15, 2)->default(0)->after('branch_name');
        });
    }

    public function down()
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn(['account_number', 'branch_name', 'opening_balance']);
        });
    }
};
