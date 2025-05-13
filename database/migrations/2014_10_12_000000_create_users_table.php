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
    Schema::create('users', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('name');
    $table->string('email');
    $table->string('mobile');
    $table->string('password');
    $table->timestamp('mobile_verified_at')->nullable();
    $table->tinyInteger('mobile_verified')->default(0)->comment('0 = pending, 1 = verified');
    $table->tinyInteger('user_roll')->default(0)->comment('1 = student, 2 = advocate');
    $table->string('card_front')->default('');
    $table->string('card_back')->default('');
    $table->tinyInteger('card_verified')->default(0)->comment('0 = pending, 1 = approve, 2 = reject, 3 = blocked');
    $table->string('current_designation')->default('');
    $table->string('year_of_enrollment')->default('');
  //  $table->text('previous_experiences')->nullable();  // Removed default value
  $table->json('previous_experiences')->nullable();
   // $table->text('home_courts')->nullable();  // Removed default value
   $table->json('home_courts')->nullable();
   $table->json('area_of_practice')->nullable();
  //  $table->text('area_of_practice')->nullable();  // Removed default value
    $table->string('law_school')->default('');
    $table->string('batch')->default('');
    $table->string('linkedin_profile')->default('');
    $table->text('description')->default('');  // Removed default value
    $table->string('profile_tagline')->default('');
   // $table->text('top_5_skills')->nullable();  // Removed default value
    $table->json('top_5_skills')->nullable();
    $table->integer('total_follow')->default(0);
    $table->integer('total_followers')->default(0);
   // $table->json('questions_asked')->default(json_encode([])); 
    $table->integer('questions_asked')->default(0);
    $table->integer('answers_given')->default(0);
    $table->string('profile')->default('');
    $table->string('bannerImage')->default('');
    $table->string('reason')->default('');    
    $table->integer('following_id')->default(0);
    $table->integer('follower_id')->default(0);
    $table->string('fcm_token')->nullable();
    $table->timestamps();
    $table->rememberToken();
});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    Schema::dropIfExists('users');
        
}
    
};