<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="au theme template">
    <meta name="author" content="Hau Nguyen">
    <meta name="keywords" content="au theme template">

    <!-- Title Page-->
    <title>Dashboard</title>

    <!-- Fontfaces CSS-->
    <link href="{{  asset("vendor/pushnotify/css/font-face.css")  }}" rel="stylesheet" media="all">
    <link href="{{  asset("vendor/pushnotify/font-awesome-4.7/css/font-awesome.min.css")  }}" rel="stylesheet" media="all">
    <link href="{{  asset("vendor/pushnotify/font-awesome-5/css/fontawesome-all.min.css")  }}" rel="stylesheet" media="all">
    <link href="{{ asset("vendor/pushnotify/mdi-font/css/material-design-iconic-font.min.css") }}" rel="stylesheet" media="all">

    <!-- Bootstrap CSS-->
    <link href="{{ asset("vendor/pushnotify/bootstrap-4.1/bootstrap.min.css") }}" rel="stylesheet" media="all">

    <!-- Vendor CSS-->
    <link href="{{ asset("vendor/pushnotify/animsition/animsition.min.css") }}" rel="stylesheet" media="all">
    <link href="{{ asset("vendor/pushnotify/bootstrap-progressbar/bootstrap-progressbar-3.3.4.min.css") }}" rel="stylesheet" media="all">
    <link href="{{ asset("vendor/pushnotify/wow/animate.css") }}" rel="stylesheet" media="all">
    <link href="{{ asset("vendor/pushnotify/css-hamburgers/hamburgers.min.css") }}" rel="stylesheet" media="all">
    <link href="{{ asset("vendor/pushnotify/slick/slick.css") }}" rel="stylesheet" media="all">
    <link href="{{ asset("vendor/pushnotify/select2/select2.min.css") }}" rel="stylesheet" media="all">
    <link href="{{ asset("vendor/pushnotify/perfect-scrollbar/perfect-scrollbar.css") }}" rel="stylesheet" media="all">

    <!-- Main CSS-->
    <link href="{{ asset("vendor/pushnotify/css/theme.css") }}" rel="stylesheet" media="all">

    <script defer src="{{ asset("vendor/pushnotify/js/subscripton.js") }}"></script>

    <style>
        nav.flex.items-center{
            gap: 2rem !important;
            width: 100%;
            text-align: center;
            margin-top: 15px;
        }
        nav.flex.items-center svg{
            margin: auto;
            width: 100%;
            text-align: center;
            width: 20px;
            height: 20px;
        }

        .page-wrapper, .header-desktop, .header-mobile {
            left: 0 !important;
        }
    </style>

</head>

<body" class="animsition w-100">
    <div class="page-wrapper w-100">
        @include("notify::header")

        @include("notify::sidebar")

        <!-- PAGE CONTAINER-->
        <div class="page-container p-0 w-100">
            @include("notify::desktop-header")

            <!-- MAIN CONTENT-->
            <div class="main-content w-100">
                <div class="section__content section__content--p30 w-100">
                    <div class="container-fluid w-100">
                        <div class="row">
                            <div class="col-12-lg text-center w-100 mb-4">
                                <button onclick="handleSubscription()" class="btn btn-primary w-100">Notify Me</button>
                            </div>
                        </div>
                        @yield("body")
                        @include("notify::footer")
                    </div>
                </div>
            </div>
            <!-- END MAIN CONTENT-->
            <!-- END PAGE CONTAINER-->
        </div>

    </div>

    <!-- Jquery JS-->

    <!-- <script>
        document.addEventListener('DOMContentLoaded', function() {
            handleSubscription();
        })
    </script> -->

    <script src="{{ asset('vendor/pushnotify/jquery-3.2.1.min.js') }}"></script>
<!-- Bootstrap JS-->
<script src="{{ asset('vendor/pushnotify/bootstrap-4.1/popper.min.js') }}"></script>
<script src="{{ asset('vendor/pushnotify/bootstrap-4.1/bootstrap.min.js') }}"></script>
<!-- Vendor JS -->
<script src="{{ asset('vendor/pushnotify/slick/slick.min.js') }}"></script>
<script src="{{ asset('vendor/pushnotify/wow/wow.min.js') }}"></script>
<script src="{{ asset('vendor/pushnotify/animsition/animsition.min.js') }}"></script>
<script src="{{ asset('vendor/pushnotify/bootstrap-progressbar/bootstrap-progressbar.min.js') }}"></script>
<script src="{{ asset('vendor/pushnotify/counter-up/jquery.waypoints.min.js') }}"></script>
<script src="{{ asset('vendor/pushnotify/counter-up/jquery.counterup.min.js') }}"></script>
<script src="{{ asset('vendor/pushnotify/circle-progress/circle-progress.min.js') }}"></script>
<script src="{{ asset('vendor/pushnotify/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('vendor/pushnotify/chartjs/Chart.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/pushnotify/select2/select2.min.js') }}"></script>

<!-- Main JS -->
<script src="{{ asset('vendor/pushnotify/js/main.js') }}"></script>


</body>

</html>
<!-- end document-->