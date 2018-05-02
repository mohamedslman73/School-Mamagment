<?php

use App\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       
        $faker = \Faker\Factory::Create();
        for($i = 0;$i < 7; $i++){
        	User::Create([
        		'fname' => $faker->name,
        		'lname' => $faker->name,
        		'image' => 'avatar.jpg',
        		'phone' => '00966'.rand(111111111,999999999),
        		'email' => $faker->email,
        		'password' => '$2y$10$WeFJ6lIFKLT1Oc/k6joE0exIeP1aOni4ZudwZ6mPTabMlXYX3y6L2',
        		'role_id' => 2

        	]);
        }
    }
}
