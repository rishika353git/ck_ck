<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('forum_online_event_post', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->string('image')->nullable();
            $table->string('event_link')->nullable();
            $table->string('event_name')->nullable();
            $table->dateTime('event_date_time')->nullable();
            $table->text('description')->nullable();
            $table->string('hashtags')->nullable();
            $table->string('post_type');
            $table->string('speakers')->nullable();
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
        Schema::dropIfExists('forum_online_event_post');
    }
};
