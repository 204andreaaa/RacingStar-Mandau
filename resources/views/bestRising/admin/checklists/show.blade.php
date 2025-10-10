{{-- resources/views/admin/checklists/show.blade.php --}}
@extends('layouts.appBestRising')

@section('main')
@php
  $items       = $items ?? ($checklist->items ?? collect());
  $fmtMulai    = optional($checklist->started_at)->format('Y-m-d H:i:s') ?? '-';
  $fmtSelesai  = optional($checklist->submitted_at)->format('Y-m-d H:i:s') ?? '-';
  $team        = $checklist->team ?? '-';
  $userNama    = $checklist->user->nama ?? '-';
  $namaRegion  = $checklist->region->nama_region ?? '-';
  $namaSerpo   = $checklist->serpo->nama_serpo ?? '-';
  $namaSegmen  = $checklist->segmen->nama_segmen ?? '-';
  $status      = $checklist->status ?? '-';
  $badge       = $status === 'completed' ? 'badge-success'
                : ($status === 'review admin' || $status === 'pending' ? 'badge-warning' : 'badge-secondary');
  $totalPoint  = (int) ($checklist->total_point ?? 0);

  // URL normalizer → selalu /storage/<path> (root-relative) supaya aman di HP
  $toUrl = function ($p) {
    $p = (string) $p;
    if ($p === '') return '';
    if (preg_match('#^https?://#i', $p)) return $p; // sudah absolut, biarkan
    $p = ltrim($p, '/');
    $p = preg_replace('#^(public|storage)/#', '', $p);
    return '/storage/' . $p; // pastikan storage:link aktif
  };
@endphp


<style>
  .gallery-grid {
    display:grid;
    grid-template-columns: repeat(3, 1fr);
    gap:.5rem;
  }
  .gallery-grid img, .gallery-row img {
    width:100%; height:90px; object-fit:cover; border-radius:.5rem; border:1px solid #e5e7eb;
  }
  .gallery-row { display:flex; gap:.5rem; flex-wrap:wrap; }
  .gallery-row img { height:110px; }
  .note-pre { white-space: pre-wrap; word-break: break-word; }
  @media (max-width: 767.98px){ .shadow-sm-sm { box-shadow:0 .125rem .25rem rgba(0,0,0,.075); } }
</style>

