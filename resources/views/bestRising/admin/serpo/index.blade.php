@extends('layouts.appBestRising')

@section('main')
<div class="content-wrapper">

    <div class="card">
        {{-- Header: hanya judul + tombol di kanan --}}
        <div class="card-header d-flex align-items-center">
            <h3 class="mb-0">Data Serpo</h3>
            <button class="btn btn-primary ms-auto ml-auto" id="btnAdd">Tambah Serpo</button>
        </div>

        <div class="card-body">
            {{-- Template filter (dipindah ke toolbar DataTables) --}}
            <div id="dt-filters-template" style="display:none;">
                <div id="dt-filters" class="d-inline-flex align-items-center" style="gap:8px;margin-left:12px;">
                    <select id="filter_region" class="form-control form-control-sm" style="min-width:220px;">
                        <option value="">Semua Region</option>
                        @foreach($regions as $r)
                            <option value="{{ $r->id_region }}">{{ $r->nama_region }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <table id="table-serpo" class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Region</th>
                        <th>Nama Serpo</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="modalSerpo" tabindex="-1">
    <div class="modal-dialog">
        <form id="formSerpo">@csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Serpo</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id_serpo">
                    <div class="form-group">
                        <label>Region</label>
                        <select name="id_region" id="id_region" class="form-control" required>
                            <option value="">-- Pilih Region --</option>
                            @foreach($regions as $r)
                                <option value="{{ $r->id_region }}">{{ $r->nama_region }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nama Serpo</label>
                        <input type="text" name="nama_serpo" id="nama_serpo" class="form-control" required>
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

    // --- route helpers (admin.serpo.*) ---
    const ROUTES = {
        index   : "{{ route('admin.serpo.index') }}",
        store   : "{{ route('admin.serpo.store') }}",
        update  : "{{ route('admin.serpo.update', ':id') }}",   // replace(':id', id)
        destroy : "{{ route('admin.serpo.destroy', ':id') }}",  // replace(':id', id)
        export  : "{{ route('admin.serpo.export') }}",          // server-side export semua data
    };
    const urlUpdate  = id => ROUTES.update.replace(':id', id);
    const urlDestroy = id => ROUTES.destroy.replace(':id', id);

    // SweetAlert helpers (anggap sudah include di layout)
    const swalSuccess = (t='Berhasil diproses') => Swal.fire({title:'Sukses', text:t, icon:'success'});
    const swalError   = (t='Terjadi kesalahan') => Swal.fire({title:'Gagal', text:t, icon:'error'});
    const swalConfirm = (t='Hapus data ini?')    => Swal.fire({title:'Yakin?', text:t, icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus', cancelButtonText:'Batal'});

    // DataTable + filter di toolbar + tombol export server-side
    const table = $('#table-serpo').DataTable({
        processing:true, serverSide:true,
        // letakkan Buttons (B) setelah length (l) supaya nempel dengan "Show entries"
        dom: '<"row align-items-center"<"col-sm-6 d-flex"lB<"dt-extra-filters d-inline-flex align-items-center ms-2 ml-2">><"col-sm-6"f>>rt<"row"<"col-sm-5"i><"col-sm-7"p>>',
        ajax: {
            url: ROUTES.index,
            data: d => { d.id_region = $('#filter_region').val(); }
        },
        columns: [
            {data:'DT_RowIndex', orderable:false, searchable:false},
            {data:'region',      orderable:false, searchable:false},
            {data:'nama_serpo'},
            {data:'action',      orderable:false, searchable:false},
        ],
        buttons: [
            {
                text: 'Export Excel (Semua)',
                className: 'btn btn-success btn-sm',
                action: function(){
                    // ikuti filter Region + global search yang aktif
                    const id_region = $('#filter_region').val() || '';
                    const q         = $('#table-serpo_filter input').val() || '';
                    const params = new URLSearchParams();
                    if (id_region) params.set('id_region', id_region);
                    if (q)         params.set('q', q);
                    window.location = ROUTES.export + (params.toString() ? ('?'+params.toString()) : '');
                }
            }
        ]
    });

    // pindahkan filter ke toolbar
    const $filters = $('#dt-filters-template #dt-filters');
    $('.dt-extra-filters').append($filters);
    $('#dt-filters-template').remove();

    $(document).on('change', '#filter_region', ()=> table.ajax.reload(null,true));

    // Add
    $('#btnAdd').on('click', function(){
        $('#modalTitle').text('Tambah Serpo');
        $('#formSerpo')[0].reset();
        $('#id_serpo').val('');
        $('#modalSerpo').modal('show');
    });

    // Edit
    $(document).on('click','.btn-edit', function(){
        $('#modalTitle').text('Edit Serpo');
        $('#formSerpo')[0].reset();
        $('#id_serpo').val($(this).data('id'));
        $('#nama_serpo').val($(this).data('nama'));
        $('#id_region').val($(this).data('region'));
        $('#modalSerpo').modal('show');
    });

    // Submit (create/update)
    $('#formSerpo').on('submit', function(e){
        e.preventDefault();
        const id   = $('#id_serpo').val();
        const data = $(this).serialize() + (id ? '&_method=PUT' : '');
        const url  = id ? urlUpdate(id) : ROUTES.store;

        $('#btnSave').prop('disabled', true).text('Menyimpan...');

        $.post(url, data)
        .done(res => {
            $('#modalSerpo').modal('hide');
            table.ajax.reload(null,false);
            swalSuccess(res.message ?? (id ? 'Data berhasil diperbarui!' : 'Data berhasil ditambahkan!'));
        })
        .fail(xhr => {
            let msg = xhr.responseJSON?.message || 'Terjadi kesalahan';
            if (xhr.responseJSON?.errors) msg += "\n" + Object.values(xhr.responseJSON.errors).flat().join('\n');
            swalError(msg);
        })
        .always(() => {
            $('#btnSave').prop('disabled', false).text('Simpan');
        });
    });

    // Delete
    $(document).on('click','.btn-delete', function(){
        const id = $(this).data('id');
        swalConfirm('Serpo ini dan data terkaitnya akan dihapus.')
        .then(r => {
            if(!r.isConfirmed) return;
            $.post(urlDestroy(id), {_method:'DELETE'})
            .done(res => {
                table.ajax.reload(null,false);
                swalSuccess(res.message ?? 'Berhasil dihapus');
            })
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
