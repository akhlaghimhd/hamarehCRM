<?php

namespace App\Filament\Resources\CompanyFinancialIdentityResource\RelationManagers;

use App\Actions\Identity\SaveCompanyBankAccount;
use App\Models\CompanyBankAccount;
use App\Models\CompanyFinancialIdentity;
use App\Models\User;
use App\Support\CompanyMembershipAccess;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Table;

class BankAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'bankAccounts';
    protected static ?string $title = 'حساب‌های دریافت';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('مشخصات حساب')
                ->columns(2)
                ->schema([
                    TextInput::make('label')->label('عنوان نمایشی')->maxLength(100)->placeholder('مثلاً حساب اصلی'),
                    TextInput::make('account_holder')->label('صاحب حساب')->required()->maxLength(255),
                    TextInput::make('bank_name')->label('نام بانک')->required()->maxLength(100),
                    TextInput::make('account_number')->label('شماره حساب')->maxLength(64)->extraInputAttributes(['dir' => 'ltr', 'inputmode' => 'numeric']),
                    TextInput::make('iban')->label('شماره شبا')->maxLength(64)->extraInputAttributes(['dir' => 'ltr', 'autocapitalize' => 'characters']),
                    TextInput::make('card_number')->label('شماره کارت')->maxLength(32)->extraInputAttributes(['dir' => 'ltr', 'inputmode' => 'numeric']),
                    TextInput::make('branch')->label('شعبه')->maxLength(255),
                    Toggle::make('is_default')->label('حساب پیش‌فرض')->inline(false),
                    Toggle::make('is_active')->label('فعال')->default(true)->inline(false),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')->label('عنوان')->placeholder('—')->searchable(),
                Tables\Columns\TextColumn::make('bank_name')->label('بانک')->searchable(),
                Tables\Columns\TextColumn::make('account_holder')->label('صاحب حساب')->searchable(),
                Tables\Columns\TextColumn::make('account_number')->label('شماره حساب')->copyable()->placeholder('—'),
                Tables\Columns\TextColumn::make('iban')->label('شبا')->copyable()->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('card_number')->label('شماره کارت')->copyable()->placeholder('—')->toggleable(),
                Tables\Columns\IconColumn::make('is_default')->label('پیش‌فرض')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
                Tables\Columns\TextColumn::make('version')->label('نسخه'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('افزودن حساب')
                    ->modalWidth(Width::Large)
                    ->using(function (array $data): CompanyBankAccount {
                        $actor = auth()->user();
                        abort_unless($actor instanceof User, 403);
                        return app(SaveCompanyBankAccount::class)->handle(null, $this->getOwnerRecord(), $data, $actor);
                    })
                    ->visible(fn (): bool => $this->canManageAccounts()),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('ویرایش')
                    ->modalWidth(Width::Large)
                    ->using(function (CompanyBankAccount $record, array $data): CompanyBankAccount {
                        $actor = auth()->user();
                        abort_unless($actor instanceof User, 403);
                        return app(SaveCompanyBankAccount::class)->handle($record, $this->getOwnerRecord(), $data, $actor);
                    })
                    ->visible(fn (): bool => $this->canManageAccounts()),
            ])
            ->emptyStateHeading('حسابی ثبت نشده')
            ->emptyStateDescription('برای این هویت مالی حساب دریافت اضافه کنید.');
    }

    private function canManageAccounts(): bool
    {
        $actor = auth()->user();
        $identity = $this->getOwnerRecord();

        return $actor instanceof User
            && $identity instanceof CompanyFinancialIdentity
            && $identity->is_active
            && $actor->is_active
            && ($actor->isPlatformOwner() || app(CompanyMembershipAccess::class)->canManage($actor, (int) $identity->company_id));
    }
}
