@extends('layouts.appBestRising')

@section('main')
@php
  $u = session('auth_user');
  $isSuper = isset($u['email']) && $u['email'] === 'superadmin@mandau.id';
  $lockedRegionId = $u['region_id'] ?? $u['id_region'] ?? null;
@endphp

<div class="content-wrapper">
  <div class="card shadow-sm border-0">
    <div class="card-header d-flex align-items-center flex-wrap gap-2" style="background:#f8fafc;">
      <h3 class="mb-0 fw-bold">Data Sesi Activity</h3>

      <div class="ms-auto ml-auto d-flex gap-2 align-items-center flex-wrap">
        <div class="d-flex gap-2">
          <select id="f_region" class="form-control form-control-sm" style="min-width:200px">
            @if(!$lockedRegionId)
              <option value="">Semua Region</option>
            @endif
            @foreach(\DB::table('regions')->orderBy('nama_region')->get() as $rg)
              <option value="{{ $rg->id_region }}">{{ $rg->nama_region }}</option>
            @endforeach
          </select>

          <select id="f_serpo" class="form-control form-control-sm" style="min-width:200px">
            <option value="">Semua Serpo</option>
          </select>
        </div>

        <button class="btn btn-sm btn-primary d-none" id="btnFilter">
          <i class="fas fa-filter me-1"></i> Terapkan
        </button>
        <button class="btn btn-sm btn-light border" id="btnReset">
          <i class="fas fa-redo me-1"></i> Reset
        </button>
      </div>
    </div>

    <div class="card-body">
      <div class="rounded-3 p-3 mb-3" style="background:#f1f5f9; border:1px dashed #cbd5e1;">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="fw-semibold text-muted"><i class="fas fa-sliders-h me-2"></i>Filter aktif:</span>
          <div id="activeChips" class="flex-grow-1"></div>
          <button class="btn btn-sm btn-outline-secondary" id="btnClearAll" type="button">
            <i class="fas fa-eraser me-1"></i>Bersihkan
          </button>
        </div>
      </div>

      {{-- ===== TABS STATUS (nilai sesuai DB) ===== --}}
      <div class="mb-3">
        <ul class="nav nav-pills gap-2" id="statusTabs">
          <li class="nav-item">
            <a href="#" class="nav-link active" data-status="">
              <i class="fas fa-layer-group me-1"></i> Semua
              <span class="badge bg-secondary" id="b-all">0</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link" data-status="review admin">
              <i class="fas fa-clipboard-check me-1"></i> Review Admin
              <span class="badge bg-secondary" id="b-review_admin">0</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link" data-status="pending">
              Pending <span class="badge bg-secondary" id="b-pending">0</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link" data-status="completed">
              Completed <span class="badge bg-secondary" id="b-completed">0</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link" data-status="rejected">
              Rejected <span class="badge bg-secondary" id="b-reject">0</span>
            </a>
          </li>
        </ul>
      </div>

      <table id="table-checklists" class="table table-bordered table-striped w-100">
        <thead class="table-light">
          <tr>
            <th style="width:36px;"><input type="checkbox" id="cb-all"></th>
            <th style="width:34px;" class="text-center">#</th>
            <th style="width:60px;">No.</th>
            <th>Mulai</th>
            <th>Selesai</th>
            <th>Team</th>
            <th>Nama</th>
            <th>Lokasi (Region / Serpo)</th>
            <th class="text-end">Total Star</th>
            <th>Status</th>
            <th style="width:160px;">Aksi</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

{{-- ================= MODAL PREVIEW FOTO ================= --}}
<div id="photoModal" class="modal fade" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content bg-dark text-center">
      <div class="modal-header border-0">
        <h5 class="modal-title text-white" id="photoModalTitle">Preview Foto</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div id="photoCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-touch="true" data-bs-interval="false">
          <div class="carousel-inner" id="photoCarouselInner"></div>
          <button class="carousel-control-prev" type="button" data-bs-target="#photoCarousel" data-bs-slide="prev" data-target="#photoCarousel" data-slide="prev">
            <span class="carousel-control-prev-icon"></span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#photoCarousel" data-bs-slide="next" data-target="#photoCarousel" data-slide="next">
            <span class="carousel-control-next-icon"></span>
          </button>
        </div>
      </div>
      <div class="modal-footer border-0 text-white small justify-content-center" id="photoModalCaption"></div>
    </div>
  </div>
