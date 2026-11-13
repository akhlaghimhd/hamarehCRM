<?php

namespace App\Filament\Resources\CoverSkuResource\RelationManagers;

use App\Models\CoverColor;
use App\Models\CoverStructure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static ?string $title = 'موجودی بر اساس رنگ';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('color_primary_id')
                ->label('رنگ اصلی')
                ->options(fn () => CoverColor::query()->where('is_active', true)->orderBy('sort')->pluck('name', 'id'))
                ->required()
                ->searchable(),
            Select::make('color_secondary_id')
                ->label('رنگ دوم (برای دوتکه)')
                ->options(fn () => CoverColor::query()->where('is_active', true)->orderBy('sort')->pluck('name', 'id'))
                ->searchable()
                ->nullable(),
            TextInput::make('stock_qty')->label('موجودی')->numeric()->default(0)->required(),
            TextInput::make('note')->label('یادداشت')->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('colorPrimary.name')->label('رنگ اصلی'),
                Tables\Columns\ColorColumn::make('colorPrimary.hex')->label(''),
                Tables\Columns\TextColumn::make('colorSecondary.name')->label('رنگ دوم')->placeholder('—'),
                Tables\Columns\ColorColumn::make('colorSecondary.hex')->label(''),
                Tables\Columns\TextColumn::make('stock_qty')->label('موجودی'),
                Tables\Columns\TextColumn::make('note')->label('یادداشت')->limit(30),
            ])
            ->headerActions([
                CreateAction::make()->label('افزودن موجودی رنگ'),
            ])
            ->recordActions([
                EditAction::make()->label('ویرایش'),
                DeleteAction::make()->label('حذف'),
            ]);
    }
}
