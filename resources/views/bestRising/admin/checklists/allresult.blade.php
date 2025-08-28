@extends('layouts.appBestRising')

@section('main')
<div class="content-wrapper">

  <div class="card">
    <div class="card-header">
      <h3 class="mb-0">All Activity Results</h3>
    </div>

    <div class="card-body">

      {{-- FILTERS --}}
      <div class="row g-2 mb-3 align-items-end">
        <div class="col-md-2">
          <label class="form-label mb-1">Region</label>
          <select id="f_region" class="form-control">
            <option value="">Semua</option>
            @foreach(\DB::table('regions')->orderBy('nama_region')->get() as $rg)
              <option value="{{ $rg->id_region }}">{{ $rg->nama_region }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Serpo</label>
          <select id="f_serpo" class="form-control">
            <option value="">Semua</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Segmen</label>
          <select id="f_segmen" class="form-control">
            <option value="">Semua</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Tanggal From</label>
          <input type="date" id="f_from" class="form-control">
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Tanggal To</label>
          <input type="date" id="f_to" class="form-control">
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Nama / Email</label>
          <input type="text" id="f_name" class="form-control" placeholder="cari nama/email">
        </div>

        <div class="col-12 mt-2 d-flex gap-2">
          <button id="btnApply" class="btn btn-primary btn-sm mr-1">Terapkan</button>
          <button id="btnReset" class="btn btn-outline-secondary btn-sm mr-1">Reset</button>
          <a id="btnExport" class="btn btn-success btn-sm mr-1" href="#">Export Excel</a>
        </div>
      </div>

      <table id="table-results" class="table table-bordered table-striped w-100">
        <thead>
        <tr>
          <th>No</th>
          {{-- hidden (tetap di-fetch) --}}
          <th>ID</th>
          <th>Checklist</th>
          <th>Submitted At</th>
          {{-- shown --}}
          <th>User</th>
          <th>Activity</th>
          <th>Region</th>
          <th>Serpo</th>
          <th>Segmen</th>
          <th>Status</th>
          <th>Before Photo</th>
          <th>After Photo</th>
          <th>Star</th>
          <th>Note</th>
          <th>Created At</th>
        </tr>
        </thead>
      </table>

    </div>
  </div>

</div>

<style>
  .thumb-img{ width:54px;height:54px;object-fit:cover;border-radius:6px;border:1px solid rgba(0,0,0,.1); }
  table.dataTable td, table.dataTable th { vertical-align: middle; }
</style>
<script>
    function buildExportUrl(){
        const params = new URLSearchParams({
            region:    $('#f_region').val() || '',
            serpo:     $('#f_serpo').val()  || '',
            segmen:    $('#f_segmen').val() || '',
            date_from: $('#f_from').val()   || '',
            date_to:   $('#f_to').val()     || '',
            keyword:   $('#f_name').val()   || '',
        });
        return "{{ route('admin.checklists.allresult.export') }}?" + params.toString();
        }

        $('#btnExport').on('click', function(e){
        e.preventDefault();
        window.location.href = buildExportUrl();
    });

$(function(){

  // Helper isi <select> yang toleran terhadap berbagai bentuk JSON
  function fillOptions($select, items, placeholder='Semua') {
    $select.empty().append(`<option value="">${placeholder}</option>`);
    if (!items) return;
    items.forEach(row => {
      const id   = row.id ?? row.id_serpo ?? row.id_segmen ?? row.value ?? null;
      const text = row.text ?? row.nama_serpo ?? row.nama_segmen ?? row.nama ?? row.label ?? '';
      if (id !== null && text !== '') {
        $select.append(`<option value="${id}">${text}</option>`);
      }
    });
  }

  // Dependent dropdowns
  $('#f_region').on('change', function(){
    const id = $(this).val();
    fillOptions($('#f_serpo'),  null);
    fillOptions($('#f_segmen'), null);
    if (!id) return;

    $.get("{{ route('admin.serpo.byRegion', ['id_region' => 'IDR']) }}".replace('IDR', id))
      .done(res => fillOptions($('#f_serpo'), (res?.data ?? res), 'Semua'))
      .fail(() => fillOptions($('#f_serpo'), null, 'Semua'));
  });

  $('#f_serpo').on('change', function(){
    const id = $(this).val();
    fillOptions($('#f_segmen'), null);
    if (!id) return;

    $.get("{{ route('admin.segmen.bySerpo', ['id_serpo' => 'IDS']) }}".replace('IDS', id))
      .done(res => fillOptions($('#f_segmen'), (res?.data ?? res), 'Semua'))
      .fail(() => fillOptions($('#f_segmen'), null, 'Semua'));
  });

  // DataTable
  const table = $('#table-results').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
      url: "{{ route('admin.checklists.allresult') }}",
      type: 'GET',
      data: function(d){
        d.region    = $('#f_region').val() || '';
        d.serpo     = $('#f_serpo').val()  || '';
        d.segmen    = $('#f_segmen').val() || '';
        d.date_from = $('#f_from').val()   || '';
        d.date_to   = $('#f_to').val()     || '';
        d.keyword   = $('#f_name').val()   || '';
        return d;
      }
    },
    order: [[14,'desc']], // Created At
    columns: [
        { data:'DT_RowIndex', orderable:false, searchable:false },

        // hidden – matikan searchable biar nggak dipakai global search
        { data:'id',           visible:false, searchable:false, defaultContent:'-' },
        { data:'checklist_id', visible:false, searchable:false, defaultContent:'-' },
        { data:'submitted_at', visible:false, searchable:false, defaultContent:'-' },

        // shown – name menunjuk ke kolom DB asli agar order/search aman
        { data:'user_nama',     name:'u.nama',        defaultContent:'-' }, // email ikut dicari via filterColumn
        { data:'activity_nama', name:'act.name',      defaultContent:'-' },
        { data:'region_nama',   name:'r.nama_region', defaultContent:'-' },
        { data:'serpo_nama',    name:'sp.nama_serpo', defaultContent:'-' },

        // segmen_list dari subquery → non-searchable (atau bikin filterColumn sendiri kalau mau)
        { data:'segmen_list',   searchable:false,     defaultContent:'-' },

        { data:'status',        name:'ar.status',     defaultContent:'-' },
        { data:'before_photo',  orderable:false, searchable:false, defaultContent:'-' },
        { data:'after_photo',   orderable:false, searchable:false, defaultContent:'-' },
        { data:'point_earned',  name:'ar.point_earned', className:'text-end', defaultContent:'0' },
        { data:'note',          name:'ar.note',       defaultContent:'-' },
        { data:'created_at',    name:'ar.created_at', defaultContent:'-' },
        ],

    pageLength: 25
  });

  // Apply / Reset
  $('#btnApply').on('click', function(){ table.ajax.reload(null, false); });
  $('#btnReset').on('click', function(){
    $('#f_region').val('');
    fillOptions($('#f_serpo'),  null);
    fillOptions($('#f_segmen'), null);
    $('#f_from,#f_to,#f_name').val('');
    table.ajax.reload(null, false);
  });

  // (opsional) auto-apply saat filter berubah:
  // $('#f_region,#f_serpo,#f_segmen,#f_from,#f_to').on('change', ()=>table.ajax.reload(null,false));
  // $('#f_name').on('keyup', _.debounce(()=>table.ajax.reload(null,false), 400));

});
</script>
@endsection