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
        Schema::create('forum_normal_post', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->text('description')->nullable();
            $table->text('files')->nullable();
            $table->string('hashtags')->nullable();
            $table->string('post_type');
            $table->integer('upvote')->default(0); // Set default value to 0
            $table->integer('downvote')->default(0); // Set default value to 0
            $table->integer('share')->default(0); // Set default value to 0
            $table->integer('repost')->default(0); // Set default value to 0
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
        Schema::dropIfExists('forum_normal_post');
    }
};
