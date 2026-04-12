<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChequeBooksTable extends Migration
{
    public function up()
    {
        Schema::create('cheque_books', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('bank_name');
            $table->string('account_title')->nullable();
            $table->string('account_number')->nullable();
            $table->string('book_no');
            $table->unsignedInteger('start_cheque_no');
            $table->unsignedInteger('end_cheque_no');
            $table->unsignedInteger('total_cheques');
            $table->unsignedInteger('remaining_cheques');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('company_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cheque_books');
    }
}
