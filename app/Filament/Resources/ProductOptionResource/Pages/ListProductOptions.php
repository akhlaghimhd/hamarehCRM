<?php

namespace App\Filament\Resources\ProductOptionResource\Pages;

use App\Filament\Concerns\HasNotopiaBreadcrumbs;
use App\Filament\Resources\ProductOptionResource;
use App\Models\ProductOption;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListProductOptions extends ListRecords
{
    use HasNotopiaBreadcrumbs;

    protected static string $resource = ProductOptionResource::class;

    protected static ?string $title = 'اقلام و آپشن‌ها';

    public function getBreadcrumbs(): array
    {
        return $this->notopiaBreadcrumbs(['' => 'اقلام و آپشن‌ها']);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('قلم جدید')
                ->visible(fn () => ProductOptionResource::canCreate()),
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

    protected function exportRows()
    {
        return ProductOption::query()
            ->where('product_line_id', \App\Models\ProductLine::query()->where('code', 'notopia')->value('id'))
            ->withCount(['customAttributes', 'variants'])
            ->orderBy('sort')
            ->orderBy('name')
            ->get();
    }

    protected function exportExcel(): StreamedResponse
    {
        $rows = $this->exportRows();
        $filename = 'product-options-'.date('Ymd-His').'.xlsx';
        $generated = $this->jalaliNow();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('اقلام');

        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'گزارش اقلام و آپشن‌های نوتوپیا');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'تاریخ خروجی');
        $sheet->setCellValue('B3', $generated);
        $sheet->setCellValue('A4', 'تعداد قلم');
        $sheet->setCellValue('B4', $rows->count());
        $sheet->getStyle('A3:A4')->getFont()->setBold(true);

        $headerRow = 6;
        $headers = ['ردیف', 'نام', 'کد', 'نوع', 'مدل‌ها', 'ویژگی‌ها', 'ترکیب‌ها', 'قیمت پیش‌فرض', 'فعال'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).$headerRow, $h);
        }
        $hr = 'A'.$headerRow.':I'.$headerRow;
        $sheet->getStyle($hr)->getFont()->setBold(true);
        $sheet->getStyle($hr)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
        $sheet->getStyle($hr)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $r = $headerRow + 1;
        $n = 1;
        foreach ($rows as $row) {
            $type = match ($row->type) {
                'addon' => 'لوازم جانبی',
                'nfc' => 'NFC',
                'software' => 'نرم‌افزار',
                'other' => 'سایر',
                default => $row->type ?: '—',
            };
            $sheet->setCellValue('A'.$r, $n++);
            $sheet->setCellValue('B'.$r, $row->name);
            $sheet->setCellValue('C'.$r, $row->code);
            $sheet->setCellValue('D'.$r, $type);
            $sheet->setCellValue('E'.$r, $row->model_scope === 'all' ? 'همه' : 'انتخابی');
            $sheet->setCellValue('F'.$r, (int) $row->custom_attributes_count);
            $sheet->setCellValue('G'.$r, (int) $row->variants_count);
            $sheet->setCellValue('H'.$r, $row->unit_price !== null ? (float) $row->unit_price : null);
            $sheet->setCellValue('I'.$r, $row->is_active ? 'بله' : 'خیر');
            if ($row->unit_price !== null) {
                $sheet->getStyle('H'.$r)->getNumberFormat()->setFormatCode('#,##0');
            }
            $sheet->getStyle('A'.$r.':I'.$r)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $r++;
        }
        foreach (range(1, 9) as $c) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function exportPdf(): StreamedResponse
    {
        $rows = $this->exportRows();
        $generated = e($this->jalaliNow());
        $count = $rows->count();

        $html = '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8">';
        $html .= '<title>گزارش اقلام و آپشن‌ها</title>';
        $html .= '<style>
            body{font-family:Tahoma,Arial,sans-serif;font-size:12px;margin:28px;line-height:1.7}
            h1{font-size:18px;margin:0 0 8px}
            .meta{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;margin:12px 0 20px}
            table{width:100%;border-collapse:collapse}
            th,td{border:1px solid #cbd5e1;padding:8px 10px;text-align:right}
            th{background:#e2e8f0}
            tr:nth-child(even){background:#f8fafc}
            .num{text-align:center;direction:ltr}
            .foot{margin-top:16px;color:#64748b;font-size:11px}
            @media print{body{margin:12px}}
        </style></head><body>';
        $html .= '<h1>گزارش اقلام و آپشن‌های نوتوپیا</h1>';
        $html .= '<div class="meta">تاریخ خروجی: '.$generated.' — تعداد قلم: '.$count.'</div>';
        $html .= '<table><thead><tr>';
        $html .= '<th class="num">ردیف</th><th>نام</th><th>کد</th><th>نوع</th><th>مدل‌ها</th><th>ویژگی</th><th>ترکیب</th><th>فعال</th>';
        $html .= '</tr></thead><tbody>';
        $i = 1;
        foreach ($rows as $row) {
            $type = match ($row->type) {
                'addon' => 'لوازم جانبی',
                'nfc' => 'NFC',
                'software' => 'نرم‌افزار',
                'other' => 'سایر',
                default => $row->type ?: '—',
            };
            $html .= '<tr>';
            $html .= '<td class="num">'.$i++.'</td>';
            $html .= '<td>'.e($row->name).'</td>';
            $html .= '<td class="num">'.e($row->code).'</td>';
            $html .= '<td>'.e($type).'</td>';
            $html .= '<td>'.($row->model_scope === 'all' ? 'همه' : 'انتخابی').'</td>';
            $html .= '<td class="num">'.(int) $row->custom_attributes_count.'</td>';
            $html .= '<td class="num">'.(int) $row->variants_count.'</td>';
            $html .= '<td>'.($row->is_active ? 'بله' : 'خیر').'</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table><p class="foot">مای‌آفیس — خروجی سیستم مدیریت</p>';
        $html .= '<script>window.onload=function(){window.print()}</script></body></html>';

        return response()->streamDownload(function () use ($html) {
            echo $html;
        }, 'product-options-'.date('Ymd-His').'.html', [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }


}
