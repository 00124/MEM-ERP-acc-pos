<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('dispatch_number', 50)->nullable();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->date('dispatch_date');
            $table->enum('status', ['pending', 'dispatched', 'delivered'])->default('pending');
            $table->string('driver_name', 100)->nullable();
            $table->string('vehicle_no', 50)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('sale_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatches');
    }
};
