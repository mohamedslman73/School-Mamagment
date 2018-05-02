<?php

use App\Post;
//use Faker\Factory;
use Illuminate\Database\Seeder;

class PostTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	//`user_id`, ``, `content`
        Post::truncate();
        $faker = \Faker\Factory::Create();
        for($i = 0;$i < 50; $i++){
        	Post::Create([
        		'user_id' => rand(2,3),
        		'post_id' => 0,
        		'content' => $faker->paragraph,

        	]);
        }
    }
}
