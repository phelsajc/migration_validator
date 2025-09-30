<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Admin Panel')</title>

    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">

    @yield('styles')
</head>
{{-- <body class="hold-transition sidebar-mini"> --}}
    <body class="hold-transition sidebar-mini layout-fixed">

<div class="wrapper">

    <!-- Navbar -->
    @include('layouts.navbar')

    <!-- Sidebar -->
    @include('layouts.sidebar')

    <!-- Content Wrapper -->
    <div class="content-wrapper p-4">
        @yield('content')
    </div>

    <!-- Footer -->
    @include('layouts.footer')
</div>

<!-- Scripts -->
<script src="{{ asset('adminlte/js/adminlte.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/moment/moment.min.js') }}"></script>
<script>
    document.querySelector('[data-widget="pushmenu"]').addEventListener('click', function (e) {
        e.preventDefault();
        document.body.classList.toggle('sidebar-collapse');
    });
</script>
@yield('scripts')

</body>
</html>
