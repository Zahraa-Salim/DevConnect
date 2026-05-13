<?php

namespace Database\Seeders;

use App\Models\HelpRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class HelpRequestSeeder extends Seeder
{
    public function run(): void
    {
        $lara = User::where('email', 'lara@devconnect.lb')->first();
        $u6   = User::where('email', 'user6@devconnect.lb')->first();
        $u7   = User::where('email', 'user7@devconnect.lb')->first();
        $u8   = User::where('email', 'user8@devconnect.lb')->first();

        HelpRequest::create([
            'user_id'      => $u7->id,
            'project_id'   => null,
            'title'        => 'Stuck on Inertia SSR setup',
            'description'  => 'I am trying to set up Inertia.js SSR with Laravel and Vue 3 but keep hitting a Node.js process error. The documentation examples do not match my setup.',
            'tech_tags'    => ['Laravel', 'Vue', 'Inertia'],
            'status'       => 'pending',
            'requester_id' => $u7->id,
            'mentor_id'    => $lara->id,
            'context'      => 'Setting up Inertia.js SSR in a Laravel 12 + Vue 3 project. The Node process crashes with ENOENT on the ssr.js bundle.',
            'stack'        => 'Laravel, Vue, Inertia',
        ]);

        HelpRequest::create([
            'user_id'      => $u8->id,
            'project_id'   => null,
            'title'        => 'How to structure Redis cache keys?',
            'description'  => 'I need advice on naming conventions and TTL strategies for Redis cache keys in a Laravel API used by multiple tenants.',
            'tech_tags'    => ['Redis', 'Laravel'],
            'status'       => 'accepted',
            'requester_id' => $u8->id,
            'mentor_id'    => $u6->id,
            'context'      => 'Building a multi-tenant SaaS with Laravel. Need guidance on Redis key namespacing per tenant and cache invalidation patterns.',
            'stack'        => 'Redis, Laravel',
        ]);
    }
}
