<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\ProjectMember;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectMembershipTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $member;
    private User $otherUser;
    private Project $project;
    private ProjectRole $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->member = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->project = Project::factory()->create([
            'owner_id' => $this->owner->id,
            'status' => 'active',
        ]);

        $this->role = ProjectRole::factory()->create([
            'project_id' => $this->project->id,
            'role_name' => 'Developer',
            'slots' => 3,
            'filled' => 1,
            'is_open' => true,
        ]);

        // Add owner as member
        $this->project->members()->create([
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'status' => 'active',
            'access_level' => 3,
            'joined_at' => now(),
        ]);

        // Add regular member
        $this->project->members()->create([
            'user_id' => $this->member->id,
            'role' => 'Developer',
            'status' => 'active',
            'access_level' => 1,
            'joined_at' => now(),
        ]);
    }

    public function test_member_can_leave_project()
    {
        $response = $this->actingAs($this->member)
            ->post(route('projects.leave', $this->project->id));

        $response->assertRedirect(route('projects.index'));

        $member = $this->project->members()->where('user_id', $this->member->id)->first();
        $this->assertEquals('left', $member->status);
        $this->assertNotNull($member->left_at);
    }

    public function test_role_reopens_when_member_leaves()
    {
        $this->role->update(['filled' => 3, 'is_open' => false]);

        $this->actingAs($this->member)
            ->post(route('projects.leave', $this->project->id));

        $this->role->refresh();
        $this->assertEquals(2, $this->role->filled);
        $this->assertTrue($this->role->is_open);
    }

    public function test_owner_cannot_leave_project()
    {
        $response = $this->actingAs($this->owner)
            ->post(route('projects.leave', $this->project->id));

        $response->assertStatus(403);
    }

    public function test_owner_can_remove_member()
    {
        $memberRecord = $this->project->members()
            ->where('user_id', $this->member->id)
            ->first();

        $response = $this->actingAs($this->owner)
            ->delete(route('projects.members.remove', [$this->project->id, $memberRecord->id]));

        $response->assertRedirect();

        $memberRecord->refresh();
        $this->assertEquals('removed', $memberRecord->status);
        $this->assertNotNull($memberRecord->left_at);
    }

    public function test_role_reopens_when_member_is_removed()
    {
        $this->role->update(['filled' => 3, 'is_open' => false]);

        $memberRecord = $this->project->members()
            ->where('user_id', $this->member->id)
            ->first();

        $this->actingAs($this->owner)
            ->delete(route('projects.members.remove', [$this->project->id, $memberRecord->id]));

        $this->role->refresh();
        $this->assertEquals(2, $this->role->filled);
        $this->assertTrue($this->role->is_open);
    }

    public function test_non_owner_cannot_remove_member()
    {
        $memberRecord = $this->project->members()
            ->where('user_id', $this->member->id)
            ->first();

        $response = $this->actingAs($this->otherUser)
            ->delete(route('projects.members.remove', [$this->project->id, $memberRecord->id]));

        $response->assertStatus(403);
    }

    public function test_owner_cannot_remove_themselves()
    {
        $ownerRecord = $this->project->members()
            ->where('user_id', $this->owner->id)
            ->first();

        $response = $this->actingAs($this->owner)
            ->delete(route('projects.members.remove', [$this->project->id, $ownerRecord->id]));

        $response->assertStatus(400);
    }

    public function test_undecided_member_leave_does_not_decrement_filled()
    {
        // Create an undecided member
        $undecidedUser = User::factory()->create();
        $this->project->members()->create([
            'user_id' => $undecidedUser->id,
            'role' => 'undecided',
            'status' => 'active',
            'access_level' => 1,
            'joined_at' => now(),
        ]);

        $initialFilled = $this->role->filled;

        $this->actingAs($undecidedUser)
            ->post(route('projects.leave', $this->project->id));

        $this->role->refresh();
        $this->assertEquals($initialFilled, $this->role->filled);
    }
}
