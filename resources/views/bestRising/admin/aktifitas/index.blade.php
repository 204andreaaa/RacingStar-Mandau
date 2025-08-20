@extends('layouts.appBestRising')

@section('main')
<div class="content-wrapper">
  <section class="content-header"><h1>Activity</h1></section>

  <div class="card">
    <div class="card-header d-flex align-items-center">
      <h3 class="mb-0">Data Activity</h3>
      <button class="btn btn-primary ms-auto ml-auto" id="btnAdd">Tambah Activity</button>
    </div>

    <div class="card-body">
      <table id="table-aktifitas" class="table table-bordered">
        <thead>
          <tr>
            <th>No</th><th>Team</th><th>Nama</th><th>Poin</th><th>Status</th><th>Aksi</th>
          </tr>
        </thead>
      </table>

      {{-- FILTER TEAM (akan dipindah ke "Show entries" via JS) --}}
      <div id="dtTeamFilter" class="d-none dt-team-filter">
        <label class="mb-0 mr-2 font-weight-bold">Team:</label>
        <select id="f_team" class="form-control form-control-sm d-inline-block" style="width:auto;min-width:130px">
          <option value="">Semua</option>
          @foreach($teams as $id => $nm)
            <option value="{{ $id }}">{{ $nm }}</option>
          @endforeach
        </select>
      </div>
    </div>
  </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="modalAktifitas" tabindex="-1">
  <div class="modal-dialog">
    <form id="formAktifitas">@csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Tambah Activity</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="id_row">

          <div class="form-group">
            <label>Team</label>
            <select name="team_id" id="team_id" class="form-control" required>
              @foreach($teams as $id => $nm)
                <option value="{{ $id }}">{{ $nm }}</option>
              @endforeach
            </select>
          </div>

          <div class="form-group">
            <label>Nama</label>
            <input type="text" name="name" id="name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label>Poin</label>
            <input type="number" name="point" id="point" class="form-control" min="0" value="0" required>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
            <label class="form-check-label" for="is_active">Aktif</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="btnSave">Simpan</button>
        </div>
      </div>
    </form>
  </div>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  /* rapihin area length biar muat filter */
  #table-aktifitas_wrapper .dataTables_length{
    display:flex; align-items:center; gap:.75rem;
  }
  .dt-team-filter{ display:flex; align-items:center; gap:.5rem; margin-left:.75rem; }
</style>

<script>
$(function(){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

  const ROUTES = {
    index  : "{{ route('admin.aktifitas.index') }}",
    store  : "{{ route('admin.aktifitas.store') }}",
    update : "{{ route('admin.aktifitas.update', ':id') }}",
    destroy: "{{ route('admin.aktifitas.destroy', ':id') }}",
  };
  const urlUpdate  = id => ROUTES.update.replace(':id', id);
  const urlDestroy = id => ROUTES.destroy.replace(':id', id);

  const table = $('#table-aktifitas').DataTable({
    processing:true, serverSide:true,
    ajax:{
      url: ROUTES.index,
      data: d => { d.team = $('#f_team').val(); }
    },
    columns: [
      {data:'DT_RowIndex', orderable:false, searchable:false},
      {data:'team', name:'team'},
      {data:'name', name:'name'},
      {data:'point', className:'text-end'},
      {data:'status', orderable:false, searchable:false},
      {data:'action', orderable:false, searchable:false},
    ]
  });

  // Pindahkan filter ke sebelah "Show entries"
  table.on('init.dt', function(){
    $('#dtTeamFilter')
      .appendTo('#table-aktifitas_wrapper .dataTables_length')
      .removeClass('d-none');
  });

  // Apply filter
  $(document).on('change', '#f_team', function(){
    table.ajax.reload();
  });

  // Add
  $('#btnAdd').on('click', function(){
    $('#modalTitle').text('Tambah Activity');
    $('#formAktifitas')[0].reset();
    $('#id_row').val('');
    $('#is_active').prop('checked', true);
    const tf = $('#f_team').val(); if(tf) $('#team_id').val(tf); // default team = filter aktif
    $('#modalAktifitas').modal('show');
  });

  // Edit
  $(document).on('click','.btn-edit', function(){
    $('#modalTitle').text('Edit Activity');
    $('#formAktifitas')[0].reset();
    $('#id_row').val($(this).data('id'));
    $('#team_id').val($(this).data('team'));
    $('#name').val($(this).data('name'));
    $('#description').val($(this).data('desc'));
    $('#point').val($(this).data('point'));
    $('#is_active').prop('checked', +$(this).data('active') === 1);
    $('#modalAktifitas').modal('show');
  });

  // Submit
  $('#formAktifitas').on('submit', function(e){
    e.preventDefault();
    const id  = $('#id_row').val();
    let data  = $(this).serialize();
    if(!$('#is_active').is(':checked')) data += '&is_active=0';

    const url  = id ? urlUpdate(id) : ROUTES.store;
    const body = id ? (data + '&_method=PUT') : data;

    $('#btnSave').prop('disabled',true).text('Menyimpan...');
    $.post(url, body)
      .done(res => { $('#modalAktifitas').modal('hide'); table.ajax.reload(null,false);
        Swal.fire({icon:'success', title:'OK', text:res.message}); })
      .fail(xhr => {
        let msg = xhr.responseJSON?.message || 'Terjadi kesalahan';
        if(xhr.responseJSON?.errors) msg += "\n" + Object.values(xhr.responseJSON.errors).flat().join('\n');
        Swal.fire({icon:'error', title:'Gagal', text:msg});
      })
      .always(() => $('#btnSave').prop('disabled',false).text('Simpan'));
  });

  // Delete
  $(document).on('click','.btn-delete', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Yakin?', text:'Activity akan dihapus.', icon:'warning', showCancelButton:true})
      .then(r => {
        if(!r.isConfirmed) return;
        $.post(urlDestroy(id), {_method:'DELETE'})
          .done(res => { table.ajax.reload(null,false); Swal.fire({icon:'success', title:'OK', text:res.message}); })
          .fail(xhr => {
            let msg = xhr.responseJSON?.message || 'Gagal menghapus data';
            if(xhr.responseJSON?.errors) msg += "\n" + Object.values(xhr.responseJSON.errors).flat().join('\n');
            Swal.fire({icon:'error', title:'Gagal', text:msg});
          });
      });
  });
});
</script>
@endsection
