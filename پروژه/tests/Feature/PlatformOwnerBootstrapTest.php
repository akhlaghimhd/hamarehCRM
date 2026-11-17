<?php

namespace Tests\Feature;

use App\Models\PlatformOwner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformOwnerBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_cli_bootstrap_creates_an_independent_platform_owner_account(): void
    {
        $this->artisan('platform-owner:create')
            ->expectsQuestion('نام مالک پلتفرم', 'مالک سامانه')
            ->expectsQuestion('نام کاربری مالک پلتفرم', 'platform-owner')
            ->expectsQuestion('ایمیل مالک پلتفرم', 'owner@example.test')
            ->expectsQuestion('گذرواژه مالک پلتفرم', 'VerySecurePass123')
            ->expectsQuestion('تکرار گذرواژه', 'VerySecurePass123')
            ->expectsConfirmation('حساب مستقل مالک پلتفرم ایجاد شود؟', 'yes')
            ->assertExitCode(0);

        $owner = User::query()->where('username', 'platform-owner')->firstOrFail();

        $this->assertTrue($owner->isPlatformOwner());
        $this->assertDatabaseHas('platform_owners', ['user_id' => $owner->id]);
        $this->assertDatabaseMissing('company_user', ['user_id' => $owner->id]);
        $this->assertTrue(password_verify('VerySecurePass123', $owner->password));
    }
}
