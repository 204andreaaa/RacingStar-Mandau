@extends('layouts.appBestRising')

@section('main')
<div class="content-wrapper">

    <div class="card">
        {{-- Header: tombol di kanan --}}
        <div class="card-header d-flex align-items-center">
            <h3 class="mb-0">Data Region</h3>
            <button class="btn btn-primary ms-auto ml-auto" id="btnAdd">Tambah Region</button>
        </div>

        <div class="card-body">
            <table id="table-region" class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Region</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="modalRegion" tabindex="-1">
    <div class="modal-dialog">
        <form id="formRegion">@csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Region</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id_region">
                    <div class="form-group">
                        <label>Nama Region</label>
                        <input type="text" name="nama_region" id="nama_region" class="form-control" required>
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

{{-- DataTables Buttons (untuk tombol custom Export) --}}
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>

<script>
$(function(){
    $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

    // SweetAlert helpers
    const swalSuccess = (text='Berhasil diproses') =>
        Swal.fire({title:'Sukses', text, icon:'success', confirmButtonText:'OK'});
    const swalError = (text='Terjadi kesalahan') =>
        Swal.fire({title:'Gagal', text, icon:'error', confirmButtonText:'OK'});
    const swalConfirm = (text='Hapus region ini?') =>
        Swal.fire({title:'Yakin?', text, icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus', cancelButtonText:'Batal'});

    // ---- Routes ----
    const ROUTES = {
        index   : "{{ route('admin.region.index') }}",
        store   : "{{ route('admin.region.store') }}",
        update  : "{{ route('admin.region.update', ':id') }}",
        destroy : "{{ route('admin.region.destroy', ':id') }}",
        export  : "{{ route('admin.region.export') }}", // ⬅️ server-side export
    };
    const urlUpdate  = id => ROUTES.update.replace(':id', id);
    const urlDestroy = id => ROUTES.destroy.replace(':id', id);

    const table = $('#table-region').DataTable({
        processing:true, serverSide:true,
        // letakkan Buttons (B) setelah length (l)
        dom: '<"row align-items-center"<"col-sm-6 d-flex"lB><"col-sm-6"f>>rt<"row"<"col-sm-5"i><"col-sm-7"p>>',
        ajax: ROUTES.index,
        columns: [
            {data:'DT_RowIndex', name:'DT_RowIndex', orderable:false, searchable:false},
            {data:'nama_region', name:'nama_region'},
            {data:'action', name:'action', orderable:false, searchable:false},
        ],
        buttons: [
            {
                text: 'Export Excel (Semua)',
                className: 'btn btn-success btn-sm',
                action: function(){
                    // ikutkan global search
                    const q = $('#table-region_filter input').val() || '';
                    const params = new URLSearchParams();
                    if (q) params.set('q', q);
                    window.location = ROUTES.export + (params.toString() ? ('?'+params.toString()) : '');
                }
            }
        ]
    });

    // Add
    $('#btnAdd').on('click', function(){
        $('#modalTitle').text('Tambah Region');
        $('#formRegion')[0].reset();
        $('#id_region').val('');
        $('#modalRegion').modal('show');
    });

    // Edit
    $(document).on('click','.btn-edit', function(){
        $('#modalTitle').text('Edit Region');
        $('#formRegion')[0].reset();
        $('#id_region').val($(this).data('id'));
        $('#nama_region').val($(this).data('nama'));
        $('#modalRegion').modal('show');
    });

    // Create / Update
    $('#formRegion').on('submit', function(e){
        e.preventDefault();
        const id   = $('#id_region').val();
        const data = $(this).serialize() + (id ? '&_method=PUT' : '');
        const url  = id ? urlUpdate(id) : ROUTES.store;

        $('#btnSave').prop('disabled',true).text('Menyimpan...');
        $.post(url, data)
        .done(res => {
            $('#modalRegion').modal('hide');
            table.ajax.reload(null,false);
            swalSuccess(res.message ?? (id ? 'Data berhasil diperbarui!' : 'Data berhasil ditambahkan!'));
        })
        .fail(xhr => {
            let msg = xhr.responseJSON?.message || 'Terjadi kesalahan';
            if (xhr.responseJSON?.errors) msg += "\n" + Object.values(xhr.responseJSON.errors).flat().join('\n');
            swalError(msg);
        })
        .always(() => $('#btnSave').prop('disabled',false).text('Simpan'));
    });

    // Delete
    $(document).on('click','.btn-delete', function(){
        const id = $(this).data('id');
        swalConfirm('Region ini akan dihapus.')
        .then(r => {
            if (!r.isConfirmed) return;
            $.post(urlDestroy(id), {_method:'DELETE'})
            .done(res => { table.ajax.reload(null,false); swalSuccess(res.message ?? 'Berhasil dihapus'); })
            .fail(xhr => {
                let msg = xhr.responseJSON?.message || 'Gagal menghapus data';
                if (xhr.responseJSON?.errors) msg += "\n" + Object.values(xhr.responseJSON.errors).flat().join('\n');
                swalError(msg);
            });
        });
    });
});
</script>
@endsection
