<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('party_cheques', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('cheque_no');
            $table->unsignedBigInteger('party_id');
            $table->enum('party_type', ['customer', 'supplier']);
            $table->enum('type', ['received', 'issued']);
            $table->decimal('amount', 15, 2);
            $table->date('cheque_date');
            $table->string('bank_name')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->enum('status', ['in_hand', 'pending', 'deposited', 'cleared', 'bounced', 'returned'])->default('in_hand');
            $table->date('action_date')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->unique(['company_id', 'type', 'cheque_no'], 'unique_company_type_cheque');
        });
    }

    public function down()
    {
        Schema::dropIfExists('party_cheques');
    }
};
