@extends('layouts.appBestRising')

@section('main')
<div class="content-wrapper">

    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex align-items-center flex-wrap gap-2">
                <h3 class="mb-0">Manajemen User Racing Star</h3>
                <button class="btn btn-primary ms-auto ml-auto" id="btnAdd">Tambah User</button>
            </div>
            <div class="card-body">
                <table id="table-kategori-user" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIK</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Kategori</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formUser">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUserTitle">Tambah User</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="user_id" name="user_id">

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>NIK</label>
                                <input type="text" name="nik" id="nik" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Nama</label>
                                <input type="text" name="nama" id="nama" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" id="email" class="form-control" required>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="form-group" id="formPassword">
                                <label>Password</label>
                                <input type="password" name="password" id="password" class="form-control">
                                <small class="form-text text-muted" id="passwordHint"></small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group" id="formPasswordConfirmation">
                                <label>Konfirmasi Password</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label>Kategori</label>
                                <select name="kategori_user_id" id="kategori_user_id" class="form-control" required>
                                    <option value="">--Pilih Kategori--</option>
                                    @foreach($kategoriUsers as $kat)
                                        <option value="{{ $kat->id_kategoriuser }}">{{ $kat->nama_kategoriuser }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- REGION --}}
                        <div class="col-lg-6 d-none field-region">
                            <div class="form-group">
                                <label>Region</label>
                                <select name="id_region" id="form_region" class="form-control">
                                    <option value="">-- Pilih Region --</option>
                                    @foreach($regions as $r)
                                        <option value="{{ $r->id_region }}">{{ $r->nama_region }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- SERPO --}}
                        <div class="col-lg-6 d-none field-serpo">
                            <div class="form-group">
                                <label>Serpo</label>
                                <select name="id_serpo" id="form_serpo" class="form-control">
                                    <option value="">-- Pilih Serpo --</option>
                                </select>
                            </div>
                        </div>

                        {{-- SEGMEN (disembunyikan untuk SERPO karena auto oleh backend) --}}
                        <div class="col-12 d-none field-segmen">
                            <div class="form-group">
                                <label>Segmen (bisa pilih lebih dari satu)</label>
                                <select name="id_segmen[]" id="form_segmen" class="form-control" multiple>
                                    {{-- tidak dipakai untuk SERPO --}}
                                </select>
                                <small class="text-muted">Tahan Ctrl/Command atau gunakan klik untuk multi-pilih.</small>
                            </div>
                        </div>

                    </div> {{-- row --}}
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

    const ROUTES = {
        index   : "{{ route('admin.user-bestrising.index') }}",
        store   : "{{ route('admin.user-bestrising.store') }}",
        update  : "{{ route('admin.user-bestrising.update', ':id') }}",
        destroy : "{{ route('admin.user-bestrising.destroy', ':id') }}",
        serpoByRegion : "{{ route('admin.serpo.byRegion', ['id_region' => 'RID']) }}",
        segmenBySerpo : "{{ route('admin.segmen.bySerpo', ['id_serpo' => 'SID']) }}", // tidak dipakai di UI SERPO
    };
    const urlUpdate   = id => ROUTES.update.replace(':id', id);
    const urlDestroy  = id => ROUTES.destroy.replace(':id', id);
    const urlByRegion = rid => ROUTES.serpoByRegion.replace('RID', rid);

    const swalSuccess = (text='Berhasil diproses') =>
        Swal.fire({title:'Sukses', text, icon:'success', confirmButtonText:'OK'});
    const swalError = (text='Terjadi kesalahan') =>
        Swal.fire({title:'Gagal', text, icon:'error', confirmButtonText:'OK'});
    const swalConfirm = (text='Lanjutkan aksi ini?') =>
        Swal.fire({ title:'Yakin?', text, icon:'warning', showCancelButton:true, confirmButtonText:'Ya, lanjut', cancelButtonText:'Batal' });

    const $region = $('#form_region');
    const $serpo  = $('#form_serpo');
    const $segmen = $('#form_segmen');

    let table = $('#table-kategori-user').DataTable({
        processing: true,
        serverSide: true,
        ajax: ROUTES.index,
        columns: [
            { data: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'nik' },
            { data: 'nama' },
            { data: 'email' },
            { data: 'kategori', orderable:false, searchable:false },
            { data: 'action', orderable:false, searchable:false },
        ]
    });

    function setRequired($el, required) {
        if(required) $el.attr('required', 'required');
        else $el.removeAttr('required');
    }

    function updateVisibilityByCategory() {
        const text = ($('#kategori_user_id option:selected').text() || '').trim().toUpperCase();

        // reset
        $('.field-region, .field-serpo, .field-segmen').addClass('d-none');
        setRequired($region, false);
        setRequired($serpo, false);
        setRequired($segmen, false);

        if (text.includes('ADMIN')) {
            $region.val('');
            $serpo.empty().append('<option value="">-- Pilih Serpo --</option>').val('');
            $segmen.empty();
        } else if (text.includes('SERPO')) {
            // HANYA Region + Serpo tampil & required
            $('.field-region, .field-serpo').removeClass('d-none');
            setRequired($region, true);
            setRequired($serpo, true);
            // segmen di-hide (auto backend)
            $segmen.empty();
        } else if (text.includes('NOC')) {
            // hanya region
            $('.field-region').removeClass('d-none');
            setRequired($region, true);
            $serpo.empty().append('<option value="">-- Pilih Serpo --</option>');
            $segmen.empty();
        } else {
            $region.val('');
            $serpo.empty().append('<option value="">-- Pilih Serpo --</option>').val('');
            $segmen.empty();
        }
    }

    function loadSerpoByRegion(regionId, $select, selected = '') {
        $select.prop('disabled', true).empty().append('<option value="">Memuat Serpo...</option>');
        $segmen.prop('disabled', true).empty();

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

    $('#kategori_user_id').on('change', function(){
        updateVisibilityByCategory();
    });

    $('#form_region').on('change', function(){
        const kat = ($('#kategori_user_id option:selected').text()||'').toUpperCase();
        if (kat.includes('NOC')) {
            $serpo.empty().append('<option value="">-- Pilih Serpo --</option>');
            $segmen.empty();
            return;
        }
        if (kat.includes('SERPO')) {
            loadSerpoByRegion(this.value, $serpo);
            $segmen.empty(); // tidak dipakai
        }
    });

    // Tidak perlu load segmen saat serpo change (auto di backend)
    $('#form_serpo').on('change', function(){ /* no-op */ });

    // Add
    $('#btnAdd').click(function() {
        $('#modalUserTitle').text('Tambah User');
        $('#formUser')[0].reset();
        $('#user_id').val('');

        $('#formPassword').show();
        $('#password').prop('required', true);
        $('#password_confirmation').prop('required', true);
        $('#passwordHint').text('Minimal 6 karakter.');

        $segmen.empty();
        $serpo.empty().append('<option value="">-- Pilih Serpo --</option>');

        updateVisibilityByCategory();
        $('#modalUser').modal('show');
    });

    // Edit
    $(document).on('click', '.btn-edit', function() {
        $('#modalUserTitle').text('Edit User');
        $('#formUser')[0].reset();

        $('#user_id').val($(this).data('id'));
        $('#nik').val($(this).data('nik'));
        $('#nama').val($(this).data('nama'));
        $('#email').val($(this).data('email'));
        $('#kategori_user_id').val($(this).data('kategori_id'));

        $('#formPassword').show();
        $('#password').prop('required', false).val('');
        $('#password_confirmation').prop('required', false).val('');
        $('#passwordHint').text('Kosongkan jika tidak ingin mengubah.');

        const regionId = $(this).data('region') || '';
        const serpoId  = $(this).data('serpo')  || '';
        $('#form_region').val(String(regionId));
        updateVisibilityByCategory();

        const katText = ($('#kategori_user_id option:selected').text()||'').toUpperCase();
        if (katText.includes('SERPO')) {
            loadSerpoByRegion(regionId, $serpo, serpoId)
              .always(() => $('#modalUser').modal('show'));
        } else {
            $('#modalUser').modal('show');
        }
    });

    // Submit
    $('#formUser').submit(function(e) {
        e.preventDefault();
        let id = $('#user_id').val();
        let url = id ? urlUpdate(id) : ROUTES.store;

        const fd = new FormData(this);
        if (id) fd.append('_method', 'PUT');

        $('#btnSave').prop('disabled', true).text('Menyimpan...');

        $.ajax({
            url: url, method: 'POST',
            data: fd, processData: false, contentType: false,
        })
        .done(function(res){
            $('#modalUser').modal('hide');
            table.ajax.reload(null, false);
            const msg = res.message ?? (id ? 'Data berhasil diperbarui!' : 'Data berhasil ditambahkan!');
            swalSuccess(msg);
        })
        .fail(function(xhr){
            let msg = xhr.responseJSON?.message ?? 'Terjadi kesalahan';
            if(xhr.responseJSON?.errors) {
                msg += "\n" + Object.values(xhr.responseJSON.errors).flat().join('\n');
            }
            swalError(msg);
        })
        .always(function(){
            $('#btnSave').prop('disabled', false).text('Simpan');
        });
    });

    // Delete
    $(document).on('click', '.btn-delete', function() {
        const id = $(this).data('id');
        swalConfirm('Apakah anda yakin ingin menghapus user ini?')
        .then(result => {
            if(!result.isConfirmed) return;

            $.post(urlDestroy(id), { _method: 'DELETE' })
            .done(function(res){
                table.ajax.reload(null, false);
                swalSuccess(res.message ?? 'Data berhasil dihapus!');
            })
            .fail(function(xhr){
                let msg = xhr.responseJSON?.message ?? 'Gagal menghapus data';
                if(xhr.responseJSON?.errors) {
                    msg += "\n" + Object.values(xhr.responseJSON.errors).flat().join('\n');
                }
                swalError(msg);
            });
        });
    });
});
</script>
@endsection
