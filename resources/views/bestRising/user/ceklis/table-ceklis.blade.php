@extends('layouts.userapp')

@section('main')
<div class="content-wrapper">
  <div class="card">
    <div class="card-header d-flex align-items-center flex-wrap gap-2">
      <h3 class="mb-0">Data Activity</h3>
      <!-- tambahkan class .filters supaya gampang diatur responsif -->
      <div class="ms-auto ml-auto d-flex gap-2 filters">
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
      <div class="table-responsive">
        <table id="table" class="table table-bordered">
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
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- ====== RESPONSIVE ONLY: tidak mengubah fungsi ====== --}}
<style>
  /* Ruang nafas */
  .content-wrapper { padding: 0.75rem; }
  .card { overflow: hidden; }

  /* Table: lebih padat di mobile */
  @media (max-width: 768px) {
    #table { font-size: 13px; }
    #table td, #table th { padding: .45rem .5rem; vertical-align: middle; }
  }

  /* Header jadi kolom, filter jadi grid di HP */
  @media (max-width: 768px) {
    .card-header {
      flex-direction: column;
      align-items: stretch !important;
      gap: .5rem !important;
    }
    .card-header h3 { margin-bottom: .25rem !important; }
    .filters {
      width: 100%;
      display: grid !important;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: .5rem !important;
    }
    .filters .btn { width: 100%; }
  }

  /* HP kecil banget → 1 kolom */
  @media (max-width: 480px) {
    .filters { grid-template-columns: 1fr; }
  }

  /* Table container */
  .table-responsive { width: 100%; overflow-x: auto; }

  /* Bungkus teks panjang di kolom Lokasi (kolom ke-6) */
  #table td:nth-child(6) { white-space: normal; word-break: break-word; }

  /* Sembunyikan kolom “berat” di layar kecil (tanpa sentuh data/fungsi) 
     Urutan kolom: 1 No | 2 Mulai | 3 Selesai | 4 Team | 5 Nama | 6 Lokasi | 7 Total | 8 Status | 9 Aksi
  */
  /* ≤576px: hide Team(4) & Lokasi(6) */
  @media (max-width: 576px) {
    #table thead th:nth-child(4), #table tbody td:nth-child(4),
    #table thead th:nth-child(6), #table tbody td:nth-child(6) { display: none; }
  }
  /* ≤400px: hide Total Star(7) juga */
  @media (max-width: 400px) {
    #table thead th:nth-child(7), #table tbody td:nth-child(7) { display: none; }
  }

  /* Kolom aksi biar nggak kebungkus */
  #table td:last-child { white-space: nowrap; }
</style>

<script>
$(function(){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

  const url = "{{ route('checklists.table-ceklis') }}";

  const table = $('#table').DataTable({
    processing: true,
    serverSide: true,
    order: [[1, 'desc']],
    ajax: {
      url: url,
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
      { data:'action',      orderable:false, searchable:false, className:'text-nowrap' },
    ]
  });

  $('#btnFilter').on('click', () => table.ajax.reload());
  $('#btnReset').on('click', function(){
    $('#f_team, #f_status').val('');
    $('#f_from, #f_to').val('');
    table.ajax.reload();
  });
});
</script>
@endsection
