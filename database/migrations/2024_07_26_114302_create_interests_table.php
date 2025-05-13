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
        Schema::create('interests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('icon');
            $table->string('color')->nullable();
            $table->timestamps();
        });

        // Insert default records
        DB::table('interests')->insert([
            ['id' => 1, 'title' => 'Criminal', 'icon' => asset("storage/interestsImages/police.png"), 'color' => 'FFE1C9', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'title' => 'Corporate', 'icon' => asset("storage/interestsImages/suitcase-solid.png"), 'color' => 'FFF6DD', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'title' => 'Civil Rights', 'icon' => asset("storage/interestsImages/civil-rights.png") , 'color' => 'F8DDDD', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'title' => 'Family', 'icon' =>  asset("storage/interestsImages/family.png"), 'color' => 'FDEEEE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'title' => 'Tax Law', 'icon' =>  asset("storage/interestsImages/tax.png"), 'color' => 'EAFFFB', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'title' => 'Solicitor', 'icon' =>  asset("storage/interestsImages/female.png"), 'color' => 'E3E6FF', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'title' => 'Medical', 'icon' => asset("storage/interestsImages/cardiogram.png"), 'color' => 'FDE9FF', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'title' => 'Environment', 'icon' => asset("storage/interestsImages/agriculture.png"), 'color' => 'DDFFE0', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'title' => 'Admiralty', 'icon' => asset("storage/interestsImages/auction.png"), 'color' => 'E1F4FE', 'created_at' => now(), 'updated_at' => now()],
        ]);
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('interests');
    }
};
