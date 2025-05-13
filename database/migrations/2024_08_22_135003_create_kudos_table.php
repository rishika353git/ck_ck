<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKudosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kudos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');  // Add user_id field
            $table->unsignedBigInteger('kudos_card_id')->nullable(); // New field for kudos_card_id
            $table->string('title');
            $table->text('description');
            $table->string('file_image')->nullable();
           // $table->json('kudos_details')->nullable();  // Storing the details of kudos_id
            $table->string('kudos_title')->nullable(); // New field for kudos_title
            $table->text('kudos_description')->nullable(); // New field for kudos_description
            $table->string('kudos_image')->nullable();
            $table->string('hashtags')->nullable();
            $table->string('post_type'); 
            $table->json('following_ids')->nullable();// Storing the following IDs as JSON
            $table->timestamps();

            // Optional: Add foreign key constraint if you have a users table
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
        Schema::dropIfExists('kudos');
    }
}
