<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PDO;
use Tests\TestCase;

class DashboardCarryForwardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The pdo_sqlite extension is not available.');
        }

        parent::setUp();
    }

    public function test_undone_priority_tasks_carry_forward_with_overdue_badges(): void
    {
        $today = Carbon::parse('2026-05-04 10:00:00', 'Asia/Kuala_Lumpur');
        Carbon::setTestNow($today);

        $yesterday = $today->copy()->subDay()->toDateString();
        $twoDaysAgo = $today->copy()->subDays(2)->toDateString();

        $user = User::factory()->create();

        Task::create([
            'user_id' => $user->id,
            'text' => 'Yesterday should task',
            'section' => 'should',
            'date' => $yesterday,
        ]);

        Task::create([
            'user_id' => $user->id,
            'text' => 'Older must task',
            'section' => 'must',
            'date' => $twoDaysAgo,
        ]);

        Task::create([
            'user_id' => $user->id,
            'text' => 'Parked idea',
            'section' => 'park',
            'date' => $yesterday,
        ]);

        Task::create([
            'user_id' => $user->id,
            'text' => 'Old good task',
            'section' => 'good',
            'date' => $yesterday,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Yesterday should task')
            ->assertSee('LATE')
            ->assertSee('Older must task')
            ->assertSee('DO IT NOW')
            ->assertSee('Parked idea')
            ->assertDontSee('Old good task');
    }
}
