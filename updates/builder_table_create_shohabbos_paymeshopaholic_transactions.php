<?php namespace Shohabbos\Paymeshopaholic\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateShohabbosPaymeshopaholicTransactions extends Migration
{
    public function up()
    {
        Schema::create('shohabbos_paymeshopaholic_transactions', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('transaction')->nullable();
            $table->string('code')->nullable();
            $table->smallInteger('state')->nullable();
            $table->string('owner_id')->nullable();
            $table->string('amount')->nullable();
            $table->string('reason')->nullable();
            $table->bigInteger('payme_time')->nullable();
            $table->bigInteger('cancel_time')->nullable();
            $table->bigInteger('create_time')->nullable();
            $table->bigInteger('perform_time')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('shohabbos_paymeshopaholic_transactions');
    }
}
