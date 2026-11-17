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

class AttachmentPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_preview_supported_image_inline(): void
    {
        Storage::fake('local');
        [$company, $user, $task] = $this->fixture();
        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $attachment = app(StoreAttachment::class)->handle(
            UploadedFile::fake()->image('preview.png'),
            $task,
            $user,
        );

        $response = $this->get(route('attachments.preview', $attachment));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);

        $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
    }

    public function test_attachment_preview_is_not_available_in_another_company_context(): void
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
            UploadedFile::fake()->image('private.png'),
            $task,
            $user,
        );

        session(['current_company_id' => $companyB->id]);

        $this->get(route('attachments.preview', $attachment))->assertNotFound();
    }

    public function test_non_image_attachment_cannot_be_previewed(): void
    {
        Storage::fake('local');
        [$company, $user, $task] = $this->fixture();
        $this->actingAs($user);
        session(['current_company_id' => $company->id]);

        $attachment = app(StoreAttachment::class)->handle(
            UploadedFile::fake()->createWithContent('document.pdf', '%PDF-1.4 preview test'),
            $task,
            $user,
        );

        $this->get(route('attachments.preview', $attachment))->assertNotFound();
    }

    private function fixture(): array
    {
        $company = Company::query()->create([
            'name' => 'Attachment Preview Company',
            'slug' => 'attachment-preview-company',
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
            'subject' => 'Attachment preview parent task',
            'assignee_user_id' => $user->id,
            'reporter_user_id' => $user->id,
        ]));

        return [$company, $user, $task];
    }
}
