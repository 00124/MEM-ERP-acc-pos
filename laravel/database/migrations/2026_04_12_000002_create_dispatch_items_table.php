<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dispatch_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->decimal('quantity', 18, 2)->default(0);
            $table->unsignedBigInteger('warehouse_id');
            $table->timestamps();

            $table->foreign('dispatch_id')->references('id')->on('dispatches')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_items');
    }
};
