<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\WorkPackageResource\Pages;
use App\Models\User;
use App\Models\WorkPackage;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WorkPackageResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'work_packages.view';

    protected static ?string $permissionManage = 'work_packages.manage';

    protected static ?string $model = WorkPackage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $modelLabel = 'بسته کاری';

    protected static ?string $pluralModelLabel = 'بسته‌های کاری';

    protected static ?string $navigationLabel = 'بسته‌های کاری';

    protected static string|UnitEnum|null $navigationGroup = 'عملیات';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        $companyId = auth()->user()?->currentCompanyId();
        $assignees = User::query()
            ->where('is_active', true)
            ->whereHas('companies', fn (Builder $q) => $q->where('companies.id', $companyId ?? 0))
            ->orderBy('name')
            ->pluck('name', 'id');

        return $schema->components([
            Section::make('بسته کاری')
                ->description('هاب پیمانکار/داخلی — بدون hardcode مراحل محصول')
                ->schema([
                    TextInput::make('number')->label('شماره')->disabled(),
                    TextInput::make('title')->label('عنوان')->required()->maxLength(255),
                    TextInput::make('order.number')->label('سفارش')->disabled(),
                    Select::make('execution_mode')
                        ->label('نوع اجرا')
                        ->options(WorkPackage::executionModeOptions())
                        ->required()
                        ->native(false),
                    Select::make('status')
                        ->label('وضعیت')
                        ->options(WorkPackage::statusOptions())
                        ->disabled()
                        ->dehydrated(false)
                        ->native(false),
                    TextInput::make('contractor_name')->label('پیمانکار / واحد'),
                    Select::make('assignee_user_id')
                        ->label('مسئول داخلی')
                        ->options($assignees)
                        ->searchable()
                        ->native(false),
                    DateTimePicker::make('due_at')->label('سررسید')->seconds(false)->native(false),
                    Textarea::make('description')->label('شرح')->rows(3)->columnSpanFull(),
                    Textarea::make('notes')->label('یادداشت')->rows(2)->columnSpanFull(),
                ])
                ->columns(['default' => 1, 'md' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('شماره')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->label('عنوان')->limit(36)->searchable(),
                Tables\Columns\TextColumn::make('order.number')->label('سفارش')->toggleable(),
                Tables\Columns\TextColumn::make('execution_mode')
                    ->label('اجرا')
                    ->formatStateUsing(fn (?string $state): string => WorkPackage::executionModeOptions()[$state] ?? ($state ?? '—'))
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->formatStateUsing(fn (?string $state): string => WorkPackage::statusOptions()[$state] ?? ($state ?? '—'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'draft' => 'gray',
                        'assigned' => 'info',
                        'in_progress' => 'warning',
                        'delivered' => 'primary',
                        'accepted' => 'success',
                        'rejected', 'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('contractor_name')->label('پیمانکار')->toggleable(),
                Tables\Columns\TextColumn::make('assignee.name')->label('مسئول')->toggleable(),
                Tables\Columns\TextColumn::make('due_at')->label('سررسید')->jalaliDate('Y/m/d')->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('وضعیت')->options(WorkPackage::statusOptions()),
                Tables\Filters\SelectFilter::make('execution_mode')->label('نوع اجرا')->options(WorkPackage::executionModeOptions()),
            ])
            ->emptyStateHeading('بسته کاری نیست')
            ->recordActions([
                EditAction::make()->label('مدیریت'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkPackages::route('/'),
            'edit' => Pages\EditWorkPackage::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
