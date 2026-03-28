<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_mark_a_single_notification_as_read(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('notifications')) {
            $this->markTestSkipped('notifications table not migrated.');
        }

        /** @var User $user */
        $user = User::factory()->create();

        $notificationId = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'Illuminate\Notifications\DatabaseNotification',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Test', 'message' => 'Hello', 'url' => '/rooms']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('notifications.read', ['id' => $notificationId]));

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertNotNull(
            DB::table('notifications')->where('id', $notificationId)->value('read_at')
        );
    }
}
