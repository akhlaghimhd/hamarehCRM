<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Actions\Identity\AssignCompanyManager;
use App\Actions\Identity\SaveCompanyBankAccount;
use App\Actions\Identity\SaveCompanyFinancialIdentity;
use App\Actions\Identity\UpdateCompanyProfile;
use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    protected static ?string $title = 'داشبورد مدیریت شرکت';

    protected string $view = 'filament.resources.company-resource.pages.view-company';

    protected function getHeaderActions(): array
    {
        $actions = [];

        if (CompanyResource::canEdit($this->record)) {
            $actions[] = EditAction::make()
                ->label('ویرایش اطلاعات شرکت')
                ->icon('heroicon-o-pencil-square')
                ->using(function (Company $record, array $data): Company {
                    $actor = auth()->user();
                    abort_unless($actor instanceof User, 403);
                    return app(UpdateCompanyProfile::class)->handle($record, $data, $actor);
                });

            $managerId = DB::table('company_user')
                ->join('roles', 'roles.id', '=', 'company_user.role_id')
                ->where('company_user.company_id', $this->record->id)
                ->where('roles.name', 'admin')
                ->orderBy('company_user.id')
                ->value('company_user.user_id');
            $managerAccount = $managerId ? User::query()->find($managerId) : null;

            if ($managerAccount) {
                $actions[] = Action::make('editManagerAccount')
                    ->label('ویرایش اطلاعات مدیر')
                    ->icon('heroicon-o-identification')
                    ->modalHeading('ویرایش حساب مدیر شرکت')
                    ->modalDescription('این حساب کاربری در همه شرکت‌هایی که عضو آن است مشترک است؛ تغییر نام، ایمیل یا گذرواژه در تمام آن شرکت‌ها اعمال می‌شود.')
                    ->fillForm(fn () => $managerAccount->only(['name', 'username', 'email']))
                    ->form([
                        TextInput::make('name')->label('نام')->required()->maxLength(255),
                        TextInput::make('username')->label('نام کاربری')->alphaDash()->required()->maxLength(100),
                        TextInput::make('email')->label('ایمیل')->email()->required()->maxLength(255),
                        TextInput::make('password')->label('گذرواژه جدید (اختیاری)')->password()->revealable()->minLength(8),
                        TextInput::make('password_confirmation')->label('تکرار گذرواژه جدید')->password()->revealable()->same('password'),
                    ])
                    ->action(function (array $data) use ($managerAccount): void {
                        $validated = Validator::make($data, [
                            'name' => ['required', 'string', 'max:255'],
                            'username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('users', 'username')->ignore($managerAccount->id)],
                            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($managerAccount->id)],
                            'password' => ['nullable', 'string', 'min:8', 'same:password_confirmation'],
                            'password_confirmation' => ['nullable', 'string', 'required_with:password'],
                        ], [
                            'username.unique' => 'این نام کاربری قبلاً ثبت شده است.',
                            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
                            'password.min' => 'گذرواژه باید حداقل ۸ نویسه باشد.',
                            'password.same' => 'تکرار گذرواژه با گذرواژه یکسان نیست.',
                        ])->validate();

                        if (blank($validated['password'] ?? null)) {
                            unset($validated['password'], $validated['password_confirmation']);
                        } else {
                            unset($validated['password_confirmation']);
                        }

                        $managerAccount->fill($validated)->save();
                        Notification::make()->title('اطلاعات مدیر شرکت ذخیره شد')->success()->send();
                    });
            }

            $actions[] = Action::make('assignManager')
                ->label('مدیریت مدیر شرکت')
                ->icon('heroicon-o-user-plus')
                ->modalHeading('تعیین مدیر شرکت')
                ->modalDescription('کاربر موجود را انتخاب کنید یا حساب جدید بسازید. مدیر شرکت عضو همین شرکت خواهد بود.')
                ->modalWidth(Width::Large)
                ->form([
                    Select::make('mode')->label('روش تعیین مدیر')->options(['existing' => 'انتخاب کاربر موجود', 'new' => 'ایجاد کاربر جدید'])->default('existing')->live()->required()->native(false),
                    Select::make('user_id')->label('کاربر')->options(fn (): array => User::query()->where('is_active', true)->orderBy('name')->get()->mapWithKeys(fn (User $user) => [$user->id => $user->name.' — '.$user->username])->all())->searchable()->visible(fn ($get) => $get('mode') === 'existing')->required(fn ($get) => $get('mode') === 'existing')->native(false),
                    TextInput::make('name')->label('نام')->visible(fn ($get) => $get('mode') === 'new')->required(fn ($get) => $get('mode') === 'new'),
                    TextInput::make('username')->label('نام کاربری')->alphaDash()->maxLength(100)->visible(fn ($get) => $get('mode') === 'new')->required(fn ($get) => $get('mode') === 'new'),
                    TextInput::make('email')->label('ایمیل')->email()->visible(fn ($get) => $get('mode') === 'new')->required(fn ($get) => $get('mode') === 'new'),
                    TextInput::make('password')->label('گذرواژه')->password()->revealable()->minLength(8)->visible(fn ($get) => $get('mode') === 'new')->required(fn ($get) => $get('mode') === 'new'),
                    TextInput::make('password_confirmation')->label('تکرار گذرواژه')->password()->revealable()->same('password')->visible(fn ($get) => $get('mode') === 'new')->required(fn ($get) => $get('mode') === 'new'),
                ])
                ->action(function (array $data): void {
                    $actor = auth()->user();
                    abort_unless($actor instanceof User, 403);
                    app(AssignCompanyManager::class)->handle($this->record, $data, $actor);
                    Notification::make()->title('مدیر شرکت به‌روزرسانی شد')->success()->send();
                });

            foreach (['official' => 'رسمی', 'unofficial' => 'غیررسمی'] as $type => $label) {
                $identity = $this->record->financialIdentities()->where('type', $type)->first();
                if (! $identity) {
                    continue;
                }

                $actions[] = Action::make('editIdentity_'.$type)
                    ->label('هویت '.$label)
                    ->icon('heroicon-o-building-library')
                    ->modalHeading('ویرایش هویت مالی '.$label)
                    ->modalWidth(Width::FourExtraLarge)
                    ->fillForm(fn () => $identity->only(['legal_name','national_id','economic_code','registration_number','phone','email','postal_code','address','website','is_active']))
                    ->form([
                        TextInput::make('legal_name')->label('نام حقوقی / صادرکننده')->required()->maxLength(255),
                        TextInput::make('national_id')->label('شناسه ملی')->maxLength(32)->extraInputAttributes(['dir'=>'ltr','inputmode'=>'numeric']),
                        TextInput::make('economic_code')->label('کد اقتصادی')->maxLength(64)->extraInputAttributes(['dir'=>'ltr','inputmode'=>'numeric']),
                        TextInput::make('registration_number')->label('شماره ثبت')->maxLength(64)->extraInputAttributes(['dir'=>'ltr']),
                        TextInput::make('phone')->label('تلفن')->tel()->maxLength(32)->extraInputAttributes(['dir'=>'ltr']),
                        TextInput::make('email')->label('ایمیل')->email()->maxLength(255)->extraInputAttributes(['dir'=>'ltr']),
                        TextInput::make('postal_code')->label('کد پستی')->maxLength(20)->extraInputAttributes(['dir'=>'ltr','inputmode'=>'numeric']),
                        TextInput::make('website')->label('وب‌سایت')->maxLength(255)->extraInputAttributes(['dir'=>'ltr']),
                        Textarea::make('address')->label('نشانی')->rows(2)->maxLength(2000)->columnSpanFull(),
                        Toggle::make('is_active')->label('فعال')->inline(false),
                    ])
                    ->action(function (array $data) use ($identity): void {
                        $actor = auth()->user();
                        abort_unless($actor instanceof User, 403);
                        app(SaveCompanyFinancialIdentity::class)->handle($identity, $this->record, ['type' => $identity->type, ...$data], $actor);
                        Notification::make()->title('هویت مالی ذخیره شد')->success()->send();
                    });
            }

            $identities = $this->record->financialIdentities()->with('bankAccounts')->get();
            foreach ($identities as $identity) {
                $typeLabel = $identity->type === 'official' ? 'رسمی' : 'غیررسمی';
                $actions[] = Action::make('addAccount_'.$identity->id)
                    ->label('حساب جدید '.$typeLabel)
                    ->icon('heroicon-o-plus')
                    ->modalHeading('افزودن حساب دریافت برای هویت '.$typeLabel)
                    ->modalWidth(Width::Large)
                    ->form($this->bankAccountForm())
                    ->action(function (array $data) use ($identity): void {
                        $actor = auth()->user();
                        abort_unless($actor instanceof User, 403);
                        app(SaveCompanyBankAccount::class)->handle(null, $identity, $data, $actor);
                        Notification::make()->title('حساب دریافت اضافه شد')->success()->send();
                    });

                foreach ($identity->bankAccounts as $account) {
                    $actions[] = Action::make('editAccount_'.$account->id)
                        ->label('ویرایش حساب '.($account->label ?: $account->bank_name))
                        ->icon('heroicon-o-pencil-square')
                        ->modalHeading('ویرایش حساب دریافت')
                        ->modalWidth(Width::Large)
                        ->fillForm(fn () => $account->only(['label','account_holder','bank_name','account_number','iban','card_number','branch','is_default','is_active']))
                        ->form($this->bankAccountForm())
                        ->action(function (array $data) use ($identity, $account): void {
                            $actor = auth()->user();
                            abort_unless($actor instanceof User, 403);
                            app(SaveCompanyBankAccount::class)->handle($account, $identity, $data, $actor);
                            Notification::make()->title('حساب دریافت ویرایش شد')->success()->send();
                        });
                }
            }
        }

        $actions[] = Action::make('backToCompanies')->label('بازگشت به فهرست')->icon('heroicon-o-arrow-right')->color('gray')->url(CompanyResource::getUrl('index'));

        return $actions;
    }

    private function bankAccountForm(): array
    {
        return [
            TextInput::make('label')->label('عنوان نمایشی')->maxLength(100),
            TextInput::make('account_holder')->label('صاحب حساب')->required()->maxLength(255),
            TextInput::make('bank_name')->label('نام بانک')->required()->maxLength(100),
            TextInput::make('account_number')->label('شماره حساب')->maxLength(64)->extraInputAttributes(['dir'=>'ltr','inputmode'=>'numeric']),
            TextInput::make('iban')->label('شماره شبا')->maxLength(64)->extraInputAttributes(['dir'=>'ltr','autocapitalize'=>'characters']),
            TextInput::make('card_number')->label('شماره کارت')->maxLength(32)->extraInputAttributes(['dir'=>'ltr','inputmode'=>'numeric']),
            TextInput::make('branch')->label('شعبه')->maxLength(255),
            Toggle::make('is_default')->label('حساب پیش‌فرض')->inline(false),
            Toggle::make('is_active')->label('فعال')->default(true)->inline(false),
        ];
    }

    protected function getViewData(): array
    {
        /** @var Company $company */
        $company = $this->record->loadMissing('financialIdentities.bankAccounts');
        $manager = DB::table('company_user')
            ->join('users', 'users.id', '=', 'company_user.user_id')
            ->join('roles', 'roles.id', '=', 'company_user.role_id')
            ->where('company_user.company_id', $company->id)
            ->where('roles.name', 'admin')
            ->orderBy('company_user.id')
            ->select('users.id', 'users.name', 'users.username', 'users.email')
            ->first();

        return [
            'company' => $company,
            'manager' => $manager,
            'identities' => $company->financialIdentities,
        ];
    }
}
