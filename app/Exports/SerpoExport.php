<?php

namespace App\Exports;

use App\Models\Serpo;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SerpoExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filters;
    protected int $row = 0;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function headings(): array
    {
        return ['No', 'Region', 'Nama Serpo'];
    }

    public function collection()
    {
        $q = Serpo::with('region:id_region,nama_region');

        if (!empty($this->filters['id_region'])) {
            $q->where('id_region', $this->filters['id_region']);
        }

        if (!empty($this->filters['q'])) {
            $s = $this->filters['q'];
            $q->where(function (Builder $qq) use ($s) {
                $qq->where('nama_serpo', 'like', "%{$s}%")
                   ->orWhereHas('region', function (Builder $rr) use ($s) {
                       $rr->where('nama_region', 'like', "%{$s}%");
                   });
            });
        }

        return $q->orderBy('nama_serpo')->get();
    }

    public function map($serpo): array
    {
        $this->row++;
        return [
            $this->row,
            $serpo->region->nama_region ?? '-',
            $serpo->nama_serpo,
        ];
    }
}
