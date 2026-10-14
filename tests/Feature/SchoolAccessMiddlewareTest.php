<?php

use App\Enums\SchoolRole;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\SchoolMembershipRole;
use App\Models\User;

beforeEach(function () {
    app('router')->get('/test-school/{school}', fn (School $school) => response()->json(['school' => $school->id]))
        ->middleware(['web', 'auth', 'school.access']);

    app('router')->get('/test-school-role/{school}', fn (School $school) => response()->json(['status' => 'authorized']))
        ->middleware(['web', 'auth', 'school.role:DIRECTOR,ADMIN_ESCOLAR']);
});

test('user without school membership cannot access school routes', function () {
    $user = User::factory()->create();
    $school = School::create(['name' => 'Escuela A', 'code' => 'ESC-A']);

    $response = $this->actingAs($user)->get("/test-school/{$school->id}");

    $response->assertNotFound();
});

test('user with school membership can access school routes', function () {
    $user = User::factory()->create();
    $school = School::create(['name' => 'Escuela A', 'code' => 'ESC-A']);

    SchoolMembership::create([
        'school_id' => $school->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get("/test-school/{$school->id}");

    $response->assertOk();
    $response->assertJson(['school' => $school->id]);
});

test('global admin can access any school without membership', function () {
    $admin = User::factory()->create(['global_role' => 'ADMIN_GLOBAL']);
    $school = School::create(['name' => 'Escuela B', 'code' => 'ESC-B']);

    $response = $this->actingAs($admin)->get("/test-school/{$school->id}");

    $response->assertOk();
});

test('school member with correct role can access role-protected route', function () {
    $user = User::factory()->create();
    $school = School::create(['name' => 'Escuela C', 'code' => 'ESC-C']);

    $membership = SchoolMembership::create([
        'school_id' => $school->id,
        'user_id' => $user->id,
    ]);

    SchoolMembershipRole::create([
        'school_membership_id' => $membership->id,
        'role' => SchoolRole::DIRECTOR->value,
    ]);

    $response = $this->actingAs($user)->get("/test-school-role/{$school->id}");

    $response->assertOk();
});

test('school member without required role is forbidden', function () {
    $user = User::factory()->create();
    $school = School::create(['name' => 'Escuela D', 'code' => 'ESC-D']);

    $membership = SchoolMembership::create([
        'school_id' => $school->id,
        'user_id' => $user->id,
    ]);

    SchoolMembershipRole::create([
        'school_membership_id' => $membership->id,
        'role' => SchoolRole::PROFESOR->value,
    ]);

    $response = $this->actingAs($user)->get("/test-school-role/{$school->id}");

    $response->assertForbidden();
});
