@extends('layouts.appBestRising')

@section('main')
@php
  $u = session('auth_user');
  $isSuper = isset($u['email']) && $u['email'] === 'superadmin@mandau.id';
@endphp

<div class="content-wrapper">

  <div class="card shadow-sm border-0">
    {{-- HEADER --}}
    <div class="card-header d-flex align-items-center flex-wrap gap-2" style="background:#f8fafc;">
      <h3 class="mb-0 fw-bold">Data Sesi Activity</h3>

      <div class="ms-auto ml-auto d-flex gap-2 align-items-center flex-wrap">
        {{-- Filter ringkas (Region → Serpo), auto-apply --}}
        <div class="d-flex gap-2">
          <select id="f_region" class="form-control form-control-sm" style="min-width:200px">
            <option value="">Semua Region</option>
            @foreach(\DB::table('regions')->orderBy('nama_region')->get() as $rg)
              <option value="{{ $rg->id_region }}">{{ $rg->nama_region }}</option>
            @endforeach
          </select>

          <select id="f_serpo" class="form-control form-control-sm" style="min-width:200px">
            <option value="">Semua Serpo</option>
          </select>
        </div>

        {{-- “Terapkan” disembunyikan karena auto-apply --}}
        <button class="btn btn-sm btn-primary d-none" id="btnFilter">
          <i class="fas fa-filter me-1"></i> Terapkan
        </button>
        <button class="btn btn-sm btn-light border" id="btnReset">
          <i class="fas fa-redo me-1"></i> Reset
        </button>
      </div>
    </div>

    <div class="card-body">

      {{-- PANEL FILTER (soft look) --}}
      <div class="rounded-3 p-3 mb-3" style="background:#f1f5f9; border:1px dashed #cbd5e1;">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="fw-semibold text-muted"><i class="fas fa-sliders-h me-2"></i>Filter aktif:</span>
          <div id="activeChips" class="flex-grow-1"></div>
          <button class="btn btn-sm btn-outline-secondary" id="btnClearAll" type="button">
            <i class="fas fa-eraser me-1"></i>Bersihkan
          </button>
        </div>
      </div>

      <table id="table-checklists" class="table table-bordered table-striped w-100">
        <thead class="table-light">
          <tr>
            <th style="width:60px;">No</th>
            <th>Mulai</th>
            <th>Selesai</th>
            <th>Team</th>
            <th>Nama</th>
            <th>Lokasi (Region / Serpo)</th>
            <th class="text-end">Total Star</th>
            <th>Status</th>
            <th style="width:140px;">Aksi</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  /* Tempat tombol custom di sisi "Show entries" */
  #table-checklists_length { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }
  #btnExport, #btnDelAll { white-space: nowrap; }

  /* Chip filter */
  .chip {
    display:inline-flex; align-items:center; gap:.45rem;
    background:#e2e8f0; color:#0f172a; border-radius:999px;
    padding:.28rem .65rem; font-size:.76rem; font-weight:600; margin:.25rem .35rem .25rem 0;
    border:1px solid #cbd5e1;
  }
  .chip .x {
    cursor:pointer; display:inline-flex; align-items:center; justify-content:center;
    width:18px; height:18px; border-radius:50%; background:#0f172a; color:#fff; font-size:.65rem;
  }

  .table thead th { vertical-align: middle; }
</style>

<script>
  const IS_SUPER = @json($isSuper);
