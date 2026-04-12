<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_incentives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');         // employee
            $table->unsignedBigInteger('order_id');        // sale that triggered this
            $table->decimal('amount', 15, 4)->default(0);
            $table->enum('type', ['percentage', 'fixed', 'profit_based']);
            $table->date('date');
            $table->timestamps();

            $table->index(['company_id', 'user_id']);
            $table->index(['company_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrm_incentives');
    }
};
