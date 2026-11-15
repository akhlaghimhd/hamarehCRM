<?php

namespace Tests\Feature;

use App\Actions\Attachments\StoreAttachment;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Permission;
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
            ->assertForbidden();
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
