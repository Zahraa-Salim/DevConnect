<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_are_returned_with_normalized_data(): void
    {
        $user = User::factory()->create();
        $user->workingStyle()->create([
            'communication_pref' => 'async',
            'timezone' => 'Asia/Beirut',
        ]);
        $createdAt = now();

        DB::table('notifications')->insert([
            [
                'id' => (string) Str::uuid(),
                'type' => 'demo.notification',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => 'Normal notification',
                    'message' => 'This payload is already valid.',
                    'url' => '/dashboard',
                ]),
                'source_type' => null,
                'source_id' => null,
                'read_at' => null,
                'created_at' => $createdAt->copy()->subMinute(),
                'updated_at' => $createdAt->copy()->subMinute(),
            ],
            [
                'id' => (string) Str::uuid(),
                'type' => 'demo.notification',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode(json_encode([
                    'title' => 'Double encoded notification',
                    'message' => 'This payload used to render blank.',
                    'url' => '/projects/1',
                ])),
                'source_type' => null,
                'source_id' => null,
                'read_at' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ],
        ]);

        $this->actingAs($user)
            ->getJson('/notifications')
            ->assertOk()
            ->assertJsonPath('0.data.title', 'Double encoded notification')
            ->assertJsonPath('0.data.message', 'This payload used to render blank.')
            ->assertJsonPath('0.data.url', '/projects/1')
            ->assertJsonPath('1.data.title', 'Normal notification');
    }

    public function test_notification_can_be_marked_unread(): void
    {
        $user = User::factory()->create();
        $user->workingStyle()->create([
            'communication_pref' => 'async',
            'timezone' => 'Asia/Beirut',
        ]);
        $notificationId = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'demo.notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => 'Already read',
                'message' => 'This notification should become unread.',
                'url' => '/dashboard',
            ]),
            'source_type' => null,
            'source_id' => null,
            'read_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson("/notifications/{$notificationId}/unread")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notificationId,
            'read_at' => null,
        ]);
    }
}
