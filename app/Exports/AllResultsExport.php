<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AllResultsExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithDrawings,
    WithColumnFormatting,
    WithEvents
{
    private const IMG_SUBCOL_CHAR_WIDTH = 12;
    private const ROW_HEIGHT_PX = 110;
    private const PAD_X = 10;
    private const PAD_Y = 10;

    protected array $f;
    protected $rows;
    protected int $rowNum = 0;

    public function __construct(array $filters = [])
    {
        $this->f = $filters;
    }

    private static function px2pt(int $px): float { return $px * 0.75; }
    private static function colCharsToPx(float $chars): int { return (int) floor($chars * 7 + 5); }

    public function collection()
    {
        // Subquery ambil foto PERTAMA (berdasar id) per jenis
        $beforeSub = DB::table('activity_result_photos as p')
            ->selectRaw('p.activity_result_id, SUBSTRING_INDEX(GROUP_CONCAT(p.path ORDER BY p.id ASC SEPARATOR "|"), "|", 1) as before_photo')
            ->where('p.kind', 'before')
            ->groupBy('p.activity_result_id');

        $afterSub = DB::table('activity_result_photos as p')
            ->selectRaw('p.activity_result_id, SUBSTRING_INDEX(GROUP_CONCAT(p.path ORDER BY p.id ASC SEPARATOR "|"), "|", 1) as after_photo')
            ->where('p.kind', 'after')
            ->groupBy('p.activity_result_id');

        $q = DB::table('activity_results as ar')
            ->leftJoin('user_bestrising as u', 'u.id_userBestrising', '=', 'ar.user_id')
            ->leftJoin('regions as r', 'r.id_region', '=', 'u.id_region')
            ->leftJoin('serpos as sp', 'sp.id_serpo', '=', 'u.id_serpo')
            ->leftJoin('activities as act', 'act.id', '=', 'ar.activity_id')
            ->leftJoin('segmens as sg', 'sg.id_segmen', '=', 'ar.id_segmen')
            ->leftJoinSub($beforeSub, 'bf', 'bf.activity_result_id', '=', 'ar.id')
            ->leftJoinSub($afterSub,  'af', 'af.activity_result_id', '=', 'ar.id')
            ->select([
                'ar.id','ar.checklist_id','ar.submitted_at','ar.status',
                // ⬇️ alias dari subquery, menggantikan kolom lama yang sudah tidak ada
                DB::raw('bf.before_photo'),
                DB::raw('af.after_photo'),
                'ar.point_earned','ar.note','ar.created_at',
                DB::raw('COALESCE(u.nama, u.email) as user_nama'),
                DB::raw('act.name as activity_nama'),
                DB::raw('COALESCE(r.nama_region, "-") as region_nama'),
                DB::raw('COALESCE(sp.nama_serpo, "-") as serpo_nama'),
                DB::raw('COALESCE(sg.nama_segmen, "-") as segmen_nama'),
            ]);

        if (!empty($this->f['region'])) $q->where('u.id_region', (int)$this->f['region']);
        if (!empty($this->f['serpo']))  $q->where('u.id_serpo',  (int)$this->f['serpo']);
        if (!empty($this->f['segmen'])) $q->where('ar.id_segmen', (int)$this->f['segmen']);

        if (!empty($this->f['date_from']) || !empty($this->f['date_to'])) {
            $from = $this->f['date_from'] ?? null;
            $to   = $this->f['date_to']   ?? null;
            if ($from && $to)      $q->whereBetween(DB::raw('DATE(ar.submitted_at)'), [$from,$to]);
            elseif ($from)         $q->whereDate('ar.submitted_at','>=',$from);
            elseif ($to)           $q->whereDate('ar.submitted_at','<=',$to);
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
        return [
            'No','User','Activity','Region','Serpo','Segmen','Status','Point','Note',
            'Submitted At','Created At',
            'Before Photo (L)','Before Photo (M)',
            'After Photo (N)','After Photo (O)',
            'Checklist ID','Activity Result ID',
        ];
    }

    public function map($row): array
    {
        $this->rowNum++;

        return [
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
            is_string($row->created_at)   ? $row->created_at   : (optional($row->created_at)->format('Y-m-d H:i:s') ?? ''),
            '', '', '', '',
            $row->checklist_id,
            $row->id,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'J' => NumberFormat::FORMAT_DATE_YYYYMMDD2 . ' hh:mm:ss',
            'K' => NumberFormat::FORMAT_DATE_YYYYMMDD2 . ' hh:mm:ss',
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
                $rowPt   = self::px2pt(self::ROW_HEIGHT_PX);

                for ($i = 0; $i < count($this->rows); $i++) {
                    $sheet->getRowDimension($start + $i)->setRowHeight($rowPt);
                }

                foreach (['L','M','N','O'] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(self::IMG_SUBCOL_CHAR_WIDTH);
                }

                $sheet->mergeCells('L1:M1');
                $sheet->mergeCells('N1:O1');
                $sheet->setCellValue('L1', 'Before Photo');
                $sheet->setCellValue('N1', 'After Photo');

                for ($r = $start; $r <= $lastRow; $r++) {
                    $sheet->mergeCells("L{$r}:M{$r}");
                    $sheet->mergeCells("N{$r}:O{$r}");
                }

                $sheet->getStyle('L1:O'.$lastRow)
                      ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A2:O'.$lastRow)
                      ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }
        ];
    }

    public function drawings(): array
    {
        $drawings = [];
        if (!$this->rows) return $drawings;

        $startRow = 2;

        $subColPx = self::colCharsToPx(self::IMG_SUBCOL_CHAR_WIDTH);
        $areaW    = $subColPx * 2 - self::PAD_X * 2;
        $areaH    = self::ROW_HEIGHT_PX - self::PAD_Y * 2;

        $resolve = function (?string $path) {
            if (!$path) return null;
            $path = ltrim($path, '/');
            $full = Storage::disk('public')->path($path);
            return is_file($full) ? $full : null;
        };

        $place = function (string $file, string $anchorCell) use (&$drawings, $areaW, $areaH) {
            [$w, $h] = @getimagesize($file) ?: [0, 0];
            if ($w <= 0 || $h <= 0) return;

            $scale   = min($areaW / $w, $areaH / $h, 1);
            $finalW  = (int) floor($w * $scale);
            $finalH  = (int) floor($h * $scale);
            $offsetX = max(0, intdiv(($areaW - $finalW), 2) + self::PAD_X);
            $offsetY = max(0, intdiv(($areaH - $finalH), 2) + self::PAD_Y);

            $d = new Drawing();
            $d->setPath($file);
            $d->setResizeProportional(true);
            $d->setWidth($finalW);
            $d->setCoordinates($anchorCell);
            $d->setOffsetX($offsetX);
            $d->setOffsetY($offsetY);

            $drawings[] = $d;
        };

        $i = 0;
        foreach ($this->rows as $row) {
            $excelRow = $startRow + $i;

            if ($file = $resolve($row->before_photo ?? null)) {
                $place($file, 'L'.$excelRow);
            }
            if ($file = $resolve($row->after_photo ?? null)) {
                $place($file, 'N'.$excelRow);
            }
            $i++;
        }

        return $drawings;
    }
}
