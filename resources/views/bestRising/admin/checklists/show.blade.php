@extends('layouts.appBestRising')

@section('main')
@php
  // fallback kalau controller belum nge-pass $items
  $items = $items ?? ($checklist->items ?? collect());
  $fmtMulai   = optional($checklist->started_at)->format('Y-m-d H:i:s') ?? '-';
  $fmtSelesai = optional($checklist->submitted_at)->format('Y-m-d H:i:s') ?? '-';
  $team       = $checklist->team ?? '-';
  $userNama   = $checklist->user->nama ?? '-';
  $namaRegion = $checklist->region->nama_region ?? '-';
  $namaSerpo  = $checklist->serpo->nama_serpo ?? '-';
  $namaSegmen = $checklist->segmen->nama_segmen ?? '-';
  $status     = $checklist->status ?? '-';
  $badge      = $status === 'completed' ? 'badge-success'
              : ($status === 'pending' ? 'badge-warning' : 'badge-secondary');
  $totalPoint = (int) ($checklist->total_point ?? 0);
@endphp

<div class="content-wrapper">
  <section class="content-header d-flex align-items-start align-items-md-center justify-content-between flex-column flex-md-row gap-2">
    <div class="w-100">
      <h1 class="mb-3">Detail Activity #{{ $checklist->id }}</h1>

      <div class="card mb-3">
        <div class="card-body small text-muted">
          <div class="mb-2">Team        : <strong>{{ $team }}</strong></div>
          <div class="mb-2">User        : <strong>{{ $userNama }}</strong></div>
          <div class="mb-2">Lokasi      : <strong>{{ $namaRegion }} / {{ $namaSerpo }} / {{ $namaSegmen }}</strong></div>
          <div class="mb-2">Mulai       : {{ $fmtMulai }}</div>
          <div class="mb-2">Selesai     : {{ $fmtSelesai }}</div>
          <div class="mb-2">
            Status      : 
            <span class="badge {{ $badge }}">{{ ucfirst($status) }}</span>
          </div>
          <div>Total Star  : <strong>{{ $totalPoint }}</strong></div>
        </div>
      </div>
      
          <div class="mt-1 mt-md-0 w-100 w-md-auto text-md-end">
            <a href="{{ route('admin.checklists.index') }}" class="btn btn-primary w-100 w-md-auto">← Kembali</a>
          </div>
    </div>

  </section>

  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h3 class="mb-0">Item Aktivitas</h3>
      <div class="text-muted small">
        Total item: <strong>{{ $items->count() }}</strong>
        <span class="mx-1">•</span>
        Total Star (hitung ulang): <strong>{{ $items->sum('point_earned') }}</strong>
      </div>
    </div>

    <div class="card-body">
      {{-- =========================
           DESKTOP/TABLET (>= md)
           ========================= --}}
      <div class="d-none d-md-block">
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead>
              <tr>
                <th style="width:160px;">Waktu</th>
                <th>Aktivitas</th>
                <th style="width:110px;">Status</th>
                <th style="width:90px;" class="text-end">Star</th>
                <th style="width:120px;">Before</th>
                <th style="width:120px;">After</th>
                <th>Catatan</th>
              </tr>
            </thead>
            <tbody>
              @forelse($items as $it)
                <tr>
                  <td class="text-nowrap">{{ optional($it->submitted_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                  <td>{{ $it->activity->name ?? '-' }}</td>
                  <td>
                    @php
                      $st = $it->status ?? '-';
                      $bd = $st === 'done' ? 'badge-success'
                           : ($st === 'pending' ? 'badge-warning' : 'badge-secondary');
                    @endphp
                    <span class="badge {{ $bd }}">{{ ucfirst($st) }}</span>
                  </td>
                  <td class="text-end">{{ (int)($it->point_earned ?? 0) }}</td>
                  <td>
                    @if(!empty($it->before_photo))
                      <a href="{{ asset('storage/'.$it->before_photo) }}" target="_blank" title="Lihat gambar">
                        <img
                          src="{{ asset('storage/'.$it->before_photo) }}"
                          class="img-fluid img-thumb-fixed rounded border"
                          alt="before" loading="lazy" referrerpolicy="no-referrer">
                      </a>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    @if(!empty($it->after_photo))
                      <a href="{{ asset('storage/'.$it->after_photo) }}" target="_blank" title="Lihat gambar">
                        <img
                          src="{{ asset('storage/'.$it->after_photo) }}"
                          class="img-fluid img-thumb-fixed rounded border"
                          alt="after" loading="lazy" referrerpolicy="no-referrer">
                      </a>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="note-pre">{{ $it->note ?? '-' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted">Belum ada item</td>
                </tr>
              @endforelse
            </tbody>
            @if($items->count())
            <tfoot>
              <tr>
                <th colspan="3" class="text-end">Total</th>
                <th class="text-end">{{ $items->sum('point_earned') }}</th>
                <th colspan="3"></th>
              </tr>
            </tfoot>
            @endif
          </table>
        </div>
      </div>

      {{-- =========================
           MOBILE (< md)
           ========================= --}}
      <div class="d-md-none">
        @forelse($items as $it)
          <div class="border rounded-3 p-3 mb-3 shadow-sm-sm">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <div class="font-weight-bold">{{ $it->activity->name ?? '-' }}</div>
                <div class="small text-muted">{{ optional($it->submitted_at)->format('Y-m-d H:i:s') ?? '-' }}</div>
              </div>
              @php
                $st = $it->status ?? '-';
                $bd = $st === 'done' ? 'badge-success'
                     : ($st === 'pending' ? 'badge-warning' : 'badge-secondary');
              @endphp
              <span class="badge {{ $bd }}">{{ ucfirst($st) }}</span>
            </div>

            <div class="d-flex justify-content-between mt-2 small">
              <div class="text-muted">Star</div>
              <div class="font-weight-bold">{{ (int)($it->point_earned ?? 0) }}</div>
            </div>

            <div class="row g-2 mt-2">
              <div class="col-6">
                <div class="small text-muted mb-1">Before</div>
                @if(!empty($it->before_photo))
                  <a href="{{ asset('storage/'.$it->before_photo) }}" target="_blank" class="d-block">
                    <img
                      src="{{ asset('storage/'.$it->before_photo) }}"
                      class="img-fluid rounded-3 border img-thumb-fluid"
                      alt="before" loading="lazy" referrerpolicy="no-referrer">
                  </a>
                @else
                  <div class="text-muted">-</div>
                @endif
              </div>
              <div class="col-6">
                <div class="small text-muted mb-1">After</div>
                @if(!empty($it->after_photo))
                  <a href="{{ asset('storage/'.$it->after_photo) }}" target="_blank" class="d-block">
                    <img
                      src="{{ asset('storage/'.$it->after_photo) }}"
                      class="img-fluid rounded-3 border img-thumb-fluid"
                      alt="after" loading="lazy" referrerpolicy="no-referrer">
                  </a>
                @else
                  <div class="text-muted">-</div>
                @endif
              </div>
            </div>

            @if(!empty($it->note))
              <div class="mt-2 small note-pre">{{ $it->note }}</div>
            @endif
          </div>
        @empty
          <div class="text-center text-muted">Belum ada item</div>
        @endforelse

        @if($items->count())
          <div class="d-flex justify-content-between align-items-center mt-3 p-2 bg-light rounded">
            <div class="small text-muted">Total Star</div>
            <div class="font-weight-bold">{{ $items->sum('point_earned') }}</div>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

  <style>
    /* Pastikan di layout ada: <meta name="viewport" content="width=device-width, initial-scale=1"> */
    .meta-inline { display:flex; flex-wrap:wrap; gap:.25rem .5rem; }

    /* Shim kecil untuk utilitas "gap-2" di Bootstrap 4 (AdminLTE 3) */
    .gap-2 { gap: .5rem; }

    /* Batasi ukuran thumbnail agar konsisten di desktop */
    .img-thumb-fixed { max-width: 120px; max-height: 90px; object-fit: cover; }

    /* Thumbnail fluid untuk mobile */
    .img-thumb-fluid { max-height: 180px; object-fit: cover; width: 100%; }

    /* Catatan rapi & tidak melebar */
    .note-pre { white-space: pre-wrap; word-break: break-word; }

    /* Soft shadow di mobile card */
    @media (max-width: 767.98px){ .shadow-sm-sm { box-shadow: 0 .125rem .25rem rgba(0,0,0,.075); } }
  </style>
@endsection
