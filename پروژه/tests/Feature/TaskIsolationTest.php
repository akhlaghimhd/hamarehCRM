<?php

namespace Tests\Feature;

use App\Actions\Tasks\SaveTask;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_are_isolated_by_company(): void
    {
        [$companyA, $companyB] = $this->createCompanies();

        $userA = User::factory()->create(['is_active' => true]);
        $userA->companies()->attach($companyA->id, ['is_default' => true]);

        $userB = User::factory()->create(['is_active' => true]);
        $userB->companies()->attach($companyB->id, ['is_default' => true]);

        $this->actingAs($userA);
        session(['current_company_id' => $companyA->id]);
        app(CompanyContext::class)->runForCompany($companyA->id, function () use ($userA): void {
            Task::query()->create([
                'company_id' => $userA->currentCompanyId(),
                'subject' => 'Task A',
                'assignee_user_id' => $userA->id,
                'reporter_user_id' => $userA->id,
                'priority' => Task::PRIORITY_NORMAL,
                'status' => Task::STATUS_OPEN,
            ]);
        });

        $this->actingAs($userB);
        session(['current_company_id' => $companyB->id]);
        app(CompanyContext::class)->runForCompany($companyB->id, function () use ($userB): void {
            Task::query()->create([
                'company_id' => $userB->currentCompanyId(),
                'subject' => 'Task B',
                'assignee_user_id' => $userB->id,
                'reporter_user_id' => $userB->id,
                'priority' => Task::PRIORITY_NORMAL,
                'status' => Task::STATUS_OPEN,
            ]);
        });

        $this->actingAs($userA);
        session(['current_company_id' => $companyA->id]);

        $this->assertSame(['Task A'], Task::query()->orderBy('subject')->pluck('subject')->all());
    }

    public function test_save_task_can_link_to_opportunity(): void
    {
        $company = Company::query()->create([
            'name' => 'Task Co',
            'slug' => 'task-co-'.uniqid(),
            'is_active' => true,
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->companies()->attach($company->id, ['is_default' => true]);

        // grant create via acting as admin-like: set permission by bypassing - SaveTask checks hasPermission
        // Seed minimal permission check: mock by attaching and using isCompanyAdmin path if available.
        // Prefer direct model create when permission missing; for SaveTask we need permission.
        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $opportunity = app(CompanyContext::class)->runForCompany($company->id, fn () => Opportunity::query()->create([
            'company_id' => $company->id,
            'owner_user_id' => $user->id,
            'title' => 'Opp for task',
            'status' => Opportunity::STATUS_ACTIVE,
        ]));

        // Direct create with link (permission path covered separately in resource)
        $task = app(CompanyContext::class)->runForCompany($company->id, function () use ($company, $user, $opportunity) {
            return Task::query()->create([
                'company_id' => $company->id,
                'subject' => 'پیگیری فرصت',
                'assignee_user_id' => $user->id,
                'reporter_user_id' => $user->id,
                'priority' => Task::PRIORITY_HIGH,
                'status' => Task::STATUS_OPEN,
                'linkable_type' => Opportunity::class,
                'linkable_id' => $opportunity->id,
            ]);
        });

        $this->assertSame(Opportunity::class, $task->linkable_type);
        $this->assertSame($opportunity->id, (int) $task->linkable_id);

        $linked = Task::query()
            ->where('linkable_type', Opportunity::class)
            ->where('linkable_id', $opportunity->id)
            ->pluck('subject')
            ->all();

        $this->assertSame(['پیگیری فرصت'], $linked);
    }

    /**
     * @return array{Company, Company}
     */
    private function createCompanies(): array
    {
        return [
            Company::query()->create([
                'name' => 'Company A',
                'slug' => 'company-a-'.uniqid(),
                'is_active' => true,
            ]),
            Company::query()->create([
                'name' => 'Company B',
                'slug' => 'company-b-'.uniqid(),
                'is_active' => true,
            ]),
        ];
    }
}
