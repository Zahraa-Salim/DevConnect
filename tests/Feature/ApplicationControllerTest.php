<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\Application;
use App\Models\ProjectMember;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApplicationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $owner;
    private Project $project;
    private ProjectRole $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->owner = User::factory()->create();

        $this->project = Project::factory()->create([
            'owner_id' => $this->owner->id,
            'status' => 'open',
        ]);

        $this->role = ProjectRole::factory()->create([
            'project_id' => $this->project->id,
            'role_name' => 'Backend Developer',
            'slots' => 2,
            'filled' => 0,
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
    }

    public function test_user_can_apply_to_project_with_role()
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.apply', $this->project->id), [
                'role_id' => $this->role->id,
                'message' => 'I am interested in this role',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('applications', [
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'role_id' => $this->role->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_apply_as_undecided()
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.apply', $this->project->id), [
                'role_id' => null,
                'message' => 'Interested in joining',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('applications', [
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'role_id' => null,
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_apply_if_already_member()
    {
        $this->project->members()->create([
            'user_id' => $this->user->id,
            'role' => 'Backend Developer',
            'status' => 'active',
            'access_level' => 1,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('projects.apply', $this->project->id), [
                'role_id' => $this->role->id,
            ]);

        $response->assertSessionHasErrors('message');
    }

    public function test_user_cannot_apply_twice()
    {
        $this->actingAs($this->user)
            ->post(route('projects.apply', $this->project->id), [
                'role_id' => $this->role->id,
            ]);

        $response = $this->actingAs($this->user)
            ->post(route('projects.apply', $this->project->id), [
                'role_id' => $this->role->id,
            ]);

        $response->assertSessionHasErrors('message');
    }

    public function test_user_cannot_apply_to_closed_project()
    {
        $this->project->update(['status' => 'completed']);

        $response = $this->actingAs($this->user)
            ->post(route('projects.apply', $this->project->id), [
                'role_id' => $this->role->id,
            ]);

        $response->assertSessionHasErrors('message');
    }

    public function test_user_cannot_apply_to_full_role()
    {
        $this->role->update([
            'filled' => 2,
            'is_open' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('projects.apply', $this->project->id), [
                'role_id' => $this->role->id,
            ]);

        $response->assertSessionHasErrors('message');
    }

    public function test_owner_can_accept_application()
    {
        $application = Application::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'role_id' => $this->role->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->owner)
            ->patch(route('projects.applications.accept', [$this->project->id, $application->id]));

        $response->assertRedirect();
        $application->refresh();
        $this->assertEquals('accepted', $application->status);
        $this->assertNotNull($application->reviewed_at);

        // Verify member was created
        $this->assertDatabaseHas('project_members', [
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'role' => 'Backend Developer',
            'status' => 'active',
        ]);

        // Verify role filled count incremented
        $this->role->refresh();
        $this->assertEquals(1, $this->role->filled);
    }

    public function test_role_closes_when_full()
    {
        $application = Application::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'role_id' => $this->role->id,
            'status' => 'pending',
        ]);

        // Fill the second slot
        $user2 = User::factory()->create();
        $application2 = Application::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $user2->id,
            'role_id' => $this->role->id,
            'status' => 'pending',
        ]);

        // Accept first application
        $this->actingAs($this->owner)
            ->patch(route('projects.applications.accept', [$this->project->id, $application->id]));

        $this->role->refresh();
        $this->assertEquals(1, $this->role->filled);
        $this->assertTrue($this->role->is_open);

        // Accept second application
        $this->actingAs($this->owner)
            ->patch(route('projects.applications.accept', [$this->project->id, $application2->id]));

        $this->role->refresh();
        $this->assertEquals(2, $this->role->filled);
        $this->assertFalse($this->role->is_open);
    }

    public function test_owner_can_decline_application()
    {
        $application = Application::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'role_id' => $this->role->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->owner)
            ->patch(route('projects.applications.decline', [$this->project->id, $application->id]));

        $response->assertRedirect();
        $application->refresh();
        $this->assertEquals('declined', $application->status);
    }

    public function test_applicant_can_withdraw_application()
    {
        $application = Application::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'role_id' => $this->role->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('projects.applications.withdraw', [$this->project->id, $application->id]));

        $response->assertRedirect();
        $application->refresh();
        $this->assertEquals('withdrawn', $application->status);
    }

    public function test_non_owner_cannot_accept_application()
    {
        $application = Application::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'role_id' => $this->role->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('projects.applications.accept', [$this->project->id, $application->id]));

        $response->assertStatus(403);
    }
}
