<?php

namespace App\Filament\Resources\ProductOptionResource\RelationManagers;

use App\Models\ProductOptionVariant;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Support\Fa;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'قیمت و موجودی هر ترکیب';

    protected static ?string $modelLabel = 'ترکیب';

    protected static ?string $pluralModelLabel = 'ترکیب‌ها';

    public function form(Schema $schema): Schema
    {
        $option = $this->getOwnerRecord();
        $dims = $option->customAttributes()
            ->where('affects_inventory', true)
            ->orderBy('sort')
            ->get();

        $dimFields = [];
        foreach ($dims as $dim) {
            $opts = $dim->options_json ?? [];
            $choices = [];
            foreach ($opts as $o) {
                $val = is_array($o) ? ($o['value'] ?? $o['label'] ?? null) : $o;
                if ($val !== null && $val !== '') {
                    $choices[(string) $val] = (string) $val;
                }
            }

            $dimFields[] = Select::make('combination.'.$dim->key)
                ->label($dim->label)
                ->options($choices)
                ->required()
                ->native(false);
        }

        if ($dimFields === []) {
            return $schema->components([
                Section::make('توجه')
                    ->description('ابتدا در تب «ویژگی‌های قابل‌انتخاب» حداقل یک ویژگی با تیک «مؤثر در قیمت و موجودی» تعریف و ذخیره کنید.')
                    ->schema([]),
            ]);
        }

        return $schema->components([
            Section::make('مشخصات ترکیب')
                ->schema($dimFields)
                ->columns(2),
            Section::make('قیمت و موجودی')
                ->schema([
                    TextInput::make('unit_price')->label('قیمت واحد')->numeric()->prefix('ریال')->required(),
                    TextInput::make('stock_qty')->label('موجودی')->numeric()->default(0)->required(),
                    TextInput::make('sku')->label('کد فرعی')->placeholder('مثلاً PEN-BLUE'),
                    Toggle::make('is_active')->label('فعال')->default(true)->inline(false),
                ])
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label('ترکیب')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->wrap(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('قیمت واحد')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('stock_qty')
                    ->label('موجودی')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => (int) $state > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('sku')
                    ->label('کد')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخرین تغییر')
                    ->formatStateUsing(fn ($state) => Fa::dateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('label')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('وضعیت')
                    ->trueLabel('فقط فعال')
                    ->falseLabel('فقط غیرفعال')
                    ->placeholder('همه'),
                SelectFilter::make('stock')
                    ->label('موجودی')
                    ->options([
                        'in' => 'موجود (بیشتر از صفر)',
                        'out' => 'ناموجود',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'in' => $query->where('stock_qty', '>', 0),
                            'out' => $query->where('stock_qty', '<=', 0),
                            default => $query,
                        };
                    }),
            ])
            ->filtersFormColumns(2)
            ->emptyStateHeading('ترکیبی ثبت نشده')
            ->emptyStateDescription('با «ساخت خودکار» همه حالت‌ها را بسازید، بعد قیمت و موجودی را پر کنید.')
            ->headerActions([
                CreateAction::make()
                    ->label('افزودن')
                    ->modalHeading('افزودن ترکیب')
                    ->modalSubmitActionLabel('ذخیره')
                    ->mutateFormDataUsing(fn (array $data) => $this->normalizeCombination($data)),
                Action::make('generate')
                    ->label('ساخت خودکار')
                    ->icon('heroicon-o-sparkles')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('ساخت ترکیب‌ها')
                    ->modalDescription('از روی گزینه‌های ویژگی‌های مؤثر، همه حالت‌ها ساخته می‌شود.')
                    ->action(fn () => $this->generateCombinations()),
                ActionGroup::make([
                    Action::make('export_excel')
                        ->label('Excel (.xlsx)')
                        ->icon('heroicon-o-table-cells')
                        ->action(fn () => $this->exportExcel()),
                    Action::make('export_pdf')
                        ->label('PDF / چاپ')
                        ->icon('heroicon-o-printer')
                        ->action(fn () => $this->exportPdf()),
                ])
                    ->label('خروجی')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->button()
                    ->outlined()
                    ->color('gray'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('ویرایش')
                    ->modalHeading('ویرایش ترکیب')
                    ->modalSubmitActionLabel('ذخیره')
                    ->mutateFormDataUsing(fn (array $data) => $this->normalizeCombination($data))
                    ->fillForm(fn ($record) => [
                        'combination' => $record->combination ?? [],
                        'sku' => $record->sku,
                        'unit_price' => $record->unit_price,
                        'stock_qty' => $record->stock_qty,
                        'is_active' => $record->is_active,
                        'label' => $record->label,
                    ]),
                DeleteAction::make()
                    ->label('حذف')
                    ->modalHeading('حذف ترکیب'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف انتخاب‌شده‌ها'),
                    BulkAction::make('activate')
                        ->label('فعال‌سازی')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label('غیرفعال‌سازی')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    protected function normalizeCombination(array $data): array
    {
        $combination = Arr::get($data, 'combination', []);
        if (is_array($combination) && $combination !== []) {
            $data['combination'] = $combination;
            $data['label'] = ProductOptionVariant::makeLabel($combination);
        }

        return $data;
    }

    protected function generateCombinations(): void
    {
        $option = $this->getOwnerRecord();
        $dims = $option->customAttributes()
            ->where('affects_inventory', true)
            ->orderBy('sort')
            ->get();

        if ($dims->isEmpty()) {
            Notification::make()->warning()->title('ویژگی مؤثر ندارید')->send();

            return;
        }

        $sets = [];
        foreach ($dims as $dim) {
            $values = collect($dim->options_json ?? [])
                ->map(fn ($o) => is_array($o) ? ($o['value'] ?? $o['label'] ?? null) : $o)
                ->filter(fn ($v) => $v !== null && $v !== '')
                ->values()
                ->all();
            if ($values === []) {
                Notification::make()->warning()->title('گزینه خالی: '.$dim->label)->send();

                return;
            }
            $sets[] = ['key' => $dim->key, 'values' => $values];
        }

        $combos = [[]];
        foreach ($sets as $set) {
            $next = [];
            foreach ($combos as $combo) {
                foreach ($set['values'] as $val) {
                    $next[] = array_merge($combo, [$set['key'] => $val]);
                }
            }
            $combos = $next;
        }

        $created = 0;
        foreach ($combos as $combo) {
            $exists = $option->variants->contains(fn ($v) => ($v->combination ?? []) == $combo);
            if ($exists) {
                continue;
            }
            $option->variants()->create([
                'combination' => $combo,
                'label' => ProductOptionVariant::makeLabel($combo),
                'stock_qty' => 0,
                'is_active' => true,
            ]);
            $created++;
        }
        $option->unsetRelation('variants');

        Notification::make()
            ->success()
            ->title($created ? "{$created} ترکیب اضافه شد" : 'ترکیب جدیدی لازم نبود')
            ->send();
    }

    protected function exportMeta(): array
    {
        $option = $this->getOwnerRecord();
        $rows = $option->variants()->orderBy('label')->get();

        return [
            'option' => $option,
            'rows' => $rows,
            'title' => 'گزارش قیمت و موجودی ترکیب‌ها',
            'product' => $option->name,
            'code' => $option->code,
            'count' => $rows->count(),
            'total_stock' => (int) $rows->sum('stock_qty'),
            'generated_at' => $this->jalaliNow(),
        ];
    }

    protected function jalaliNow(): string
    {
        try {
            if (class_exists(\Morilog\Jalali\Jalalian::class)) {
                return \Morilog\Jalali\Jalalian::now()->format('Y/m/d H:i');
            }
        } catch (\Throwable $e) {
        }

        return now()->format('Y-m-d H:i');
    }

    protected function exportExcel(): StreamedResponse
    {
        $meta = $this->exportMeta();
        $filename = 'variants-'.($meta['code'] ?? $meta['option']->id).'-'.date('Ymd-His').'.xlsx';

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('ترکیب‌ها');

        // عنوان
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', $meta['title']);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // متادیتا
        $sheet->setCellValue('A3', 'قلم');
        $sheet->setCellValue('B3', $meta['product']);
        $sheet->setCellValue('A4', 'کد قلم');
        $sheet->setCellValue('B4', $meta['code'] ?? '—');
        $sheet->setCellValue('A5', 'تاریخ خروجی');
        $sheet->setCellValue('B5', $meta['generated_at']);
        $sheet->setCellValue('A6', 'تعداد ترکیب');
        $sheet->setCellValue('B6', $meta['count']);
        $sheet->setCellValue('A7', 'جمع موجودی');
        $sheet->setCellValue('B7', $meta['total_stock']);

        $sheet->getStyle('A3:A7')->getFont()->setBold(true);
        $sheet->getStyle('A3:A7')->getFont()->getColor()->setRGB('64748B');

        // هدر جدول
        $headerRow = 9;
        $headers = ['ردیف', 'ترکیب', 'کد فرعی', 'قیمت واحد (ریال)', 'موجودی', 'فعال'];
        foreach ($headers as $i => $header) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col.$headerRow, $header);
        }
        $headerRange = 'A'.$headerRow.':F'.$headerRow;
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2E8F0');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $rowNum = $headerRow + 1;
        $i = 1;
        foreach ($meta['rows'] as $row) {
            $sheet->setCellValue('A'.$rowNum, $i++);
            $sheet->setCellValue('B'.$rowNum, $row->label);
            $sheet->setCellValue('C'.$rowNum, $row->sku ?: '—');
            $sheet->setCellValue('D'.$rowNum, $row->unit_price !== null ? (float) $row->unit_price : null);
            $sheet->setCellValue('E'.$rowNum, (int) $row->stock_qty);
            $sheet->setCellValue('F'.$rowNum, $row->is_active ? 'بله' : 'خیر');

            $sheet->getStyle('A'.$rowNum.':F'.$rowNum)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle('A'.$rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D'.$rowNum.':E'.$rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F'.$rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($row->unit_price !== null) {
                $sheet->getStyle('D'.$rowNum)->getNumberFormat()->setFormatCode('#,##0');
            }

            $rowNum++;
        }

        foreach (range(1, 6) as $col) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function exportPdf(): StreamedResponse
    {
        $meta = $this->exportMeta();
        $title = e($meta['title']);
        $product = e($meta['product']);
        $code = e($meta['code'] ?? '—');
        $generated = e($meta['generated_at']);
        $count = $meta['count'];
        $totalStock = $meta['total_stock'];

        $html = '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8">';
        $html .= '<title>'.$title.' — '.$product.'</title>';
        $html .= '<style>
            body{font-family:Tahoma,"Segoe UI",Arial,sans-serif;font-size:12px;color:#111;margin:28px;line-height:1.7}
            h1{font-size:18px;margin:0 0 4px;font-weight:700}
            .meta{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;margin:12px 0 20px}
            .meta table{width:100%;border-collapse:collapse}
            .meta td{padding:4px 8px;border:none}
            .meta td:first-child{color:#64748b;width:140px}
            table.data{width:100%;border-collapse:collapse}
            table.data th,table.data td{border:1px solid #cbd5e1;padding:8px 10px;text-align:right}
            table.data th{background:#e2e8f0;font-weight:700}
            table.data tr:nth-child(even){background:#f8fafc}
            .num{text-align:center;direction:ltr}
            .foot{margin-top:16px;color:#64748b;font-size:11px}
            @media print{body{margin:12px}}
        </style></head><body>';
        $html .= '<h1>'.$title.'</h1>';
        $html .= '<div class="meta"><table>';
        $html .= '<tr><td>قلم</td><td><strong>'.$product.'</strong></td></tr>';
        $html .= '<tr><td>کد قلم</td><td>'.$code.'</td></tr>';
        $html .= '<tr><td>تاریخ خروجی</td><td>'.$generated.'</td></tr>';
        $html .= '<tr><td>تعداد ترکیب</td><td>'.$count.'</td></tr>';
        $html .= '<tr><td>جمع موجودی</td><td>'.$totalStock.'</td></tr>';
        $html .= '</table></div>';
        $html .= '<table class="data"><thead><tr>';
        $html .= '<th class="num">ردیف</th><th>ترکیب</th><th>کد فرعی</th><th>قیمت واحد (ریال)</th><th>موجودی</th><th>فعال</th>';
        $html .= '</tr></thead><tbody>';

        $i = 1;
        foreach ($meta['rows'] as $row) {
            $html .= '<tr>';
            $html .= '<td class="num">'.$i++.'</td>';
            $html .= '<td>'.e($row->label).'</td>';
            $html .= '<td class="num">'.e($row->sku ?: '—').'</td>';
            $html .= '<td class="num">'.e($row->unit_price !== null ? number_format((float) $row->unit_price) : '—').'</td>';
            $html .= '<td class="num">'.e((string) $row->stock_qty).'</td>';
            $html .= '<td>'.($row->is_active ? 'بله' : 'خیر').'</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $html .= '<p class="foot">مای‌آفیس — خروجی سیستم مدیریت</p>';
        $html .= '<script>window.onload=function(){window.print()}</script>';
        $html .= '</body></html>';

        $filename = 'variants-'.($meta['code'] ?? $meta['option']->id).'-'.date('Ymd-His').'.html';

        return response()->streamDownload(function () use ($html) {
            echo $html;
        }, $filename, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
