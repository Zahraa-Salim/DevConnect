<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\Application;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * End-to-end integration test for Phase 3.2 workflow:
 * Owner creates project with roles → User applies → Owner accepts → Member joins → User leaves
 */
class Phase3_2_IntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_project_application_flow()
    {
        // Setup: Create project owner and applicant
        $owner = User::factory()->create(['name' => 'Alice Owner']);
        $applicant = User::factory()->create(['name' => 'Bob Applicant']);

        // Step 1: Owner creates project
        $createResponse = $this->actingAs($owner)->post(route('projects.store'), [
            'title' => 'Mobile App Development',
            'description' => 'Build an iOS app for a startup',
            'type' => 'real_client',
            'domain' => 'Mobile',
            'tech_stack' => ['Swift', 'Firebase'],
            'max_members' => 5,
            'estimated_duration' => '3 months',
            'roles' => [
                [
                    'role_name' => 'iOS Developer',
                    'slots' => 2,
                    'description' => 'Lead iOS development',
                ],
                [
                    'role_name' => 'Backend Developer',
                    'slots' => 1,
                    'description' => 'API development',
                ],
            ],
        ]);

        $createResponse->assertRedirect();
        $project = Project::where('title', 'Mobile App Development')->first();
        $this->assertNotNull($project);
        $this->assertTrue($project->members()->where('user_id', $owner->id)->exists());

        // Verify roles were created
        $this->assertEquals(2, $project->roles()->count());
        $iosRole = $project->roles()->where('role_name', 'iOS Developer')->first();
        $backendRole = $project->roles()->where('role_name', 'Backend Developer')->first();
        $this->assertEquals(2, $iosRole->slots);
        $this->assertEquals(1, $backendRole->slots);

        // Step 2: Applicant applies for iOS Developer role
        $applyResponse = $this->actingAs($applicant)
            ->post(route('projects.apply', $project->id), [
                'role_id' => $iosRole->id,
                'message' => 'I have 5 years of iOS experience',
            ]);

        $applyResponse->assertRedirect();
        $application = $project->applications()
            ->where('user_id', $applicant->id)
            ->first();
        $this->assertNotNull($application);
        $this->assertEquals('pending', $application->status);

        // Step 3: Verify applicant can see their application
        $showResponse = $this->actingAs($applicant)->get(route('projects.show', $project->id));
        $showResponse->assertOk();

        // Step 4: Owner can see pending applications
        $showResponse = $this->actingAs($owner)->get(route('projects.show', $project->id));
        $showResponse->assertOk();

        // Step 5: Owner accepts the application
        $acceptResponse = $this->actingAs($owner)
            ->patch(route('projects.applications.accept', [$project->id, $application->id]));

        $acceptResponse->assertRedirect();

        // Verify application is now accepted
        $application->refresh();
        $this->assertEquals('accepted', $application->status);

        // Verify member was added
        $this->assertTrue($project->members()->where('user_id', $applicant->id)->exists());
        $member = $project->members()->where('user_id', $applicant->id)->first();
        $this->assertEquals('iOS Developer', $member->role);
        $this->assertEquals('active', $member->status);

        // Verify role filled count incremented
        $iosRole->refresh();
        $this->assertEquals(1, $iosRole->filled);
        $this->assertTrue($iosRole->is_open);

        // Step 6: Add another user and fill the iOS role
        $applicant2 = User::factory()->create(['name' => 'Charlie Developer']);
        $application2 = Application::factory()->create([
            'project_id' => $project->id,
            'user_id' => $applicant2->id,
            'role_id' => $iosRole->id,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patch(route('projects.applications.accept', [$project->id, $application2->id]));

        $iosRole->refresh();
        $this->assertEquals(2, $iosRole->filled);
        $this->assertFalse($iosRole->is_open); // Should be closed now

        // Step 7: Original applicant leaves the project
        $member->refresh();
        $leaveResponse = $this->actingAs($applicant)
            ->post(route('projects.leave', $project->id));

        $leaveResponse->assertRedirect(route('projects.index'));

        // Verify member status changed
        $member->refresh();
        $this->assertEquals('left', $member->status);

        // Verify role reopened and filled decremented
        $iosRole->refresh();
        $this->assertEquals(1, $iosRole->filled);
        $this->assertTrue($iosRole->is_open);

        // Step 8: New user can now apply to reopened role
        $applicant3 = User::factory()->create(['name' => 'Diana Mobile']);
        $applyResponse2 = $this->actingAs($applicant3)
            ->post(route('projects.apply', $project->id), [
                'role_id' => $iosRole->id,
            ]);

        $applyResponse2->assertRedirect();
        $this->assertTrue(
            $project->applications()
                ->where('user_id', $applicant3->id)
                ->where('status', 'pending')
                ->exists()
        );

        // Step 9: Owner can remove a member
        $removedMember = $project->members()
            ->where('user_id', $applicant2->id)
            ->first();

        $removeResponse = $this->actingAs($owner)
            ->delete(route('projects.members.remove', [$project->id, $removedMember->id]));

        $removeResponse->assertRedirect();

        $removedMember->refresh();
        $this->assertEquals('removed', $removedMember->status);

        // Verify role reopened again
        $iosRole->refresh();
        $this->assertEquals(0, $iosRole->filled);
        $this->assertTrue($iosRole->is_open);

        // Step 10: Owner can manage roles
        $updateResponse = $this->actingAs($owner)
            ->put(route('projects.roles.update', [$project->id, $backendRole->id]), [
                'role_name' => 'Senior Backend Engineer',
                'slots' => 2,
                'description' => 'Lead backend development and architecture',
            ]);

        $updateResponse->assertRedirect();
        $backendRole->refresh();
        $this->assertEquals('Senior Backend Engineer', $backendRole->role_name);
        $this->assertEquals(2, $backendRole->slots);
    }
}
