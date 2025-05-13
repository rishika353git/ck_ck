<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFollowersTable extends Migration
{
    public function up()
    {
        Schema::create('followers', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('follower_id');
    $table->unsignedBigInteger('following_id');
    $table->foreign('follower_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('following_id')->references('id')->on('users')->onDelete('cascade');
    $table->timestamps();
});

    }

    public function down()
    {
        Schema::dropIfExists('followers');
    }
}
