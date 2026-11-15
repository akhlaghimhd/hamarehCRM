<?php

namespace Tests\Feature;

use App\Actions\Attachments\StoreAttachment;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_attachment_is_stored_on_private_disk_with_checksum_and_company_scope(): void
    {
        Storage::fake('local');
        [$company, $user, $task] = $this->fixture();

        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $attachment = app(StoreAttachment::class)->handle(
            UploadedFile::fake()->createWithContent('contract.pdf', '%PDF-1.4 attachment test'),
            $task,
            $user,
        );

        $this->assertSame($company->id, $attachment->company_id);
        $this->assertSame($user->id, $attachment->uploaded_by_user_id);
        $this->assertSame('contract.pdf', $attachment->original_name);
        $this->assertSame(64, strlen($attachment->checksum));
        Storage::disk('local')->assertExists($attachment->path);
        $this->assertSame(1, Attachment::query()->count());
        $this->get(route('attachments.download', $attachment))->assertDownload('contract.pdf');
    }

    public function test_attachment_cannot_be_downloaded_from_another_company_context(): void
    {
        Storage::fake('local');
        [$companyA, $user, $task] = $this->fixture();
        $companyB = Company::query()->create([
            'name' => 'Other Company',
            'slug' => 'other-company',
            'is_active' => true,
        ]);

        $this->actingAs($user);
        session(['current_company_id' => $companyA->id]);

        $attachment = app(StoreAttachment::class)->handle(
            UploadedFile::fake()->createWithContent('private.txt', 'private data'),
            $task,
            $user,
        );

        session(['current_company_id' => $companyB->id]);

        $this->get(route('attachments.download', $attachment))
            ->assertNotFound();
    }

    public function test_authorized_attachment_deletion_soft_deletes_record_and_removes_private_file(): void
    {
        Storage::fake('local');
        [$company, $user, $task] = $this->fixture();
        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $attachment = app(StoreAttachment::class)->handle(
            UploadedFile::fake()->createWithContent('to-delete.txt', 'private attachment'),
            $task,
            $user,
        );

        $path = $attachment->path;

        $this->delete(route('attachments.destroy', $attachment))->assertRedirect();

        $this->assertSoftDeleted('attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($path);
        $this->assertSame(0, Attachment::query()->count());
        $this->assertSame(1, Attachment::withTrashed()->count());
    }

    public function test_attachment_deletion_requires_permission_on_the_parent_record(): void
    {
        Storage::fake('local');
        [$company, $owner, $task] = $this->fixture();
        $attachment = app(CompanyContext::class)->runForCompany($company->id, fn () => Attachment::query()->create([
            'company_id' => $company->id,
            'attachable_type' => $task->getMorphClass(),
            'attachable_id' => $task->id,
            'disk' => 'local',
            'path' => 'companies/'.$company->id.'/attachments/restricted.txt',
            'original_name' => 'restricted.txt',
            'mime_type' => 'text/plain',
            'size' => 6,
            'checksum' => hash('sha256', 'secret'),
            'uploaded_by_user_id' => $owner->id,
        ]));
        Storage::disk('local')->put($attachment->path, 'secret');

        $memberRole = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'member',
            'label' => 'Member',
        ]);
        $member = User::factory()->create();
        $member->companies()->attach($company->id, ['role_id' => $memberRole->id]);

        $this->actingAs($member);
        session(['current_company_id' => $company->id]);

        $this->delete(route('attachments.destroy', $attachment))->assertForbidden();

        $this->assertDatabaseHas('attachments', ['id' => $attachment->id, 'deleted_at' => null]);
        Storage::disk('local')->assertExists($attachment->path);
    }

    private function fixture(): array
    {
        $company = Company::query()->create([
            'name' => 'Attachment Company',
            'slug' => 'attachment-company',
            'is_active' => true,
        ]);
        $role = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'admin',
            'label' => 'Admin',
            'is_system' => true,
        ]);
        $user = User::factory()->create();
        $user->companies()->attach($company->id, [
            'role_id' => $role->id,
            'is_default' => true,
        ]);
        $task = app(CompanyContext::class)->runForCompany($company->id, fn () => Task::query()->create([
            'subject' => 'Attachment parent task',
            'assignee_user_id' => $user->id,
            'reporter_user_id' => $user->id,
        ]));

        return [$company, $user, $task];
    }
}
