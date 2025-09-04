<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class AllResultsExport implements FromCollection, WithHeadings, WithMapping, WithDrawings, WithColumnFormatting, WithEvents
{
    /** ===== Layout foto ===== */
    private const BEFORE_SLOTS = 5;
    private const AFTER_SLOTS  = 5;

    // FIXED size
    private const THUMB_W_PX = 100;
    private const THUMB_H_PX = 120;

    // Padding dalam sel (biar ada jarak di semua sisi)
    private const PAD_X = 3;   // kiri/kanan
    private const PAD_Y = 2;   // atas/bawah

    // Gutter ekstra kecil untuk sisi kanan & bawah (biar gak mepet border)
    private const GUTTER_X_PX = 1;
    private const GUTTER_Y_PX = 1;

    // Row height mengikuti tinggi gambar + padding + gutter
    private const ROW_HEIGHT_PX = self::THUMB_H_PX + (self::PAD_Y * 2) + self::GUTTER_Y_PX;

    /** Lebar kolom teks */
    private const COLW_NO_CH       = 6;   // A
    private const COLW_USER_CH     = 16;  // B
    private const COLW_ACTIVITY_CH = 18;  // C
    private const COLW_REGION_CH   = 14;  // D
    private const COLW_SERPO_CH    = 14;  // E
    private const COLW_SEGMEN_CH   = 14;  // F
    private const COLW_STATUS_CH   = 10;  // G
    private const COLW_POINT_CH    = 6;   // H
    private const COLW_NOTE_CH     = 18;  // I
    private const COLW_SUBMIT_CH   = 20;  // J

    private static function px2pt(int $px): float { return $px * 0.75; }
    private static function px2chars(int $px): float { return max(1, ($px - 5) / 7); }

    protected array $f;
    protected $rows;
    protected int $rowNum = 0;

    public function __construct(array $filters = []) { $this->f = $filters; }

    public function collection()
    {
        $beforeSub = DB::table('activity_result_photos as p')
            ->selectRaw('p.activity_result_id, GROUP_CONCAT(p.path ORDER BY p.id ASC SEPARATOR "|") as before_photos')
            ->where('p.kind', 'before')
            ->groupBy('p.activity_result_id');

        $afterSub = DB::table('activity_result_photos as p')
            ->selectRaw('p.activity_result_id, GROUP_CONCAT(p.path ORDER BY p.id ASC SEPARATOR "|") as after_photos')
            ->where('p.kind', 'after')
            ->groupBy('p.activity_result_id');

        $q = DB::table('activity_results as ar')
            ->leftJoin('user_bestrising as u', 'u.id_userBestrising', '=', 'ar.user_id')
            ->leftJoin('regions as r', 'r.id_region', '=', 'u.id_region')
            ->leftJoin('serpos as sp', 'sp.id_serpo', '=', 'u.id_serpo')
            ->leftJoin('activities as act', 'act.id', '=', 'ar.activity_id')
            ->leftJoin('segmens as sg', 'sg.id_segmen', '=', 'ar.id_segmen')
            ->leftJoinSub($beforeSub, 'bf', 'bf.activity_result_id', '=', 'ar.id')
            ->leftJoinSub($afterSub, 'af', 'af.activity_result_id', '=', 'ar.id')
            ->select([
                'ar.id','ar.checklist_id','ar.submitted_at','ar.status',
                DB::raw('bf.before_photos'),
                DB::raw('af.after_photos'),
                'ar.point_earned','ar.note','ar.created_at',
                DB::raw('COALESCE(u.nama, u.email) as user_nama'),
                DB::raw('act.name as activity_nama'),
                DB::raw('COALESCE(r.nama_region, "-") as region_nama'),
                DB::raw('COALESCE(sp.nama_serpo, "-") as serpo_nama'),
                DB::raw('COALESCE(sg.nama_segmen, "-") as segmen_nama'),
            ]);

        if (!empty($this->f['region'])) $q->where('u.id_region', (int)$this->f['region']);
        if (!empty($this->f['serpo']))  $q->where('u.id_serpo', (int)$this->f['serpo']);
        if (!empty($this->f['segmen'])) $q->where('ar.id_segmen', (int)$this->f['segmen']);

        if (!empty($this->f['date_from']) || !empty($this->f['date_to'])) {
            $from = $this->f['date_from'] ?? null;
            $to   = $this->f['date_to']   ?? null;
            if ($from && $to)       $q->whereBetween(DB::raw('DATE(ar.submitted_at)'), [$from, $to]);
            elseif ($from)          $q->whereDate('ar.submitted_at', '>=', $from);
            elseif ($to)            $q->whereDate('ar.submitted_at', '<=', $to);
        }

        if (!empty($this->f['keyword'])) {
            $kw = '%'.$this->f['keyword'].'%';
            $q->where(function ($w) use ($kw) {
                $w->where('u.nama','like',$kw)
                  ->orWhere('u.email','like',$kw)
                  ->orWhere('act.name','like',$kw)
                  ->orWhere('r.nama_region','like',$kw)
                  ->orWhere('sp.nama_serpo','like',$kw);
            });
        }

        $this->rows = $q->orderByDesc('ar.created_at')->get();
        return $this->rows;
    }

    public function headings(): array
    {
        $heads = ['No','User','Activity','Region','Serpo','Segmen','Status','Point','Note','Submitted At'];
        for ($i=1; $i<=self::BEFORE_SLOTS; $i++) $heads[] = "Before #{$i}";
        for ($i=1; $i<=self::AFTER_SLOTS;  $i++) $heads[] = "After #{$i}";
        return $heads;
    }

    public function map($row): array
    {
        $this->rowNum++;

        $mapped = [
            $this->rowNum,
            $row->user_nama,
            $row->activity_nama,
            $row->region_nama,
            $row->serpo_nama,
            $row->segmen_nama,
            $row->status,
            (int)$row->point_earned,
            $row->note ?? '',
            is_string($row->submitted_at) ? $row->submitted_at : (optional($row->submitted_at)->format('Y-m-d H:i:s') ?? ''),
        ];

        // placeholder kolom gambar
        for ($i=0; $i<(self::BEFORE_SLOTS + self::AFTER_SLOTS); $i++) $mapped[] = '';

        return $mapped;
    }

    public function columnFormats(): array
    {
        return [
            'J' => NumberFormat::FORMAT_DATE_YYYYMMDD2 . ' hh:mm:ss',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                if (!$this->rows) return;

                $sheet   = $event->sheet->getDelegate();
                $start   = 2;
                $lastRow = $start - 1 + count($this->rows);

                // Lebar kolom teks
                $sheet->getColumnDimension('A')->setWidth(self::COLW_NO_CH);
                $sheet->getColumnDimension('B')->setWidth(self::COLW_USER_CH);
                $sheet->getColumnDimension('C')->setWidth(self::COLW_ACTIVITY_CH);
                $sheet->getColumnDimension('D')->setWidth(self::COLW_REGION_CH);
                $sheet->getColumnDimension('E')->setWidth(self::COLW_SERPO_CH);
                $sheet->getColumnDimension('F')->setWidth(self::COLW_SEGMEN_CH);
                $sheet->getColumnDimension('G')->setWidth(self::COLW_STATUS_CH);
                $sheet->getColumnDimension('H')->setWidth(self::COLW_POINT_CH);
                $sheet->getColumnDimension('I')->setWidth(self::COLW_NOTE_CH);
                $sheet->getColumnDimension('J')->setWidth(self::COLW_SUBMIT_CH);

                // Lebar kolom foto (K..)
                $cellWpx     = self::THUMB_W_PX + self::PAD_X * 2 + self::GUTTER_X_PX;
                $thumbCharW  = self::px2chars($cellWpx);   // cukup longgar sedikit untuk right-gap
                $firstImgIdx = 11; // K
                $totalImg    = self::BEFORE_SLOTS + self::AFTER_SLOTS;
                for ($i=0; $i<$totalImg; $i++) {
                    $col = Coordinate::stringFromColumnIndex($firstImgIdx + $i);
                    $sheet->getColumnDimension($col)->setWidth($thumbCharW);
                }

                // Tinggi baris data
                $rowPt = self::px2pt(self::ROW_HEIGHT_PX);
                for ($r=$start; $r<=$lastRow; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight($rowPt);
                }

                // Header style
                $lastCol = Coordinate::stringFromColumnIndex($firstImgIdx + $totalImg - 1);
                $sheet->getStyle("A1:{$lastCol}1")->getAlignment()
                      ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                      ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A1:{$lastCol}1")->getFill()->setFillType(Fill::FILL_SOLID)
                      ->getStartColor()->setRGB('93C47D');

                // Wrap note
                $sheet->getStyle("I2:I{$lastRow}")->getAlignment()->setWrapText(true);

                // Freeze & AutoFilter
                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastCol}1");

                // Border tipis
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")
                      ->getBorders()->getAllBorders()
                      ->setBorderStyle(Border::BORDER_THIN);

                // Rata kiri area foto (offset di drawings)
                $sheet->getStyle(
                    Coordinate::stringFromColumnIndex($firstImgIdx) . "2:" . $lastCol . $lastRow
                )->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            }
        ];
    }

    public function drawings(): array
    {
        $drawings = [];
        if (!$this->rows) return $drawings;

        $resolve = function (?string $path) {
            if (!$path) return null;
            $path = ltrim($path, '/');
            $full = Storage::disk('public')->path($path);
            return is_file($full) ? $full : null;
        };

        $startRow = 2;
        $rowIdx   = 0;

        foreach ($this->rows as $row) {
            $excelRow = $startRow + $rowIdx;

            $before = array_values(array_filter(explode('|', (string)($row->before_photos ?? ''))));
            $after  = array_values(array_filter(explode('|', (string)($row->after_photos  ?? ''))));

            // BEFORE: K..O
            for ($i=0; $i<self::BEFORE_SLOTS; $i++) {
                if (!isset($before[$i])) break;
                $file = $resolve($before[$i]);
                if (!$file) continue;

                $colIndex  = 11 + $i;
                $colLetter = Coordinate::stringFromColumnIndex($colIndex);

                $d = new Drawing();
                $d->setPath($file);
                $d->setResizeProportional(false); // 100x120 persis
                $d->setWidth(self::THUMB_W_PX);
                $d->setHeight(self::THUMB_H_PX);
                $d->setCoordinates($colLetter.$excelRow);
                // nempel kiri/atas sesuai padding → ada gap tipis di kanan & bawah karena gutter
                $d->setOffsetX(self::PAD_X);
                $d->setOffsetY(self::PAD_Y);
                $drawings[] = $d;
            }

            // AFTER: P..T
            for ($i=0; $i<self::AFTER_SLOTS; $i++) {
                if (!isset($after[$i])) break;
                $file = $resolve($after[$i]);
                if (!$file) continue;

                $colIndex  = 11 + self::BEFORE_SLOTS + $i;
                $colLetter = Coordinate::stringFromColumnIndex($colIndex);

                $d = new Drawing();
                $d->setPath($file);
                $d->setResizeProportional(false);
                $d->setWidth(self::THUMB_W_PX);
                $d->setHeight(self::THUMB_H_PX);
                $d->setCoordinates($colLetter.$excelRow);
                $d->setOffsetX(self::PAD_X);
                $d->setOffsetY(self::PAD_Y);
                $drawings[] = $d;
            }

            $rowIdx++;
        }

        return $drawings;
    }
}
