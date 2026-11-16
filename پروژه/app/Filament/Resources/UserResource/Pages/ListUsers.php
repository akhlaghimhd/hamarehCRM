<?php

namespace App\\Filament\\Resources\\UserResource\\Pages;

use App\\Actions\\Identity\\AddExistingUserToCompany;
use App\\Filament\\Resources\\UserResource;
use App\\Models\\Role;
use App\\Models\\User;
use Filament\\Actions\\Action;
use Filament\\Actions\\CreateAction;
use Filament\\Forms\\Components\\Select;
use Filament\\Resources\\Pages\\ListRecords;
use Illuminate\\Database\\Eloquent\\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'کاربران';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addExistingUser')
                ->label('افزودن کاربر موجود')
                ->icon('heroicon-o-user-plus')
                ->schema(function (): array {
                    $companyId = auth()->user()?->currentCompanyId();

                    return [
                        Select::make('user_id')
                            ->label('کاربر')
                            ->options(fn () => User::query()
                                ->where('is_active', true)
                                ->whereDoesntHave('companies', fn (Builder $query) => $query->where('companies.id', $companyId ?? 0))
                                ->orderBy('name')
                                ->limit(100)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Select::make('role_ids')
                            ->label('نقش‌ها در شرکت جاری')
                            ->multiple()
                            ->options(fn () => Role::query()
                                ->where('company_id', $companyId ?? 0)
                                ->orderBy('label')
                                ->pluck('label', 'id'))
                            ->searchable()
                            ->required(),

                        Select::make('manager_user_id')
                            ->label('مدیر مستقیم')
                            ->options(fn () => User::query()
                                ->where('is_active', true)
                                ->whereHas('companies', fn (Builder $query) => $query->where('companies.id', $companyId ?? 0))
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                    ];
                })
                ->action(function (array $data): void {
                    $companyId = auth()->user()?->currentCompanyId();

                    abort_unless($companyId, 403);

                    app(AddExistingUserToCompany::class)->handle(
                        (int) $companyId,
                        (int) $data['user_id'],
                        $data['role_ids'] ?? [],
                        filled($data['manager_user_id'] ?? null) ? (int) $data['manager_user_id'] : null,
                        auth()->user(),
                    );
                })
                ->successNotificationTitle('کاربر به شرکت جاری اضافه شد')
                ->visible(fn () => UserResource::canCreate()),

            CreateAction::make()
                ->label('ایجاد کاربر')
                ->visible(fn () => UserResource::canCreate()),
        ];
    }
}
