<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\RelationManagers;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TaskResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'tasks.view';
    protected static ?string $permissionManage = 'tasks.create';
    protected static ?string $model = Task::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $modelLabel = 'کار';
    protected static ?string $pluralModelLabel = 'کارها';
    protected static ?string $navigationLabel = 'کارهای تیم';
    protected static string|UnitEnum|null $navigationGroup = 'کارها و تیم';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && ($user->isCompanyAdmin()
            || $user->hasPermission('tasks.view')
            || $user->hasPermission('team.tasks.view'));
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->hasPermission('tasks.create');
    }

    public static function canView($record): bool
    {
        return $record instanceof Task
            && (bool) auth()->user()?->canViewTaskOwner((int) $record->assignee_user_id);
    }

    public static function canEdit($record): bool
    {
        return $record instanceof Task
            && (bool) auth()->user()?->canManageTaskOwner((int) $record->assignee_user_id);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['assignee', 'reporter']);
        $user = auth()->user();

        if (! $user || ! $user->currentCompanyId()) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isCompanyAdmin()) {
            return $query;
        }

        $visibleIds = collect();
        if ($user->hasPermission('tasks.view')) {
            $visibleIds->push((int) $user->id);
        }
        if ($user->hasPermission('team.tasks.view')) {
            $visibleIds = $visibleIds->merge($user->managedUserIds());
        }

        return $query->whereIn('assignee_user_id', $visibleIds->unique()->values());
    }

    public static function form(Schema $schema): Schema
    {
        $user = auth()->user();
        $companyId = $user?->currentCompanyId();
        $assignees = User::query()
            ->where('is_active', true)
            ->whereHas('companies', fn (Builder $q) => $q->where('companies.id', $companyId ?? 0))
            ->orderBy('name')->pluck('name', 'id');

        return $schema->components([
            TextInput::make('subject')->label('عنوان کار')->required()->maxLength(255),
            Textarea::make('description')->label('شرح')->rows(3)->columnSpanFull(),
            Select::make('assignee_user_id')->label('مسئول')->options($assignees)->searchable()->required()->default(auth()->id()),
            Select::make('priority')->label('اولویت')->options(Task::priorityOptions())->default(Task::PRIORITY_NORMAL)->required(),
            Select::make('status')->label('وضعیت')->options(Task::statusOptions())->default(Task::STATUS_OPEN)->required(),
            DateTimePicker::make('due_at')->label('سررسید')->seconds(false)->native(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        $companyId = auth()->user()?->currentCompanyId();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject')->label('عنوان')->searchable()->sortable()->limit(45),
                Tables\Columns\TextColumn::make('assignee.name')->label('مسئول')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('priority')->label('اولویت')
                    ->formatStateUsing(fn (string $state) => Task::priorityOptions()[$state] ?? $state)
                    ->badge()->color(fn (string $state) => match ($state) {
                        Task::PRIORITY_URGENT => 'danger',
                        Task::PRIORITY_HIGH => 'warning',
                        Task::PRIORITY_LOW => 'gray',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('status')->label('وضعیت')
                    ->formatStateUsing(fn (string $state) => Task::statusOptions()[$state] ?? $state)->badge(),
                Tables\Columns\TextColumn::make('due_at')->label('سررسید')->dateTime('Y/m/d H:i')->sortable()
                    ->color(fn (Task $record) => ! in_array($record->status, [Task::STATUS_DONE, Task::STATUS_CANCELLED], true)
                        && $record->due_at?->isPast() ? 'danger' : null),
            ])
            ->stackedOnMobile()
            ->filters([
                SelectFilter::make('assignee_user_id')->label('مسئول')->options(
                    User::query()->whereHas('companies', fn (Builder $q) => $q->where('companies.id', $companyId ?? 0))
                        ->orderBy('name')->pluck('name', 'id')
                ),
                SelectFilter::make('status')->label('وضعیت')->options(Task::statusOptions()),
                SelectFilter::make('priority')->label('اولویت')->options(Task::priorityOptions()),
                Filter::make('overdue')->label('سررسید گذشته')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('due_at')
                        ->where('due_at', '<', now())
                        ->whereNotIn('status', [Task::STATUS_DONE, Task::STATUS_CANCELLED])),
                Filter::make('mine')->label('کارهای من')
                    ->query(fn (Builder $query): Builder => $query->where('assignee_user_id', auth()->id())),
            ])
            ->defaultSort('due_at')
            ->recordActions([
                EditAction::make()->label('ویرایش')->visible(fn (Task $record) => static::canEdit($record)),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttachmentsRelationManager::class,
            RelationManagers\AuditEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