</script>
<script>
$(function(){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

  // ================= ROUTES (tetap) =================
  const ROUTES = {
    index     : "{{ route('admin.checklists.index') }}",
    destroy   : "{{ route('admin.checklists.destroy', ':id') }}",
    show      : "{{ route('admin.checklists.show', ':id') }}",
    export    : "{{ route('admin.checklists.export') }}",
    destroyAll: "{{ route('admin.checklists.destroyAll') }}",
  };
  const urlDestroy = id => ROUTES.destroy.replace(':id', id);
  const urlShow    = id => ROUTES.show.replace(':id', id);

  // ================= Helpers =================
  function fillOptions($select, items, placeholder='Semua Serpo') {
    $select.empty().append(`<option value="">${placeholder}</option>`);
    if (!items) return;
    items.forEach(row => {
      const id   = row.id ?? row.id_serpo ?? row.value ?? null;
      const text = row.text ?? row.nama_serpo ?? row.label ?? '';
      if (id !== null && text !== '') {
        $select.append(`<option value="${id}">${text}</option>`);
      }
    });
  }

  // Debounce reload agar smooth (auto-apply)
  let reloadTimer = null;
  function reloadTableDebounced(ms=200){
    clearTimeout(reloadTimer);
    reloadTimer = setTimeout(() => $('#table-checklists').DataTable().ajax.reload(null,false), ms);
  }

  // Chip builder
  function refreshChips(){
    const $wrap = $('#activeChips').empty();
    const regionVal = $('#f_region').val();
    const serpoVal  = $('#f_serpo').val();

    if (regionVal) {
      const txt = $('#f_region option:selected').text();
      $wrap.append(`<span class="chip">Region: ${txt} <span class="x" data-k="region">&times;</span></span>`);
    }
    if (serpoVal) {
      const txt = $('#f_serpo option:selected').text();
      $wrap.append(`<span class="chip">Serpo: ${txt} <span class="x" data-k="serpo">&times;</span></span>`);
    }

    if (!regionVal && !serpoVal) {
      $wrap.append('<span class="text-muted small">Tidak ada filter</span>');
    }
  }

  $(document).on('click', '.chip .x', function(){
    const k = $(this).data('k');
    if (k === 'region') { $('#f_region').val(''); fillOptions($('#f_serpo'), null); }
    if (k === 'serpo')  { $('#f_serpo').val(''); }
    refreshChips();
    reloadTableDebounced();
  });

  $('#btnClearAll').on('click', function(){
    $('#f_region').val('');
    fillOptions($('#f_serpo'), null);
    refreshChips();
    reloadTableDebounced();
  });

  // ================= Dependent Serpo + Auto-apply =================
  $('#f_region').on('change', function(){
    const id = $(this).val();
    fillOptions($('#f_serpo'), null);

    if (!id) { refreshChips(); return reloadTableDebounced(); }

    $.get("{{ route('admin.serpo.byRegion', ['id_region' => 'IDR']) }}".replace('IDR', id))
      .done(res => { fillOptions($('#f_serpo'), (res?.data ?? res)); })
      .always(() => { refreshChips(); reloadTableDebounced(); }); // reload setelah serpo dimuat/gagal
  });

  $('#f_serpo').on('change', function(){
    refreshChips();
    reloadTableDebounced();
  });

  $('#btnReset').on('click', function(){
    $('#f_region').val('');
    fillOptions($('#f_serpo'), null);
    refreshChips();
    reloadTableDebounced();
  });

  // ================= DataTables =================
  const table = $('#table-checklists').DataTable({
    processing: true,
    serverSide: true,
    order: [[1, 'desc']], // Mulai terbaru
    ajax: {
      url: ROUTES.index,
      data: d => {
        d.region = $('#f_region').val() || '';
        d.serpo  = $('#f_serpo').val()  || '';
      }
    },
    columns: [
      { data:'DT_RowIndex', orderable:false, searchable:false },
      { data:'started_at',  name:'started_at' },
      { data:'submitted_at',name:'submitted_at' },
      { data:'team',        name:'team' },
      { data:'user_nama',   name:'user_nama' },
      { data:'lokasi',      name:'lokasi', orderable:false },
      { data:'total_point', name:'total_point', className:'text-end' },
      { data:'status',      name:'status' },
      { data:'action',      orderable:false, searchable:false },
    ],
    initComplete: function(){
      // inject tombol Export & Hapus Semua di samping "Show X entries"
      const $len = $('#table-checklists_length');
      if ($('#btnExport').length === 0) {
        $len.append('<button id="btnExport" class="btn btn-sm btn-success"><i class="fas fa-file-excel me-1"></i> Export Excel</button>');
      }
      if (IS_SUPER && $('#btnDelAll').length === 0) {
        $len.append('<button id="btnDelAll" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt me-1"></i> Hapus Semua (sesuai filter)</button>');
      }
      refreshChips();
    }
  });

  // ================= Export (ikut filter + search global) =================
  $(document).on('click', '#btnExport', function(){
    const params = new URLSearchParams({
      region: $('#f_region').val() || '',
      serpo : $('#f_serpo').val()  || '',
      search: $('#table-checklists').DataTable().search() || ''
    });
    window.location.href = ROUTES.export + '?' + params.toString();
  });

  // ================= Hapus satuan =================
  $(document).on('click', '.btn-del', function(){
    const $btn = $(this);
    const url  = $btn.data('url');

    Swal.fire({
      title: 'Hapus checklist ini?',
      text: 'Semua item, hasil aktivitas, dan FOTO terkait akan terhapus.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus',
      cancelButtonText: 'Batal'
    }).then(r => {
      if (!r.isConfirmed) return;

      $btn.prop('disabled', true);

      $.post(url, {_method:'DELETE'})
        .done(res => {
          $('#table-checklists').DataTable().ajax.reload(null, false);
          Swal.fire({ title: 'Berhasil!', text: res?.message ?? 'Checklist dihapus.', icon: 'success', confirmButtonText: 'OK' });
        })
        .fail(xhr => {
          const msg = xhr.responseJSON?.message ?? 'Gagal menghapus.';
          Swal.fire('Gagal', msg, 'error');
        })
        .always(() => $btn.prop('disabled', false));
    });
  });

  // ================= Hapus massal (ikut filter + search global) =================
  $(document).on('click', '#btnDelAll', function(){
    Swal.fire({
      title: 'Hapus SEMUA data sesuai filter?',
      html: 'Ini akan menghapus semua checklist yang sedang difilter <b>BESERTA seluruh foto</b> di storage/public.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus semua',
      cancelButtonText: 'Batal'
    }).then(r => {
      if (!r.isConfirmed) return;

      const payload = {
        _method: 'DELETE',
        region: $('#f_region').val() || '',
        serpo : $('#f_serpo').val()  || '',
        search: $('#table-checklists').DataTable().search() || ''
      };

      $.ajax({
        url: ROUTES.destroyAll,
        type: 'POST',
        data: payload
      }).done(res => {
        $('#table-checklists').DataTable().ajax.reload(null, false);
        Swal.fire('Berhasil', res?.message || 'Data dihapus.', 'success');
      }).fail(xhr => {
        Swal.fire('Gagal', xhr.responseJSON?.message || 'Error', 'error');
      });
    });
  });

});
</script>
@endsection
