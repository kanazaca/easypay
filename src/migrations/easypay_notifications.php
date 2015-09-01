<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEasypayNotificationsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('easypay_notifications', function(Blueprint $table)
        {
            $table->increments('ep_key');
            $table->string('ep_doc', 50)->default(null)->unique()->nullable();
            $table->string('ep_cin', 20)->default(null)->nullable();
            $table->string('ep_user', 20)->default(null)->nullable();
            $table->string('ep_status', 20)->default(null)->nullable();
            $table->string('ep_entity', 10)->default(null)->nullable();
            $table->string('ep_reference', 9)->default(null)->nullable();
            $table->double('ep_value', 10, 2)->default(null)->nullable();
            $table->dateTime('ep_date')->default(null)->nullable();
            $table->string('ep_payment_type' , 10)->default(null)->nullable();
            $table->double('ep_value_fixed' , 10 ,2)->default(null)->nullable();
            $table->double('ep_value_var' , 10 ,2)->default(null)->nullable();
            $table->double('ep_value_tax' , 10 ,2)->default(null)->nullable();
            $table->date('ep_value_transf')->default(null)->nullable();
            $table->string('t_key' , 255)->default(null)->nullable();
            $table->timestamp('notification_date')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->string('ep_type' , 15)->default(null)->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('easypay_notifications');
    }

}