<?php

namespace Tests\Feature;

use App\Actions\Tasks\SaveTask;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Task;
use App\Filament\Resources\TaskResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskTeamAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_query_scope_includes_only_self_and_direct_or_indirect_reports(): void
    {
        [$company, $manager, $staff, $outsider, $role] = $this->fixture();
        $this->grant($role, ['tasks.view', 'team.tasks.view']);
        $grandchild = User::factory()->create();
        $grandchild->companies()->attach($company->id, ['role_id' => $role->id, 'is_default' => true]);
        DB::table('company_user')->where('company_id', $company->id)->where('user_id', $staff->id)->update(['manager_user_id' => $manager->id]);
        DB::table('company_user')->where('company_id', $company->id)->where('user_id', $grandchild->id)->update(['manager_user_id' => $staff->id]);

        $this->actingAs($manager);
        session(['current_company_id' => $company->id]);

        $own = Task::query()->create(['company_id' => $company->id, 'subject' => 'Mine', 'assignee_user_id' => $manager->id, 'reporter_user_id' => $manager->id]);
        $team = Task::query()->create(['company_id' => $company->id, 'subject' => 'Team', 'assignee_user_id' => $staff->id, 'reporter_user_id' => $manager->id]);
        $indirect = Task::query()->create(['company_id' => $company->id, 'subject' => 'Indirect team', 'assignee_user_id' => $grandchild->id, 'reporter_user_id' => $manager->id]);

        $visibleIds = TaskResource::getEloquentQuery()->pluck('id');
        $this->assertContains($own->id, $visibleIds);
        $this->assertContains($team->id, $visibleIds);
        $this->assertContains($indirect->id, $visibleIds);
        $this->assertSame(3, $visibleIds->count());
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

    public function test_activity_changes_are_audited_with_before_after_values(): void
    {
        [$company, $manager] = array_slice($this->fixture(), 0, 2);
        $this->actingAs($manager);
        session(['current_company_id' => $company->id]);

        $activity = Activity::query()->create([
            'company_id' => $company->id,
            'user_id' => $manager->id,
            'type' => Activity::TYPE_NOTE,
            'subject' => 'Initial subject',
            'body' => 'Initial body',
        ]);

        $activity->update(['subject' => 'Updated subject']);

        $event = DB::table('audit_events')
            ->where('auditable_type', $activity->getMorphClass())
            ->where('auditable_id', $activity->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($event);
        $this->assertSame('Initial subject', json_decode($event->old_values, true)['subject']);
        $this->assertSame('Updated subject', json_decode($event->new_values, true)['subject']);
    }

    public function test_task_creation_is_idempotent_within_a_company(): void
    {
        [$company, $manager, $staff, $outsider, $role] = $this->fixture();
        $this->grant($role, ['tasks.create']);
        $this->actingAs($manager);
        session(['current_company_id' => $company->id]);

        $data = [
            'subject' => 'Single task',
            'assignee_user_id' => $manager->id,
            'idempotency_key' => 'workflow-step-123',
        ];

        $first = app(SaveTask::class)->handle($data, $manager);
        $second = app(SaveTask::class)->handle($data, $manager);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Task::query()->where('idempotency_key', 'workflow-step-123')->count());
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