<div class="content-wrapper">
  {{-- HEADER --}}
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
          <div class="mb-2">Status      :
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

  {{-- CARD ITEMS --}}
  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <h3 class="mb-0">Item Aktivitas</h3>
      {{-- <div class="text-muted small">
        Total item: <strong>{{ $items->count() }}</strong>
        <span class="mx-1">•</span>
        Total Star (hitung ulang): <strong>{{ $items->sum('point_earned') }}</strong>
      </div> --}}
      @if ($items[0]->is_approval == 0 && $status === 'review admin')
        <div class="d-flex gap-2">
          <button type="button" id="btnApprove" class="btn btn-success mr-2">✓ Setujui Aktivitas</button>
          <button type="button" id="btnReject"  class="btn btn-danger">✗ Tolak Aktivitas</button>
        </div>
      @endif
    </div>

    <div class="card-body">
      @if ($status === 'rejected')
        <div class="alert alert-danger" role="alert">
          <h4 class="alert-heading">Alasan Penolakan!</h4>
          <hr>
          <p class="mb-0">{{ $items[0]->alasan_tolak ?? 'Tidak ada alasan penolakan.' }}</p>
        </div>
      @endif
      {{-- DESKTOP --}}
      <div class="d-none d-md-block">
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead>
              <tr>
                <th style="width:160px;">Waktu</th>
                <th>Aktivitas</th>
                {{-- <th style="width:110px;">Status</th>
                <th style="width:90px;" class="text-end">Star</th> --}}
                <th style="width:220px;">Before</th>
                <th style="width:220px;">After</th>
                <th>Catatan</th>
              </tr>
            </thead>
            <tbody>
              @forelse($items as $it)
                @php
                  $beforeSet = collect($it->beforePhotos ?? [])->pluck('path')->all();
                  $afterSet  = collect($it->afterPhotos  ?? [])->pluck('path')->all();
                  if (empty($beforeSet) && $it->before_photo) $beforeSet = [$it->before_photo];
                  if (empty($afterSet)  && $it->after_photo)  $afterSet  = [$it->after_photo];
                  $st = $it->status ?? '-';
                  $bd = $st === 'done' ? 'badge-success'
                       : ($st === 'pending' ? 'badge-warning' : 'badge-secondary');
                @endphp
                <tr>
                  <td class="text-nowrap">{{ optional($it->submitted_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                  <td>
                    {{ $it->activity->name ?? '-' }} 
                    <br><small class="text-muted">Sub Aktivitas: {{ $it->sub_activities ?? '-' }}</small>
                    <br><small class="text-muted">Segmen: {{ $it->segmen->nama_segmen ?? '-' }}</small>
                  </td>
                  {{-- <td><span class="badge {{ $bd }}">{{ ucfirst($st) }}</span></td>
                  <td class="text-end">{{ (int)($it->point_earned ?? 0) }}</td> --}}
                  <td>
                    @if(count($beforeSet))
                      <div class="gallery-grid">
                        @foreach($beforeSet as $path)
                          @php $u = $toUrl($path); @endphp
                          <a href="{{ $u }}" target="_blank" class="g-item">
                            <img src="{{ $u }}" alt="before">
                          </a>
                        @endforeach
                      </div>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    @if(count($afterSet))
                      <div class="gallery-grid">
                        @foreach($afterSet as $path)
                          @php $u = $toUrl($path); @endphp
                          <a href="{{ $u }}" target="_blank" class="g-item">
                            <img src="{{ $u }}" alt="after">
                          </a>
                        @endforeach
                      </div>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="note-pre">{{ $it->note ?? '-' }}</td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted">Belum ada item</td></tr>
              @endforelse
            </tbody>
            {{-- @if($items->count())
            <tfoot>
              <tr>
                <th colspan="3" class="text-end">Total</th>
                <th class="text-end">{{ $items->sum('point_earned') }}</th>
                <th colspan="3"></th>
              </tr>
            </tfoot>
            @endif --}}
          </table>
        </div>
      </div>

      {{-- MOBILE --}}
      <div class="d-md-none">
        @forelse($items as $it)
          @php
            $beforeSet = collect($it->beforePhotos ?? [])->pluck('path')->all();
            $afterSet  = collect($it->afterPhotos  ?? [])->pluck('path')->all();
            if (empty($beforeSet) && $it->before_photo) $beforeSet = [$it->before_photo];
            if (empty($afterSet)  && $it->after_photo)  $afterSet  = [$it->after_photo];
            $st = $it->status ?? '-';
            $bd = $st === 'done' ? 'badge-success'
                 : ($st === 'pending' ? 'badge-warning' : 'badge-secondary');
          @endphp
          <div class="border rounded-3 p-3 mb-3 shadow-sm-sm">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <div class="font-weight-bold">{{ $it->activity->name ?? '-' }}</div>
                <div class="small text-muted">{{ optional($it->submitted_at)->format('Y-m-d H:i:s') ?? '-' }}</div>
              </div>
              <span class="badge {{ $bd }}">{{ ucfirst($st) }}</span>
            </div>

            <div class="d-flex justify-content-between mt-2 small">
              <div class="text-muted">Star</div>
              <div class="font-weight-bold">{{ (int)($it->point_earned ?? 0) }}</div>
            </div>

            <div class="mt-2">
              <div class="small text-muted mb-1">Before</div>
              @if(count($beforeSet))
                <div class="gallery-row">
                  @foreach($beforeSet as $path)
                    @php $u = $toUrl($path); @endphp
                    <a href="{{ $u }}" target="_blank" class="g-item"><img src="{{ $u }}" alt="before"></a>
                  @endforeach
                </div>
              @else
                <div class="text-muted">-</div>
              @endif
            </div>

            <div class="mt-2">
              <div class="small text-muted mb-1">After</div>
              @if(count($afterSet))
                <div class="gallery-row">
                  @foreach($afterSet as $path)
                    @php $u = $toUrl($path); @endphp
                    <a href="{{ $u }}" target="_blank" class="g-item"><img src="{{ $u }}" alt="after"></a>
                  @endforeach
                </div>
              @else
                <div class="text-muted">-</div>
              @endif
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

{{-- Modal Tolak Aktivitas --}}
<div class="modal fade" id="modalTolak" tabindex="-1" aria-labelledby="modalTolakLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="{{ route('admin.checklists.review', $checklist->id) }}">
      @csrf
      <input type="hidden" name="action" value="reject">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTolakLabel">Alasan Penolakan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2 small text-muted">
          Berikan alasan penolakan untuk checklist ini. Alasan akan disimpan di setiap item.
        </div>
        <textarea name="alasan_tolak" class="form-control" rows="4" required placeholder="Tulis alasan penolakan..."></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-danger">Tolak Aktivitas</button>
      </div>
    </form>
  </div>
</div>

<script>
$(function () {
  const route = @json(route('admin.checklists.review', $checklist->id));
  const csrf  = $('meta[name="csrf-token"]').attr('content');

  function ajaxReview(payload) {
    return $.ajax({
      url: route,
      type: 'POST',
      data: JSON.stringify(payload),
      contentType: 'application/json; charset=utf-8',
      dataType: 'json',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    });
  }

  // APPROVE (confirm → ajax)
  $('#btnApprove').on('click', function () {
    Swal.fire({
      icon: 'question',
      title: 'Setujui Aktivitas',
      text: 'Aktivitas akan di-setujui dan star dihitung dari item disetujui.',
      showCancelButton: true,
      confirmButtonText: 'Ya, setujui',
      cancelButtonText: 'Batal'
    }).then(function (res) {
      if (!res.isConfirmed) return;

      Swal.fire({title:'Memproses…', allowOutsideClick:false, didOpen: () => Swal.showLoading()});
      ajaxReview({ action: 'approve' })
        .done(function (resp) {
          Swal.fire({icon:'success', title:'Berhasil', text: resp.message || 'Checklist disetujui.'})
            .then(() => location.reload());
        })
        .fail(function (xhr) {
          const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Gagal memproses data...';
          Swal.fire({icon:'error', title:'Gagal', text: msg});
        });
    });
  });

  // REJECT (prompt alasan → ajax)
  $('#btnReject').on('click', function () {
    Swal.fire({
      icon: 'warning',
      title: 'Tolak Aktivitas',
      input: 'textarea',
      inputLabel: 'Alasan penolakan',
      inputPlaceholder: 'Tulis alasan penolakan…',
      inputValidator: v => !v ? 'Alasan wajib diisi' : undefined,
      showCancelButton: true,
      confirmButtonText: 'Tolak',
      cancelButtonText: 'Batal'
    }).then(function (res) {
      if (!res.isConfirmed) return;

      Swal.fire({title:'Memproses…', allowOutsideClick:false, didOpen: () => Swal.showLoading()});
      ajaxReview({ action: 'reject', alasan_tolak: res.value })
        .done(function (resp) {
          Swal.fire({icon:'success', title:'Berhasil', text: resp.message || 'Checklist ditolak.'})
            .then(() => location.reload());
        })
        .fail(function (xhr) {
          const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Gagal memproses data...';
          Swal.fire({icon:'error', title:'Gagal', text: msg});
        });
    });
  });
});
</script>
@endsection
