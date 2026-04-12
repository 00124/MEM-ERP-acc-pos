<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cheques', function (Blueprint $table) {
            $table->date('clear_date')->nullable()->after('issue_date');
            $table->string('bounce_reason')->nullable()->after('clear_date');
            $table->unsignedBigInteger('cleared_by')->nullable()->after('bounce_reason');
        });

        DB::statement("ALTER TABLE cheques MODIFY COLUMN status ENUM('unused','issued','cleared','bounced','cancelled') DEFAULT 'unused'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE cheques MODIFY COLUMN status ENUM('unused','issued','cancelled') DEFAULT 'unused'");

        Schema::table('cheques', function (Blueprint $table) {
            $table->dropColumn(['clear_date', 'bounce_reason', 'cleared_by']);
        });
    }
};