</div>

<style>
  .modal-content.bg-dark { background-color: rgba(0,0,0,0.9) !important; }
  .carousel-item img { max-height: 80vh; max-width: 100%; object-fit: contain; }

  #cb-all { transform: translateY(2px); }
  #table-checklists_length { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }
  #btnExport, #btnDelAll { white-space: nowrap; }
  .chip { display:inline-flex; align-items:center; gap:.45rem; background:#e2e8f0; color:#0f172a; border-radius:999px; padding:.28rem .65rem; font-size:.76rem; font-weight:600; margin:.25rem .35rem .25rem 0; border:1px solid #cbd5e1; }
  .chip .x { cursor:pointer; display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; border-radius:50%; background:#0f172a; color:#fff; font-size:.65rem; }
  .table thead th { vertical-align: middle; }
  .gallery-grid { display:grid; grid-template-columns: repeat(2, 1fr); gap:.5rem; }
  .gallery-grid .g-item { display:block; border:1px solid #e2e8f0; border-radius:.5rem; overflow:hidden; }
  .gallery-grid img { width:100%; height:100px; object-fit:cover; display:block; }
  .note-pre { white-space:pre-wrap; }
  td.dt-control { cursor: pointer; }
  td.dt-control i { transition: transform .15s ease; }
  .dataTables_wrapper .dataTables_scrollHeadInner,
  .dataTables_wrapper .dataTables_scrollHeadInner table{ width: 100% !important; }

  #statusTabs .nav-link { font-weight:600; }
  #statusTabs .badge { margin-left:.4rem; }
</style>

<script>
  const IS_BS5 = !!(window.bootstrap && bootstrap.Modal && typeof bootstrap.Modal.getOrCreateInstance === 'function');
  function modalShow(modalEl){ if (IS_BS5) bootstrap.Modal.getOrCreateInstance(modalEl).show(); else $(modalEl).modal('show'); }
  function carouselApi(el){
    if (IS_BS5) return bootstrap.Carousel.getOrCreateInstance(el, { interval:false, touch:true, wrap:true });
    return { next:()=>$(el).carousel('next'), prev:()=>$(el).carousel('prev'), to:(i)=>$(el).carousel(i) };
  }
</script>

<script>
  // PREVIEW FOTO (gallery-grid)
  $(document).on('click', '.gallery-grid .g-item img', function(e){
    e.preventDefault();
    const $grid = $(this).closest('.gallery-grid');
    const allImages = $grid.find('.g-item img').map(function(){ return $(this).attr('src'); }).get();
    const idx = $grid.find('.g-item img').index($(this));
    const $inner = $('#photoCarouselInner').empty();
    allImages.forEach((u,i)=> $inner.append(`<div class="carousel-item ${i===idx?'active':''}"><img src="${u}" class="d-block mx-auto" alt=""></div>`));
    $('#photoModalTitle').text('Foto'); $('#photoModalCaption').text(`${idx+1} / ${allImages.length}`); modalShow(document.getElementById('photoModal'));
  });
  // PREVIEW (photo-scroller) optional
  $(document).on('click', '.photo-scroller .photo-item img', function(e){
    e.preventDefault();
    const $imgs = $(this).closest('td, .photo-scroller').find('img');
    const urls  = $imgs.map(function(){ return $(this).attr('data-full') || $(this).attr('src'); }).get();
    const start = $imgs.index(this);
    const $inner = $('#photoCarouselInner').empty();
    urls.forEach((u,i)=> $inner.append(`<div class="carousel-item ${i===start?'active':''}"><img src="${u}" class="d-block mx-auto" alt=""></div>`));
    $('#photoModalTitle').text('Foto'); $('#photoModalCaption').text(`${start+1} / ${urls.length}`); modalShow(document.getElementById('photoModal'));
  });
  // Caption updater
  (function(){ const el=document.getElementById('photoCarousel'); if(!el) return;
    el.addEventListener('slid.bs.carousel', function(){ const items=el.querySelectorAll('.carousel-item'); const i=[...items].findIndex(x=>x.classList.contains('active'));
      const cap=document.getElementById('photoModalCaption'); if(cap) cap.textContent=`${i+1} / ${items.length}`; });
  })();
  // Swipe
  (function(){ const MIN=40; let startX=0, down=false; function bindSwipe($el){ const el=$el[0]; if(!el) return; const api=carouselApi(el);
    el.addEventListener('touchstart',e=>{startX=e.touches[0].clientX;},{passive:true});
    el.addEventListener('touchmove', e=>{ const dx=e.touches[0].clientX-startX; if(Math.abs(dx)>MIN){ dx<0?api.next():api.prev(); startX=e.touches[0].clientX; } },{passive:true});
    el.addEventListener('mousedown',e=>{down=true; startX=e.clientX;}); el.addEventListener('mouseup',e=>{ if(!down) return; const dx=e.clientX-startX; if(Math.abs(dx)>MIN) (dx<0?api.next():api.prev()); down=false;});
    el.addEventListener('mouseleave',()=>{down=false;}); }
    document.getElementById('photoModal')?.addEventListener('shown.bs.modal', ()=>bindSwipe($('#photoCarousel')));
  })();
</script>

<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
  const IS_SUPER    = @json($isSuper);
  const LOCK_REGION = @json($lockedRegionId);

  // label & nilai status sesuai DB
  const STATUS_LABELS = {
    '' : 'Semua',
    'review admin': 'Review Admin',
    'pending': 'Pending',
    'completed': 'Completed',
    'rejected': 'Rejected'
  };
  let CURRENT_STATUS = ''; // default tab "Semua"

  // Tampilkan tombol & kolom sesuai tab:
  // - Kolom checkbox disembunyikan kalau tab "pending"
  // - Tombol Approve hanya muncul di "" (Semua) & "review admin"
  function updateActionToolbar(){
    const showApproveBtn = (CURRENT_STATUS === '' || CURRENT_STATUS === 'review admin');
    const showCbCol      = (CURRENT_STATUS !== 'pending');
    $('#btnApprove').toggleClass('d-none', !showApproveBtn);
    if (!showApproveBtn) $('#btnApprove').prop('disabled', true);
    const api = $('#table-checklists').DataTable();
    if (api.column) api.column(0).visible(showCbCol);
  }
</script>

<script>
$(function(){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

  const ROUTES = {
    index     : "{{ route('admin.checklists.index') }}",
    destroy   : "{{ route('admin.checklists.destroy', ':id') }}",
    show      : "{{ route('admin.checklists.show', ':id') }}",
    destroyAll: "{{ route('admin.checklists.destroyAll') }}",
    counts    : "/admin/checklists/counts", // endpoint badge
  };
  const urlDestroy = id => ROUTES.destroy.replace(':id', id);
  const urlShow    = id => ROUTES.show.replace(':id', id);

  function fillOptions($select, items, placeholder='Semua Serpo') {
    $select.empty().append(`<option value="">${placeholder}</option>`);
    if (!items) return;
    items.forEach(row => {
      const id   = row.id ?? row.id_serpo ?? row.value ?? null;
      const text = row.text ?? row.nama_serpo ?? row.label ?? '';
      if (id !== null && text !== '') $select.append(`<option value="${id}">${text}</option>`);
    });
  }

  let reloadTimer = null;
  const reloadTableDebounced = (ms=200) => { clearTimeout(reloadTimer); reloadTimer=setTimeout(()=>$('#table-checklists').DataTable().ajax.reload(null,false), ms); };

  function refreshChips(){
    const $wrap = $('#activeChips').empty();
    const regionVal = $('#f_region').val();
    const serpoVal  = $('#f_serpo').val();

    if (regionVal) {
      const txt = $('#f_region option:selected').text();
      if (LOCK_REGION) $wrap.append(`<span class="chip">Region: ${txt}</span>`);
      else $wrap.append(`<span class="chip">Region: ${txt} <span class="x" data-k="region">&times;</span></span>`);
    }
    if (serpoVal)  $wrap.append(`<span class="chip">Serpo: ${$('#f_serpo option:selected').text()} <span class="x" data-k="serpo">&times;</span></span>`);

    const lbl = STATUS_LABELS[CURRENT_STATUS] || 'Semua';
    if (CURRENT_STATUS) $wrap.append(`<span class="chip">Status: ${lbl} <span class="x" data-k="status">&times;</span></span>`);

    if (!regionVal && !serpoVal && !CURRENT_STATUS) $wrap.append('<span class="text-muted small">Tidak ada filter</span>');
  }

  // chip X
  $(document).on('click', '.chip .x', function(){
    const k = $(this).data('k');
    if (k === 'region') { if (LOCK_REGION) return; $('#f_region').val(''); fillOptions($('#f_serpo'), null); }
    if (k === 'serpo')  { $('#f_serpo').val(''); }
    if (k === 'status') {
      CURRENT_STATUS = '';
      $('#statusTabs .nav-link').removeClass('active');
      $('#statusTabs .nav-link[data-status=""]').addClass('active');
    }
    refreshChips(); updateActionToolbar(); reloadTableDebounced();
  });

  $('#btnClearAll').on('click', function(){
    if (LOCK_REGION) { $('#f_serpo').val(''); }
    else { $('#f_region').val(''); fillOptions($('#f_serpo'), null); }
    CURRENT_STATUS = '';
    $('#statusTabs .nav-link').removeClass('active');
    $('#statusTabs .nav-link[data-status=""]').addClass('active');
    refreshChips(); updateActionToolbar(); reloadTableDebounced();
  });

  $('#btnReset').on('click', function(){
    if (LOCK_REGION) {
      $('#f_region').val(String(LOCK_REGION));
      $.get("{{ route('admin.serpo.byRegion', ['id_region' => 'IDR']) }}".replace('IDR', LOCK_REGION))
        .done(res => { fillOptions($('#f_serpo'), (res?.data ?? res)); })
        .always(() => { $('#f_serpo').val(''); });
    } else { $('#f_region').val(''); fillOptions($('#f_serpo'), null); }
    CURRENT_STATUS = '';
    $('#statusTabs .nav-link').removeClass('active');
    $('#statusTabs .nav-link[data-status=""]').addClass('active');
    refreshChips(); updateActionToolbar(); reloadTableDebounced();
  });

  $('#f_region').on('change', function(){
    if (LOCK_REGION) return;
    const id = $(this).val();
    fillOptions($('#f_serpo'), null);
    if (!id) { refreshChips(); updateActionToolbar(); return reloadTableDebounced(); }
    $.get("{{ route('admin.serpo.byRegion', ['id_region' => 'IDR']) }}".replace('IDR', id))
      .done(res => { fillOptions($('#f_serpo'), (res?.data ?? res)); })
      .always(() => { refreshChips(); updateActionToolbar(); reloadTableDebounced(); });
  });

  $('#f_serpo').on('change', function(){ refreshChips(); updateActionToolbar(); reloadTableDebounced(); });

  function applyRegionLockIfAny(){
    if (!LOCK_REGION) return;
    $('#f_region').val(String(LOCK_REGION)).prop('disabled', true);
    $('#f_region option[value=""]').remove();
    $.get("{{ route('admin.serpo.byRegion', ['id_region' => 'IDR']) }}".replace('IDR', LOCK_REGION))
      .done(res => { fillOptions($('#f_serpo'), (res?.data ?? res)); })
      .always(() => { refreshChips(); updateActionToolbar(); });
  }

  // tab status
  $('#statusTabs').on('click', '.nav-link', function(e){
    e.preventDefault();
    $('#statusTabs .nav-link').removeClass('active');
    $(this).addClass('active');
    CURRENT_STATUS = $(this).data('status') || '';
    refreshChips(); updateActionToolbar(); reloadTableDebounced();
  });

  // ===== DataTable =====
  const table = $('#table-checklists').DataTable({
    processing: true,
    serverSide: true,
    scrollX: true,
    order: [[3, 'desc']],
    ajax: {
      url: ROUTES.index,
      data: d => {
        d.region = LOCK_REGION ? String(LOCK_REGION) : ($('#f_region').val() || '');
        d.serpo  = $('#f_serpo').val()  || '';
        d.status = CURRENT_STATUS || ''; // kirim nilai sesuai DB (termasuk spasi)
      }
    },
    columns: [
      { data:'cb', orderable:false, searchable:false, width: 26 },
      { data:null, orderable:false, searchable:false, className:'dt-control text-center', width:34, defaultContent:'<i class="fas fa-chevron-right"></i>' },
      { data:'DT_RowIndex', orderable:false, searchable:false, width:60 },
      { data:'started_at',  name:'started_at' },
      { data:'submitted_at',name:'submitted_at' },
      { data:'team',        name:'team' },
      { data:'user_nama',   name:'user_nama' },
      { data:'lokasi',      name:'lokasi', orderable:false },
      { data:'total_point', name:'total_point', className:'text-end' },
      {
        data: 'status',
        name: 'status',
        render: function (st, type) {
          if (type !== 'display') return st; // biar sort/search/export tetap pakai teks asli
          const s = String(st || '').toLowerCase();
          const map = {
            'review admin': 'secondary',
            'pending'     : 'warning',
            'completed'   : 'success',
            'rejected'    : 'danger'
          };
          const cls = map[s] || 'secondary';
          return `<span class="badge badge-${cls}">${st || '-'}</span>`;
        }
      },
      { data:'action',      orderable:false, searchable:false, width: 160 },
    ],
    // Hapus checkbox per-baris kalau statusnya 'pending' (berlaku juga saat tab "Semua")
    createdRow: function(row, data){
      const st = String(data?.status || '').toLowerCase();
      if (st === 'pending') { $('td:eq(0)', row).html(''); }
    },
    rowCallback: (row) => { $(row).addClass('row-clickable'); },
    initComplete: function(){
      const $len = $('#table-checklists_length');
      if ($('#btnApprove').length === 0) $len.append('<button id="btnApprove" class="btn btn-sm btn-primary" disabled><i class="fas fa-check me-1"></i> Approve Terpilih</button>');
      if (IS_SUPER && $('#btnDelAll').length === 0) $len.append('<button id="btnDelAll" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt me-1"></i> Hapus Semua (sesuai filter)</button>');
      applyRegionLockIfAny(); refreshChips(); updateActionToolbar();
    }
  });

  // Hapus satuan
  $(document).on('click', '.btn-del', function(){
    const $btn = $(this); const url  = $btn.data('url');
    Swal.fire({ title:'Hapus checklist ini?', text:'Semua item, hasil aktivitas, dan FOTO terkait akan terhapus.', icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus', cancelButtonText:'Batal' })
      .then(r => {
        if (!r.isConfirmed) return;
        $btn.prop('disabled', true);
        $.post(url, {_method:'DELETE'})
          .done(res => { $('#table-checklists').DataTable().ajax.reload(null, false); Swal.fire({ title:'Berhasil!', text: res?.message ?? 'Checklist dihapus.', icon:'success' }); })
          .fail(xhr => { Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Gagal menghapus.', 'error'); })
          .always(() => $btn.prop('disabled', false));
      });
  });

  // Hapus massal
  $(document).on('click', '#btnDelAll', function(){
    Swal.fire({ title:'Hapus SEMUA data sesuai filter?', html:'Ini akan menghapus semua checklist yang sedang difilter <b>BESERTA seluruh foto</b> di storage/public.', icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus semua', cancelButtonText:'Batal' })
      .then(r => {
        if (!r.isConfirmed) return;
        const payload = {
          _method: 'DELETE',
          region: LOCK_REGION ? String(LOCK_REGION) : ($('#f_region').val() || ''),
          serpo : $('#f_serpo').val() || '',
          search: $('#table-checklists').DataTable().search() || '',
          status: CURRENT_STATUS || ''
        };
        $.ajax({ url: ROUTES.destroyAll, type:'POST', data: payload })
          .done(res => { $('#table-checklists').DataTable().ajax.reload(null, false); Swal.fire('Berhasil', res?.message || 'Data dihapus.', 'success'); })
          .fail(xhr => { Swal.fire('Gagal', xhr.responseJSON?.message || 'Error', 'error'); });
      });
  });

  // Detail (child row)
  function renderDetailTable(items){
    const cellImgs = (arr) => {
      if (!arr || !arr.length) return '<span class="text-muted">-</span>';
      return `<div class="gallery-grid">${arr.map(u => `<a href="${u}" target="_blank" class="g-item"><img src="${u}" alt=""></a>`).join('')}</div>`;
    };
    const rows = items.map(it => `
      <tr>
        <td>${it.activity}<br><small class="text-muted">Sub Aktivitas: ${it.sub_activities}</small><br><small class="text-muted">Segmen: ${it.segmen}</small></td>
        <td>${cellImgs(it.before)}</td>
        <td>${cellImgs(it.after)}</td>
        <td class="note-pre">${it.note}</td>
      </tr>`).join('');
    return `
      <table class="table table-bordered align-middle mt-2 mb-2">
        <thead><tr><th>Aktivitas</th><th style="width:220px;">Before</th><th style="width:220px;">After</th><th>Catatan</th></tr></thead>
        <tbody>${rows || `<tr><td colspan="4" class="text-center text-muted">Belum ada item</td></tr>`}</tbody>
      </table>`;
  }

  $('#table-checklists tbody').on('click', 'td.dt-control', function(){
    const tr  = $(this).closest('tr'); const row = table.row(tr); const $icon = $(this).find('i');
    if (row.child.isShown()) { row.child.hide(); tr.removeClass('shown'); $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right'); return; }
    const id = row.data()?.id; if (!id) return;
    row.child('<div class="text-muted p-2">Memuat detail…</div>').show(); tr.addClass('shown');
    $.get("{{ route('admin.checklists.items', ':id') }}".replace(':id', id))
      .done(res => { row.child(renderDetailTable(res?.data || [])).show(); $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down'); })
      .fail(() => { row.child('<div class="text-danger p-2">Gagal memuat detail.</div>').show(); });
  });

  // Approve bulk
  $(document).on('click', '#btnApprove', function(){
    const ids = $('#table-checklists tbody input.cb-approve:checked').map(function(){ return $(this).val(); }).get();
    if (!ids.length) return Swal.fire('Info', 'Pilih minimal satu checklist yang dapat di-approve.', 'info');
    Swal.fire({ title:'Approve checklist terpilih?', html:'Status akan diubah menjadi <b>completed</b>.', icon:'question', showCancelButton:true, confirmButtonText:'Ya, approve', cancelButtonText:'Batal' })
      .then(r => {
        if (!r.isConfirmed) return;
        $.post("{{ route('admin.checklists.approveBulk') }}", { ids })
          .done(res => { Swal.fire('Berhasil', res?.message || 'Approve sukses.', 'success'); $('#table-checklists').DataTable().ajax.reload(null, false); })
          .fail(xhr => { Swal.fire('Gagal', xhr.responseJSON?.message || 'Approve gagal.', 'error'); });
      });
  });

  // Header "select all" & state tombol
  function updateApproveBtnState() {
    const $cb = $('#table-checklists tbody .cb-approve');
    const total = $cb.length, checked = $cb.filter(':checked').length;
    $('#btnApprove').prop('disabled', checked === 0);
    const $all = $('#cb-all')[0]; if (!$all) return;
    if (total === 0) { $all.checked = false; $all.indeterminate = false; }
    else if (checked === 0) { $all.checked = false; $all.indeterminate = false; }
    else if (checked === total) { $all.checked = true; $all.indeterminate = false; }
    else { $all.checked = false; $all.indeterminate = true; }
  }
  $(document).on('change', '#cb-all', function(){
    const check = this.checked;
    $('#table-checklists tbody .cb-approve').prop('checked', check);
    updateApproveBtnState();
  });
  $(document).on('change', '#table-checklists tbody .cb-approve', updateApproveBtnState);

  $('#table-checklists').on('draw.dt', function(){
    updateApproveBtnState();
    $('#table-checklists tbody td.dt-control i').removeClass('fa-chevron-down').addClass('fa-chevron-right');
    refreshStatusCounts();
    updateActionToolbar();
  });

  // ===== Badge count per status =====
  function pick(obj, keys){ for(const k of keys){ if (obj && obj[k] != null) return obj[k]; } return 0; }
  function refreshStatusCounts(){
    const api = $('#table-checklists').DataTable();
    const payload = {
      region: LOCK_REGION ? String(LOCK_REGION) : ($('#f_region').val() || ''),
      serpo : $('#f_serpo').val() || '',
      search: api.search() || ''
    };
    $.get(ROUTES.counts, payload).done(res => {
      const d = res?.data || res || {};
      $('#b-all').text(pick(d, ['all','total']));
      $('#b-review_admin').text(pick(d, ['review admin','review_admin','review','reviewAdmin']));
      $('#b-pending').text(pick(d, ['pending']));
      $('#b-completed').text(pick(d, ['completed','complete']));
      $('#b-reject').text(pick(d, ['rejected','reject']));
    }).fail(()=>{ /* silent */ });
  }
  setTimeout(refreshStatusCounts, 400);

  // apply lock + awal
  applyRegionLockIfAny();
});
</script>
@endsection
