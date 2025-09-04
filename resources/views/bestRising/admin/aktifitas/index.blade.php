@extends('layouts.appBestRising')

@section('main')
<div class="content-wrapper">
  <section class="content-header"><h1>Activity</h1></section>

  <div class="card">
    <div class="card-header d-flex align-items-center">
      <h3 class="mb-0">Data Activity</h3>
      <div class="ms-auto ml-auto d-flex gap-2">
        <select id="f_team" class="form-control form-control-sm" style="min-width:140px">
          <option value="">Semua Team</option>
          @foreach($teams as $id => $nm)
            <option value="{{ $id }}">{{ $nm }}</option>
          @endforeach
        </select>
        <button class="btn btn-primary" id="btnAdd">Tambah Activity</button>
      </div>
    </div>

    <div class="card-body">
      <table id="table-aktifitas" class="table table-bordered">
        <thead>
          <tr>
            <th style="width:60px">#</th>
            <th>Team</th>
            <th>Nama</th>
            <th class="text-end">Point</th>
            <th>Status</th>
            <th>Segmen</th>
            <th style="width:160px">Aksi</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="modalAktifitas" tabindex="-1">
  <div class="modal-dialog">
    <form id="formAktifitas" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Tambah Activity</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="id_row" name="id_row">

        <div class="form-group mb-2">
          <label>Team</label>
          <select name="team_id" id="team_id" class="form-control select2" data-placeholder="-- Pilih Team --" required>
            @foreach($teams as $id => $nm)
              <option value=""></option>
              <option value="{{ $id }}">{{ $nm }}</option>
            @endforeach
          </select>
        </div>

        <div class="form-group mb-2">
          <label>Nama</label>
          <input type="text" name="name" id="name" class="form-control" required>
        </div>

        <div class="form-group mb-2">
          <label>Deskripsi</label>
          <textarea name="description" id="description" class="form-control" rows="2" placeholder="Opsional"></textarea>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group mb-2">
              <label>Point</label>
              <input type="number" name="point" id="point" class="form-control" min="0" value="0" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group mb-2">
              <label>Status Aktif</label>
              <select name="is_active" id="is_active" class="form-control select2" data-placeholder="-- Pilih Status --" required>
                @foreach (Dropdown::activeStatusOpt() as $key => $item)
                  <option value=""></option>
                  <option value="{{ $key }}">{{ $item }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group mb-2">
              <label>Status Segmen</label>
              <select name="is_checked_segmen" id="is_checked_segmen" class="form-control select2" data-placeholder="-- Pilih Status --" required>
                @foreach (Dropdown::requiredStatusOpt() as $key => $item)
                  <option value=""></option>
                  <option value="{{ $key }}">{{ $item }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>

        <hr class="my-2">

        <div class="row">
          <div class="col-md-6">
            <div class="form-group mb-2">
              <label>Limit Periode</label>
              <select name="limit_period" id="limit_period" class="form-control" required>
                <option value="none">Tidak dibatasi</option>
                <option value="daily">Harian</option>
                <option value="weekly">Mingguan</option>
                <option value="monthly">Bulanan</option>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group mb-2">
              <label>Kuota / Periode</label>
              <input type="number" name="limit_quota" id="limit_quota" class="form-control" min="1" value="1" required>
              <small class="text-muted">Contoh: 1 = maksimal 1x tiap periode.</small>
            </div>
          </div>
        </div>

        {{-- ===== BARU: WAJIB FOTO ===== --}}
        <div class="form-group mb-2">
          <div class="form-check">
            {{-- hidden 0 supaya uncheck tetap terkirim --}}
            <input type="hidden" name="requires_photo" value="0">
            <input type="checkbox" class="form-check-input" id="requires_photo" name="requires_photo" value="1">
            <label class="form-check-label" for="requires_photo">Wajib Foto (Before & After)</label>
          </div>
          <small class="text-muted">Jika dicentang, user harus upload foto before & after saat menandai <em>Done</em>.</small>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
        <button type="submit" id="btnSave" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function(){
  // Init ulang setiap modal dibuka
  $('#modalAktifitas').on('shown.bs.modal', function () {
    $(this).find('select.select2').each(function () {
      if (!$(this).hasClass('select2-hidden-accessible')) {
        $(this).select2({ dropdownParent: $('#modalAktifitas'), width: '100%' });
      }
    });
  });

  // Destroy yang SUDAH di-init saat modal ditutup
  $('#modalAktifitas').on('hidden.bs.modal', function () {
    const $form = $('#formAktifitas');
    $(this).find('select.select2').each(function () {
      if ($(this).hasClass('select2-hidden-accessible')) {
        $(this).select2('destroy');
      }
    });
    if ($form.length && $form[0]) $form[0].reset();
    // pastikan checkbox kembali default (off)
    $('#requires_photo').prop('checked', false);
  });
  
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

  const ROUTES = {
    index  : "{{ route('admin.aktifitas.index') }}",
    store  : "{{ route('admin.aktifitas.store') }}",
    update : "{{ route('admin.aktifitas.update', ['activity' => '__ID__']) }}",
    destroy: "{{ route('admin.aktifitas.destroy', ['activity' => '__ID__']) }}",
  };
  const urlUpdate  = id => ROUTES.update.replace('__ID__', encodeURIComponent(id));
  const urlDestroy = id => ROUTES.destroy.replace('__ID__', encodeURIComponent(id));

  const table = $('#table-aktifitas').DataTable({
    processing:true, serverSide:true,
    ajax:{ url: ROUTES.index, data: d => { d.team = $('#f_team').val(); } },
    order:[[2,'asc']],
    columns: [
      {data:'DT_RowIndex', orderable:false, searchable:false},
      {data:'team', name:'team'},
      {data:'name', name:'name'},
      {data:'point', className:'text-end'},
      {data:'status', orderable:false, searchable:false},
      {data:'is_checked_segmen', orderable:false, searchable:false},
      {data:'action', orderable:false, searchable:false},
    ]
  });

  $('#f_team').on('change', function(){ table.ajax.reload(); });

  // Add
  $('#btnAdd').on('click', function(){
    $('#modalTitle').text('Tambah Activity');
    $('#formAktifitas')[0].reset();
    $('#id_row').val('');
    $('#limit_period').val('none');
    $('#limit_quota').val(1);
    $('#requires_photo').prop('checked', false); // default off
    const tf = $('#f_team').val(); if(tf) $('#team_id').val(tf);
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
    $('#is_active').val(String($(this).data('active'))).trigger('change');
    $('#is_checked_segmen').val(String($(this).data('is_checked_segmen'))).trigger('change');
    $('#limit_period').val($(this).data('limit_period') || 'none');
    $('#limit_quota').val($(this).data('limit_quota') || 1);
    $('#requires_photo').prop('checked', String($(this).data('requires_photo')) === '1'); // ⟵ BARU
    $('#modalAktifitas').modal('show');
  });

  // Submit create/update
  $('#formAktifitas').on('submit', function(e){
    e.preventDefault();
    const id  = $('#id_row').val();
    const body = $(this).serialize();
    const url  = id ? urlUpdate(id) : ROUTES.store;
    const type = 'POST';
    const data = id ? (body + '&_method=PUT') : body;

    $('#btnSave').prop('disabled',true).text('Menyimpan...');
    $.ajax({ url, type, data })
      .done(res => {
        $('#modalAktifitas').modal('hide');
        table.ajax.reload(null,false);
        Swal.fire({icon:'success', title:'OK', text:res.message});
      })
      .fail(xhr => {
        let msg = xhr.responseJSON?.message || 'Terjadi kesalahan';
        if(xhr.responseJSON?.errors) msg += "\n" + Object.values(xhr.responseJSON.errors).flat().join('\n');
        Swal.fire({icon:'error', title:'Gagal', text:msg});
      })
      .always(() => $('#btnSave').prop('disabled',false).text('Simpan'));
  });

  // ====== DELETE FIX ======
  $(document).on('click','.btn-delete', function(){
    const id = $(this).data('id');
    const url = urlDestroy(id);
    Swal.fire({icon:'warning', title:'Hapus data?', showCancelButton:true})
      .then(r => {
        if(!r.isConfirmed) return;
        $.ajax({ url, type: 'DELETE' })
          .done(res => { table.ajax.reload(null,false); Swal.fire({icon:'success', title:'OK', text: res.message || 'Berhasil dihapus'}); })
          .fail(xhr => {
            let msg = 'Gagal menghapus data';
            if (xhr.status === 419) msg = 'Sesi kedaluwarsa (CSRF). Refresh lalu ulangi.';
            if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
            Swal.fire({icon:'error', title:'Error', text: msg});
          });
      });
  });
});
</script>
@endsection
