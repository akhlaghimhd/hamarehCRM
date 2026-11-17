<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class MarketingSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPermissions();

        $companies = Company::query()->get();

        foreach ($companies as $company) {
            session(['current_company_id' => $company->id]);
            $this->seedStages($company);
            $this->syncSalesPermissions($company);
        }

        // دمو فقط برای شرکت اول
        $primary = Company::query()->where('slug', 'myoffice')->first()
            ?? Company::query()->first();

        if ($primary) {
            session(['current_company_id' => $primary->id]);
            $this->seedDemo($primary);
        }
    }

    private function seedPermissions(): void
    {
        $items = [
            ['name' => 'opportunities.view', 'label' => 'مشاهده فرصت‌ها', 'group' => 'بازاریابی'],
            ['name' => 'opportunities.manage', 'label' => 'مدیریت فرصت‌ها', 'group' => 'بازاریابی'],
            ['name' => 'activities.view', 'label' => 'مشاهده فعالیت‌ها', 'group' => 'بازاریابی'],
            ['name' => 'activities.manage', 'label' => 'مدیریت فعالیت‌ها', 'group' => 'بازاریابی'],
        ];

        foreach ($items as $item) {
            Permission::query()->updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }

    private function syncSalesPermissions(Company $company): void
    {
        $sales = Role::query()
            ->where('company_id', $company->id)
            ->where('name', 'sales')
            ->first();

        $admin = Role::query()
            ->where('company_id', $company->id)
            ->where('name', 'admin')
            ->first();

        $marketingPerms = Permission::query()
            ->whereIn('name', [
                'opportunities.view',
                'opportunities.manage',
                'activities.view',
                'activities.manage',
                'customers.view',
                'customers.manage',
                'products.view',
            ])
            ->pluck('id');

        if ($sales) {
            $sales->permissions()->syncWithoutDetaching($marketingPerms);
        }

        if ($admin) {
            $admin->permissions()->sync(Permission::query()->pluck('id'));
        }
    }

    private function seedStages(Company $company): void
    {
        $stages = [
            ['code' => 'lead', 'name' => 'سرنخ اولیه', 'description' => 'اولین تماس یا معرفی؛ هنوز جلسه رسمی یا نیاز مشخص نشده.', 'sort_order' => 10, 'is_won' => false, 'is_lost' => false],
            ['code' => 'qualify', 'name' => 'واجد شرایط', 'description' => 'بودجه، زمان خرید و نقش تصمیم‌گیر تا حدی روشن است.', 'sort_order' => 20, 'is_won' => false, 'is_lost' => false],
            ['code' => 'meeting', 'name' => 'جلسه / نمایش', 'description' => 'جلسه حضوری یا آنلاین برگزار شده یا قطعی شده است.', 'sort_order' => 30, 'is_won' => false, 'is_lost' => false],
            ['code' => 'proposal', 'name' => 'پیشنهاد ارسال‌شده', 'description' => 'پیشنهاد قیمت یا طرح رسمی برای مشتری ارسال شده.', 'sort_order' => 40, 'is_won' => false, 'is_lost' => false],
            ['code' => 'negotiate', 'name' => 'مذاکره', 'description' => 'چانه‌زنی روی قیمت، تیراژ یا شرایط قرارداد.', 'sort_order' => 50, 'is_won' => false, 'is_lost' => false],
            ['code' => 'won', 'name' => 'بسته شدن موفق', 'description' => 'سفارش قطعی یا قرارداد امضا شده.', 'sort_order' => 60, 'is_won' => true, 'is_lost' => false],
            ['code' => 'lost', 'name' => 'بسته شدن ناموفق', 'description' => 'فرصت از دست رفته؛ دلیل باخت را ثبت کنید.', 'sort_order' => 70, 'is_won' => false, 'is_lost' => true],
        ];

        foreach ($stages as $stage) {
            OpportunityStage::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => $stage['code']],
                array_merge($stage, [
                    'company_id' => $company->id,
                    'is_active' => true,
                    'description' => $stage['description'] ?? null,
                ])
            );
        }
    }

    private function seedDemo(Company $company): void
    {
        $sales = User::query()->where('username', 'sales')->first()
            ?? User::query()->where('username', 'admin')->first();

        $customer = Customer::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'مدرسه نمونه نوتوپیا',
            ],
            [
                'type' => Customer::TYPE_ORGANIZATION,
                'phone' => '02191000000',
                'is_active' => true,
                'notes' => 'مشتری دموی بازاریابی',
            ]
        );

        $proposal = OpportunityStage::query()
            ->where('company_id', $company->id)
            ->where('code', 'proposal')
            ->first();

        if (! $proposal || ! $sales) {
            return;
        }

        $opportunity = Opportunity::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'سفارش سالنامه کیوان ۱۴۰۵',
            ],
            [
                'customer_id' => $customer->id,
                'stage_id' => $proposal->id,
                'owner_user_id' => $sales->id,
                'amount' => 85000000,
                'probability' => 60,
                'source' => 'نمایشگاه مدارس',
                'notes' => 'نیاز به نمونه جلد چرم و تیراژ ۵۰۰',
            ]
        );

        Activity::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'opportunity_id' => $opportunity->id,
                'subject' => 'تماس اولیه با مدیر خرید',
            ],
            [
                'user_id' => $sales->id,
                'type' => Activity::TYPE_CALL,
                'body' => 'معرفی خانواده کیوان و زمان تحویل.',
                'due_at' => now()->subDays(2),
                'is_done' => true,
                'completed_at' => now()->subDays(2),
            ]
        );

        Activity::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'opportunity_id' => $opportunity->id,
                'subject' => 'ارسال پیش‌نویس پیشنهاد',
            ],
            [
                'user_id' => $sales->id,
                'type' => Activity::TYPE_TASK,
                'body' => 'ارسال فایل پیشنهاد قیمت و نمونه جلد.',
                'due_at' => now()->addDays(3),
                'is_done' => false,
                'completed_at' => null,
            ]
        );
    }
}
