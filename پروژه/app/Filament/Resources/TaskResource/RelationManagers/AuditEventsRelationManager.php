<?php

namespace App\Filament\Resources\TaskResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AuditEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditEvents';

    protected static ?string $title = 'تاریخچه تغییرات';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('occurred_at')
                    ->label('زمان')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('event')
                    ->label('رویداد')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'created' => 'ایجاد',
                        'updated' => 'ویرایش',
                        'deleted' => 'حذف',
                        default => $state ?? '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('actor.name')
                    ->label('عامل')
                    ->placeholder('سیستم'),
                Tables\Columns\TextColumn::make('new_values')
                    ->label('خلاصه تغییر')
                    ->formatStateUsing(function ($state, $record): string {
                        $new = is_array($record->new_values) ? $record->new_values : [];
                        $old = is_array($record->old_values) ? $record->old_values : [];

                        if ($new === [] && $old === []) {
                            return '—';
                        }

                        $labels = [
                            'subject' => 'عنوان',
                            'status' => 'وضعیت',
                            'priority' => 'اولویت',
                            'assignee_user_id' => 'مسئول',
                            'due_at' => 'سررسید',
                            'description' => 'شرح',
                            'completed_at' => 'اتمام',
                        ];

                        $parts = [];
                        foreach (array_keys($new + $old) as $key) {
                            $label = $labels[$key] ?? $key;
                            $from = $old[$key] ?? null;
                            $to = $new[$key] ?? null;
                            if ($from === null && $to === null) {
                                continue;
                            }
                            $parts[] = $label.': '.($from === null || $from === '' ? '—' : (string) $from)
                                .' → '.($to === null || $to === '' ? '—' : (string) $to);
                        }

                        return $parts === [] ? '—' : implode(' | ', array_slice($parts, 0, 4));
                    })
                    ->wrap()
                    ->limit(120),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->paginated([10, 25])
            ->emptyStateHeading('رویدادی ثبت نشده');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
