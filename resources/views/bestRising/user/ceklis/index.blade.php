@extends('layouts.userapp')

@section('main')
@php use Illuminate\Support\Facades\Storage; @endphp

<div class="content-wrapper">
  <section class="content-header mb-3">
    <h1 class="mb-1">Data Activity</h1>
    <div class="text-muted">
      Team: {{ $checklist->team ?? '—' }} • User ID: {{ $checklist->user_id ?? '—' }}
    </div>
  </section>

  <form method="post" action="{{ route('checklists.item.bulkStore', $checklist->id) }}" enctype="multipart/form-data">
    @csrf

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error'))   <div class="alert alert-danger" style="white-space:pre-line">{{ session('error') }}</div> @endif
    @if($errors->any())
      <div class="alert alert-danger">
        <div class="mb-1 fw-bold">Periksa input:</div>
        <ul class="mb-0">@foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
      </div>
    @endif

    <div class="row g-3">
      @foreach($activities as $i => $a)
        @php
          $already      = isset($items) ? $items->firstWhere('activity_id', $a->id) : null;
          $isDone       = $already && $already->status === 'done';
          $beforeExists = $already && $already->before_photo && Storage::disk('public')->exists($already->before_photo);
          $afterExists  = $already && $already->after_photo  && Storage::disk('public')->exists($already->after_photo);

          $u = $usage[$a->id] ?? [
            'used'    => 0,
            'max'     => (($a->limit_period ?? 'none') !== 'none') ? (int)($a->limit_quota ?? 1) : null,
            'blocked' => false,
            'label'   => match($a->limit_period ?? 'none') {
              'daily' => 'Hari ini', 'weekly' => 'Minggu ini', 'monthly' => 'Bulan ini', default => 'Tidak dibatasi',
            },
          ];
          $used    = (int)($u['used'] ?? 0);
          $max     = $u['max']; // null = tak dibatasi
          $blocked = (bool)($u['blocked'] ?? false);
          $label   = $u['label'] ?? 'Tidak dibatasi';

          // kalau item ini BELUM done & kuota penuh → lock checkbox (bukan field)
          $shouldDisable = !$isDone && $blocked;
        @endphp

        <div class="col-12">
          <div class="card activity-card shadow-sm {{ $isDone ? 'border-success' : '' }}">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <h5 class="mb-1">{{ $i+1 }}. {{ $a->name }}</h5>
                  @if($a->description) <p class="small text-muted mb-1">{{ $a->description }}</p> @endif

                  <span class="badge bg-info">{{ $a->point }} Star</span>
                  @if(($a->limit_period ?? 'none') !== 'none')
                    <span class="badge bg-light text-dark ms-1">{{ $label }}: {{ $used }}/{{ $max }}</span>
                  @else
                    <span class="badge bg-light text-dark ms-1">Tidak dibatasi</span>
                  @endif
                  @if($already)       <span class="badge bg-success ms-1">Tersimpan</span> @endif
                  @if($shouldDisable) <span class="badge bg-danger ms-1">Penuh</span>     @endif
                </div>

                <div class="form-check text-nowrap">
                  <input type="checkbox"
                         class="form-check-input toggle-done"
                         name="status[{{ $a->id }}]"
                         data-period="{{ $a->limit_period ?? 'none' }}"
                         data-used="{{ $used }}"
                         data-max="{{ $max === null ? '' : $max }}"
                         @if($isDone) checked @endif
                         @if($shouldDisable) disabled @endif
                         onchange="(function(cb){var card=cb.closest('.activity-card');var fs=card&&card.querySelector('.activity-fields');if(!fs)return;var p=cb.dataset.period||'none';var u=parseInt(cb.dataset.used||'0',10);var mr=cb.dataset.max||'';var m=mr===''?null:parseInt(mr,10);var hasQ=Number.isInteger(m)&&m>0;if(cb.checked&&p!=='none'&&hasQ&&u>=m){cb.checked=false;alert('Kuota aktivitas ini sudah penuh '+(p==='daily'?'hari ini':p==='weekly'?'minggu ini':'bulan ini')+'.');return;}if(cb.checked){fs.removeAttribute('disabled');fs.querySelectorAll('input,textarea,select,button').forEach(function(el){el.removeAttribute('disabled');});}else{fs.setAttribute('disabled','disabled');fs.querySelectorAll('input,textarea,select,button').forEach(function(el){el.setAttribute('disabled','disabled');});}})(this)">
                  <label class="form-check-label">Done</label>
                </div>
              </div>

              {{-- >>> JANGAN kasih disabled di sini. JS yang set saat load & saat ceklis. --}}
              <fieldset class="activity-fields">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Foto Before</label>
                    <div class="d-flex gap-2 align-items-start flex-nowrap">
                      @if($beforeExists)
                        @php $urlB = Storage::disk('public')->url($already->before_photo); @endphp
                        <a href="{{ $urlB }}" target="_blank"><img src="{{ $urlB }}" class="img-thumbnail rounded border" width="90" alt="before"></a>
                      @endif
                      <input type="file" name="before_photo[{{ $a->id }}]" class="form-control">
                    </div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Foto After</label>
                    <div class="d-flex gap-2 align-items-start flex-nowrap">
                      @if($afterExists)
                        @php $urlA = Storage::disk('public')->url($already->after_photo); @endphp
                        <a href="{{ $urlA }}" target="_blank"><img src="{{ $urlA }}" class="img-thumbnail rounded border" width="90" alt="after"></a>
                      @endif
                      <input type="file" name="after_photo[{{ $a->id }}]" class="form-control">
                    </div>
                  </div>

                  <div class="col-12">
                    <label class="form-label fw-semibold">Catatan</label>
                    <textarea name="note[{{ $a->id }}]" class="form-control" rows="2" placeholder="opsional">{{ $already->note ?? '' }}</textarea>
                    @if($shouldDisable)
                      <div class="small text-danger mt-1">Kuota aktivitas ini sudah penuh {{ $label }} ({{ $used }}/{{ $max }}).</div>
                    @endif
                  </div>

                  @if ($a->is_checked_segmen)
                    <div class="col-md-6">
                      <label class="form-label">Segmen</label>
                      <select
                        name="id_segmen[{{ $a->id }}]"
                        id="id_segmen_{{ $a->id }}"
                        class="form-control br-control segmen-select"
                        {{-- data-selected="{{ $already->id_segmen ?? '' }}" --}}
                        data-selected="{{ old('id_segmen.'.$a->id, $already->id_segmen ?? '') }}"
                        required>
                        <option value="">— pilih segmen —</option>
                      </select>
                      <small class="text-muted">Daftar segmen mengikuti region dari Serpo.</small>
                    </div>
                  @endif
                </div>
              </fieldset>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary px-4" name="finish" value="0">💾 Simpan</button>
      <button class="btn btn-success px-4"  name="finish" value="1">✅ Simpan &amp; Selesaikan</button>
    </div>
  </form>
