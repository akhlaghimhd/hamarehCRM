<?php

namespace Tests\\Feature;

use App\\Actions\\Tasks\\SaveTask;
use App\\Models\\Company;
use App\\Models\\Permission;
use App\\Models\\Role;
use App\\Models\\Task;
use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\ValidationException;
use Tests\\TestCase;

class TaskTeamAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_query_scope_includes_only_self_and_direct_or_indirect_reports(): void
    {
        [$company, $manager, $staff, $outsider, $role] = $this->fixture();
        $this->grant($role, ['tasks.view', 'team.tasks.view']);
        DB::table('company_user')->where('company_id', $company->id)->where('user_id', $staff->id)->update(['manager_user_id' => $manager->id]);

        $this->actingAs($manager);
        session(['current_company_id' => $company->id]);

        $own = Task::query()->create(['company_id' => $company->id, 'subject' => 'Mine', 'assignee_user_id' => $manager->id, 'reporter_user_id' => $manager->id]);
        $team = Task::query()->create(['company_id' => $company->id, 'subject' => 'Team', 'assignee_user_id' => $staff->id, 'reporter_user_id' => $manager->id]);

        $visibleIds = Task::query()->whereIn('assignee_user_id', $manager->managedUserIds()->prepend($manager->id))->pluck('id');
        $this->assertContains($own->id, $visibleIds);
        $this->assertContains($team->id, $visibleIds);
        $this->assertSame(2, $visibleIds->count());
    }

    public function test_task_assignment_cannot_cross_company_membership(): void
    {
        [$company, $manager, $staff, $outsider, $role] = $this->fixture();
        $this->grant($role, ['tasks.create']);
        $this->actingAs($manager);
        session(['current_company_id' => $company->id]);

        try {
            app(SaveTask::class)->handle(['subject' => 'Invalid assignment', 'assignee_user_id' => $outsider->id], $manager);
            $this->fail('Expected assignment validation to fail.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }

    public function test_task_changes_are_audited_with_actor_and_before_after_values(): void
    {
        [$company, $manager, $staff, $outsider, $role] = $this->fixture();
        $this->actingAs($manager);
        session(['current_company_id' => $company->id]);

        $task = Task::query()->create(['company_id' => $company->id, 'subject' => 'Audit me', 'assignee_user_id' => $manager->id, 'reporter_user_id' => $manager->id]);
        $task->update(['status' => Task::STATUS_IN_PROGRESS]);

        $this->assertDatabaseHas('audit_events', [
            'company_id' => $company->id,
            'auditable_type' => $task->getMorphClass(),
            'auditable_id' => $task->id,
            'actor_user_id' => $manager->id,
            'event' => 'updated',
        ]);

        $event = $task->auditEvents()->where('event', 'updated')->first();
        $this->assertSame(Task::STATUS_OPEN, $event->old_values['status']);
        $this->assertSame(Task::STATUS_IN_PROGRESS, $event->new_values['status']);
    }

    private function fixture(): array
    {
        $company = Company::query()->create(['name' => 'Tasks Co', 'slug' => 'tasks-co', 'is_active' => true]);
        $role = Role::query()->create(['company_id' => $company->id, 'name' => 'staff', 'label' => 'Staff']);
        $manager = User::factory()->create();
        $staff = User::factory()->create();
        $outsider = User::factory()->create();
        $manager->companies()->attach($company->id, ['role_id' => $role->id, 'is_default' => true]);
        $staff->companies()->attach($company->id, ['role_id' => $role->id, 'is_default' => true]);
        return [$company, $manager, $staff, $outsider, $role];
    }

    private function grant(Role $role, array $names): void
    {
        foreach ($names as $name) {
            $permission = Permission::query()->create(['name' => $name, 'label' => $name, 'group' => 'tasks']);
            $role->permissions()->attach($permission);
        }
    }
}
