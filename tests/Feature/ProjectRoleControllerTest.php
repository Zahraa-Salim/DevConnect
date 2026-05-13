<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\ProjectMember;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectRoleControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $otherUser;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->project = Project::factory()->create([
            'owner_id' => $this->owner->id,
            'status' => 'open',
        ]);

        // Add owner as member
        $this->project->members()->create([
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'status' => 'active',
            'access_level' => 3,
            'joined_at' => now(),
        ]);
    }

    public function test_owner_can_create_role()
    {
        $response = $this->actingAs($this->owner)
            ->post(route('projects.roles.store', $this->project->id), [
                'role_name' => 'Frontend Developer',
                'slots' => 3,
                'description' => 'Work on UI components',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_roles', [
            'project_id' => $this->project->id,
            'role_name' => 'Frontend Developer',
            'slots' => 3,
            'filled' => 0,
            'is_open' => true,
        ]);
    }

    public function test_non_owner_cannot_create_role()
    {
        $response = $this->actingAs($this->otherUser)
            ->post(route('projects.roles.store', $this->project->id), [
                'role_name' => 'Frontend Developer',
                'slots' => 3,
            ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_update_role()
    {
        $role = ProjectRole::factory()->create([
            'project_id' => $this->project->id,
            'role_name' => 'Backend Developer',
            'slots' => 2,
        ]);

        $response = $this->actingAs($this->owner)
            ->put(route('projects.roles.update', [$this->project->id, $role->id]), [
                'role_name' => 'Senior Backend Developer',
                'slots' => 3,
                'description' => 'Lead the backend team',
            ]);

        $response->assertRedirect();
        $role->refresh();
        $this->assertEquals('Senior Backend Developer', $role->role_name);
        $this->assertEquals(3, $role->slots);
        $this->assertEquals('Lead the backend team', $role->description);
    }

    public function test_non_owner_cannot_update_role()
    {
        $role = ProjectRole::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $response = $this->actingAs($this->otherUser)
            ->put(route('projects.roles.update', [$this->project->id, $role->id]), [
                'role_name' => 'Updated Name',
                'slots' => 5,
            ]);

        $response->assertStatus(403);
    }

    public function test_owner_cannot_delete_role_with_members()
    {
        $role = ProjectRole::factory()->create([
            'project_id' => $this->project->id,
            'role_name' => 'Developer',
            'filled' => 1,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete(route('projects.roles.destroy', [$this->project->id, $role->id]));

        $response->assertSessionHasErrors('message');
        $this->assertDatabaseHas('project_roles', ['id' => $role->id]);
    }

    public function test_owner_can_delete_empty_role()
    {
        $role = ProjectRole::factory()->create([
            'project_id' => $this->project->id,
            'role_name' => 'Designer',
            'filled' => 0,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete(route('projects.roles.destroy', [$this->project->id, $role->id]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('project_roles', ['id' => $role->id]);
    }

    public function test_cannot_delete_role_from_different_project()
    {
        $otherProject = Project::factory()->create([
            'owner_id' => $this->owner->id,
        ]);

        $role = ProjectRole::factory()->create([
            'project_id' => $otherProject->id,
            'filled' => 0,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete(route('projects.roles.destroy', [$this->project->id, $role->id]));

        $response->assertStatus(404);
    }
}
