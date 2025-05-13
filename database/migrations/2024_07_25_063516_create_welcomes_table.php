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
        Schema::create('welcomes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('welcomes_card_id')->default(0);
            $table->string('title');
            $table->text('description');
            $table->string('file_image')->nullable();
          //  $table->json('card_details')->nullable();
           $table->string('welcomes_title')->nullable(); // New field for kudos_title
           $table->text('welcomes_description')->nullable(); // New field for kudos_description
           $table->string('welcomes_image')->nullable();
           $table->string('hashtags')->nullable();
           $table->string('post_type');
           $table->json('following_ids')->nullable();  // Storing the following IDs as JSON
           $table->timestamps();

            // Add foreign key constraint if needed
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
        Schema::dropIfExists('welcomes');
    }
};
