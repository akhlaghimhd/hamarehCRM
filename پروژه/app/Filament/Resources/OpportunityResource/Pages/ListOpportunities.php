<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\OpportunityResource;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Services\OpportunityQuickCreate;
use App\Support\Fa;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListOpportunities extends ListRecords
{
    protected static string $resource = OpportunityResource::class;

    protected static ?string $title = 'فرصت‌ها';

    public function getSubheading(): ?string
    {
        $count = $this->getFilteredTableQuery()?->count() ?? 0;

        return 'همه فرصت‌ها · '.Fa::digits((string) $count).' مورد';
    }

    public function getFooter(): ?View
    {
        return view('filament.resources.opportunity-resource.pages.list-opportunities-filter-styles');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importExcel')
                ->label('ورود از اکسل')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(fn () => OpportunityResource::canCreate())
                ->modalHeading('ورود فرصت از اکسل')
                ->modalDescription('ستون‌ها مطابق فرم ثبت سریع: organization_name, contact_name, mobile, phone, email, product_interest, stage_code, amount, source, next_step, next_step_due, notes')
                ->form([
                    FileUpload::make('file')
                        ->label('فایل Excel')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ])
                        ->required()
                        ->disk('local')
                        ->directory('imports/opportunities')
                        ->visibility('private'),
                ])
                ->action(function (array $data): void {
                    $path = Storage::disk('local')->path($data['file']);
                    $sheet = IOFactory::load($path)->getActiveSheet();
                    $rows = $sheet->toArray(null, true, true, true);
                    if ($rows === []) {
                        Notification::make()->title('فایل خالی است')->danger()->send();

                        return;
                    }

                    $header = array_shift($rows);
                    $map = [];
                    foreach ($header as $col => $name) {
                        $key = strtolower(trim((string) $name));
                        if ($key !== '') {
                            $map[$key] = $col;
                        }
                    }

                    $created = 0;
                    $failed = 0;
                    $service = app(OpportunityQuickCreate::class);
                    $defaultStage = OpportunityStage::query()->where('is_active', true)->orderBy('sort_order')->value('id');

                    foreach ($rows as $row) {
                        $get = function (string $key) use ($map, $row) {
                            $col = $map[$key] ?? null;
                            if ($col === null) {
                                return null;
                            }
                            $v = $row[$col] ?? null;

                            return is_string($v) ? trim($v) : $v;
                        };

                        $org = $get('organization_name') ?? $get('سازمان') ?? null;
                        $mobile = $get('mobile') ?? $get('موبایل') ?? null;
                        if (blank($org) || blank($mobile)) {
                            $failed++;

                            continue;
                        }

                        $stageCode = $get('stage_code') ?? $get('stage') ?? null;
                        $stageId = $defaultStage;
                        if (filled($stageCode)) {
                            $stageId = OpportunityStage::query()
                                ->where('code', $stageCode)
                                ->orWhere('name', $stageCode)
                                ->value('id') ?: $defaultStage;
                        }

                        $products = $get('product_interest') ?? $get('محصول') ?? null;
                        $productList = [];
                        if (filled($products)) {
                            $productList = array_values(array_filter(array_map('trim', preg_split('/[,|،]/', (string) $products) ?: [])));
                        }

                        try {
                            $service->create([
                                'organization_name' => (string) $org,
                                'contact_name' => $get('contact_name') ?? $get('مخاطب'),
                                'mobile' => (string) $mobile,
                                'phone' => $get('phone'),
                                'email' => $get('email'),
                                'title' => $get('title') ?: ('فرصت '.$org),
                                'product_interest' => $productList,
                                'stage_id' => $stageId,
                                'amount' => $get('amount'),
                                'source' => $get('source') ?: 'ورود اکسل',
                                'next_step' => $get('next_step'),
                                'next_step_due' => $get('next_step_due') ?: now()->addDays(3)->toDateString(),
                                'notes' => $get('notes'),
                                'owner_user_id' => auth()->id(),
                                'followup_user_id' => auth()->id(),
                            ]);
                            $created++;
                        } catch (\Throwable $e) {
                            $failed++;
                        }
                    }

                    Notification::make()
                        ->title('ورود اکسل انجام شد')
                        ->body('موفق: '.Fa::digits((string) $created).' · ناموفق: '.Fa::digits((string) $failed))
                        ->success()
                        ->send();
                }),

            Action::make('quickCreate')
                ->label('ثبت سریع')
                ->icon('heroicon-o-bolt')
                ->color('gray')
                ->url(OpportunityResource::getUrl('quick-create'))
                ->visible(fn () => OpportunityResource::canCreate()),

            CreateAction::make()
                ->label('فرصت')
                ->icon('heroicon-o-plus')
                ->visible(fn () => OpportunityResource::canCreate()),

            Action::make('exportExcel')
                ->label('')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->tooltip('خروجی اکسل از نتایج فیلترشده')
                ->action(function (): StreamedResponse {
                    $query = $this->getFilteredTableQuery();
                    $rows = $query
                        ->with(['stage', 'owner', 'followupUser'])
                        ->orderByDesc('updated_at')
                        ->get();

                    $spreadsheet = new Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();
                    $headers = [
                        'code', 'organization_name', 'contact_name', 'mobile', 'product', 'status',
                        'stage', 'next_step', 'next_step_due', 'owner', 'followup', 'amount', 'source',
                    ];
                    foreach ($headers as $i => $h) {
                        $sheet->setCellValue([$i + 1, 1], $h);
                    }

                    $r = 2;
                    foreach ($rows as $opp) {
                        /** @var Opportunity $opp */
                        $sheet->setCellValue([1, $r], 'OPP-'.str_pad((string) $opp->id, 3, '0', STR_PAD_LEFT));
                        $sheet->setCellValue([2, $r], $opp->organization_name);
                        $sheet->setCellValue([3, $r], $opp->contact_name);
                        $sheet->setCellValue([4, $r], $opp->mobile);
                        $sheet->setCellValue([5, $r], $opp->productInterestLabel());
                        $sheet->setCellValue([6, $r], Opportunity::statusOptions()[$opp->status] ?? $opp->status);
                        $sheet->setCellValue([7, $r], $opp->stage?->name);
                        $sheet->setCellValue([8, $r], $opp->next_step);
                        $sheet->setCellValue([9, $r], optional($opp->next_step_due)?->format('Y-m-d'));
                        $sheet->setCellValue([10, $r], $opp->owner?->name);
                        $sheet->setCellValue([11, $r], $opp->followupUser?->name);
                        $sheet->setCellValue([12, $r], $opp->amount);
                        $sheet->setCellValue([13, $r], $opp->source);
                        $r++;
                    }

                    $writer = new Xlsx($spreadsheet);

                    return response()->streamDownload(function () use ($writer): void {
                        $writer->save('php://output');
                    }, 'opportunities-'.now()->format('Ymd-His').'.xlsx');
                }),
        ];
    }
}
