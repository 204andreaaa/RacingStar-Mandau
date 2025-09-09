@extends('layouts.userapp')

@section('main')
<div class="content-wrapper">
  <div class="card">
    <div class="card-header d-flex align-items-center flex-wrap gap-2">
      <h3 class="mb-0">Data Activity</h3>

      {{-- FILTERS --}}
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
        {{-- TABLE (desktop) --}}
        <table id="table" class="table table-bordered">
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

        {{-- CARD LIST (mobile) --}}
        <div id="cardList" class="dt-cardlist"></div>
      </div>
    </div>
  </div>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- ====== STYLE ====== --}}
<style>
  .content-wrapper { padding: 0.75rem; }
  .card { overflow: hidden; }

  /* default: sembunyikan card list di desktop */
  .dt-cardlist { display: none; }

  /* --- CARD LIST (mobile) --- */
  .dt-cardlist .item {
    border: 1px solid #e5e7eb; border-radius: .5rem; padding: .75rem;
    margin-bottom: .75rem; background: #fff;
  }
  .dt-cardlist .row1 {
    display:flex; justify-content:space-between; gap:.5rem; font-weight:600;
  }
  .dt-cardlist .meta {
    font-size: .85rem; color:#6b7280; margin:.25rem 0 .5rem;
  }
  .dt-cardlist .kv {
    display:grid; grid-template-columns: 110px 1fr; gap:.25rem .5rem; font-size:.9rem;
  }
  .dt-cardlist .actions { margin-top:.5rem; display:flex; flex-wrap:wrap; gap:.5rem; }
  .badge { display:inline-block; padding:.15rem .45rem; border-radius:.25rem; font-size:.75rem; }
  .badge-success{background:#d1fae5;color:#065f46}
  .badge-warning{background:#fef3c7;color:#92400e}
  .badge-secondary{background:#e5e7eb;color:#374151}

  /* Table tweaks / responsive switch */
  @media (max-width: 768px) {
    #table { display: none; }          /* hide tabel di mobile */
    .dt-cardlist { display: block; }   /* show card list */
  }

  /* Header → kolom; filter jadi grid di mobile */
  @media (max-width: 768px) {
    .card-header { flex-direction: column; align-items: stretch !important; gap: .5rem !important; }
    .card-header h3 { margin-bottom: .25rem !important; }
    .filters {
      width: 100%; display: grid !important;
      grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem !important;
    }
    .filters .btn { width: 100%; }
  }
  @media (max-width: 480px) { .filters { grid-template-columns: 1fr; } }

  /* Table container (desktop) */
  .table-responsive { width: 100%; overflow-x: auto; }
  #table td:nth-child(6) { white-space: normal; word-break: break-word; }
  #table td:last-child { white-space: nowrap; }
</style>

{{-- ====== SCRIPT ====== --}}
<script>
$(function(){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

  const url = "{{ route('checklists.table-ceklis') }}";
  const $cardList = $('#cardList');

  const table = $('#table').DataTable({
    processing: true,
    serverSide: true,
    order: [[1, 'desc']],
    ajax: {
      url: url,
      data: d => {
        d.team      = $('#f_team').val();   // biarkan, kalau nggak ada akan undefined (aman)
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

  function renderCards(rows){
    let html = '';
    rows.forEach(r => {
      const badgeClass = r.status === 'completed' ? 'badge-success'
                        : (r.status === 'pending' ? 'badge-warning' : 'badge-secondary');
      html += `
        <div class="item">
          <div class="row1">
            <div>${r.user_nama ?? '-'}</div>
            <div class="badge ${badgeClass}">${r.status ?? '-'}</div>
          </div>
          <div class="meta">${r.lokasi ?? '-'}</div>
          <div class="kv">
            <div>Mulai</div><div>: ${r.started_at ?? '-'}</div>
            <div>Selesai</div><div>: ${r.submitted_at ?? '-'}</div>
            <div>Team</div><div>: ${r.team ?? '-'}</div>
            <div>Total Star</div><div>: ${r.total_point ?? 0}</div>
          </div>
          <div class="actions">${r.action ?? ''}</div>
        </div>`;
    });
    if (!rows.length) html = `<div class="item">Tidak ada data.</div>`;
    $cardList.html(html);
  }

  // pertama kali data masuk dari server
  table.on('xhr.dt', function () {
    const json = table.ajax.json() || {};
    renderCards(json.data || []);
  });

  // setiap paging/sort/search (halaman aktif)
  $('#table').on('draw.dt', function(){
    const rows = table.rows({ page: 'current' }).data().toArray();
    renderCards(rows);
  });

  // Filter & Reset
  $('#btnFilter').on('click', () => table.ajax.reload());
  $('#btnReset').on('click', function(){
    $('#f_team, #f_status').val('');
    $('#f_from, #f_to').val('');
    table.ajax.reload();
  });
});
</script>
@endsection
