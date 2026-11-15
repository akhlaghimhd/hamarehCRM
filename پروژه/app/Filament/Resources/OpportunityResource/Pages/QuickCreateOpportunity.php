<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\OpportunityResource;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Services\OpportunityQuickCreate;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class QuickCreateOpportunity extends CreateRecord
{
    protected static string $resource = OpportunityResource::class;

    protected static ?string $title = 'ثبت سریع فرصت';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'فرصت ثبت شد';
    }

    public function getBreadcrumb(): string
    {
        return 'ثبت سریع';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('full')
                ->label('فرم کامل')
                ->url(OpportunityResource::getUrl('create'))
                ->color('gray'),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('هویت طرف حساب')
                ->description('با نام سازمان یا موبایل، مشتری پیدا یا ساخته می‌شود.')
                ->schema([
                    TextInput::make('organization_name')
                        ->label('نام سازمان / شرکت')
                        ->placeholder('مثال: صنایع غذایی آفتاب')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            if (blank($get('title')) && filled($state)) {
                                $set('title', 'فرصت ' . $state);
                            }
                        }),
                    TextInput::make('contact_name')
                        ->label('نام فرد تماس')
                        ->placeholder('مثلاً خانم رضایی')
                        ->maxLength(255),
                    TextInput::make('mobile')
                        ->label('موبایل')
                        ->placeholder('۰۹۱۲۱۲۳۴۵۶۷')
                        ->tel()
                        ->required()
                        ->maxLength(30),
                    TextInput::make('phone')
                        ->label('تلفن ثابت')
                        ->tel()
                        ->maxLength(30),
                    TextInput::make('email')
                        ->label('ایمیل')
                        ->email()
                        ->maxLength(255),
                    Select::make('contact_role')
                        ->label('نقش تصمیم')
                        ->options(Opportunity::decisionRoleOptions())
                        ->native(false)
                        ->placeholder('— اختیاری —'),
                ])
                ->columns(['default' => 1, 'md' => 2]),

            Section::make('محصول و مرحله')
                ->description('می‌توانید چند خط محصول انتخاب کنید (مثلاً نوتوپیا و چلیوا).')
                ->schema([
                    Select::make('product_interest')
                        ->label('خطوط محصول')
                        ->options(Opportunity::productInterestOptions())
                        ->multiple()
                        ->searchable()
                        ->native(false)
                        ->required()
                        ->default(['notopia'])
                        ->helperText('از فهرست محصولات اصلی شرکت — چندتایی مجاز است')
                        ->columnSpanFull(),
                    Select::make('stage_id')
                        ->label('مرحله قیف')
                        ->options(fn () => OpportunityStage::query()
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->pluck('name', 'id'))
                        ->default(fn () => OpportunityStage::query()
                            ->where('code', 'lead')
                            ->where('is_active', true)
                            ->value('id'))
                        ->required()
                        ->native(false),
                    TextInput::make('title')
                        ->label('عنوان فرصت')
                        ->maxLength(255)
                        ->helperText('خالی = خودکار از نام سازمان')
                        ->columnSpanFull(),
                    TextInput::make('amount')
                        ->label('مبلغ تخمینی (ریال)')
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('source')
                        ->label('منبع')
                        ->placeholder('تماس ورودی / نمایشگاه / معرفی')
                        ->default('ثبت سریع')
                        ->maxLength(255),
                ])
                ->columns(['default' => 1, 'md' => 2]),

            Section::make('گام اول پیگیری')
                ->description('اگر خالی باشد، سیستم پیشنهاد می‌دهد.')
                ->schema([
                    Textarea::make('next_step')
                        ->label('گام بعدی')
                        ->placeholder('خالی = پیشنهاد خودکار بر اساس تماس و محصول')
                        ->rows(2)
                        ->columnSpanFull(),
                    DatePicker::make('next_step_due')
                        ->label('مهلت')
                        ->native(false)
                        ->displayFormat('Y/m/d')
                        ->default(now()->addDays(3))
                        ->required(),
                    Textarea::make('notes')
                        ->label('یادداشت کوتاه')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 1, 'md' => 2]),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['owner_user_id'] = auth()->id();
        $data['followup_user_id'] = auth()->id();

        return app(OpportunityQuickCreate::class)->create($data);
    }

    protected function getRedirectUrl(): string
    {
        return OpportunityResource::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('ثبت سریع')
            ->icon('heroicon-o-bolt');
    }
}
