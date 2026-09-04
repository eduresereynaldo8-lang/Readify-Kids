<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Badge;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            [
                'badge_name'  => 'First Recording',
                'description' => 'Submitted your very first Read Aloud recording!',
                'badge_icon'  => '🎙️',
                'criteria'    => 'first_recording',
            ],
            [
                'badge_name'  => 'Bookworm',
                'description' => 'Completed 5 reading activities.',
                'badge_icon'  => '📖',
                'criteria'    => 'activities_5',
            ],
            [
                'badge_name'  => 'Rising Star',
                'description' => 'Completed 10 reading activities.',
                'badge_icon'  => '🌟',
                'criteria'    => 'activities_10',
            ],
            [
                'badge_name'  => 'Battle Rookie',
                'description' => 'Won your very first Battle Arena game!',
                'badge_icon'  => '⚔️',
                'criteria'    => 'first_battle_win',
            ],
            [
                'badge_name'  => 'Champion',
                'description' => 'Won 5 Battle Arena games.',
                'badge_icon'  => '🏆',
                'criteria'    => 'battles_won_5',
            ],
            [
                'badge_name'  => 'On Fire',
                'description' => 'Scored 90% or higher on any activity.',
                'badge_icon'  => '🔥',
                'criteria'    => 'score_90',
            ],
            [
                'badge_name'  => 'Sharp Shooter',
                'description' => 'Scored a perfect 100% on any activity!',
                'badge_icon'  => '🎯',
                'criteria'    => 'score_100',
            ],
            [
                'badge_name'  => 'Point Collector',
                'description' => 'Earned 100 total points.',
                'badge_icon'  => '⭐',
                'criteria'    => 'points_100',
            ],
            [
                'badge_name'  => 'Point Master',
                'description' => 'Earned 500 total points.',
                'badge_icon'  => '💫',
                'criteria'    => 'points_500',
            ],
            [
                'badge_name'  => 'Never Give Up',
                'description' => 'Attempted the same activity 3 times.',
                'badge_icon'  => '💪',
                'criteria'    => 'reattempt_3',
            ],
            [
                'badge_name'  => 'Top Reader',
                'description' => 'Ranked #1 in your class leaderboard!',
                'badge_icon'  => '👑',
                'criteria'    => 'rank_1',
            ],
            [
                'badge_name'  => 'Phonics Pro',
                'description' => 'Completed 3 Phonics activities.',
                'badge_icon'  => '🔤',
                'criteria'    => 'phonics_3',
            ],
            [
                'badge_name'  => 'Word Wizard',
                'description' => 'Completed 3 Word Game activities.',
                'badge_icon'  => '🧩',
                'criteria'    => 'wordgame_3',
            ],
            [
                'badge_name'  => 'Speed Reader',
                'description' => 'Completed an activity in under 2 minutes.',
                'badge_icon'  => '⚡',
                'criteria'    => 'speed_reader',
            ],
            [
                'badge_name'  => 'Streak Master',
                'description' => 'Maintained a 5-day learning streak!',
                'badge_icon'  => '🗓️',
                'criteria'    => 'streak_5',
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(
                ['criteria' => $badge['criteria']],
                $badge
            );
        }

        $this->command->info('✅ ' . count($badges) . ' badges seeded successfully!');
    }
}