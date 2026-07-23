<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Enemy;

class EnemySeeder extends Seeder
{
    public function run(): void
    {
        Enemy::insert([
            [
                'name'        => 'Letter Goblin',
                'sprite'      => '👺',
                'max_hp'      => 500,
                'level'       => 1,
                'description' => 'A sneaky goblin who scrambles letters!',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Word Witch',
                'sprite'      => '🧙‍♀️',
                'max_hp'      => 750,
                'level'       => 2,
                'description' => 'A witch who casts word-confusion spells!',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Story Dragon',
                'sprite'      => '🐉',
                'max_hp'      => 1000,
                'level'       => 3,
                'description' => 'A fearsome dragon who swallows whole stories!',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }
}