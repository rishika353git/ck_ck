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
        Schema::create('profile', function (Blueprint $table) {
           $table->id();
$table->integer('user_id')->nullable();
$table->integer('year_of_enrollment')->default(0);
$table->string('current_designation')->default('not set');
$table->string('previous_experiences')->default('not set');
$table->string('home_courts')->default('not set');
$table->string('area_of_practice')->default('not set');
$table->string('law_school')->default("");
$table->string('batch')->default("");
$table->string('linkedin_profile')->default('not set');
$table->text('description');
$table->string('profile_tagline')->default('not set');
$table->string('top_5_skills')->default('not set');
$table->integer('total_follow')->default(0);
$table->integer('total_followers')->default(0);
$table->integer('questions_asked')->default(0);
$table->integer('answers_given')->default(0);
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
        Schema::dropIfExists('profile');
    }
};
