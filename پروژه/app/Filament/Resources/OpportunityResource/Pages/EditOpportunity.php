<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\OpportunityResource;
use App\Filament\Resources\QuoteResource;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\Quote;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOpportunity extends EditRecord
{
    protected static string $resource = OpportunityResource::class;

    protected static ?string $title = 'ویرایش فرصت';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toQuote')
                ->label('ساخت پیش‌فاکتور')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('ساخت پیش‌فاکتور از این فرصت')
                ->modalDescription('یک پیش‌فاکتور پیش‌نویس با مبلغ و عنوان فرصت ساخته می‌شود.')
                ->visible(fn () => OpportunityResource::canEdit($this->record)
                    && filled($this->record->customer_id))
                ->action(function () {
                    $quote = Quote::fromOpportunity($this->record);

                    Notification::make()
                        ->title('پیش‌فاکتور ساخته شد')
                        ->body($quote->number)
                        ->success()
                        ->send();

                    return redirect(QuoteResource::getUrl('edit', ['record' => $quote]));
                }),

            Action::make('moveStage')
                ->label('تغییر مرحله')
                ->icon('heroicon-o-arrows-right-left')
                ->color('gray')
                ->visible(fn () => OpportunityResource::canEdit($this->record))
                ->form([
                    Select::make('stage_id')
                        ->label('مرحله جدید')
                        ->options(fn () => OpportunityStage::query()
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->pluck('name', 'id'))
                        ->required()
                        ->native(false)
                        ->default(fn () => $this->record->stage_id),
                ])
                ->action(function (array $data): void {
                    $stage = OpportunityStage::query()->find($data['stage_id']);
                    if (! $stage) {
                        return;
                    }

                    $this->record->stage_id = $stage->id;

                    if ($stage->is_won) {
                        $this->record->status = Opportunity::STATUS_WON;
                        $this->record->won_at = now();
                        $this->record->lost_at = null;
                        $this->record->lost_reason = null;
                        $this->record->lost_reason_code = null;
                    } elseif ($stage->is_lost) {
                        $this->record->status = Opportunity::STATUS_LOST;
                        $this->record->lost_at = now();
                        $this->record->won_at = null;
                    } else {
                        if (in_array($this->record->status, [Opportunity::STATUS_WON, Opportunity::STATUS_LOST], true)) {
                            $this->record->status = Opportunity::STATUS_ACTIVE;
                        }
                        $this->record->won_at = null;
                        $this->record->lost_at = null;
                    }

                    $this->record->save();
                    $this->refreshFormData(['stage_id', 'status']);

                    Notification::make()
                        ->title('مرحله به‌روز شد')
                        ->body($stage->name)
                        ->success()
                        ->send();
                }),

            Action::make('won')
                ->label('برنده')
                ->icon('heroicon-o-trophy')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => OpportunityResource::canEdit($this->record)
                    && $this->record->status !== Opportunity::STATUS_WON)
                ->action(function (): void {
                    $this->record->markWon();
                    $this->refreshFormData(['stage_id', 'status']);
                    Notification::make()->title('فرصت برنده شد')->success()->send();
                }),

            Action::make('lost')
                ->label('بازنده')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => OpportunityResource::canEdit($this->record)
                    && $this->record->status !== Opportunity::STATUS_LOST)
                ->form([
                    Select::make('lost_reason_code')
                        ->label('دلیل باخت')
                        ->options(Opportunity::lostReasonOptions())
                        ->required()
                        ->native(false),
                    TextInput::make('lost_reason')
                        ->label('توضیح')
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $this->record->markLost($data['lost_reason'] ?? null, $data['lost_reason_code'] ?? null);
                    $this->refreshFormData(['stage_id', 'status', 'lost_reason', 'lost_reason_code']);
                    Notification::make()->title('فرصت بازنده شد')->warning()->send();
                }),

            DeleteAction::make()
                ->label('حذف')
                ->visible(fn () => OpportunityResource::canDelete($this->record)),
        ];
    }
}
