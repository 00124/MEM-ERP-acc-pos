<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddMacColumns extends Migration
{
    public function up()
    {
        // Add MAC cost columns to product_details
        Schema::table('product_details', function (Blueprint $table) {
            if (!Schema::hasColumn('product_details', 'current_avg_cost_invoice')) {
                $table->double('current_avg_cost_invoice')->default(0)->after('current_stock');
            }
            if (!Schema::hasColumn('product_details', 'current_avg_cost_net')) {
                $table->double('current_avg_cost_net')->default(0)->after('current_avg_cost_invoice');
            }
        });

        // Add net_cost_rate (purchase input) and cost_invoice / cost_net (sale snapshot) to order_items
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'net_cost_rate')) {
                $table->double('net_cost_rate')->nullable()->default(null)->after('single_unit_price');
            }
            if (!Schema::hasColumn('order_items', 'cost_invoice')) {
                $table->double('cost_invoice')->nullable()->default(null)->after('subtotal');
            }
            if (!Schema::hasColumn('order_items', 'cost_net')) {
                $table->double('cost_net')->nullable()->default(null)->after('cost_invoice');
            }
        });

        // Seed existing product_details: set MAC costs equal to purchase_price
        DB::statement('
            UPDATE product_details
            SET current_avg_cost_invoice = COALESCE(purchase_price, 0),
                current_avg_cost_net     = COALESCE(purchase_price, 0)
            WHERE current_avg_cost_invoice = 0
        ');

        // Seed net_cost_rate on existing purchase order_items from single_unit_price
        DB::statement("
            UPDATE order_items oi
            JOIN orders o ON o.id = oi.order_id
            SET oi.net_cost_rate = COALESCE(oi.single_unit_price, oi.unit_price, 0)
            WHERE o.order_type = 'purchases'
              AND oi.net_cost_rate IS NULL
        ");
    }

    public function down()
    {
        Schema::table('product_details', function (Blueprint $table) {
            $table->dropColumn(['current_avg_cost_invoice', 'current_avg_cost_net']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['net_cost_rate', 'cost_invoice', 'cost_net']);
        });
    }
}
