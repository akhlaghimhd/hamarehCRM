<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\OpportunityResource\Pages;
use App\Filament\Resources\OpportunityResource\RelationManagers\ActivitiesRelationManager;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OpportunityResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'opportunities.view';

    protected static ?string $permissionManage = 'opportunities.manage';

    protected static ?string $model = Opportunity::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $modelLabel = 'فرصت';

    protected static ?string $pluralModelLabel = 'فرصت‌ها';

    protected static ?string $navigationLabel = 'فرصت‌ها';

    protected static string|UnitEnum|null $navigationGroup = 'بازاریابی';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('عنوان')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('customer_id')
                    ->label('مشتری')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('stage_id')
                    ->label('مرحله')
                    ->options(fn () => OpportunityStage::query()
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->pluck('name', 'id'))
                    ->required()
                    ->native(false),
                Select::make('owner_user_id')
                    ->label('مسئول')
                    ->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->default(fn () => auth()->id()),
                TextInput::make('amount')
                    ->label('مبلغ تخمینی (ریال)')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('probability')
                    ->label('احتمال (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100),
                TextInput::make('source')
                    ->label('منبع / کمپین')
                    ->maxLength(255),
                Textarea::make('notes')
                    ->label('یادداشت')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('lost_reason')
                    ->label('دلیل باخت')
                    ->maxLength(255)
                    ->visible(fn ($get, $record) => filled($get('lost_reason')) || ($record?->lost_at)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('مشتری')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stage.name')
                    ->label('مرحله')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('مسئول')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('مبلغ')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('probability')
                    ->label('احتمال')
                    ->suffix('%')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('به‌روزرسانی')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('stage_id')
                    ->label('مرحله')
                    ->options(fn () => OpportunityStage::query()
                        ->orderBy('sort_order')
                        ->pluck('name', 'id')),
                Tables\Filters\Filter::make('mine')
                    ->label('مال من')
                    ->query(fn (Builder $query) => $query->where('owner_user_id', auth()->id()))
                    ->toggle(),
            ])
            ->emptyStateHeading('فرصتی ثبت نشده')
            ->emptyStateDescription('اولین فرصت فروش را بسازید.')
            ->recordActions([
                EditAction::make()->label('ویرایش')
                    ->visible(fn ($record) => static::canEdit($record)),
                Action::make('won')
                    ->label('برنده')
                    ->icon('heroicon-o-trophy')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => static::canEdit($record) && ! $record->won_at)
                    ->action(function (Opportunity $record) {
                        $record->markWon();
                        Notification::make()->title('فرصت برنده شد')->success()->send();
                    }),
                Action::make('lost')
                    ->label('بازنده')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        TextInput::make('lost_reason')
                            ->label('دلیل باخت')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->visible(fn ($record) => static::canEdit($record) && ! $record->lost_at)
                    ->action(function (Opportunity $record, array $data) {
                        $record->markLost($data['lost_reason'] ?? null);
                        Notification::make()->title('فرصت بازنده شد')->warning()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف انتخاب‌شده‌ها')
                        ->visible(fn () => static::canDeleteAny()),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpportunities::route('/'),
            'create' => Pages\CreateOpportunity::route('/create'),
            'edit' => Pages\EditOpportunity::route('/{record}/edit'),
        ];
    }
}
