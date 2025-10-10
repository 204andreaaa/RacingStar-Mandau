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
    <h1 class="mb-1">Data Aktifitas</h1>
    <div class="text-muted">
      Team: {{ $checklist->team ?? '—' }} • User ID: {{ $checklist->user_id ?? '—' }}
    </div>
  </section>

  <form method="post" action="{{ route('checklists.item.store', $checklist->id) }}" enctype="multipart/form-data" id="activityForm">
    @csrf

    {{-- ALERT SERVER (tetap tampil di atas, tidak dihapus) --}}
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

          $shouldDisable = !$isDone && $blocked;
          $baselineNote   = trim($already->note ?? '');
          $baselineSegmen = old('id_segmen.'.$a->id, $already->id_segmen ?? '');

          // jumlah foto existing utk validasi
          $existingBeforeCount = $already ? ($already->beforePhotos->count() ?? 0) : 0;
          $existingAfterCount  = $already ? ($already->afterPhotos->count() ?? 0)  : 0;

          // Mengambil sub_activities yang terkait dengan aktivitas
          $subActivities = $a->sub_activities ?? [];
        @endphp

        <div class="col-12">
          <div class="card activity-card shadow-sm {{ $isDone ? 'border-success' : '' }}"
            data-activity-id="{{ $a->id }}"
            data-need-photo="{{ $needPhoto ? 1 : 0 }}"
            data-needs-segmen="{{ $a->is_checked_segmen ? 1 : 0 }}"
            data-existing-before="{{ $existingBeforeCount }}"
            data-existing-after="{{ $existingAfterCount }}"
          >
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
                          data-initial="{{ $isDone ? 1 : 0 }}"
                          @if($isDone) checked @endif
                          @if($shouldDisable) disabled @endif
                          onchange="(function(cb){
                            var card=cb.closest('.activity-card');var fs=card&&card.querySelector('.activity-fields');if(!fs)return;
                            var p=cb.dataset.period||'none';var u=parseInt(cb.dataset.used||'0',10);
                            var mr=cb.dataset.max||'';var m=mr===''?null:parseInt(mr,10);var hasQ=Number.isInteger(m)&&m>0;
                            if(cb.checked&&p!=='none'&&hasQ&&u>=m){cb.checked=false;alert('Kuota aktivitas ini sudah penuh '+(p==='daily'?'hari ini':p==='weekly'?'minggu ini':'bulan ini')+'.');return;}
                            if(cb.checked){fs.removeAttribute('disabled');fs.querySelectorAll('input,textarea,select,button').forEach(function(el){el.removeAttribute('disabled');});}
                            else{fs.setAttribute('disabled','disabled');fs.querySelectorAll('input,textarea,select,button').forEach(function(el){el.setAttribute('disabled','disabled');});}
                          })(this)">
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
                          @if($existingBeforeCount > 0)
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
                        <div class="smart-uploader uploader-before" data-max="5" data-picked="0">
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
                          @if($existingAfterCount > 0)
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
                        <div class="smart-uploader uploader-after" data-max="5" data-picked="0">
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

                    @if(!empty($subActivities))
                      <div class="mb-3">
                        <label class="form-label">Sub-Aktivitas</label>
                        <div>
                          @foreach($subActivities as $sub)
                            <div class="form-check">
                              <input type="radio" class="form-check-input" name="sub_activities[{{ $a->id }}]" value="{{ $sub }}" id="sub_{{ $a->id }}_{{ $loop->index }}">
                              <label class="form-check-label" for="sub_{{ $a->id }}_{{ $loop->index }}">{{ $sub }}</label>
                            </div>
                          @endforeach
                        </div>
                      </div>
                    @endif

                    {{-- Catatan & Segmen tetap --}}
                    <div class="col-12">
                      <label class="form-label fw-semibold">Catatan/Lokasi</label>
                      <textarea
                        name="note[{{ $a->id }}]"
                        class="form-control note-input"
                        rows="2"
                        data-initial="{{ $baselineNote }}"
                        placeholder="opsional">{{ $already->note ?? '' }}</textarea>
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
                          data-initial="{{ $baselineSegmen }}"
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

    <div class="mt-4 d-flex">
      <a href="#">&nbsp;&nbsp;&nbsp;</a>
      {{-- <button class="btn btn-primary px-4 me-10" name="finish" value="0" id="btnSave">
        💾 Simpan
      </button> --}}
      <a href="#">&nbsp;&nbsp;&nbsp;</a>
      <button class="btn btn-success px-4" name="finish" value="1" id="btnFinish">
        ✅ Simpan &amp; Selesaikan
      </button>
    </div>
  </form>
      <br>
      <br>
