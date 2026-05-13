<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $rami = User::where('email', 'rami@devconnect.lb')->first();

        DB::table('notifications')->insert([
            [
                'id'              => (string) Str::uuid(),
                'type'            => 'App\Notifications\RatingsReceivedNotification',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id'   => $rami->id,
                'data'            => json_encode([
                    'title'   => 'You received new ratings',
                    'message' => 'Your teammates rated you on DevTrack',
                    'url'     => '/profile',
                    'type'    => 'ratings_received',
                ]),
                'source_type' => null,
                'source_id'   => null,
                'read_at'     => null,
                'created_at'  => now()->subDays(5),
                'updated_at'  => now()->subDays(5),
            ],
            [
                'id'              => (string) Str::uuid(),
                'type'            => 'App\Notifications\RatingsReceivedNotification',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id'   => $rami->id,
                'data'            => json_encode([
                    'title'   => 'New mentor application',
                    'message' => 'Lara Khoury applied to be a mentor',
                    'url'     => '/admin/mentors',
                    'type'    => 'mentor_pending',
                ]),
                'source_type' => null,
                'source_id'   => null,
                'read_at'     => null,
                'created_at'  => now()->subDays(3),
                'updated_at'  => now()->subDays(3),
            ],
            [
                'id'              => (string) Str::uuid(),
                'type'            => 'App\Notifications\RatingsReceivedNotification',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id'   => $rami->id,
                'data'            => json_encode([
                    'title'   => 'Session booked',
                    'message' => 'A student booked your office hours',
                    'url'     => '/mentor/dashboard',
                    'type'    => 'booking_confirmed',
                ]),
                'source_type' => null,
                'source_id'   => null,
                'read_at'     => now(),
                'created_at'  => now()->subDay(),
                'updated_at'  => now()->subDay(),
            ],
        ]);
    }
}
