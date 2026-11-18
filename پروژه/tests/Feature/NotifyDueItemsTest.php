<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotifyDueItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_task_creates_database_notification(): void
    {
        if (! Schema::hasTable('notifications')) {
            $this->markTestSkipped('notifications table missing');
        }

        $company = Company::query()->create([
            'name' => 'Notify Co',
            'slug' => 'notify-'.uniqid(),
            'is_active' => true,
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->companies()->attach($company->id, ['is_default' => true]);

        app(CompanyContext::class)->runForCompany($company->id, function () use ($user) {
            Task::query()->create([
                'subject' => 'پیگیری مشتری',
                'assignee_user_id' => $user->id,
                'reporter_user_id' => $user->id,
                'status' => Task::STATUS_OPEN,
                'priority' => Task::PRIORITY_NORMAL,
                'due_at' => now()->subHour(),
            ]);
        });

        Artisan::call('crm:notify-due-items');

        $this->assertDatabaseCount('notifications', 1);
        $this->assertTrue(
            $user->fresh()->notifications()->where('data', 'like', '%پیگیری مشتری%')->exists()
        );
    }
}
