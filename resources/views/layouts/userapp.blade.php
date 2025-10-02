<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mandau - Racing Star</title>

    <script src="{{ asset('adminLTE/plugins/jquery/jquery.min.js') }}"></script>
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('adminLTE/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('adminLTE/dist/css/adminlte.min.css') }}">
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('adminLTE/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminLTE/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminLTE/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- daterange picker -->
    <link rel="stylesheet" href="{{ asset('adminLTE/plugins/daterangepicker/daterangepicker.css') }}">
    <!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="{{ asset('adminLTE/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- Bootstrap Color Picker -->
    <link rel="stylesheet" href="{{ asset('adminLTE/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css') }}">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet" href="{{ asset('adminLTE/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('adminLTE/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminLTE/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- Bootstrap4 Duallistbox -->
    <link rel="stylesheet" href="{{ asset('adminLTE/plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css') }}">
    <!-- BS Stepper -->
    <link rel="stylesheet" href="{{ asset('adminLTE/plugins/bs-stepper/css/bs-stepper.min.css') }}">
    <!-- dropzonejs -->
    <link rel="stylesheet" href="{{ asset('adminLTE/plugins/dropzone/min/dropzone.min.css') }}">
</head>
<body class="hold-transition sidebar-mini">
@php
  $u = session('auth_user'); // user dari manual auth
@endphp

<!-- Site wrapper -->
<div class="wrapper">
  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="{{ Route::has('teknisi.index') ? route('teknisi.index') : url('/teknisi') }}" class="nav-link">Home</a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      {{-- (opsional) search bawaan --}}
      <li class="nav-item">
        <a class="nav-link" data-widget="navbar-search" href="#" role="button">
          <i class="fas fa-search"></i>
        </a>
        <div class="navbar-search-block">
          <form class="form-inline">
            <div class="input-group input-group-sm">
              <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
              <div class="input-group-append">
                <button class="btn btn-navbar" type="submit"><i class="fas fa-search"></i></button>
                <button class="btn btn-navbar" type="button" data-widget="navbar-search"><i class="fas fa-times"></i></button>
              </div>
            </div>
          </form>
        </div>
      </li>

      {{-- Nama user --}}
      <li class="nav-item d-none d-sm-flex align-items-center mr-2 text-muted">
        <i class="far fa-user-circle mr-1"></i>
        <span class="small">{{ $u['nama'] ?? 'User' }}</span>
      </li>

      {{-- Tombol Logout (POST + CSRF) --}}
      <li class="nav-item">
        <a href="#" class="nav-link" id="btnLogoutUser" title="Keluar">
          <i class="fas fa-sign-out-alt"></i> <span class="d-none d-sm-inline">Logout</span>
        </a>
      </li>
    </ul>

    {{-- Form logout tersembunyi --}}
    <form id="logoutFormUser" action="{{ route('logout') }}" method="POST" class="d-none">
      @csrf
    </form>
  </nav>

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4" style="background-color:rgb(2, 73, 2); !Important">
    <!-- Brand Logo -->
    <a href="{{ Route::has('teknisi.index') ? route('teknisi.index') : url('/teknisi') }}" class="brand-link">
      <img src="{{asset('images/mandau.png')}}" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">Mandau - Racing Star</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="info">
                <a href="#" class="d-block">{{ $u['nama'] ?? 'Racing Star' }}</a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="{{ Route::has('teknisi.index') ? route('teknisi.index') : url('/teknisi') }}" class="nav-link">
                        <i class="nav-icon fas fa-home"></i>
                        <p>Ceklis team</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('checklists.table-ceklis') }}" class="nav-link">
                        <i class="nav-icon fas fa-list"></i>
                        <p>Data Ceklis</p>
                    </a>
                </li>
            </ul>
        </nav>

    </div>
  </aside>

  @yield('main')
</div>

<footer class="main-footer" style="display: flex; justify-content: center; align-items: center; text-align: center;">
    <div>
        <b>Version</b> 1.0 |
        <strong>Copyright &copy; 2025-now 
            <a href="https://mandau.id">Mandau</a>.
        </strong> All rights reserved.
    </div>
</footer>


@stack('scripts')
{{-- SweetAlert2 (kalau belum ada di layout) --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.all.min.js"></script>

<style>
  /* kecilin font title */
  .swal-title-sm {
    font-size: 16px !important;
    line-height: 1.4;
  }
  /* opsional: kecilin isi popup juga */
  .swal-popup-sm {
    font-size: 14px !important;
  }
  .select2-container .select2-selection--single {
    height: calc(2.25rem + 2px) !important; /* sama kaya .form-control-sm / normal */
    padding: .375rem .75rem !important;     /* kasih ruang atas bawah */
    border: 1px solid #ced4da !important;   /* samain border bootstrap */
    border-radius: .25rem !important;
  }

  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 1.5 !important; /* teks lebih tengah */
  }

  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 100% !important;
    right: .75rem !important;
  }
</style>

<script>
(function(){
  try {
    const raw = localStorage.getItem('BR_SWAL_AFTER_REDIRECT');
    if (!raw) return;
    const data = JSON.parse(raw || '{}');
    localStorage.removeItem('BR_SWAL_AFTER_REDIRECT'); // cuma sekali tampil

    const kind = (data && data.kind) || 'save'; // 'save' | 'finish'
    const title = kind === 'finish'
      ? 'Berhasil disimpan & diselesaikan, Selamat Anda mendapatkan Star'
      : 'Berhasil disimpan ayo lanjutkan untuk mendapatkan star';

    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'success',
        title: title, // lama tampil 4 detik
        showConfirmButton: false,
        customClass: {
          title: 'swal-title-sm',
          popup: 'swal-popup-sm'
        }
      });
    } else {
      alert(title); // fallback kalau swal gagal load
    }
  } catch(e){}
})();
</script>


<script src="{{ asset('adminLTE/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('adminLTE/dist/js/adminlte.min.js') }}"></script>
<script src="{{ asset('adminLTE/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('adminLTE/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('adminLTE/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('adminLTE/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ asset('adminLTE/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('adminLTE/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
<script src="{{ asset('adminLTE/plugins/jszip/jszip.min.js') }}"></script>
<script src="{{ asset('adminLTE/plugins/pdfmake/pdfmake.min.js') }}"></script>
<script src="{{ asset('adminLTE/plugins/pdfmake/vfs_fonts.js') }}"></script>
<script src="{{ asset('adminLTE/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('adminLTE/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
<script src="{{ asset('adminLTE/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
<script src="{{ asset('adminLTE/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('adminLTE/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>

<script>
  // DataTables demo (jika dipakai pada halaman)
  $(function () {
    if ($('#example1').length) {
      $("#example1").DataTable({
        "responsive": true, "lengthChange": false, "autoWidth": false,
        "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
      }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
    }
  });

  // Logout + konfirmasi
  $(document).on('click', '#btnLogoutUser', function(e){
    e.preventDefault();
    if (typeof Swal === 'undefined') {
      // tanpa SweetAlert (fallback)
      document.getElementById('logoutFormUser').submit();
      return;
    }
    Swal.fire({
      title: 'Keluar dari akun?',
      text: 'Sesi kamu akan diakhiri.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, keluar',
      cancelButtonText: 'Batal'
    }).then((res) => {
      if (res.isConfirmed) {
        document.getElementById('logoutFormUser').submit();
      }
    });
  });
</script>
</body>
</html>
