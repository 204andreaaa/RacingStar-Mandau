@extends('layouts.userapp')

@section('main')
<div class="content-wrapper">
  <section class="content-header d-flex align-items-start align-items-md-center justify-content-between flex-column flex-md-row gap-2">
    <div class="w-100">
      <h1 class="mb-1">Detail Activity #{{ $checklist->id }}</h1>
      <div class="text-muted meta-inline small">
        <div>Team: <strong>{{ $meta->team }}</strong></div>
        <div class="mx-1 d-none d-md-inline">•</div>
        <div>User: <strong>{{ $meta->user_nama }}</strong></div>
        <div class="mx-1 d-none d-md-inline">•</div>
        <div>Lokasi: <strong>{{ $meta->nama_region }} / {{ $meta->nama_serpo }} / {{ $meta->nama_segmen }}</strong></div>
        <div class="mx-1 d-none d-md-inline">•</div>
        <div>Mulai: {{ $meta->started_at }}</div>
        <div class="mx-1 d-none d-md-inline">•</div>
        <div>Selesai: {{ $meta->submitted_at ?? '-' }}</div>
        <div class="mx-1 d-none d-md-inline">•</div>
        <div>
          Status:
          <span class="badge {{ $meta->status=='completed' ? 'badge-success' : 'badge-secondary' }}">
            {{ $meta->status }}
          </span>
        </div>
        <div class="mx-1 d-none d-md-inline">•</div>
        <div>Total Star: <strong>{{ $meta->total_point }}</strong></div>
      </div>
    </div>

    <div class="mt-1 mt-md-0 w-100 w-md-auto text-md-end">
      <a href="{{ route('checklists.table-ceklis') }}" class="btn btn-light w-100 w-md-auto">← Kembali</a>
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
                  <td class="text-nowrap">{{ $it->submitted_at }}</td>
                  <td>{{ $it->activity->name ?? '-' }}</td>
                  <td>
                    <span class="badge {{ $it->status === 'done' ? 'badge-success' : 'badge-secondary' }}">
                      {{ ucfirst($it->status) }}
                    </span>
                  </td>
                  <td class="text-end">{{ $it->point_earned }}</td>
                  <td>
                    @if($it->before_photo)
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
                    @if($it->after_photo)
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
                  <td class="note-pre">{{ $it->note }}</td>
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
                <div class="small text-muted">{{ $it->submitted_at }}</div>
              </div>
              <span class="badge {{ $it->status === 'done' ? 'badge-success' : 'badge-secondary' }}">
                {{ ucfirst($it->status) }}
              </span>
            </div>

            <div class="d-flex justify-content-between mt-2 small">
              <div class="text-muted">Star</div>
              <div class="font-weight-bold">{{ $it->point_earned }}</div>
            </div>

            <div class="row g-2 mt-2">
              <div class="col-6">
                <div class="small text-muted mb-1">Before</div>
                @if($it->before_photo)
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
                @if($it->after_photo)
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

            @if($it->note)
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

@push('styles')
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
@endpush
@endsection
