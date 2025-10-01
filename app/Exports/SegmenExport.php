<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

// sesuaikan namespace model lo ya
use App\Models\Segmen;

class SegmenExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithStyles
{
    use Exportable;

    protected ?string $id_region;
    protected ?string $id_serpo;
    protected ?string $keyword;

    public function __construct($id_region = null, $id_serpo = null, $keyword = null)
    {
        $this->id_region = $id_region ?: null;
        $this->id_serpo  = $id_serpo  ?: null;
        $this->keyword   = $keyword   ?: null;
    }

    public function query()
    {
        // asumsikan struktur:
        // segmens: id_segmen, id_serpo, nama_segmen, created_at
        // serpos:  id_serpo, id_region, nama_serpo
        // regions: id_region, nama_region
        return Segmen::query()
            ->select([
                'regions.nama_region as region',
                'serpos.nama_serpo as serpo',
                'segmens.nama_segmen as nama_segmen',
                'segmens.created_at as created_at',
            ])
            ->leftJoin('serpos', 'segmens.id_serpo', '=', 'serpos.id_serpo')
            ->leftJoin('regions', 'serpos.id_region', '=', 'regions.id_region')
            ->when($this->id_region, function (Builder $q) {
                $q->where('serpos.id_region', $this->id_region);
            })
            ->when($this->id_serpo, function (Builder $q) {
                $q->where('segmens.id_serpo', $this->id_serpo);
            })
            ->when($this->keyword, function (Builder $q) {
                $kw = "%{$this->keyword}%";
                $q->where(function (Builder $qq) use ($kw) {
                    $qq->where('segmens.nama_segmen', 'like', $kw)
                       ->orWhere('serpos.nama_serpo', 'like', $kw)
                       ->orWhere('regions.nama_region', 'like', $kw);
                });
            })
            ->orderBy('regions.nama_region')
            ->orderBy('serpos.nama_serpo')
            ->orderBy('segmens.nama_segmen');
    }

    public function map($row): array
    {
        return [
            $row->region ?? '-',
            $row->serpo ?? '-',
            $row->nama_segmen ?? '-',
            optional($row->created_at instanceof Carbon ? $row->created_at : Carbon::parse($row->created_at))->format('Y-m-d H:i:s'),
        ];
    }

    public function headings(): array
    {
        return ['Region', 'Serpo', 'Nama Segmen', 'Dibuat Pada'];
    }

    public function styles(Worksheet $sheet)
    {
        // Bold header + freeze
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        // Border tipis biar rapi
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$highestCol}{$highestRow}")
            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        return [];
    }
}
