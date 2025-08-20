@extends('layouts.appBestRising')

@section('main')
<div class="content-wrapper">
  <section class="content-header"><h1>Rekap Checklist</h1></section>

  <div class="card">
    <div class="card-header d-flex align-items-center flex-wrap gap-2">
      <h3 class="mb-0">Data Sesi Checklist</h3>
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
            <th>Lokasi (Region / Serpo / Segmen)</th>
            <th>Total Star</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

{{-- meta csrf sudah ada di layout, tapi aman kalau mau taruh lagi --}}
<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
$(function(){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

  // route helpers
  const ROUTES = {
    index   : "{{ route('admin.checklists.index') }}",
    destroy : "{{ route('admin.checklists.destroy', ':id') }}",
    show    : "{{ route('admin.checklists.show', ':id') }}", // kalau mau dipakai di action client-side
  };
  const urlDestroy = id => ROUTES.destroy.replace(':id', id);
  const urlShow    = id => ROUTES.show.replace(':id', id);

  const table = $('#table-checklists').DataTable({
    processing: true,
    serverSide: true,
    order: [[1, 'desc']], // sort by "Mulai" (started_at) terbaru dulu
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
    ]
  });

  // filter & reset
  $('#btnFilter').on('click', () => table.ajax.reload());
  $('#btnReset').on('click', function(){
    $('#f_team, #f_status').val('');
    $('#f_from, #f_to').val('');
    table.ajax.reload();
  });

  // hapus (SweetAlert kalau ada, fallback confirm)
  $(document).on('click', '.btn-del', function(){
    const id = $(this).data('id');
    const doDelete = () => $.post(urlDestroy(id), {_method:'DELETE'})
      .done(res => { alert(res.message ?? 'Checklist dihapus.'); table.ajax.reload(null,false); })
      .fail(xhr => alert(xhr.responseJSON?.message ?? 'Gagal menghapus.'));

    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: 'Hapus checklist ini?',
        text: 'Semua item aktivitasnya juga akan terhapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
      }).then(r => { if (r.isConfirmed) doDelete(); });
    } else {
      if (confirm('Hapus checklist ini? Semua item aktivitasnya juga akan terhapus.')) doDelete();
    }
  });

  // (opsional) kalau action kamu butuh navigasi ke detail via JS:
  // $(document).on('click', '.btn-show', function(){
  //   location.href = urlShow($(this).data('id'));
  // });
});
</script>

@endsection