</div>

<style>
  @media (max-width: 576px){
    h1, h3, h5{font-size:1rem!important}
    .card .form-label{font-size:.85rem!important}
    .badge{font-size:.7rem!important}
    .btn{font-size:.85rem!important;padding:.35rem .75rem!important}
  }
  .activity-card{transition:.2s;border-left:5px solid #0dcaf0}
  .activity-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.1)}
  .activity-card.border-success{border-left:5px solid #198754}
</style>

<script>
// INIT: pasang state awal via JS (bukan dari HTML)
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.activity-card').forEach(function(card){
    const cb = card.querySelector('.toggle-done');
    const fs = card.querySelector('.activity-fields');
    if (!cb || !fs) return;

    // atur state awal
    const enable = cb.checked && !cb.disabled;
    if (enable) {
      fs.removeAttribute('disabled');
      fs.querySelectorAll('input,textarea,select,button').forEach(el => el.removeAttribute('disabled'));
    } else {
      fs.setAttribute('disabled','disabled');
      fs.querySelectorAll('input,textarea,select,button').forEach(el => el.setAttribute('disabled','disabled'));
    }
  });

  // get segmen by region
  const sess_serpo   = @json($checklist->id_serpo ?? '');
  const routeSegmenByRegion = rid => "{{ route('api.segmen.byRegion', ':rid') }}".replace(':rid', rid);

  function resetSelect($el, placeholder) {
    $el.empty().append(new Option(placeholder, ''));
    // kalau pakai select2:
    if ($el.data('select2')) $el.val('').trigger('change.select2');
  }

  function fillSegmen(rows) {
    const $single = $('#id_segmen');                 // single
    const $many   = $('select.segmen-select');       // multiple

    if ($many.length) {
      $many.each(function () {
        const $el = $(this);
        const selected = String($el.data('selected') || '');
        resetSelect($el, '— pilih segmen —');
        rows.forEach(x => {
          const opt = new Option(x.text, x.id, false, String(x.id) === selected);
          $el.append(opt);
        });
        if ($el.data('select2')) $el.trigger('change.select2');
      });
    } else if ($single.length) {
      resetSelect($single, '— pilih segmen —');
      rows.forEach(x => {
        const opt = new Option(x.text, x.id, false, String(x.id) === String(sess_segmen));
        $single.append(opt);
      });
      if ($single.data('select2')) $single.trigger('change.select2');
    }
  }

  if (sess_serpo) {
    resetSelect($('#id_segmen'), '— pilih segmen —');
    $('select.segmen-select').each(function(){ resetSelect($(this), '— pilih segmen —'); });

    // preload SEGMEN list (by region) + pilih sess_segmen jika kamu set data-selected di elemen
    $.get(routeSegmenByRegion(sess_serpo))
      .done(rows => fillSegmen(rows))
      .fail(() => alert('Gagal memuat data Segmen (preload).'));
  }

});
</script>
@endsection
