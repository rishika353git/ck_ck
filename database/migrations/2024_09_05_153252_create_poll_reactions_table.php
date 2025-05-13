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
        Schema::create('poll_reactions', function (Blueprint $table) {
            $table->id();
            // Use unsignedBigInteger for poll_id and user_id
            $table->unsignedBigInteger('poll_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->tinyInteger('status')->nullable()->default(0)->comment('0 = remove, 1 = upvote, 2 = downvote');

            $table->timestamps();

            // Adding foreign key constraints for relationships
            $table->foreign('poll_id')->references('id')->on('choices')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('poll_reactions');
    }
};
