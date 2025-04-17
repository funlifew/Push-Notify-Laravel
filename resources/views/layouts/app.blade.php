<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-public-key" content="{{ config('push-notify.public_vapid_key') }}">
    
    <title>@yield('title', 'Push Notifications') - {{ config('app.name') }}</title>
    
    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .nav-link {
            font-weight: 500;
            color: #333;
        }
        
        .nav-link.active {
            color: #ff5000;
        }
        
        .sidebar .nav-link:hover {
            color: #ff5000;
        }
        
        .sidebar .nav-link .bi {
            margin-right: 4px;
            color: #999;
        }
        
        .sidebar .nav-link.active .bi {
            color: #ff5000;
        }
        
        main {
            padding-top: 48px;
        }
        
        .navbar-brand {
            font-size: 1rem;
            padding-top: .75rem;
            padding-bottom: .75rem;
            font-weight: 700;
        }
        
        .btn-primary {
            background-color: #ff5000;
            border-color: #ff5000;
        }
        
        .btn-primary:hover {
            background-color: #e64800;
            border-color: #e64800;
        }
        
        .btn-outline-primary {
            color: #ff5000;
            border-color: #ff5000;
        }
        
        .btn-outline-primary:hover {
            background-color: #ff5000;
            border-color: #ff5000;
        }
        
        .alert-success {
            background-color: #d1e7dd;
            border-color: #badbcc;
            color: #0f5132;
        }
        
        @media (max-width: 767.98px) {
            .sidebar {
                top: 5rem;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="{{ route('notify.dashboard') }}">
            Push Notify
        </a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" 
                data-bs-toggle="collapse" data-bs-target="#sidebarMenu" 
                aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="navbar-nav">
            <div class="nav-item text-nowrap">
                <a class="nav-link px-3" href="{{ config('push-notify.routes.home_url', '/') }}">
                    Back to Site
                </a>
            </div>
        </div>
    </header>
    
    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('notify.dashboard') ? 'active' : '' }}" 
                               href="{{ route('notify.dashboard') }}">
                                <i class="bi bi-speedometer2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('notify.subscriptions.*') ? 'active' : '' }}" 
                               href="{{ route('notify.subscriptions.index') }}">
                                <i class="bi bi-bell"></i>
                                Subscriptions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('notify.topics.*') ? 'active' : '' }}" 
                               href="{{ route('notify.topics.index') }}">
                                <i class="bi bi-tags"></i>
                                Topics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('notify.messages.*') ? 'active' : '' }}" 
                               href="{{ route('notify.messages.index') }}">
                                <i class="bi bi-chat-left-text"></i>
                                Message Templates
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('notify.scheduled.*') ? 'active' : '' }}" 
                               href="{{ route('notify.scheduled.index') }}">
                                <i class="bi bi-calendar-event"></i>
                                Scheduled Notifications
                            </a>
                        </li>
                    </ul>
                    
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Quick Actions</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('notify.subscriptions.send-all') }}">
                                <i class="bi bi-broadcast"></i>
                                Send to All
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('notify.scheduled.create') }}">
                                <i class="bi bi-calendar-plus"></i>
                                Schedule Notification
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('notify.topics.create') }}">
                                <i class="bi bi-tag"></i>
                                New Topic
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('notify.messages.create') }}">
                                <i class="bi bi-chat-square-text"></i>
                                New Template
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                
                @yield('content')
                <button onclick="window.PushNotify.handleSubscription()">Subscribe to Notifications</button>
            </main>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script src="{{ asset('vendor/push-notify/js/subscription.js') }}"></script>
    
    @stack('scripts')
</body>
</html>