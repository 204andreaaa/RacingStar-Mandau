@extends('layouts.userapp')

@section('main')
<div class="content-wrapper">
  <section class="content-header">
    <h1>Ceklis Aktivitas</h1>
    <div class="text-muted">
      Team: {{ $checklist->team }} • User ID: {{ $checklist->user_id }}
    </div>
  </section>

  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h3 class="mb-0">Isi Checklist</h3>
      <small class="text-muted">Centang <strong>Done</strong> untuk mengaktifkan upload foto</small>
    </div>

    <form method="post" action="{{ route('checklists.item.bulkStore', $checklist) }}" enctype="multipart/form-data">
      @csrf

      {{-- Notifikasi error validasi (kalau ada) --}}
      @if ($errors->any())
        <div class="alert alert-danger mx-3 mt-3">
          <div class="fw-bold mb-1">Gagal simpan. Periksa isian berikut:</div>
          <ul class="mb-0">
            @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered align-middle mb-0 table-mobile">
            <thead>
              <tr>
                <th style="width:42px">#</th>
                <th>Aktivitas</th>
                <th style="width:100px" class="text-center">Done?</th>
                <th style="width:220px">Foto Before</th>
                <th style="width:220px">Foto After</th>
                <th>Catatan</th>
              </tr>
            </thead>
            <tbody>
              @foreach($activities as $i => $a)
                @php
                  $already = $items->firstWhere('activity_id', $a->id);
                @endphp
                <tr class="{{ $already ? 'table-success' : '' }}">
                  <td data-label="#">{{ $i+1 }}</td>

                  <td data-label="Aktivitas">
                    <div class="fw-semibold">{{ $a->name }}</div>
                    @if($a->description)
                      <div class="text-muted small">{{ $a->description }}</div>
                    @endif
                    <span class="badge bg-info mt-1">{{ $a->point }} Star</span>
                    @if($already)
                      <div class="small text-success mt-1">
                        Sudah tersimpan ({{ ucfirst($already->status) }})
                      </div>
                    @endif
                  </td>

                  <td data-label="Done?" class="text-center">
                    <input type="checkbox"
                           class="form-check-input toggle-done"
                           name="status[{{ $a->id }}]"
                           data-row="{{ $a->id }}"
                           aria-label="Aktifkan Done untuk {{ $a->name }}" 
                           @if($already) checked @endif>
                  </td>

                  <td data-label="Foto Before">
                    @if($already && $already->before_photo)
                      <a href="{{ asset('storage/'.$already->before_photo) }}" target="_blank" title="Lihat foto before">
                        <img src="{{ asset('storage/'.$already->before_photo) }}" width="90" class="img-thumbnail mb-2">
                      </a>
                    @endif

                    <input type="file"
                          name="before_photo[{{ $a->id }}]"
                          class="form-control file-before"
                          data-row="{{ $a->id }}"
                          accept="image/*"
                          capture="environment"
                          @if(!$already) disabled @endif>
                  </td>

                  <td data-label="Foto After">
                    @if($already && $already->after_photo)
                      <a href="{{ asset('storage/'.$already->after_photo) }}" target="_blank" title="Lihat foto after">
                        <img src="{{ asset('storage/'.$already->after_photo) }}" width="90" class="img-thumbnail mb-2">
                      </a>
                    @endif

                    <input type="file"
                          name="after_photo[{{ $a->id }}]"
                          class="form-control file-after"
                          data-row="{{ $a->id }}"
                          accept="image/*"
                          capture="environment"
                          @if(!$already) disabled @endif>
                  </td>

                  <td data-label="Catatan">
                    <textarea name="note[{{ $a->id }}]"
                              class="form-control"
                              rows="1"
                              placeholder="opsional"
                              data-row="{{ $a->id }}"
                              disabled>{{ $already->note ?? '' }}</textarea>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer d-flex flex-wrap gap-2">
        <button class="btn btn-primary" name="finish" value="0">Simpan</button>
        <button class="btn btn-success" name="finish" value="1">Simpan & Selesaikan</button>
      </div>
    </form>
  </div>


  @if($items->count())
    <div class="card mt-3">
      <div class="card-header"><h3 class="mb-0">Item yang sudah tersimpan</h3></div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered">
            <thead>
              <tr><th>Waktu</th><th>Aktivitas</th><th>Status</th><th>Star</th><th>Before</th><th>After</th></tr>
            </thead>
            <tbody>
            @foreach($items as $it)
              <tr>
                <td data-label="Waktu">{{ $it->submitted_at }}</td>
                <td data-label="Aktivitas">{{ $it->activity->name ?? '-' }}</td>
                <td data-label="Status">{{ ucfirst($it->status) }}</td>
                <td data-label="Poin">{{ $it->point_earned }}</td>
                <td data-label="Before">
                  @if($it->before_photo)
                    <img src="{{ asset('storage/'.$it->before_photo) }}" width="80" class="img-thumbnail">
                  @endif
                </td>
                <td data-label="After">
                  @if($it->after_photo)
                    <img src="{{ asset('storage/'.$it->after_photo) }}" width="80" class="img-thumbnail">
                  @endif
                </td>
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif
</div>

{{-- responsive & UX --}}
<style>
/* umum */
.table-mobile input[type="file"],
.table-mobile textarea { width: 100%; }

/* Mobile stacked table */
@media (max-width: 768px) {
  .table-mobile thead { display: none; }
  .table-mobile, .table-mobile tbody, .table-mobile tr, .table-mobile td {
    display: block; width: 100%;
  }
  .table-mobile tr {
    margin: 10px 10px 14px;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    padding: 10px 10px 6px;
    background: #fff;
  }
  .table-mobile td {
    border: none !important;
    padding: 6px 8px;
  }
  .table-mobile td::before {
    content: attr(data-label);
    display: block;
    font-size: .75rem;
    color: #6c757d;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: .4px;
  }
  .table-mobile .img-thumbnail { width: 120px; max-width: 100%; }
  .table-mobile .form-check-input { transform: scale(1.2); }
}

/* card success row subtle */
.table-success { --bs-table-bg: #f0fff4 !important; }
</style>

<script>
$(function(){
  // toggle Done -> enable/disable file & note (+ set required kalau done)
  $('.toggle-done').on('change', function(){
    const id = $(this).data('row');
    const enable = $(this).is(':checked');
    $(`input.file-before[data-row="${id}"]`)
      .prop('disabled', !enable)
      .prop('required', enable);
    $(`input.file-after[data-row="${id}"]`)
      .prop('disabled', !enable)
      .prop('required', enable);
    $(`textarea[data-row="${id}"]`)
      .prop('disabled', !enable);
  });
});
</script>
@endsection
