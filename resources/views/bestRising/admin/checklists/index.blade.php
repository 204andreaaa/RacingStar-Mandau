@extends('layouts.appBestRising')

@section('main')
<div class="content-wrapper">

  <div class="card">
    <div class="card-header d-flex align-items-center flex-wrap gap-2">
      <h3 class="mb-0">Data Sesi Activity</h3>
      <div class="ms-auto ml-auto d-flex gap-2">
        <select id="f_team" class="form-control form-control-sm" style="min-width:130px">
          <option value="">Semua Team</option>
          <option value="SERPO">SERPO</option>
          <option value="NOC">NOC</option>
        </select>
        <select id="f_status" class="form-control form-control-sm" style="min-width:130px">
          <option value="">Semua Status</option>
          <option value="pending">Pending</option>
          <option value="completed">Completed</option>
        </select>
        <input type="date" id="f_from" class="form-control form-control-sm">
        <input type="date" id="f_to" class="form-control form-control-sm">
        <button class="btn btn-sm btn-outline-secondary" id="btnFilter">Filter</button>
        <button class="btn btn-sm btn-light" id="btnReset">Reset</button>
        {{-- tombol Export TIDAK di sini; kita inject di samping "Show entries" --}}
      </div>
    </div>

    <div class="card-body">
      <table id="table-checklists" class="table table-bordered">
        <thead>
          <tr>
            <th>No</th>
            <th>Mulai</th>
            <th>Selesai</th>
            <th>Team</th>
            <th>Nama</th>
            <th>Lokasi (Region / Serpo)</th>
            <th>Total Star</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  /* kasih jarak tombol export biar rapi di sebelah dropdown entries */
  #table-checklists_length { display:flex; align-items:center; gap:.75rem; }
  #btnExport { white-space: nowrap; }
</style>

<script>
$(function(){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

  // route helpers
  const ROUTES = {
    index   : "{{ route('admin.checklists.index') }}",
    destroy : "{{ route('admin.checklists.destroy', ':id') }}",
    show    : "{{ route('admin.checklists.show', ':id') }}", // kalau dipakai
    export  : "{{ route('admin.checklists.export') }}",      // export GET
  };
  const urlDestroy = id => ROUTES.destroy.replace(':id', id);
  const urlShow    = id => ROUTES.show.replace(':id', id);

  const table = $('#table-checklists').DataTable({
    processing: true,
    serverSide: true,
    order: [[1, 'desc']], // Mulai terbaru
    ajax: {
      url: ROUTES.index,
      data: d => {
        d.team      = $('#f_team').val();
        d.status    = $('#f_status').val();
        d.date_from = $('#f_from').val();
        d.date_to   = $('#f_to').val();
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
      // inject tombol Export di samping "Show X entries"
      if ($('#btnExport').length === 0) {
        $('#table-checklists_length').append(
          '<button id="btnExport" class="btn btn-sm btn-success">Export Excel</button>'
        );
      }
    }
  });

  // filter & reset
  $('#btnFilter').on('click', () => table.ajax.reload());
  $('#btnReset').on('click', function(){
    $('#f_team, #f_status').val('');
    $('#f_from, #f_to').val('');
    table.ajax.reload();
  });

  // Export Excel (ikut filter & global search; backend FromQuery ambil semua data, bukan cuma page)
  $(document).on('click', '#btnExport', function(){
    const params = new URLSearchParams({
      team:      $('#f_team').val() || '',
      status:    $('#f_status').val() || '',
      date_from: $('#f_from').val() || '',
      date_to:   $('#f_to').val() || '',
      search:    $('#table-checklists').DataTable().search() || ''
    });
    window.location.href = ROUTES.export + '?' + params.toString();
  });

  // hapus (SweetAlert kalau ada)
  // helper toast SweetAlert2
  const Toast = (typeof Swal !== 'undefined')
    ? Swal.mixin({ toast:true, position:'top-end', showConfirmButton:false, timer:2000, timerProgressBar:true })
    : null;

  $(document).on('click', '.btn-del', function(){
  const $btn = $(this);
  const url  = $btn.data('url');

  Swal.fire({
    title: 'Hapus checklist ini?',
    text: 'Semua item aktivitasnya juga akan terhapus.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, hapus',
    cancelButtonText: 'Batal'
  }).then(r => {
    if (!r.isConfirmed) return;

    $btn.prop('disabled', true);

    $.post(url, {_method:'DELETE'})
      .done(res => {
        // reload datatable tanpa reset halaman
        $('#table-checklists').DataTable().ajax.reload(null, false);

        // tampilkan modal sukses di tengah
        Swal.fire({
          title: 'Berhasil!',
          text: res?.message ?? 'Checklist dihapus.',
          icon: 'success',
          confirmButtonText: 'OK'
        });
      })
      .fail(xhr => {
        const msg = xhr.responseJSON?.message ?? 'Gagal menghapus.';
        Swal.fire('Gagal', msg, 'error');
      })
      .always(() => $btn.prop('disabled', false));
  });
});


});
</script>
@endsection
