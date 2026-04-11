<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWarrantyColumnsToStockAdjustments extends Migration
{
    public function up()
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->string('warranty_type', 30)->nullable()->default(null)->after('adjustment_type');
            // damage | expired | claimable | return_to_vendor | replacement
            $table->string('status', 20)->default('pending')->after('warranty_type');
            // pending | approved | completed
            $table->text('remarks')->nullable()->default(null)->after('notes');
        });
    }

    public function down()
    {
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropColumn(['warranty_type', 'status', 'remarks']);
        });
    }
}
