<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashTransfersTable extends Migration
{
    public function up()
    {
        Schema::create('cash_transfers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned()->nullable()->default(null);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('from_warehouse_id')->unsigned()->nullable();
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->onDelete('set null')->onUpdate('cascade');
            $table->bigInteger('to_warehouse_id')->unsigned()->nullable();
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->onDelete('set null')->onUpdate('cascade');
            $table->decimal('amount', 15, 2)->default(0);
            $table->enum('transfer_type', ['ho_to_branch', 'branch_to_ho', 'branch_to_branch'])->default('ho_to_branch');
            $table->string('reference_number', 50)->nullable();
            $table->date('transfer_date');
            $table->text('notes')->nullable();
            $table->bigInteger('transferred_by')->unsigned()->nullable();
            $table->foreign('transferred_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cash_transfers');
    }
}
