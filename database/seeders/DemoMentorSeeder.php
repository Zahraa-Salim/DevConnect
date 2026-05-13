<?php

namespace Database\Seeders;

use App\Models\HelpRequest;
use App\Models\MentorBooking;
use App\Models\MentorProfile;
use App\Models\MentorSlot;
use App\Models\OfficeHourBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

// Seeds Sara's mentor profile, upcoming sessions, past sessions, reviews, and mentee request.
class DemoMentorSeeder extends Seeder
{
    public function run(): void
    {
        $sara = User::where('email', DemoUserProfileSeeder::SARA_EMAIL)->firstOrFail();

        $profile = MentorProfile::updateOrCreate(
            ['user_id' => $sara->id],
            [
                'motivation' => 'I mentor early-career designers on product thinking, portfolio storytelling, design systems, and building confidence in cross-functional teams.',
                'focus_areas' => ['UX & Product Design', 'Design Systems', 'Portfolio Reviews', 'Career Growth'],
                'availability' => [
                    'mon' => [15, 17],
                    'wed' => [14, 16],
                    'this_month_earnings' => 1240,
                    'plan' => 'pro',
                ],
                'is_active' => true,
                'status' => MentorProfile::STATUS_APPROVED,
                'github_score' => 82,
                'approved_at' => Carbon::parse('2024-09-12 12:00:00'),
                'rejected_at' => null,
                'experience_years' => 8,
                'topics' => ['UX & Product Design', 'Design Systems', 'Figma', 'User Research'],
                'domains' => ['Fintech', 'SaaS', 'Marketplace'],
                'hours_per_week' => 6,
                'avg_rating' => 4.90,
                'sessions_completed' => 46,
                'projects_advised' => 12,
                'auto_approved_at' => Carbon::parse('2024-09-12 12:00:00'),
            ]
        );

        $upcoming = [
            ['ahmad.nasser@demo.io', 'UX portfolio review', '2026-05-12 15:00:00', MentorSlot::STATUS_BOOKED],
            ['lara.haddad@demo.io', 'Career switch to product', '2026-05-13 17:00:00', MentorSlot::STATUS_BOOKED],
            ['omar.fayad@demo.io', 'Freelance pricing strategy', '2026-05-14 14:00:00', MentorSlot::STATUS_AVAILABLE],
        ];

        foreach ($upcoming as [$email, $topic, $startsAt, $status]) {
            $student = User::where('email', $email)->firstOrFail();
            $slot = MentorSlot::updateOrCreate(
                ['mentor_profile_id' => $profile->id, 'starts_at' => Carbon::parse($startsAt)],
                [
                    'ends_at' => Carbon::parse($startsAt)->addHour(),
                    'status' => $status,
                    'mentor_id' => $sara->id,
                    'slot_datetime' => Carbon::parse($startsAt),
                    'is_booked' => $status === MentorSlot::STATUS_BOOKED,
                ]
            );

            MentorBooking::updateOrCreate(
                ['mentor_slot_id' => $slot->id],
                [
                    'student_id' => $student->id,
                    'topic' => $topic,
                    'cancellation_reason' => null,
                ]
            );
        }

        $reviewTexts = [
            'Sara helped me turn scattered portfolio pieces into a clear product story.',
            'The critique was specific, kind, and immediately actionable.',
            'I finally understood how to explain design systems work in interviews.',
            'Sara gave practical advice on pricing and setting boundaries with clients.',
            'The session made my case study much sharper.',
            'She spotted hierarchy issues I had missed for weeks.',
            'Excellent mentor for designers moving from visual design into product.',
            'Very thoughtful feedback with concrete next steps.',
            'The best portfolio review I have had so far.',
            'Sara balances strategic thinking with tiny craft details beautifully.',
        ];

        $reviewers = User::where('email', 'like', 'demo.member%')->orderBy('id')->limit(10)->get();
        foreach ($reviewers as $index => $student) {
            $startsAt = Carbon::parse('2026-04-01 13:00:00')->addDays($index * 3);
            $slot = MentorSlot::updateOrCreate(
                ['mentor_profile_id' => $profile->id, 'starts_at' => $startsAt],
                [
                    'ends_at' => $startsAt->copy()->addHour(),
                    'status' => MentorSlot::STATUS_COMPLETED,
                    'mentor_id' => $sara->id,
                    'slot_datetime' => $startsAt,
                    'is_booked' => true,
                ]
            );

            OfficeHourBooking::updateOrCreate(
                ['mentor_slot_id' => $slot->id],
                [
                    'booker_id' => $student->id,
                    'status' => OfficeHourBooking::STATUS_COMPLETED,
                    'session_topic' => fake()->randomElement(['Portfolio review', 'UX interview prep', 'Case study critique', 'Design systems coaching']),
                    'outcome_notes' => 'Reviewed goals, identified next steps, and agreed on a focused improvement plan.',
                    'rating' => $index === 1 ? 4 : 5,
                    'rating_comment' => $reviewTexts[$index],
                ]
            );
        }

        $requester = User::where('email', 'demo.member011@demo.io')->firstOrFail();
        HelpRequest::updateOrCreate(
            ['requester_id' => $requester->id, 'mentor_id' => $sara->id, 'context' => 'New mentee request for UX career guidance.'],
            [
                'user_id' => $requester->id,
                'project_id' => null,
                'title' => 'Help preparing for a junior UX interview',
                'description' => 'Looking for feedback on portfolio narrative and interview practice.',
                'tech_tags' => ['UX & Product Design', 'Portfolio Reviews'],
                'stack' => 'Figma, portfolio, case studies',
                'code_snippet' => null,
                'status' => HelpRequest::STATUS_PENDING,
                'response' => null,
                'responded_at' => null,
            ]
        );
    }
}
