<?php

namespace App\Exports;

use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RegionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filters;
    protected int $row = 0;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function headings(): array
    {
        return ['No', 'Nama Region'];
    }

    public function collection()
    {
        $q = Region::query()->select('id_region','nama_region');

        // global search
        if (!empty($this->filters['q'])) {
            $s = $this->filters['q'];
            $q->where('nama_region', 'like', "%{$s}%");
        }

        return $q->orderBy('nama_region')->get();
    }

    public function map($region): array
    {
        $this->row++;
        return [
            $this->row,
            $region->nama_region,
        ];
    }
}
