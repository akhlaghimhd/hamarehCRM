<?php

namespace Database\Seeders;

use App\Models\AddonModelRule;
use App\Models\CatalogRule;
use App\Models\CatalogSeason;
use App\Models\Company;
use App\Models\CompanyFinancialIdentity;
use App\Models\CompanyFinancialIdentityVersion;
use App\Models\CoverColor;
use App\Models\CoverMaterial;
use App\Models\CoverSku;
use App\Models\CoverStructure;
use App\Models\CoverType;
use App\Models\InsertGuideline;
use App\Models\PackagingType;
use App\Models\Permission;
use App\Models\PlatformOwner;
use App\Models\ProductFamily;
use App\Models\ProductLine;
use App\Models\ProductModel;
use App\Models\ProductOption;
use App\Models\ProductSize;
use App\Models\Role;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * ریست سخت دیتابیس + داده پایه برای درک کاتالوگ نوتوپیا.
 *
 * - شرکت: هوشمند آرمان هماره
 * - مدیر: مهدی اخلاقی (username: mehdi / password: password)
 * - بدون فرصت / پیش‌فاکتور / سفارش / فاکتور / پرداخت / تسک
 * - کاتالوگ محصول نوتوپیا کامل (خانواده، فصل، مدل، سایز، جلد، آپشن، بسته، قاعده)
 *
 * اجرا داخل کانتینر:
 *   docker exec -it myoffice-app php artisan db:seed --class=FreshCatalogDemoSeeder
 */
class FreshCatalogDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->wipeAllBusinessData();
        $this->seedPermissions();
        $company = $this->seedCompanyAndAdmin();
        $this->seedCatalog((int) $company->id);

        $this->command?->info('FreshCatalogDemoSeeder تمام شد.');
        $this->command?->info('ورود: username=mehdi  password=password');
        $this->command?->info('شرکت: هوشمند آرمان هماره — فرصت/فاکتور خالی؛ کاتالوگ نوتوپیا پر.');
    }

    protected function wipeAllBusinessData(): void
    {
        Schema::disableForeignKeyConstraints();

        $tables = [
            'payments',
            'invoices',
            'order_lines',
            'orders',
            'quote_lines',
            'quotes',
            'work_packages',
            'activities',
            'opportunity_steps',
            'opportunities',
            'opportunity_stages',
            'tasks',
            'audit_events',
            'attachments',
            'notifications',
            'stock_movements',
            'stock_levels',
            'cover_sku_stocks',
            'sales_representatives',
            'number_sequences',
            'company_settings',
            'company_bank_account_versions',
            'company_bank_accounts',
            'company_financial_identity_versions',
            'company_financial_identities',
            'product_model_option',
            'product_model_cover_sku',
            'product_model_size',
            'addon_model_rules',
            'product_option_variants',
            'product_option_attributes',
            'product_options',
            'packaging_model',
            'packaging_types',
            'insert_guidelines',
            'catalog_rules',
            'cover_skus',
            'cover_types',
            'cover_materials',
            'cover_structures',
            'cover_colors',
            'product_sizes',
            'product_models',
            'products',
            'catalog_seasons',
            'product_families',
            'product_lines',
            'platform_owners',
            'company_user',
            'permission_role',
            'roles',
            'customers',
            'customer_contacts',
            'companies',
            'users',
            'permissions',
            'sessions',
            'jobs',
            'job_batches',
            'failed_jobs',
            'cache',
            'cache_locks',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();
        $this->command?->warn('همه داده‌های عملیاتی و کاتالوگ پاک شد (truncate).');
    }

    protected function seedPermissions(): void
    {
        $items = [
            ['name' => 'users.view', 'label' => 'مشاهده کاربران', 'group' => 'کاربران'],
            ['name' => 'users.manage', 'label' => 'مدیریت کاربران', 'group' => 'کاربران'],
            ['name' => 'customers.view', 'label' => 'مشاهده مشتریان', 'group' => 'مشتریان'],
            ['name' => 'customers.manage', 'label' => 'مدیریت مشتریان', 'group' => 'مشتریان'],
            ['name' => 'companies.manage', 'label' => 'مدیریت شرکت‌ها', 'group' => 'شرکت'],
            ['name' => 'roles.manage', 'label' => 'مدیریت نقش‌ها', 'group' => 'سیستم'],
            ['name' => 'products.view', 'label' => 'مشاهده محصولات', 'group' => 'محصولات'],
            ['name' => 'products.manage', 'label' => 'مدیریت محصولات', 'group' => 'محصولات'],
            ['name' => 'opportunities.view', 'label' => 'مشاهده فرصت‌ها', 'group' => 'بازاریابی'],
            ['name' => 'opportunities.manage', 'label' => 'مدیریت فرصت‌ها', 'group' => 'بازاریابی'],
            ['name' => 'activities.view', 'label' => 'مشاهده فعالیت‌ها', 'group' => 'بازاریابی'],
            ['name' => 'activities.manage', 'label' => 'مدیریت فعالیت‌ها', 'group' => 'بازاریابی'],
            ['name' => 'tasks.view', 'label' => 'مشاهده کارها', 'group' => 'کارها'],
            ['name' => 'tasks.create', 'label' => 'ایجاد کار', 'group' => 'کارها'],
            ['name' => 'tasks.update', 'label' => 'ویرایش کار', 'group' => 'کارها'],
            ['name' => 'tasks.manage', 'label' => 'مدیریت کارها', 'group' => 'کارها'],
            ['name' => 'quotes.view', 'label' => 'مشاهده پیش‌فاکتور', 'group' => 'فروش'],
            ['name' => 'quotes.manage', 'label' => 'مدیریت پیش‌فاکتور', 'group' => 'فروش'],
            ['name' => 'orders.view', 'label' => 'مشاهده سفارش', 'group' => 'فروش'],
            ['name' => 'orders.manage', 'label' => 'مدیریت سفارش', 'group' => 'فروش'],
            ['name' => 'invoices.view', 'label' => 'مشاهده فاکتور', 'group' => 'مالی'],
            ['name' => 'invoices.manage', 'label' => 'مدیریت فاکتور', 'group' => 'مالی'],
            ['name' => 'payments.manage', 'label' => 'ثبت پرداخت', 'group' => 'مالی'],
            ['name' => 'inventory.view', 'label' => 'مشاهده موجودی', 'group' => 'موجودی'],
            ['name' => 'inventory.manage', 'label' => 'مدیریت موجودی', 'group' => 'موجودی'],
            ['name' => 'production.view', 'label' => 'مشاهده تولید', 'group' => 'تولید'],
            ['name' => 'production.manage', 'label' => 'مدیریت تولید', 'group' => 'تولید'],
        ];

        foreach ($items as $item) {
            Permission::query()->create($item);
        }
    }

    protected function seedCompanyAndAdmin(): Company
    {
        $company = Company::query()->create([
            'name' => 'هوشمند آرمان هماره',
            'slug' => 'houshmand-arman-hamareh',
            'national_id' => null,
            'registration_number' => null,
            'phone' => null,
            'email' => 'info@hamareh.local',
            'address' => null,
            'website' => null,
            'is_active' => true,
        ]);

        foreach (['legal', 'operational'] as $type) {
            if (! Schema::hasTable('company_financial_identities')) {
                break;
            }
            $identity = CompanyFinancialIdentity::query()->create([
                'company_id' => $company->id,
                'type' => $type,
                'legal_name' => $company->name,
                'national_id' => $company->national_id,
                'registration_number' => $company->registration_number,
                'phone' => $company->phone,
                'email' => $company->email,
                'address' => $company->address,
                'website' => $company->website,
                'version' => 1,
                'is_active' => true,
            ]);

            if (Schema::hasTable('company_financial_identity_versions')) {
                CompanyFinancialIdentityVersion::query()->create([
                    'company_id' => $company->id,
                    'company_financial_identity_id' => $identity->id,
                    'version' => 1,
                    'snapshot' => $identity->only([
                        'company_id', 'type', 'legal_name', 'national_id', 'registration_number',
                        'phone', 'email', 'address', 'website', 'version', 'is_active',
                    ]),
                ]);
            }
        }

        $adminRole = Role::query()->create([
            'company_id' => $company->id,
            'name' => 'admin',
            'label' => 'مدیر',
            'is_system' => true,
        ]);
        Role::query()->create([
            'company_id' => $company->id,
            'name' => 'sales',
            'label' => 'فروش',
            'is_system' => true,
        ]);
        Role::query()->create([
            'company_id' => $company->id,
            'name' => 'support',
            'label' => 'پشتیبانی',
            'is_system' => true,
        ]);

        $adminRole->permissions()->sync(Permission::query()->pluck('id'));

        $user = User::query()->create([
            'name' => 'مهدی اخلاقی',
            'username' => 'mehdi',
            'email' => 'mehdi@hamareh.local',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $user->companies()->sync([
            $company->id => [
                'role_id' => $adminRole->id,
                'is_default' => true,
            ],
        ]);

        if (Schema::hasTable('platform_owners')) {
            PlatformOwner::query()->create([
                'user_id' => $user->id,
                'granted_by_user_id' => $user->id,
            ]);
        }

        return $company;
    }

    protected function seedCatalog(int $cid): void
    {
        app(CompanyContext::class)->runForCompany($cid, function () use ($cid): void {
            $lines = [
                [
                    'code' => 'notopia',
                    'name' => 'نوتوپیا',
                    'type' => 'configurable',
                    'brand' => 'نوتوپیا',
                    'short_description' => 'نوشت‌افزار هوشمند قابل پیکربندی',
                    'description' => 'دفتر فیزیکی + نرم‌افزار اختیاری؛ خانواده کیوان/مانا، سایز و جلد داینامیک',
                    'sort' => 1,
                ],
                [
                    'code' => 'jelva',
                    'name' => 'جلوا',
                    'type' => 'configurable',
                    'brand' => 'جلوا',
                    'short_description' => 'محصول جلوا',
                    'description' => null,
                    'sort' => 2,
                ],
                [
                    'code' => 'bestar',
                    'name' => 'بستار',
                    'type' => 'configurable',
                    'brand' => 'بستار',
                    'short_description' => 'محصول بستار',
                    'description' => null,
                    'sort' => 3,
                ],
                [
                    'code' => 'nemago',
                    'name' => 'نماگو',
                    'type' => 'configurable',
                    'brand' => 'نماگو',
                    'short_description' => 'محصول نماگو',
                    'description' => null,
                    'sort' => 4,
                ],
            ];
            foreach ($lines as $item) {
                ProductLine::query()->create(array_merge($item, [
                    'company_id' => $cid,
                    'lifecycle_status' => 'active',
                    'unit' => 'عدد',
                    'is_active' => true,
                ]));
            }

            $line = ProductLine::query()->where('company_id', $cid)->where('code', 'notopia')->firstOrFail();

            $keyvan = ProductFamily::query()->create([
                'company_id' => $cid,
                'code' => 'KEYVAN',
                'name' => 'کیوان',
                'sort' => 10,
                'is_active' => true,
            ]);
            $manaFamily = ProductFamily::query()->create([
                'company_id' => $cid,
                'code' => 'MANA',
                'name' => 'مانا',
                'sort' => 20,
                'is_active' => true,
            ]);

            $season1405 = CatalogSeason::query()->create([
                'company_id' => $cid,
                'code' => '1405',
                'name' => 'کالکشن ۱۴۰۵',
                'year_label' => '1405',
                'sort' => 10,
                'is_active' => true,
            ]);
            CatalogSeason::query()->create([
                'company_id' => $cid,
                'code' => '1406',
                'name' => 'کالکشن ۱۴۰۶',
                'year_label' => '1406',
                'sort' => 20,
                'is_active' => true,
            ]);

            $ziba = ProductModel::query()->create([
                'company_id' => $cid,
                'product_line_id' => $line->id,
                'product_family_id' => $keyvan->id,
                'catalog_season_id' => $season1405->id,
                'code' => 'KEYVAN-ZIBA-1405',
                'name' => 'زیبا',
                'description' => 'مدل تجاری زیبا از خانواده کیوان — کالکشن ۱۴۰۵',
                'has_special_paper' => false,
                'includes_erasable_pen' => false,
                'includes_cloth' => false,
                'status' => 'published',
                'base_price' => 850000,
                'is_active' => true,
                'sort' => 1,
            ]);
            $nasim = ProductModel::query()->create([
                'company_id' => $cid,
                'product_line_id' => $line->id,
                'product_family_id' => $keyvan->id,
                'catalog_season_id' => $season1405->id,
                'code' => 'KEYVAN-NASIM-1405',
                'name' => 'نسیم',
                'description' => 'مدل تجاری نسیم از خانواده کیوان — کالکشن ۱۴۰۵',
                'has_special_paper' => false,
                'includes_erasable_pen' => false,
                'includes_cloth' => false,
                'status' => 'published',
                'base_price' => 920000,
                'is_active' => true,
                'sort' => 2,
            ]);
            $manaModel = ProductModel::query()->create([
                'company_id' => $cid,
                'product_line_id' => $line->id,
                'product_family_id' => $manaFamily->id,
                'catalog_season_id' => $season1405->id,
                'code' => 'MANA-CORE-1405',
                'name' => 'مانا پایه',
                'description' => 'کاغذ مخصوص + خودکار پاک‌شو + دستمال — خانواده مانا',
                'has_special_paper' => true,
                'includes_erasable_pen' => true,
                'includes_cloth' => true,
                'status' => 'published',
                'base_price' => 1250000,
                'is_active' => true,
                'sort' => 3,
            ]);

            $keivanLegacy = ProductModel::query()->create([
                'company_id' => $cid,
                'product_line_id' => $line->id,
                'product_family_id' => $keyvan->id,
                'catalog_season_id' => $season1405->id,
                'code' => 'keivan',
                'name' => 'کیوان (پایه)',
                'description' => 'کاغذ معمولی — مدل پایه فنی',
                'has_special_paper' => false,
                'includes_erasable_pen' => false,
                'includes_cloth' => false,
                'status' => 'published',
                'base_price' => 800000,
                'is_active' => true,
                'sort' => 4,
            ]);
            $manaLegacy = ProductModel::query()->create([
                'company_id' => $cid,
                'product_line_id' => $line->id,
                'product_family_id' => $manaFamily->id,
                'catalog_season_id' => $season1405->id,
                'code' => 'mana',
                'name' => 'مانا (پایه)',
                'description' => 'کاغذ مخصوص + خودکار پاک‌شو + دستمال',
                'has_special_paper' => true,
                'includes_erasable_pen' => true,
                'includes_cloth' => true,
                'status' => 'published',
                'base_price' => 1200000,
                'is_active' => true,
                'sort' => 5,
            ]);

            $sizes = [];
            foreach ([
                ['code' => '1_8', 'name' => '۱/۸', 'sort' => 1, 'attributes' => ['cover_hint' => 'cardboard_family']],
                ['code' => '1_16', 'name' => '۱/۱۶', 'sort' => 2, 'attributes' => ['cover_hint' => 'cardboard_family']],
                ['code' => 'european', 'name' => 'اروپایی (رقعی)', 'sort' => 3, 'attributes' => ['cover_hint' => 'leather_family']],
            ] as $s) {
                $sizes[$s['code']] = ProductSize::query()->create([
                    'company_id' => $cid,
                    'product_line_id' => $line->id,
                    'code' => $s['code'],
                    'name' => $s['name'],
                    'attributes' => $s['attributes'],
                    'is_active' => true,
                    'sort' => $s['sort'],
                ]);
            }

            $coverTypes = [];
            foreach ([
                ['code' => 'cardboard', 'name' => 'مقوایی', 'sort' => 1],
                ['code' => 'leather_soft', 'name' => 'چرمی نرم', 'sort' => 2],
                ['code' => 'leather_hard', 'name' => 'چرمی سخت', 'sort' => 3],
                ['code' => 'thermo', 'name' => 'ترمو', 'sort' => 4],
                ['code' => 'cellophane_print', 'name' => 'سلفونی چاپی', 'sort' => 5],
                ['code' => 'plexi', 'name' => 'پلکسی', 'sort' => 6],
            ] as $ct) {
                $coverTypes[$ct['code']] = CoverType::query()->create([
                    'company_id' => $cid,
                    'product_line_id' => $line->id,
                    'code' => $ct['code'],
                    'name' => $ct['name'],
                    'is_active' => true,
                    'sort' => $ct['sort'],
                ]);
            }

            $materials = [];
            foreach ([
                ['code' => 'leather', 'name' => 'چرم', 'sort' => 1],
                ['code' => 'thermo', 'name' => 'ترمو', 'sort' => 2],
                ['code' => 'cardboard', 'name' => 'مقوا', 'sort' => 3],
                ['code' => 'cellophane', 'name' => 'سلفون', 'sort' => 4],
                ['code' => 'multi_style', 'name' => 'مولتی‌استایل', 'sort' => 5],
                ['code' => 'plexi', 'name' => 'پلکسی', 'sort' => 6],
                ['code' => 'fabric', 'name' => 'پارچه', 'sort' => 7],
            ] as $m) {
                $materials[$m['code']] = CoverMaterial::query()->create([
                    'company_id' => $cid,
                    'product_line_id' => $line->id,
                    'code' => $m['code'],
                    'name' => $m['name'],
                    'is_active' => true,
                    'sort' => $m['sort'],
                ]);
            }

            $structures = [];
            foreach ([
                ['code' => 'one_piece', 'name' => 'یک‌تکه', 'color_slots' => 1, 'sort' => 1],
                ['code' => 'two_piece', 'name' => 'دوتکه', 'color_slots' => 2, 'sort' => 2],
            ] as $s) {
                $structures[$s['code']] = CoverStructure::query()->create([
                    'company_id' => $cid,
                    'product_line_id' => $line->id,
                    'code' => $s['code'],
                    'name' => $s['name'],
                    'color_slots' => $s['color_slots'],
                    'is_active' => true,
                    'sort' => $s['sort'],
                ]);
            }

            foreach ([
                ['name' => 'مشکی', 'hex' => '#1a1a1a', 'sort' => 1],
                ['name' => 'قهوه‌ای', 'hex' => '#6B3F2A', 'sort' => 2],
                ['name' => 'کرم', 'hex' => '#F5E6C8', 'sort' => 3],
                ['name' => 'آبی سرمه‌ای', 'hex' => '#1B2A4A', 'sort' => 4],
                ['name' => 'قرمز', 'hex' => '#8B1E1E', 'sort' => 5],
            ] as $c) {
                CoverColor::query()->create([
                    'company_id' => $cid,
                    'product_line_id' => $line->id,
                    'name' => $c['name'],
                    'hex' => $c['hex'],
                    'is_active' => true,
                    'sort' => $c['sort'],
                ]);
            }

            $coverSkus = [];
            $coverDefs = [
                [
                    'sku' => 'S-P200',
                    'name' => 'جلد چرمی مشکی نرم',
                    'cover_type' => 'leather_soft',
                    'material' => 'leather',
                    'structure' => 'one_piece',
                    'series_type' => 'stock',
                    'hardness' => 'soft',
                    'color' => 'مشکی',
                ],
                [
                    'sku' => 'S-P201',
                    'name' => 'جلد چرمی قهوه‌ای سخت',
                    'cover_type' => 'leather_hard',
                    'material' => 'leather',
                    'structure' => 'one_piece',
                    'series_type' => 'stock',
                    'hardness' => 'hard',
                    'color' => 'قهوه‌ای',
                ],
                [
                    'sku' => 'C-CARD-001',
                    'name' => 'جلد مقوایی سفارشی',
                    'cover_type' => 'cardboard',
                    'material' => 'cardboard',
                    'structure' => 'one_piece',
                    'series_type' => 'custom',
                    'hardness' => null,
                    'color' => 'کرم',
                ],
                [
                    'sku' => 'C-THERMO-001',
                    'name' => 'جلد ترمو سرمه‌ای',
                    'cover_type' => 'thermo',
                    'material' => 'thermo',
                    'structure' => 'one_piece',
                    'series_type' => 'stock',
                    'hardness' => null,
                    'color' => 'آبی سرمه‌ای',
                ],
                [
                    'sku' => 'C-CELL-001',
                    'name' => 'جلد سلفونی چاپی',
                    'cover_type' => 'cellophane_print',
                    'material' => 'cellophane',
                    'structure' => 'one_piece',
                    'series_type' => 'custom',
                    'hardness' => null,
                    'color' => null,
                ],
                [
                    'sku' => 'C-PLEXI-001',
                    'name' => 'جلد پلکسی دوتکه',
                    'cover_type' => 'plexi',
                    'material' => 'plexi',
                    'structure' => 'two_piece',
                    'series_type' => 'custom',
                    'hardness' => null,
                    'color' => null,
                ],
            ];
            foreach ($coverDefs as $c) {
                $coverSkus[$c['sku']] = CoverSku::query()->create([
                    'company_id' => $cid,
                    'product_line_id' => $line->id,
                    'cover_type_id' => $coverTypes[$c['cover_type']]->id,
                    'cover_material_id' => $materials[$c['material']]->id,
                    'cover_structure_id' => $structures[$c['structure']]->id,
                    'sku' => $c['sku'],
                    'series_type' => $c['series_type'],
                    'hardness' => $c['hardness'],
                    'name' => $c['name'],
                    'material' => $c['cover_type'],
                    'color' => $c['color'],
                    'structure' => $c['structure'],
                    'stock_qty' => 0,
                    'is_active' => true,
                ]);
            }

            $packKeivanA = PackagingType::query()->create([
                'company_id' => $cid,
                'product_line_id' => $line->id,
                'code' => 'keivan_box_a',
                'name' => 'جعبه کیوان نوع A',
                'is_active' => true,
                'sort' => 1,
            ]);
            $packKeivanB = PackagingType::query()->create([
                'company_id' => $cid,
                'product_line_id' => $line->id,
                'code' => 'keivan_box_b',
                'name' => 'جعبه کیوان نوع B',
                'is_active' => true,
                'sort' => 2,
            ]);
            $packMana = PackagingType::query()->create([
                'company_id' => $cid,
                'product_line_id' => $line->id,
                'code' => 'mana_box',
                'name' => 'جعبه مانا',
                'is_active' => true,
                'sort' => 3,
            ]);

            $addonIds = [];
            foreach ([
                ['code' => 'pen_erasable', 'name' => 'خودکار پاک‌شو', 'type' => 'addon'],
                ['code' => 'cloth', 'name' => 'دستمال', 'type' => 'addon'],
                ['code' => 'bag', 'name' => 'کیف', 'type' => 'addon'],
                ['code' => 'nfc', 'name' => 'NFC', 'type' => 'nfc'],
                ['code' => 'software', 'name' => 'نرم‌افزار', 'type' => 'software'],
                ['code' => 'packaging_ship', 'name' => 'بسته‌بندی ارسال', 'type' => 'addon'],
                ['code' => 'logo_cover', 'name' => 'لوگو روی جلد', 'type' => 'branding'],
                ['code' => 'logo_bag', 'name' => 'لوگو روی کیف', 'type' => 'branding'],
            ] as $i => $a) {
                $row = ProductOption::query()->create([
                    'company_id' => $cid,
                    'product_line_id' => $line->id,
                    'code' => $a['code'],
                    'name' => $a['name'],
                    'type' => $a['type'],
                    'is_active' => true,
                    'sort' => $i + 1,
                ]);
                $addonIds[$a['code']] = $row->id;
            }

            $allModels = [$ziba, $nasim, $manaModel, $keivanLegacy, $manaLegacy];
            $sizeIds = collect($sizes)->pluck('id')->all();
            $coverIds = collect($coverSkus)->pluck('id')->all();

            foreach ($allModels as $model) {
                $model->sizes()->sync($sizeIds);
                $model->coverSkus()->sync($coverIds);
                $optionSync = [];
                foreach ($addonIds as $optId) {
                    $optionSync[$optId] = [
                        'is_recommended' => false,
                        'min_qty' => null,
                        'max_qty' => null,
                    ];
                }
                $model->options()->sync($optionSync);
            }

            $rules = [
                ['pen_erasable', $manaModel->id, 'required', true, 'همراه مدل مانا'],
                ['pen_erasable', $manaLegacy->id, 'required', true, 'همراه مدل مانا'],
                ['pen_erasable', $ziba->id, 'forbidden', false, 'برای کیوان ارائه نمی‌شود'],
                ['pen_erasable', $nasim->id, 'forbidden', false, 'برای کیوان ارائه نمی‌شود'],
                ['pen_erasable', $keivanLegacy->id, 'forbidden', false, 'برای کیوان ارائه نمی‌شود'],
                ['cloth', $manaModel->id, 'required', true, 'همراه مدل مانا'],
                ['cloth', $manaLegacy->id, 'required', true, 'همراه مدل مانا'],
                ['cloth', $ziba->id, 'forbidden', false, 'برای کیوان منطقی نیست'],
                ['cloth', $nasim->id, 'forbidden', false, 'برای کیوان منطقی نیست'],
                ['cloth', $keivanLegacy->id, 'forbidden', false, 'برای کیوان منطقی نیست'],
                ['bag', $manaModel->id, 'allowed', true, 'طراحی اصلی برای مانا'],
                ['bag', $manaLegacy->id, 'allowed', true, 'طراحی اصلی برای مانا'],
                ['bag', $ziba->id, 'allowed', false, 'در صورت درخواست مشتری'],
                ['bag', $nasim->id, 'allowed', false, 'در صورت درخواست مشتری'],
                ['bag', $keivanLegacy->id, 'allowed', false, 'در صورت درخواست مشتری'],
                ['nfc', $manaModel->id, 'allowed', false, null],
                ['nfc', $ziba->id, 'allowed', false, null],
                ['software', $manaModel->id, 'allowed', false, 'کد یکتا per جلد'],
                ['software', $ziba->id, 'allowed', false, 'کد یکتا per جلد'],
            ];
            foreach ($rules as [$code, $modelId, $availability, $preferred, $note]) {
                AddonModelRule::query()->create([
                    'product_option_id' => $addonIds[$code],
                    'product_model_id' => $modelId,
                    'availability' => $availability,
                    'is_preferred' => $preferred,
                    'note' => $note,
                ]);
            }

            CatalogRule::query()->create([
                'company_id' => $cid,
                'product_line_id' => $line->id,
                'name' => 'جلد سخت برای ۱/۸ غیرمعمول',
                'scope' => 'size_cover',
                'condition_json' => ['size_code' => '1_8', 'cover_type_code' => 'leather_hard'],
                'effect_json' => ['action' => 'warn'],
                'level' => 'discouraged',
                'message' => 'جلد چرمی سخت برای سایز ۱/۸ عرف نیست ولی در صورت درخواست ممکن است.',
                'is_active' => true,
            ]);

            InsertGuideline::query()->create([
                'company_id' => $cid,
                'product_line_id' => $line->id,
                'title' => 'راهنمای لت تبلیغاتی نوتوپیا',
                'body' => "لت تبلیغاتی کاملاً اختیاری است.\n- بودن/نبودن لت\n- تعداد برگ\n- طراحی توسط شرکت یا مشتری\nفروش و طراح باید این چارچوب را هنگام مذاکره و تولید رعایت کنند.",
                'audience' => 'both',
                'is_active' => true,
            ]);

            if (Schema::hasTable('packaging_model')) {
                foreach ([$ziba, $nasim, $keivanLegacy] as $m) {
                    DB::table('packaging_model')->insert([
                        ['packaging_type_id' => $packKeivanA->id, 'product_model_id' => $m->id],
                        ['packaging_type_id' => $packKeivanB->id, 'product_model_id' => $m->id],
                    ]);
                }
                foreach ([$manaModel, $manaLegacy] as $m) {
                    DB::table('packaging_model')->insert([
                        ['packaging_type_id' => $packMana->id, 'product_model_id' => $m->id],
                    ]);
                }
            }

            $this->command?->info('کاتالوگ نوتوپیا با خانواده/فصل/مدل/سایز/جلد/آپشن Seed شد.');
        });
    }
}
