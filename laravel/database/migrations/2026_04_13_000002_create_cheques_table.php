<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChequesTable extends Migration
{
    public function up()
    {
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('cheque_book_id');
            $table->unsignedInteger('cheque_no');
            $table->enum('status', ['unused', 'issued', 'cancelled'])->default('unused');
            $table->string('payee')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->date('issue_date')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'cheque_book_id']);
            $table->unique(['company_id', 'cheque_book_id', 'cheque_no']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cheques');
    }
}
