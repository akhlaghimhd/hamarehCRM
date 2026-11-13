<?php

namespace App\Filament\Resources\OpportunityResource\RelationManagers;

use App\Models\Activity;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'فعالیت‌ها';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('نوع')
                    ->options(Activity::typeOptions())
                    ->required()
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
                    ->seconds(false),
                Toggle::make('is_done')
                    ->label('انجام‌شده')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع')
                    ->formatStateUsing(fn (string $state): string => Activity::typeOptions()[$state] ?? $state)
                    ->badge(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('موضوع')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('due_at')
                    ->label('سررسید')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_done')
                    ->label('انجام')
                    ->boolean(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('ثبت‌کننده')
                    ->toggleable(),
            ])
            ->defaultSort('due_at')
            ->headerActions([
                CreateAction::make()
                    ->label('فعالیت جدید')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        if (! empty($data['is_done'])) {
                            $data['completed_at'] = now();
                        }

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('ویرایش')
                    ->mutateFormDataUsing(function (array $data): array {
                        if (! empty($data['is_done']) && empty($data['completed_at'])) {
                            $data['completed_at'] = now();
                        }
                        if (empty($data['is_done'])) {
                            $data['completed_at'] = null;
                        }

                        return $data;
                    }),
                DeleteAction::make()->label('حذف'),
            ]);
    }
}
