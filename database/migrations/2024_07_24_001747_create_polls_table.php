<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePollsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('ask_a_question');
            $table->integer('pollsRespondCount')->default(0); 
            $table->integer('status');
            $table->integer('poll_duration');
            $table->string('hashtags')->nullable();
            $table->string('post_type')->default('polls');
            $table->integer('upvote')->default(0); // Set default value to 0
            $table->integer('downvote')->default(0); // Set default value to 0
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
        Schema::dropIfExists('polls');
    }
}
