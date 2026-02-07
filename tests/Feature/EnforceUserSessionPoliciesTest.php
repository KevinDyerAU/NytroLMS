<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EnforceUserSessionPoliciesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Migrate the database
        Artisan::call('migrate', ['--env' => 'testing']);

        // Seed the database (if required)
        Artisan::call('db:seed', ['--env' => 'testing']);

        $this->user = User::factory()->create();

        $this->user->assignRole('Student');
        $this->userDetail = UserDetail::factory()->create([
            'user_id' => $this->user->id,
            'last_logged_in' => Carbon::now()->subHours(12),
            'registered_by' => 0,
        ]);

        $this->user->setRelation('detail', $this->userDetail);
    }

    /** @test */
    public function it_redirects_inactive_user_to_login()
    {
        // Create an inactive user
        $user = $this->user->fresh();
        $user->update(['is_active' => 0]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors(['message' => 'Your account is inactive. Please contact support if you believe this is a mistake.']);
    }

    /** @test */
    public function it_redirects_user_if_logged_in_for_too_long()
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Your session has expired. Please log in again.');
    }

    /** @test */
    public function it_allows_access_if_user_is_active_and_recently_logged_in()
    {
        $user1 = User::factory()->create();
        $user1->assignRole('Student');
        $user1->userDetail = UserDetail::factory()->create([
            'user_id' => $user1->id,
            'last_logged_in' => Carbon::now()->subHours(5),
            'registered_by' => 0,
        ]);
        $user1->setRelation('detail', $user1->userDetail);

        $response = $this->actingAs($user1)->get('/dashboard');

        //        dd(intval($user1->is_active), $user1->toArray());
        $response->assertStatus(200); // Assumes the route is accessible when no issues are present
    }
}
