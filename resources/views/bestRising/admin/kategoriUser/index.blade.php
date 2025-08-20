@extends('layouts.appBestRising')
@section('main')

<script src="{{ asset('adminLTE/plugins/jquery/jquery.min.js') }}"></script>
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="content-wrapper">
    <section class="content-header">
        <h1>Kategori User</h1>
    </section>

    <section class="content">
        <div class="row">
            <!-- Tabel -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Data Kategori User</div>
                    <div class="card-body">
                        <table id="table-kategori-user" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>NO</th>
                                    <th>Nama Kategori</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">Tambah Kategori</div>
                    <div class="card-body">
                        <form id="kategoriUserForm">
                            @csrf
                            <div class="form-group">
                                <label>Nama Kategori</label>
                                <input type="text" id="nama_kategori" name="nama_kategori" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Edit -->
        <div class="modal fade" id="editModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">Edit Kategori</div>
                    <div class="modal-body">
                        <input type="hidden" id="kategori_id">
                        <div class="form-group">
                            <label>Nama Kategori</label>
                            <input type="text" id="edit_nama_kategori" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        <button class="btn btn-primary btn-update">Update</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(function () {
    $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

    // route helpers (admin.*)
    const ROUTES = {
        index  : "{{ route('admin.kategori-user.index') }}",
        store  : "{{ route('admin.kategori-user.store') }}",
        update : "{{ route('admin.kategori-user.update', ':id') }}",
        destroy: "{{ route('admin.kategori-user.destroy', ':id') }}",
    };
    const urlUpdate  = id => ROUTES.update.replace(':id', id);
    const urlDestroy = id => ROUTES.destroy.replace(':id', id);

    let table = $('#table-kategori-user').DataTable({
        processing: true,
        serverSide: true,
        ajax: ROUTES.index,
        columns: [
            {data: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'nama_kategori'},
            {data: 'action', orderable: false, searchable: false}
        ]
    });

    // Tambah
    $('#kategoriUserForm').on('submit', function (e) {
        e.preventDefault();
        $.post(ROUTES.store, $(this).serialize())
        .done(res => {
            table.ajax.reload(null,false);
            this.reset();
            Swal.fire('Sukses', res.message ?? 'Berhasil disimpan', 'success');
        })
        .fail(xhr => {
            let msg = xhr.responseJSON?.message ?? 'Terjadi kesalahan';
            if(xhr.responseJSON?.errors) msg += "\n" + Object.values(xhr.responseJSON.errors).flat().join('\n');
            Swal.fire('Gagal', msg, 'error');
        });
    });

    // Hapus
    $('body').on('click', '.btn-delete', function () {
        let id = $(this).data('id');
        Swal.fire({title:'Yakin?', icon:'warning', showCancelButton:true})
        .then((r) => {
            if (!r.isConfirmed) return;
            $.post(urlDestroy(id), {_method:'DELETE'})
            .done(res => {
                table.ajax.reload(null,false);
                Swal.fire('Sukses', res.message ?? 'Berhasil dihapus', 'success');
            })
            .fail(() => Swal.fire('Gagal','Gagal menghapus data','error'));
        });
    });

    // Edit (buka modal)
    $('body').on('click', '.btn-edit', function () {
        $('#kategori_id').val($(this).data('id'));
        $('#edit_nama_kategori').val($(this).data('nama_kategori'));
        $('#editModal').modal('show');
    });

    // Update (PUT via _method)
    $('.btn-update').on('click', function () {
        let id = $('#kategori_id').val();
        $.post(urlUpdate(id), {_method:'PUT', nama_kategori: $('#edit_nama_kategori').val()})
        .done(res => {
            table.ajax.reload(null,false);
            $('#editModal').modal('hide');
            Swal.fire('Sukses', res.message ?? 'Berhasil diupdate', 'success');
        })
        .fail(xhr => {
            let msg = xhr.responseJSON?.message ?? 'Terjadi kesalahan';
            if(xhr.responseJSON?.errors) msg += "\n" + Object.values(xhr.responseJSON.errors).flat().join('\n');
            Swal.fire('Gagal', msg, 'error');
        });
    });
});
</script>
@endsection
