<?php

namespace App\Exports;

use App\Models\Checklist;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ChecklistsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    private int $rowNumber = 0; // counter untuk nomor urut

    public function __construct(
        public ?string $team,
        public ?string $status,
        public ?string $dateFrom,
        public ?string $dateTo,
        public ?string $search
    ) {}

    public function query()
    {
        $q = Checklist::query()
            ->with(['user','region','serpo','segmen'])
            ->when($this->team,      fn($qq) => $qq->where('team', $this->team))
            ->when($this->status,    fn($qq) => $qq->where('status', $this->status))
            ->when($this->dateFrom,  fn($qq) => $qq->whereDate('started_at', '>=', $this->dateFrom))
            ->when($this->dateTo,    fn($qq) => $qq->whereDate('started_at', '<=', $this->dateTo))
            ->orderByDesc('started_at');

        if ($this->search) {
            $s = "%{$this->search}%";
            $q->where(function($qq) use ($s){
                $qq->where('team', 'like', $s)
                   ->orWhere('status', 'like', $s)
                   ->orWhere('total_point', 'like', $s)
                   ->orWhere('started_at', 'like', $s)
                   ->orWhere('submitted_at', 'like', $s)
                   ->orWhereHas('user',   fn($u) => $u->where('nama', 'like', $s))
                   ->orWhereHas('region', fn($r) => $r->where('nama_region', 'like', $s))
                   ->orWhereHas('serpo',  fn($r) => $r->where('nama_serpo',  'like', $s))
                   ->orWhereHas('segmen', fn($r) => $r->where('nama_segmen', 'like', $s));
            });
        }

        return $q;
    }

    public function headings(): array
    {
        return [
            'No',
            'Mulai',
            'Selesai',
            'Team',
            'Nama',
            'Lokasi (Region / Serpo)',
            'Total Star',
            'Status'
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++; // increment counter

        $mulai   = optional($row->started_at)->format('Y-m-d H:i:s');
        $selesai = optional($row->submitted_at)->format('Y-m-d H:i:s');
        $namaUser = $row->user->nama ?? '-';

        $region = $row->region->nama_region ?? '-';
        $serpo  = $row->serpo->nama_serpo   ?? '-';
        $lokasi = "{$region} / {$serpo}";

        return [
            $this->rowNumber, // nomor urut
            $mulai,
            $selesai,
            $row->team ?? '-',
            $namaUser,
            $lokasi,
            (int) ($row->total_point ?? 0),
            $row->status ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // bikin baris pertama (judul/headings) bold
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
