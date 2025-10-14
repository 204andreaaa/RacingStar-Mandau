@extends('layouts.appBestRising')

@section('main')
<style>
  #subs_chips .chip{
    display:inline-flex;align-items:center;gap:.5rem;
    padding:.25rem .6rem;border-radius:999px;background:#f3f4f6;border:1px solid #e5e7eb;
    margin:.15rem .3rem .15rem 0;font-size:.875rem;max-width:100%;
  }
  #subs_chips .chip .txt{max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
  #subs_chips .chip .x{border:none;background:transparent;font-weight:bold;line-height:1;cursor:pointer;}
</style>

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
            <th style="width:90px">Urutan</th>
            <th>Team</th>
            <th>Nama</th>
            <th class="text-end">Star</th>
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
            <option value=""></option>
            @foreach($teams as $id => $nm)
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
              <label>Star</label>
              <input type="number" name="point" id="point" class="form-control" min="0" value="0" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group mb-2">
              <label>Urutan</label>
              <input type="number" name="sort_order" id="sort_order" class="form-control" min="1" placeholder="Kosongkan = paling bawah">
              <small class="text-muted">Isi posisi; kosongkan jika ingin ditaruh di paling bawah.</small>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group mb-2">
              <label>Status Aktif</label>
              <select name="is_active" id="is_active" class="form-control select2" data-placeholder="-- Pilih Status --" required>
                <option value=""></option>
                @foreach (Dropdown::activeStatusOpt() as $key => $item)
                  <option value="{{ $key }}">{{ $item }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group mb-2">
              <label>Status Segmen</label>
              <select name="is_checked_segmen" id="is_checked_segmen" class="form-control select2" data-placeholder="-- Pilih Status --" required>
                <option value=""></option>
                @foreach (Dropdown::requiredStatusOpt() as $key => $item)
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

        {{-- ===== WAJIB FOTO ===== --}}
        <div class="form-group mb-2">
          <div class="form-check">
            {{-- hidden 0 supaya uncheck tetap terkirim --}}
            <input type="hidden" name="requires_photo" value="0">
            <input type="checkbox" class="form-check-input" id="requires_photo" name="requires_photo" value="1">
            <label class="form-check-label" for="requires_photo">Wajib Foto (Before & After)</label>
          </div>
          <small class="text-muted">Jika dicentang, user harus upload foto before & after saat menandai <em>Done</em>.</small>
        </div>

        <hr class="my-2">

        <div class="form-group mb-2">
          <label class="d-flex align-items-center justify-content-between">
            <span>Sub-Aktivitas <span class="text-muted">(opsional)</span></span>
          </label>

          <div class="input-group mb-2">
            <input type="text" id="sub_input" class="form-control" placeholder="Tulis sub-aktivitas lalu Enter / Tambah">
            <button type="button" id="btnAddSub" class="btn btn-outline-primary">Tambah</button>
          </div>

          <!-- daftar chip (sub_activities yang sudah ada) -->
          <div id="subs_chips" class="d-flex flex-wrap gap-2"></div>

          <!-- hidden inputs agar terkirim sebagai array -->
          <div id="subs_hidden"></div>
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
    $('#requires_photo').prop('checked', false);
    SUBS = []; renderSubs();
    $('#sort_order').val('');
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
    order:[[1,'asc']], // default sort kolom "Urutan"
    columns: [
      {data:'DT_RowIndex', orderable:false, searchable:false},
      {data:'sort_order', name:'sort_order', className:'text-end'},
      {data:'team', name:'team'},
      {data:'name', name:'name'},
      {data:'point', className:'text-end'},
      {data:'status', orderable:false, searchable:false},
      {data:'is_checked_segmen', orderable:false, searchable:false},
      {data:'action', orderable:false, searchable:false},
    ]
  });

  $('#f_team').on('change', function(){ table.ajax.reload(); });

  let SUBS = [];

  const $subInput = $('#sub_input');
  const $chips = $('#subs_chips');
  const $hidden = $('#subs_hidden');

  function renderSubs() {
    $chips.empty();
    SUBS.forEach((t, i) => {
      const esc = $('<div>').text(t).html();
      $chips.append(`
        <span class="chip" title="${esc}">
          <span class="txt">${esc}</span>
          <button type="button" class="x" data-i="${i}" aria-label="Hapus">&times;</button>
        </span>
      `);
    });
    $hidden.empty();
    SUBS.forEach(v => $hidden.append(`<input type="hidden" name="sub_activities[]" value="${$('<div>').text(v).html()}">`));
  }

  function addOne(raw) {
    const v = (raw || '').trim();
    if (!v) return;
    if (SUBS.map(x => x.toLowerCase()).includes(v.toLowerCase())) return;
    SUBS.push(v);
    renderSubs();
  }

  $('#btnAddSub').on('click', function() {
    addOne($subInput.val());
    $subInput.val('').focus();
  });

  $subInput.on('keydown', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      $('#btnAddSub').trigger('click');
    }
  });

  $chips.on('click', '.x', function() {
    const i = Number($(this).data('i'));
    SUBS.splice(i, 1);
    renderSubs();
  });

  // Add
  $('#btnAdd').on('click', function(){
    $('#modalTitle').text('Tambah Activity');
    $('#formAktifitas')[0].reset();
    $('#id_row').val('');
    $('#limit_period').val('none');
    $('#limit_quota').val(1);
    $('#sort_order').val('');
    $('#requires_photo').prop('checked', false);

    const tf = $('#f_team').val();
    if (tf) $('#team_id').val(tf).trigger('change');

    $('#modalAktifitas').modal('show');

    SUBS = []; renderSubs();
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
    $('#requires_photo').prop('checked', String($(this).data('requires_photo')) === '1');
    $('#sort_order').val($(this).data('sort_order') || '');

    $('#modalAktifitas').modal('show');

    let arr = $(this).data('sub_activities');
    if (typeof arr === 'string' && arr.trim() !== '') {
      try { arr = JSON.parse(arr); } catch (e) { arr = []; }
    }
    if (!Array.isArray(arr)) arr = [];
    SUBS = arr.filter(Boolean).map(String);
    renderSubs();
  });

  // Submit create/update
  $('#formAktifitas').on('submit', function(e){
    e.preventDefault();

    const id = $('#id_row').val();
    const body = $(this).serializeArray();
    const url = id ? urlUpdate(id) : ROUTES.store;
    const type = 'POST';

    const subActivities = [];
    $('#subs_chips .chip .txt').each(function() {
      subActivities.push($(this).text());
    });
    subActivities.forEach(function(sub) {
      body.push({ name: 'sub_activities[]', value: sub });
    });

    const data = id ? [...body, { name: '_method', value: 'PUT' }] : body;

    $('#btnSave').prop('disabled', true).text('Menyimpan...');

    $.ajax({
      url: url,
      type: type,
      data: data,
      success: function(res) {
        $('#modalAktifitas').modal('hide');
        table.ajax.reload(null, false);
        Swal.fire({ icon: 'success', title: 'OK', text: res.message });
      },
      error: function(xhr) {
        let msg = xhr.responseJSON?.message || 'Terjadi kesalahan';
        if (xhr.responseJSON?.errors) {
          msg += "\n" + Object.values(xhr.responseJSON.errors).flat().join('\n');
        }
        Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
      },
      complete: function() {
        $('#btnSave').prop('disabled', false).text('Simpan');
      }
    });
  });

  // DELETE
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
