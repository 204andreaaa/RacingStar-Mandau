@extends('layouts.appBestRising')

@section('main')
<div class="content-wrapper">

    <div class="card">
        {{-- Header: sekarang cuma judul + tombol di kanan --}}
        <div class="card-header d-flex align-items-center">
            <h3 class="mb-0">Data Segmen</h3>
            <button class="btn btn-primary ms-auto ml-auto" id="btnAdd">Tambah Segmen</button>
        </div>

        <div class="card-body">
            {{-- Template filter (disembunyikan, nanti dipindah ke toolbar DataTables) --}}
            <div id="dt-filters-template" style="display:none;">
                <div id="dt-filters" class="d-inline-flex align-items-center" style="gap:8px;margin-left:12px;">
                    <select id="filter_region" class="form-control form-control-sm" style="min-width:200px;">
                        <option value="">Semua Region</option>
                        @foreach($regions as $r)
                            <option value="{{ $r->id_region }}">{{ $r->nama_region }}</option>
                        @endforeach
                    </select>
                    <select id="filter_serpo" class="form-control form-control-sm" style="min-width:200px;">
                        <option value="">Semua Serpo</option>
                        @foreach($serpos as $s)
                            <option value="{{ $s->id_serpo }}" data-region="{{ $s->id_region }}">{{ $s->nama_serpo }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <table id="table-segmen" class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Region</th>
                        <th>Serpo</th>
                        <th>Nama Segmen</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="modalSegmen" tabindex="-1">
    <div class="modal-dialog">
        <form id="formSegmen">@csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Segmen</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id_segmen">
                    <div class="form-group">
                        <label>Region</label>
                        <select id="form_region" class="form-control" required>
                            <option value="">-- Pilih Region --</option>
                            @foreach($regions as $r)
                                <option value="{{ $r->id_region }}">{{ $r->nama_region }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Serpo</label>
                        <select name="id_serpo" id="form_serpo" class="form-control" required>
                            <option value="">-- Pilih Serpo --</option>
                            {{-- diisi dinamis byRegion --}}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nama Segmen</label>
                        <input type="text" name="nama_segmen" id="nama_segmen" class="form-control" required>
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

<script>
$(function(){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

  // SweetAlert helpers
  const swalSuccess = (text='Berhasil diproses') =>
      Swal.fire({title:'Sukses', text, icon:'success', confirmButtonText:'OK'});
  const swalError = (text='Terjadi kesalahan') =>
      Swal.fire({title:'Gagal', text, icon:'error', confirmButtonText:'OK'});
  const swalConfirm = (text='Hapus data ini?') =>
      Swal.fire({title:'Yakin?', text, icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus', cancelButtonText:'Batal'});

  // --- route helper dari Blade (pakai placeholder utk id) ---
  const ROUTES = {
    serpoByRegion : "{{ route('admin.serpo.byRegion', ['id_region' => 'RID']) }}",
    segmenIndex   : "{{ route('admin.segmen.index') }}",
    segmenStore   : "{{ route('admin.segmen.store') }}",
    segmenUpdate  : "{{ route('admin.segmen.update', ':id') }}",
    segmenDestroy : "{{ route('admin.segmen.destroy', ':id') }}",
  };
  const urlUpdate   = id => ROUTES.segmenUpdate.replace(':id', id);
  const urlDestroy  = id => ROUTES.segmenDestroy.replace(':id', id);
  const urlByRegion = rid => ROUTES.serpoByRegion.replace('RID', rid);

  function loadSerpoByRegion(regionId, $select, selected = '') {
      $select.prop('disabled', true).empty().append('<option value="">Memuat Serpo...</option>');
      $('#form_segmen').prop('disabled', true).empty().append('<option value="">-- Pilih Segmen --</option>');

      if (!regionId) {
          $select.prop('disabled', false).empty().append('<option value="">-- Pilih Serpo --</option>');
          return $.Deferred().resolve().promise();
      }

      return $.get(urlByRegion(regionId))
          .then(items => {
          $select.empty().append('<option value="">-- Pilih Serpo --</option>');
          items.forEach(it => $select.append(new Option(it.text, it.id)));
          if (selected) $select.val(String(selected));
          })
          .always(() => $select.prop('disabled', false));
  }

  // DataTable
  const table = $('#table-segmen').DataTable({
    processing:true, serverSide:true,
    dom: '<"row align-items-center"<"col-sm-6 d-flex"l<"dt-extra-filters d-inline-flex align-items-center ms-2 ml-2">><"col-sm-6"f>>rt<"row"<"col-sm-5"i><"col-sm-7"p>>',
    ajax: {
      url: ROUTES.segmenIndex,
      data: d => {
        d.id_region = $('#filter_region').val();
        d.id_serpo  = $('#filter_serpo').val();
      }
    },
    columns: [
      {data:'DT_RowIndex', name:'DT_RowIndex', orderable:false, searchable:false},
      {data:'region', name:'region', orderable:false, searchable:false},
      {data:'serpo',  name:'serpo',  orderable:false, searchable:false},
      {data:'nama_segmen', name:'nama_segmen'},
      {data:'action', name:'action', orderable:false, searchable:false},
    ]
  });

  // Tempatkan filter ke toolbar
  const $filters = $('#dt-filters-template #dt-filters');
  $('.dt-extra-filters').append($filters);
  $('#dt-filters-template').remove();

  // Toolbar: filter serpo by region
  $('#filter_region').on('change', function(){
    const rid = $(this).val();
    const $serpo = $('#filter_serpo').empty().append('<option value="">Semua Serpo</option>');
    if(!rid){ return table.ajax.reload(null,true); }
    $.get(urlByRegion(rid)).done(items => {
      items.forEach(it => $serpo.append(new Option(it.text, it.id)));
      table.ajax.reload(null,true);
    });
  });

  $('#filter_serpo').on('change', ()=> table.ajax.reload(null,true));

  // Add
  $('#btnAdd').on('click', function(){
    $('#modalTitle').text('Tambah Segmen');
    $('#formSegmen')[0].reset();
    $('#id_segmen').val('');
    $('#form_serpo').empty().append('<option value="">-- Pilih Serpo --</option>');
    $('#modalSegmen').modal('show');
  });

  // Edit
  $(document).on('click','.btn-edit', function(){
    $('#modalTitle').text('Edit Segmen');
    $('#formSegmen')[0].reset();

    const id    = $(this).data('id');
    const nama  = $(this).data('nama');
    const regionId = $(this).data('region');
    const serpoId  = $(this).data('serpo');

    $('#id_segmen').val(id);
    $('#nama_segmen').val(nama);

    // set region dari filter aktif (kalau ada)
    $('#form_region').val(String(regionId));
    loadSerpoByRegion(regionId, $('#form_serpo'), serpoId)
      .then(() => $('#modalSegmen').modal('show'))
      .fail(() => {
      console.error('Gagal preload Serpo/Segmen.');
      $('#modalSegmen').modal('show');
      });
  });

  // Submit (create/update) - pakai SweetAlert
  $('#formSegmen').on('submit', function(e){
    e.preventDefault();
    const id   = $('#id_segmen').val();
    const data = $(this).serialize() + (id ? '&_method=PUT' : '');
    const url  = id ? urlUpdate(id) : ROUTES.segmenStore;

    $('#btnSave').prop('disabled', true).text('Menyimpan...');

    $.post(url, data)
      .done(res => {
        $('#modalSegmen').modal('hide');
        table.ajax.reload(null,false);
        swalSuccess(res.message ?? (id ? 'Data berhasil diperbarui!' : 'Data berhasil ditambahkan!'));
      })
      .fail(xhr => {
        let msg = xhr.responseJSON?.message || 'Terjadi kesalahan';
        if(xhr.responseJSON?.errors) msg += "\n" + Object.values(xhr.responseJSON.errors).flat().join('\n');
        swalError(msg);
      })
      .always(() => {
        $('#btnSave').prop('disabled', false).text('Simpan');
      });
  });

  // Delete + konfirmasi SweetAlert
  $(document).on('click','.btn-delete', function(){
    const id = $(this).data('id');
    swalConfirm('Segmen ini akan dihapus.')
    .then(r => {
      if(!r.isConfirmed) return;
      $.post(urlDestroy(id), {_method:'DELETE'})
        .done(res => {
          table.ajax.reload(null,false);
          swalSuccess(res.message ?? 'Berhasil dihapus');
        })
        .fail(xhr => {
          let msg = xhr.responseJSON?.message || 'Gagal menghapus data';
          if(xhr.responseJSON?.errors) msg += "\n" + Object.values(xhr.responseJSON.errors).flat().join('\n');
          swalError(msg);
        });
    });
  });

  // Change region di form -> reload serpo options (modal)
  $('#form_region').on('change', function(){
      loadSerpoByRegion(this.value, $('#form_serpo'));
  });
});
</script>

@endsection
