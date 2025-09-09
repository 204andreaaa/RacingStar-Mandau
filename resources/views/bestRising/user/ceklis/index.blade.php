@extends('layouts.userapp')

@section('main')
@php
  use Illuminate\Support\Facades\Storage;

  // Helper URL gambar → selalu /storage/<path> (root-relative)
  $toUrl = function ($p) {
    $p = (string) $p;
    if ($p === '') return '';
    if (preg_match('#^https?://#i', $p)) return $p; // kalau sudah full URL, biarkan
    $p = ltrim($p, '/');
    $p = preg_replace('#^(public|storage)/#', '', $p);
    return '/storage/'.$p; // symlink storage:link harus aktif
  };
@endphp

<div class="content-wrapper">
  <section class="content-header mb-3">
    <h1 class="mb-1">Data Activity</h1>
    <div class="text-muted">
      Team: {{ $checklist->team ?? '—' }} • User ID: {{ $checklist->user_id ?? '—' }}
    </div>
  </section>

  <form method="post" action="{{ route('checklists.item.store', $checklist->id) }}" enctype="multipart/form-data">
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
          $already = isset($items) ? $items->firstWhere('activity_id', $a->id) : null;
          $isDone  = $already && $already->status === 'done';

          // ← activity ini butuh foto atau tidak? (dari DB)
          $needPhoto = (bool) ($a->requires_photo ?? false);

          $period = $a->limit_period ?? 'none';
          $u = $usage[$a->id] ?? [
            'used'=>0,
            'max' => $period !== 'none' ? (int)($a->limit_quota ?? 1) : null,
            'blocked'=>false,
            'label'=> match($period){ 'daily'=>'Hari ini','weekly'=>'Minggu ini','monthly'=>'Bulan ini', default=>'Tidak dibatasi' },
          ];
          $used    = (int)($u['used'] ?? 0);
          $max     = $u['max'];                 // null = tidak dibatasi
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
                  @if($a->description) <p class="small text-muted mb-1" style="white-space: pre-line;">{{ $a->description }}</p> @endif

                  <span class="badge bg-info">{{ $a->point }} Star</span>
                  @if($period !== 'none')
                    <span class="badge bg-light text-dark ms-1">{{ $label }}: {{ $used }}/{{ $max }}</span>
                  @else
                    <span class="badge bg-light text-dark ms-1">Tidak dibatasi</span>
                  @endif
                  @unless($needPhoto)
                    <span class="badge bg-secondary ms-1">Tanpa Foto</span>
                  @endunless
                  @if($already)       <span class="badge bg-success ms-1">Tersimpan</span> @endif
                  @if($shouldDisable) <span class="badge bg-danger ms-1">Penuh</span>     @endif
                </div>

                @if ((($max - $used != 0) || $used == 0) || $isDone)
                  <div class="form-check text-nowrap">
                    <input type="checkbox"
                          class="form-check-input toggle-done"
                          name="status[{{ $a->id }}]"
                          data-period="{{ $period }}"
                          data-used="{{ $used }}"
                          data-max="{{ $max === null ? '' : $max }}"
                          @if($isDone) checked @endif
                          @if($shouldDisable) disabled @endif
                          onchange="(function(cb){var card=cb.closest('.activity-card');var fs=card&&card.querySelector('.activity-fields');if(!fs)return;var p=cb.dataset.period||'none';var u=parseInt(cb.dataset.used||'0',10);var mr=cb.dataset.max||'';var m=mr===''?null:parseInt(mr,10);var hasQ=Number.isInteger(m)&&m>0;if(cb.checked&&p!=='none'&&hasQ&&u>=m){cb.checked=false;alert('Kuota aktivitas ini sudah penuh '+(p==='daily'?'hari ini':p==='weekly'?'minggu ini':'bulan ini')+'.');return;}if(cb.checked){fs.removeAttribute('disabled');fs.querySelectorAll('input,textarea,select,button').forEach(function(el){el.removeAttribute('disabled');});}else{fs.setAttribute('disabled','disabled');fs.querySelectorAll('input,textarea,select,button').forEach(function(el){el.setAttribute('disabled','disabled');});}})(this)">
                    <label class="form-check-label">Done</label>
                  </div>
                @endif
              </div>

              {{-- >>> JANGAN kasih disabled di sini. JS yang set saat load & saat ceklis. --}}
              @if ((($max - $used != 0) || $used == 0) || $isDone)
                <fieldset class="activity-fields">
                  <div class="row g-3">

                    @if ($needPhoto)
                      {{-- ===== FOTO BEFORE ===== --}}
                      <div class="col-md-6">
                        <label class="form-label fw-semibold">Foto Before <small class="text-muted">(maks 5 • maks 12MB/foto)</small></label>

                        {{-- existing photos (server) --}}
                        <div class="d-flex gap-2 align-items-start flex-wrap mb-1">
                          @if($already && $already->beforePhotos->count())
                            @foreach($already->beforePhotos as $pf)
                              @php $url = $toUrl($pf->path); @endphp
                              <a href="{{ $url }}" target="_blank">
                                <img src="{{ $url }}" class="img-thumbnail rounded border" width="90" alt="before">
                              </a>
                            @endforeach
                          @else
                            <span class="text-muted small">Belum ada foto tersimpan.</span>
                          @endif
                        </div>

                        {{-- smart uploader (client-side add incrementally) --}}
                        <div class="smart-uploader" data-max="5">
                          <div class="small text-muted mb-1 smart-files-label">Belum ada file dipilih.</div>
                          <div class="smart-thumbs d-flex gap-2 flex-wrap mb-2"></div>
                          <input
                            type="file"
                            name="before_photo[{{ $a->id }}][]"
                            class="form-control smart-file"
                            multiple
                            accept="image/*"
                            data-max="5">
                        </div>
                        <small class="text-muted d-block mt-1">
                          Kamu bisa menambah file sedikit-sedikit (maks 5). Saat disimpan, set baru akan mengganti set lama.
                        </small>
                      </div>

                      {{-- ===== FOTO AFTER ===== --}}
                      <div class="col-md-6">
                        <label class="form-label fw-semibold">Foto After <small class="text-muted">(maks 5 • maks 12MB/foto)</small></label>

                        {{-- existing photos (server) --}}
                        <div class="d-flex gap-2 align-items-start flex-wrap mb-1">
                          @if($already && $already->afterPhotos->count())
                            @foreach($already->afterPhotos as $pf)
                              @php $url = $toUrl($pf->path); @endphp
                              <a href="{{ $url }}" target="_blank">
                                <img src="{{ $url }}" class="img-thumbnail rounded border" width="90" alt="after">
                              </a>
                            @endforeach
                          @else
                            <span class="text-muted small">Belum ada foto tersimpan.</span>
                          @endif
                        </div>

                        {{-- smart uploader --}}
                        <div class="smart-uploader" data-max="5">
                          <div class="small text-muted mb-1 smart-files-label">Belum ada file dipilih.</div>
                          <div class="smart-thumbs d-flex gap-2 flex-wrap mb-2"></div>
                          <input
                            type="file"
                            name="after_photo[{{ $a->id }}][]"
                            class="form-control smart-file"
                            multiple
                            accept="image/*"
                            data-max="5">
                        </div>
                        <small class="text-muted d-block mt-1">
                          Kamu bisa menambah file sedikit-sedikit (maks 5). Saat disimpan, set baru akan mengganti set lama.
                        </small>
                      </div>
                    @else
                      {{-- Aktivitas ini tidak memerlukan foto --}}
                      <div class="col-12">
                        <div class="alert alert-secondary py-2 small mb-0">
                          Aktivitas ini <strong>tidak memerlukan</strong> foto before/after.
                        </div>
                      </div>
                    @endif

                    {{-- Catatan & Segmen tetap --}}
                    <div class="col-12">
                      <label class="form-label fw-semibold">Catatan/Lokasi</label>
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
                          class="form-control br-control segmen-select select2"
                          data-selected="{{ old('id_segmen.'.$a->id, $already->id_segmen ?? '') }}"
                          required>
                          <option value="">— pilih segmen —</option>
                          {{-- isi option segmen nanti lewat controller/ajax --}}
                        </select>
                        <small class="text-muted">Daftar segmen mengikuti region dari Serpo.</small>
                      </div>
                    @endif

                  </div>
                </fieldset>
              @endif
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
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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

  /* smart-uploader */
  .smart-thumb-wrap{position:relative;display:inline-block}
  .smart-thumb{width:90px;height:90px;object-fit:cover;border-radius:.5rem;border:1px solid rgba(0,0,0,.1)}
  .smart-remove{
    position:absolute;top:-6px;right:-6px;
    width:22px;height:22px;line-height:18px;
    border-radius:50%;
    border:0;outline:0;
    background:#dc3545;color:#fff;font-weight:700;cursor:pointer;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){
  // INIT enable/disable fields by checkbox
  document.querySelectorAll('.activity-card').forEach(function(card){
    const cb = card.querySelector('.toggle-done');
    const fs = card.querySelector('.activity-fields');
    if (!cb || !fs) return;
    const setEnabled = (on) => {
      if (on) {
        fs.removeAttribute('disabled');
        fs.querySelectorAll('input,textarea,select,button').forEach(el => el.removeAttribute('disabled'));
      } else {
        fs.setAttribute('disabled','disabled');
        fs.querySelectorAll('input,textarea,select,button').forEach(el => el.setAttribute('disabled','disabled'));
      }
    };
    setEnabled(cb.checked && !cb.disabled);
  });

  // ===== SMART FILE UPLOADER =====
  // Maintain a persistent DataTransfer per input, so picking again won't reset previous picks.
  document.querySelectorAll('.smart-uploader .smart-file').forEach(function(input){
    const wrap    = input.closest('.smart-uploader');
    const max     = parseInt(input.dataset.max || wrap.dataset.max || '5', 10);
    const labelEl = wrap.querySelector('.smart-files-label');
    const thumbs  = wrap.querySelector('.smart-thumbs');

    // store DataTransfer instance on the element to persist
    let dt = new DataTransfer();

    function render(){
      const files = Array.from(dt.files);
      // label
      if (!files.length) {
        labelEl.textContent = 'Belum ada file dipilih.';
      } else {
        labelEl.textContent = `Dipilih ${files.length}/${max} file: ${files.map(f => f.name).join(', ')}`;
      }
      // thumbs
      thumbs.innerHTML = '';
      files.forEach((f, idx) => {
        const url = URL.createObjectURL(f);
        const box = document.createElement('div');
        box.className = 'smart-thumb-wrap me-2 mb-2';
        box.innerHTML = `
          <img src="${url}" class="smart-thumb" alt="">
          <button type="button" class="smart-remove" title="Hapus" data-index="${idx}">×</button>
        `;
        thumbs.appendChild(box);
        // revoke URL on load/cleanup
        box.querySelector('img').onload = () => URL.revokeObjectURL(url);
        // remove handler
        box.querySelector('.smart-remove').addEventListener('click', function(){
          const i = parseInt(this.dataset.index, 10);
          const current = Array.from(dt.files);
          const next = new DataTransfer();
          current.forEach((ff, ii) => { if (ii !== i) next.items.add(ff); });
          dt = next;
          input.files = dt.files;
          render();
        });
      });

      // finally sync to input
      input.files = dt.files;
    }

    input.addEventListener('change', function(){
      const incoming = Array.from(input.files || []);
      // avoid duplicates (name|size|lastModified)
      const existing = new Set(Array.from(dt.files).map(f => `${f.name}|${f.size}|${f.lastModified}`));
      for (const f of incoming) {
        const key = `${f.name}|${f.size}|${f.lastModified}`;
        if (!existing.has(key) && dt.files.length < max) {
          dt.items.add(f);
          existing.add(key);
        }
        if (dt.files.length >= max) break;
      }
      // clear the raw input so the same file can be re-picked later if removed
      input.value = '';
      render();
    });

    // initial
    render();
  });

  // ===== Batasi handler lama (.limit-3) supaya tidak bentrok dengan smart-file =====
  document.addEventListener('change', function (e) {
    const el = e.target;
    if (!el.matches('input[type="file"].limit-3') || el.classList.contains('smart-file')) return;
    const max = parseInt(el.dataset.max || '3', 10);
    const files = el.files;
    if (!files || files.length <= max) return;
    try {
      const dt = new DataTransfer();
      for (let i = 0; i < Math.min(files.length, max); i++) dt.items.add(files[i]);
      el.files = dt.files;
      alert(`Maksimal ${max} foto. Hanya ${max} foto pertama yang dipakai.`);
    } catch {
      alert(`Maksimal ${max} foto. Silakan pilih ulang (≤ ${max}).`);
      el.value = '';
    }
  });

  // ===== Segmen by region (tetap) =====
  const sess_serpo   = @json($checklist->id_serpo ?? '');
  const routeSegmenByRegion = rid => "{{ route('api.segmen.byRegion', ':rid') }}".replace(':rid', rid);

  function resetSelect($el, placeholder) {
    $el.empty().append(new Option(placeholder, ''));
    if ($el.data('select2')) $el.val('').trigger('change.select2');
  }

  function fillSegmen(rows) {
    const $single = $('#id_segmen');
    const $many   = $('select.segmen-select');

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

    $.get(routeSegmenByRegion(sess_serpo))
      .done(rows => fillSegmen(rows))
      .fail(() => alert('Gagal memuat data Segmen (preload).'));
  }
});

$(document).ready(function() {
  $('.select2').select2({
    placeholder: "— pilih segmen —",
    allowClear: true,
    width: '100%'
  });
});
</script>

@endsection
