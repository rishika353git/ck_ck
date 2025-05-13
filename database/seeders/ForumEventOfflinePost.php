<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class ForumEventOfflinePost extends Seeder
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
                'image' => 'Image' . $i . '.jpg',
                'event_link' => 'https://event' . $i . '.com',
                'event_name' => 'Event Name ' . $i,
                'event_date_time' => now()->addDays(rand(1, 30)),
                'speakers' => 'Speaker ' . $i,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('forum_offline_event_post')->insert($data);
    
    }
}
