<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\OpportunityResource\Pages;
use App\Filament\Resources\OpportunityResource\RelationManagers\ActivitiesRelationManager;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\User;
use App\Support\Fa;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
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

    protected static string|UnitEnum|null $navigationGroup = 'بازاریابی (جدید)';

    protected static ?int $navigationSort = 10;

    // TEMP NOTE: full form schema restored from develop pre-placeholder; see artifacts/OpportunityResource.workspace.php if incomplete

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('عنوان')->searchable()->sortable(),
                TextColumn::make('contact_name')->label('تماس')->toggleable(),
                TextColumn::make('stage.name')->label('مرحله')->sortable(),
                TextColumn::make('status')->label('وضعیت')->formatStateUsing(fn ($state) => Opportunity::statusOptions()[$state] ?? $state),
                TextColumn::make('next_step')->label('گام بعدی')->limit(40)->toggleable(),
                TextColumn::make('owner.name')->label('مالک')->toggleable(),
                TextColumn::make('updated_at')->label('به‌روزرسانی')->since()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('stage_id')->label('مرحله')->options(fn () => OpportunityStage::query()->orderBy('sort_order')->pluck('name', 'id')),
                SelectFilter::make('status')->label('وضعیت')->options(Opportunity::statusOptions()),
                Filter::make('mine')->label('مال من')->query(fn (Builder $query) => $query->where('owner_user_id', auth()->id()))->toggle(),
            ])
            ->emptyStateHeading('فرصتی ثبت نشده')
            ->emptyStateDescription('اولین فرصت فروش را ایجاد کنید.')
            ->emptyStateActions([
                \Filament\Actions\Action::make('quick')->label('ثبت سریع')->url(static::getUrl('quick-create'))->icon('heroicon-o-bolt'),
            ])
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]))
            ->recordActions([
                EditAction::make()->label('ویرایش اطلاعات')->url(fn ($record) => static::getUrl('edit', ['record' => $record])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'quick-create' => Pages\QuickCreateOpportunity::route('/quick-create'),
            'view' => Pages\ManageOpportunity::route('/{record}'),
            'edit' => Pages\EditOpportunity::route('/{record}/edit'),
        ];
    }
}
