<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $title = 'افراد تماس';

    protected static ?string $modelLabel = 'تماس';

    protected static ?string $pluralModelLabel = 'افراد تماس';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام')
                    ->required()
                    ->maxLength(120),
                TextInput::make('title')
                    ->label('سمت')
                    ->maxLength(120),
                TextInput::make('phone')
                    ->label('موبایل / تلفن')
                    ->tel()
                    ->maxLength(50),
                TextInput::make('email')
                    ->label('ایمیل')
                    ->email()
                    ->maxLength(255),
                Toggle::make('is_primary')
                    ->label('تماس اصلی')
                    ->default(false),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->title),
                Tables\Columns\TextColumn::make('phone')
                    ->label('تلفن')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('email')
                    ->label('ایمیل')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_primary')
                    ->label('اصلی')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('افزودن تماس')
                    ->visible(fn () => CustomerResourceCanEdit($this)),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('ویرایش')
                    ->visible(fn () => CustomerResourceCanEdit($this)),
                DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn () => CustomerResourceCanEdit($this)),
            ])
            ->emptyStateHeading('فرد تماسی ثبت نشده')
            ->emptyStateDescription('حداقل یک تماس برای پیگیری فروش اضافه کنید.');
    }
}

/**
 * Helper keeps visibility aligned with parent resource permissions
 * without importing the full Resource class into every closure scope.
 */
function CustomerResourceCanEdit(RelationManager $manager): bool
{
    $owner = $manager->getOwnerRecord();

    return \App\Filament\Resources\CustomerResource::canEdit($owner);
}