</div>

{{-- Select2 & SweetAlert2 --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.all.min.js"></script>

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
  // INIT enable/disable fields by checkbox (status Done)
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

  // ===== SMART FILE UPLOADER (persist selections; add/remove per file) =====
  document.querySelectorAll('.smart-uploader .smart-file').forEach(function(input){
    const wrap    = input.closest('.smart-uploader');
    const max     = parseInt(input.dataset.max || wrap.dataset.max || '5', 10);
    const labelEl = wrap.querySelector('.smart-files-label');
    const thumbs  = wrap.querySelector('.smart-thumbs');

    let dt = new DataTransfer(); // persist files across picks

    function render(){
      const files = Array.from(dt.files);
      // label
      labelEl.textContent = files.length
        ? `Dipilih ${files.length}/${max} file: ${files.map(f => f.name).join(', ')}`
        : 'Belum ada file dipilih.';
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
        box.querySelector('img').onload = () => URL.revokeObjectURL(url);
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
      // sync to input
      input.files = dt.files;

      // ← tandai jumlah file yang dipilih (untuk deteksi perubahan & validasi)
      wrap.dataset.picked = String(files.length);
    }

    input.addEventListener('change', function(){
      const incoming = Array.from(input.files || []);
      const existing = new Set(Array.from(dt.files).map(f => `${f.name}|${f.size}|${f.lastModified}`));
      for (const f of incoming) {
        const key = `${f.name}|${f.size}|${f.lastModified}`;
        if (!existing.has(key) && dt.files.length < max) {
          dt.items.add(f);
        }
        if (dt.files.length >= max) break;
      }
      input.value = ''; // allow re-pick same file later
      render();
    });

    render(); // initial
  });

  // ===== Guard lama untuk input[type=file].limit-3 (biar nggak bentrok) =====
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

  // ===== Segmen by Region (Serpo) — preload via AJAX (fungsi lama dipertahankan) =====
  const sess_serpo   = @json($checklist->id_serpo ?? '');
  const routeSegmenByRegion = rid => "{{ route('api.segmen.byRegion', ':rid') }}".replace(':rid', rid);

  function resetSelect($el, placeholder) {
    $el.empty().append(new Option(placeholder, ''));
    if ($el.data('select2')) $el.val('').trigger('change.select2');
  }

  function fillSegmen(rows) {
    const $single = $('#id_segmen');           // case tunggal (kalau ada)
    const $many   = $('select.segmen-select'); // case banyak (per-activity)

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

  // ============ SINGLE-DONE LOCK: hanya boleh 1 aktivitas Done ============
  (function(){
    const toggles = Array.from(document.querySelectorAll('.toggle-done'));

    // catat disabled awal dari server (quota penuh, dsb)
    toggles.forEach(cb => { if (cb.disabled) cb.dataset.hard = '1'; });

    function fieldsetOf(cb){
      const card = cb.closest('.activity-card');
      return card ? card.querySelector('.activity-fields') : null;
    }
    function setFieldsetEnabled(fs, on){
      if (!fs) return;
      if (on) {
        fs.removeAttribute('disabled');
        fs.querySelectorAll('input,textarea,select,button').forEach(el => el.removeAttribute('disabled'));
      } else {
        fs.setAttribute('disabled','disabled');
        fs.querySelectorAll('input,textarea,select,button').forEach(el => el.setAttribute('disabled','disabled'));
      }
    }
    function setFieldsetByCheckbox(cb){
      const fs = fieldsetOf(cb);
      setFieldsetEnabled(fs, cb.checked && !cb.disabled);
    }

    function enforceSingleDone(changedCb=null){
      const checked = toggles.find(t => t.checked);

      if (checked) {
        // kunci semua yang lain
        toggles.forEach(t => {
          const isHard = t.dataset.hard === '1';
          if (t !== checked) {
            if (!isHard) t.disabled = true;
            setFieldsetEnabled(fieldsetOf(t), false);
          } else {
            if (!isHard) t.disabled = false;
            setFieldsetEnabled(fieldsetOf(t), true);
          }
        });

        // jika user coba centang yang kedua, batalkan & kasih info
        if (changedCb && changedCb !== checked && changedCb.checked) {
          changedCb.checked = false;
          if (window.Swal) {
            Swal.fire({
              icon: 'info',
              title: 'Hanya boleh 1 aktivitas',
              text: 'Batalkan centang aktivitas yang lain dulu untuk memilih yang berbeda.'
            });
          } else {
            alert('Hanya boleh 1 aktivitas. Batalkan centang yang lain dulu.');
          }
        }
      } else {
        // tidak ada yang checked → kembalikan ke kondisi normal per checkbox
        toggles.forEach(t => {
          if (t.dataset.hard !== '1') t.disabled = false;
          setFieldsetByCheckbox(t); // aktif/non-aktif sesuai statusnya
        });
      }
    }

    toggles.forEach(cb => {
      cb.addEventListener('change', function(){
        enforceSingleDone(this);
      });
    });

    // kondisi awal (kalau ada yang sudah Done dari server)
    enforceSingleDone();
  })();
  // ===================== /SINGLE-DONE LOCK =====================

  // ====== TANDAI AKSI & LOADING sebelum submit (agar popup bisa muncul di halaman tujuan) ======
  const form = document.getElementById('activityForm');
  const btnSave   = document.getElementById('btnSave');
  const btnFinish = document.getElementById('btnFinish');

  function markAction(kind){ // kind: 'save' | 'finish'
    try {
      localStorage.setItem('BR_SWAL_AFTER_REDIRECT', JSON.stringify({kind, t: Date.now()}));
    } catch(e){}
  }
  function showLoading() {
    if (typeof Swal === 'undefined') return;
    Swal.fire({
      title: 'Menyimpan…',
      text: 'Mohon tunggu sebentar',
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => { Swal.showLoading(); }
    });
  }

  // === VALIDATOR WAJIB KETIKA DONE ===
  function validateBeforeSubmit(){
    const cards = document.querySelectorAll('.activity-card');
    for (const card of cards) {
      const cb = card.querySelector('.toggle-done');
      if (!cb || !cb.checked || cb.disabled) continue; // validasi hanya kalau Done dicentang

      const needPhoto   = card.dataset.needPhoto === '1';
      const needsSegmen = card.dataset.needsSegmen === '1';

      // segmen wajib?
      if (needsSegmen) {
        const seg = card.querySelector('select.segmen-select');
        if (!seg || !seg.value) {
          // fokus segmen
          if (seg && seg.scrollIntoView) seg.scrollIntoView({behavior:'smooth', block:'center'});
          Swal && Swal.fire({icon:'warning', title:'Segmen belum dipilih', text:'Pilih segmen terlebih dahulu untuk aktivitas yang ditandai Done.'});
          return false;
        }
      }

      // foto wajib?
      if (needPhoto) {
        const existingBefore = parseInt(card.dataset.existingBefore || '0', 10);
        const existingAfter  = parseInt(card.dataset.existingAfter  || '0', 10);

        const upBefore = card.querySelector('.smart-uploader.uploader-before');
        const upAfter  = card.querySelector('.smart-uploader.uploader-after');
        const pickedBefore = parseInt((upBefore && upBefore.dataset.picked) || '0', 10);
        const pickedAfter  = parseInt((upAfter  && upAfter.dataset.picked)  || '0', 10);

        const totalBefore = existingBefore + pickedBefore;
        const totalAfter  = existingAfter  + pickedAfter;

        if (totalBefore < 1 || totalAfter < 1) {
          const target = (totalBefore < 1 ? upBefore : upAfter) || card;
          target && target.scrollIntoView && target.scrollIntoView({behavior:'smooth', block:'center'});
          Swal && Swal.fire({icon:'warning', title:'Foto belum lengkap', text:'Aktivitas bertanda Done wajib memiliki minimal 1 foto Before dan 1 foto After.'});
          return false;
        }
      }
    }
    return true;
  }

  if (btnSave) {
    btnSave.addEventListener('click', function(e){
      if (!validateBeforeSubmit()) { e.preventDefault(); return; }
      markAction('save');
      showLoading();
    });
  }
  if (btnFinish) {
    btnFinish.addEventListener('click', function(e){
      if (!validateBeforeSubmit()) { e.preventDefault(); return; }
      markAction('finish');
      showLoading();
    });
  }

  // === GUARD: cegah submit jika tidak ada perubahan + toggle tombol ===
  (function(){
    function activityChanged(card){
      // checkbox done
      const cb = card.querySelector('.toggle-done');
      if (cb) {
        const init = String(cb.dataset.initial ?? '');
        const now  = cb.checked ? '1' : '0';
        if (init !== now) return true;
      }

      // note
      const note = card.querySelector('.note-input');
      if (note) {
        const init = String(note.dataset.initial ?? '').trim();
        const now  = String(note.value ?? '').trim();
        if (init !== now) return true;
      }

      // segmen
      const seg = card.querySelector('select.segmen-select');
      if (seg) {
        const init = String(seg.dataset.initial ?? '');
        const now  = String(seg.value ?? '');
        if (init !== now) return true;
      }

      // file baru dipilih? (before/after)
      const uploaders = card.querySelectorAll('.smart-uploader');
      for (const up of uploaders) {
        const picked = parseInt(up.dataset.picked || '0', 10);
        if (picked > 0) return true;
      }

      return false;
    }

    function hasAnyChange(){
      const cards = document.querySelectorAll('.activity-card');
      for (const c of cards) {
        if (activityChanged(c)) return true;
      }
      return false;
    }

    function setButtonsState(){
      const changed = hasAnyChange();
      if (btnSave)   btnSave.disabled   = !changed;
      if (btnFinish) btnFinish.disabled = !changed;
    }

    // initial state
    setButtonsState();

    // pantau perubahan input2 utama
    document.addEventListener('input',  setButtonsState, true);
    document.addEventListener('change', setButtonsState, true);

    // intercept submit: selain "ada perubahan", juga validasi wajib isi saat Done
    const form = document.getElementById('activityForm');
    form.addEventListener('submit', function(e){
      if (!hasAnyChange()) {
        e.preventDefault();
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'info', title: 'Tidak ada perubahan', text: 'Tidak ada data yang diupdate. Lakukan perubahan dulu sebelum menyimpan.' });
        } else {
          alert('Tidak ada data yang diupdate. Lakukan perubahan dulu sebelum menyimpan.');
        }
        return;
      }
      if (!validateBeforeSubmit()) {
        e.preventDefault();
        return;
      }
    });
  })();

});
</script>

{{-- Inisialisasi Select2 (setelah DOM siap) --}}
<script>
$(function(){
  $('.select2').select2({
    placeholder: "— pilih segmen —",
    allowClear: true,
    width: '100%'
  });
});
</script>

@endsection
