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
        Schema::create('topicn_groups', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('');
            $table->string('description')->default('');
            $table->string('image')->default('');
            $table->integer('membersCount')->default(0);
            $table->string('bgColor')->default('');
            $table->integer('status')->default(0);
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
        Schema::dropIfExists('topicn_groups');
    }
};
