<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\OpportunityResource;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Services\OpportunityQuickCreate;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class QuickCreateOpportunity extends Page
{
    protected static string $resource = OpportunityResource::class;

    protected static ?string $title = 'ثبت سریع فرصت';

    protected static ?string $navigationLabel = 'ثبت سریع فرصت';

    /**
     * @var view-string
     */
    protected string $view = 'filament.resources.opportunity-resource.pages.quick-create-opportunity';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function canAccess(): bool
    {
        return OpportunityResource::canCreate();
    }

    public function mount(): void
    {
        $leadId = OpportunityStage::query()
            ->where('code', 'lead')
            ->where('is_active', true)
            ->value('id');

        $this->form->fill([
            'stage_id' => $leadId,
            'product_interest' => 'notopia_yearbook',
            'next_step_due' => now()->addDays(3)->format('Y-m-d'),
            'owner_user_id' => Auth::id(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->description('مرحله پیش‌فرض: سرنخ اولیه — قابل تغییر.')
                    ->schema([
                        Select::make('product_interest')
                            ->label('محصول')
                            ->options(Opportunity::productInterestOptions())
                            ->native(false)
                            ->required(),
                        Select::make('stage_id')
                            ->label('مرحله قیف')
                            ->options(fn () => OpportunityStage::query()
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->pluck('name', 'id'))
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
                            ->maxLength(255),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),

                Section::make('گام اول پیگیری')
                    ->description('اگر خالی بگذارید، سیستم پیشنهاد می‌دهد.')
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
                            ->required(),
                        Textarea::make('notes')
                            ->label('یادداشت کوتاه')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('create')
                ->label('ثبت سریع')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->action('create'),
            Action::make('full')
                ->label('فرم کامل')
                ->color('gray')
                ->url(OpportunityResource::getUrl('create'))
                ->outlined(),
        ];
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $opportunity = app(OpportunityQuickCreate::class)->create($data);

        Notification::make()
            ->title('فرصت ثبت شد')
            ->body($opportunity->title . ' — مشتری: ' . ($opportunity->customer?->name ?? '—'))
            ->success()
            ->send();

        $this->redirect(OpportunityResource::getUrl('edit', ['record' => $opportunity]));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fullForm')
                ->label('فرم کامل فرصت')
                ->url(OpportunityResource::getUrl('create'))
                ->color('gray'),
        ];
    }
}
