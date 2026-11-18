<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use App\Support\Fa;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FollowUpNeededWidget extends TableWidget
{
    protected static ?string $heading = 'نیاز به پیگیری';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $userId = Auth::id();

        return $table
            ->query(
                Task::query()
                    ->whereIn('status', [Task::STATUS_OPEN, Task::STATUS_IN_PROGRESS])
                    ->where(function (Builder $q) {
                        $q->whereNull('due_at')
                            ->orWhere('due_at', '<=', now()->endOfDay());
                    })
                    ->when($userId, fn (Builder $q) => $q->where('assignee_user_id', $userId))
                    ->orderByRaw('case when due_at is null then 1 else 0 end')
                    ->orderBy('due_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('subject')
                    ->label('کار')
                    ->limit(40)
                    ->url(fn (Task $record): string => TaskResource::getUrl('edit', ['record' => $record])),
                Tables\Columns\TextColumn::make('priority')
                    ->label('اولویت')
                    ->formatStateUsing(fn (string $state) => Task::priorityOptions()[$state] ?? $state)
                    ->badge(),
                Tables\Columns\TextColumn::make('due_at')
                    ->label('سررسید')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '—';
                        }

                        $label = $state->isPast() ? 'گذشته' : 'امروز';

                        return Fa::digits($state->format('Y/m/d H:i')).' ('.$label.')';
                    })
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->formatStateUsing(fn (string $state) => Task::statusOptions()[$state] ?? $state),
            ])
            ->paginated(false);
    }
}
