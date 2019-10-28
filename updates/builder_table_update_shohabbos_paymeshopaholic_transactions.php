<?php namespace Shohabbos\Paymeshopaholic\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateShohabbosPaymeshopaholicTransactions extends Migration
{
    public function up()
    {
        Schema::table('shohabbos_paymeshopaholic_transactions', function($table)
        {
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('shohabbos_paymeshopaholic_transactions', function($table)
        {
            $table->dropColumn('deleted_at');
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');
        });
    }
}