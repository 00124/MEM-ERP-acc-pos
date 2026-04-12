<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE chart_of_accounts MODIFY COLUMN account_type VARCHAR(20) NOT NULL DEFAULT ''");
    }

    public function down()
    {
        DB::statement("ALTER TABLE chart_of_accounts MODIFY COLUMN account_type ENUM('Asset','Liability','Equity','Income','Expense','COGS') NOT NULL DEFAULT ''");
    }
};
