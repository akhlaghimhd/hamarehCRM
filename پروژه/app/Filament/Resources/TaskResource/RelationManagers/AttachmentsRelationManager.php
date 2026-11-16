<?php

namespace App\Filament\Resources\TaskResource\RelationManagers;

use App\Actions\Attachments\DeleteAttachment;
use App\Actions\Attachments\StoreAttachment;
use App\Models\Attachment;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class AttachmentsRelationManager extends RelationManager
{
    private const PREVIEWABLE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    protected static string $relationship = 'attachments';

    protected static ?string $title = 'پیوست‌ها';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Task && Gate::allows('view', $ownerRecord);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_name')
                    ->label('نام فایل')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('mime_type')
                    ->label('نوع فایل')
                    ->toggleable(),
                TextColumn::make('size')
                    ->label('حجم')
                    ->formatStateUsing(fn (?int $state): string => $state
                        ? number_format($state / 1024, 1).' کیلوبایت'
                        : '—'),
                TextColumn::make('uploader.name')
                    ->label('بارگذاری‌کننده')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('تاریخ بارگذاری')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('upload')
                    ->label('بارگذاری فایل')
                    ->icon('heroicon-o-paper-clip')
                    ->visible(fn (): bool => Gate::allows('update', $this->getOwnerRecord()))
                    ->form([
                        FileUpload::make('file')
                            ->label('انتخاب فایل')
                            ->storeFiles(false)
                            ->maxSize(25600)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $actor = auth()->user();

                        abort_unless($actor instanceof User, 403);

                        app(StoreAttachment::class)->handle(
                            $data['file'],
                            $this->getOwnerRecord(),
                            $actor,
                        );
                    }),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('مشاهده')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Attachment $record): string => route('attachments.preview', ['attachment' => $record]))
                    ->openUrlInNewTab()
                    ->visible(fn (Attachment $record): bool => in_array($record->mime_type, self::PREVIEWABLE_MIME_TYPES, true)
                        && Gate::allows('view', $record)),
                Action::make('download')
                    ->label('دانلود')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Attachment $record): string => route('attachments.download', ['attachment' => $record]))
                    ->visible(fn (Attachment $record): bool => Gate::allows('view', $record)),
                Action::make('delete')
                    ->label('حذف')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Attachment $record): bool => Gate::allows('delete', $record))
                    ->action(fn (Attachment $record) => app(DeleteAttachment::class)->handle($record)),
            ]);
    }
}
