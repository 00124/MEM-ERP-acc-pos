<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCashTransferColumnsToCashRegisters extends Migration
{
    public function up()
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->decimal('total_cash_in', 15, 2)->default(0)->after('total_expense');
            $table->decimal('total_cash_out', 15, 2)->default(0)->after('total_cash_in');
        });
    }

    public function down()
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropColumn(['total_cash_in', 'total_cash_out']);
        });
    }
}
