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
  $u = session('auth_user'); // ambil user dari session manual auth
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
        <a href="#" class="nav-link">Home</a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      {{-- Search --}}
      <li class="nav-item">
        <a class="nav-link" data-widget="navbar-search" href="#" role="button">
          <i class="fas fa-search"></i>
        </a>
        <div class="navbar-search-block">
          <form class="form-inline">
            <div class="input-group input-group-sm">
              <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
              <div class="input-group-append">
                <button class="btn btn-navbar" type="submit">
                  <i class="fas fa-search"></i>
                </button>
                <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </form>
        </div>
      </li>

      {{-- Nama user (opsional) --}}
      <li class="nav-item d-none d-sm-flex align-items-center mr-2 text-muted">
        <i class="far fa-user-circle mr-1"></i>
        <span class="small">{{ $u['nama'] ?? 'User' }}</span>
      </li>

      {{-- Tombol Logout --}}
      <li class="nav-item">
        <a href="#" class="nav-link" id="btnLogout" title="Keluar">
          <i class="fas fa-sign-out-alt"></i> <span class="d-none d-sm-inline">Logout</span>
        </a>
      </li>
    </ul>

    {{-- Form POST logout tersembunyi --}}
    <form id="logoutForm" action="{{ route('logout') }}" method="POST" class="d-none">
      @csrf
    </form>
  </nav>

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4" style="background-color:rgb(2, 73, 2); !Important">
    <!-- Brand Logo -->
    <a href="#" class="brand-link">
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
                    <a href="{{ route('admin.home') }}" class="nav-link">
                        <i class="nav-icon fas fa-home"></i>
                        <p> HOME </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.user-bestrising.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-users"></i>
                        <p> User </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.kategori-user.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-user-tag"></i>
                        <p> Kategori User </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.region.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-map-marked-alt"></i>
                        <p> Region </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.serpo.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-warehouse"></i>
                        <p> Serpo </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.segmen.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-cubes"></i>
                        <p> Segmen </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.aktifitas.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-tasks"></i>
                        <p>Master Activity </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.checklists.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-check-square"></i>
                        <p> Activity Result </p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.checklists.allresult') }}" class="nav-link">
                        <i class="nav-icon fas fa-check-square"></i>
                        <p> All Activity </p>
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
        <b>Version</b> 3.2.0 |
        <strong>Copyright &copy; 2025-now 
            <a href="https://mandau.id">Mandau</a>.
        </strong> All rights reserved.
    </div>
</footer>


<script src="{{asset('adminLTE/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('adminLTE/dist/js/adminlte.min.js')}}"></script>
<script src="{{asset('adminLTE/plugins/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('adminLTE/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{asset('adminLTE/plugins/datatables-responsive/js/dataTables.responsive.min.js')}}"></script>
<script src="{{asset('adminLTE/plugins/datatables-responsive/js/responsive.bootstrap4.min.js')}}"></script>
<script src="{{asset('adminLTE/plugins/datatables-buttons/js/dataTables.buttons.min.js')}}"></script>
<script src="{{asset('adminLTE/plugins/datatables-buttons/js/buttons.bootstrap4.min.js')}}"></script>
<script src="{{asset('adminLTE/plugins/jszip/jszip.min.js')}}"></script>
<script src="{{asset('adminLTE/plugins/pdfmake/pdfmake.min.js')}}"></script>
<script src="{{asset('adminLTE/plugins/pdfmake/vfs_fonts.js')}}"></script>
<script src="{{asset('adminLTE/plugins/datatables-buttons/js/buttons.html5.min.js')}}"></script>
<script src="{{asset('adminLTE/plugins/datatables-buttons/js/buttons.print.min.js')}}"></script>
<script src="{{asset('adminLTE/plugins/datatables-buttons/js/buttons.colVis.min.js')}}"></script>
<script src="{{asset('adminLTE/plugins/moment/moment.min.js')}}"></script>
<script src="{{asset('adminLTE/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js')}}"></script>

<style>
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
  // DataTables demo (punya kamu)
  $(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
  });

  // Tombol Logout dengan konfirmasi SweetAlert
  $(document).on('click', '#btnLogout', function(e){
    e.preventDefault();
    Swal.fire({
      title: 'Keluar dari akun?',
      text: 'Kamu akan mengakhiri sesi saat ini.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, keluar',
      cancelButtonText: 'Batal'
    }).then((res) => {
      if (res.isConfirmed) {
        document.getElementById('logoutForm').submit();
      }
    });
  });
</script>
</body>
</html>
