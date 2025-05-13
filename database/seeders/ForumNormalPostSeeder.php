<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ForumNormalPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [];
        for ($i = 1; $i <= 5000; $i++) {
            $data[] = [
                'user_id' => rand(1, 100),
                'description' => 'Description ' . $i,
                'files' => 'File ' . $i,
                'hashtags' => '#hashtag' . $i,
                'upvote' => 0,
                'downvote' => 0,
                'share' => 0,
                'repost' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('forum_normal_post')->insert($data);
        
    
    }
    
}
