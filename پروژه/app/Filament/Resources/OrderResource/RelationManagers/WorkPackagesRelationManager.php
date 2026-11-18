<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Filament\Resources\WorkPackageResource;
use App\Models\WorkPackage;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WorkPackagesRelationManager extends RelationManager
{
    protected static string $relationship = 'workPackages';

    protected static ?string $title = 'بسته‌های کاری';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('شماره')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->limit(36),
                Tables\Columns\TextColumn::make('execution_mode')
                    ->label('اجرا')
                    ->formatStateUsing(fn (?string $state): string => WorkPackage::executionModeOptions()[$state] ?? ($state ?? '—'))
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->formatStateUsing(fn (?string $state): string => WorkPackage::statusOptions()[$state] ?? ($state ?? '—'))
                    ->badge(),
                Tables\Columns\TextColumn::make('contractor_name')
                    ->label('پیمانکار')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('due_at')
                    ->label('سررسید')
                    ->dateTime('Y/m/d')
                    ->placeholder('—'),
            ])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('بسته کاری برای این سفارش نیست')
            ->recordActions([
                Action::make('open')
                    ->label('باز کردن')
                    ->url(fn (WorkPackage $record): string => WorkPackageResource::getUrl('edit', ['record' => $record])),
            ]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
