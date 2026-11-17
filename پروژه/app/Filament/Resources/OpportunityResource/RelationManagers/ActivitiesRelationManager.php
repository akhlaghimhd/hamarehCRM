<?php

namespace App\Filament\Resources\OpportunityResource\RelationManagers;

use App\Models\Activity;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'فعالیت‌ها و پیگیری‌ها';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('نوع')
                    ->options(Activity::typeOptions())
                    ->required()
                    ->default(Activity::TYPE_TASK)
                    ->native(false),
                TextInput::make('subject')
                    ->label('موضوع')
                    ->required()
                    ->maxLength(255),
                Textarea::make('body')
                    ->label('شرح')
                    ->rows(3)
                    ->columnSpanFull(),
                DateTimePicker::make('due_at')
                    ->label('سررسید')
                    ->seconds(false)
                    ->native(false),
                Toggle::make('is_done')
                    ->label('انجام‌شده')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                $user = auth()->user();

                if (! $user) {
                    return $query->whereRaw('1 = 0');
                }

                if ($user->isCompanyAdmin()) {
                    return $query;
                }

                $visibleOwnerIds = collect();

                if ($user->hasPermission('activities.view')) {
                    $visibleOwnerIds->push((int) $user->id);
                }

                if ($user->hasPermission('team.activities.view')) {
                    $visibleOwnerIds = $visibleOwnerIds->merge($user->managedUserIds());
                }

                return $query->whereIn('activities.user_id', $visibleOwnerIds->unique()->values());
            })
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع')
                    ->formatStateUsing(fn (string $state): string => Activity::typeOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'call' => 'info',
                        'meeting' => 'warning',
                        'task' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('subject')
                    ->label('موضوع')
                    ->searchable()
                    ->limit(40)
                    ->description(fn ($record) => $record->body ? \Illuminate\Support\Str::limit($record->body, 50) : null),
                Tables\Columns\TextColumn::make('due_at')
                    ->label('سررسید')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->color(fn ($record) => (! $record->is_done && $record->due_at && $record->due_at->isPast())
                        ? 'danger'
                        : null),
                Tables\Columns\IconColumn::make('is_done')
                    ->label('انجام')
                    ->boolean(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('مسئول')
                    ->toggleable(),
            ])
            ->defaultSort('due_at')
            ->headerActions([
                CreateAction::make()
                    ->label('فعالیت جدید')
                    ->visible(fn (): bool => (bool) auth()->user()?->hasPermission('activities.manage'))
                    ->mutateFormDataUsing(function (array $data): array {
                        abort_unless(auth()->user()?->hasPermission('activities.manage'), 403);

                        $data['user_id'] = auth()->id();
                        if (! empty($data['is_done'])) {
                            $data['completed_at'] = now();
                        }

                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('done')
                    ->label('انجام شد')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Activity $record): bool => ! $record->is_done
                        && (bool) auth()->user()?->canManageActivityOwner((int) $record->user_id))
                    ->action(function (Activity $record): void {
                        abort_unless(auth()->user()?->canManageActivityOwner((int) $record->user_id), 403);

                        $record->markDone();
                        Notification::make()->title('فعالیت انجام شد')->success()->send();
                    }),
                EditAction::make()->label('ویرایش')
                    ->visible(fn (Activity $record): bool => (bool) auth()->user()?->canManageActivityOwner((int) $record->user_id))
                    ->mutateFormDataUsing(function (array $data, Activity $record): array {
                        abort_unless(auth()->user()?->canManageActivityOwner((int) $record->user_id), 403);

                        if (! empty($data['is_done'])) {
                            $data['completed_at'] = $data['completed_at'] ?? now();
                        } else {
                            $data['completed_at'] = null;
                        }

                        return $data;
                    }),
                DeleteAction::make()->label('حذف')
                    ->visible(fn (Activity $record): bool => (bool) auth()->user()?->canManageActivityOwner((int) $record->user_id)),
            ]);
    }
}
