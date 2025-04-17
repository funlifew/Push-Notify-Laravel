@extends('push-notify::layouts.app')

@section('title', 'Push Notifications Dashboard')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="{{ route('notify.subscriptions.send-all') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-broadcast"></i> Send to All
            </a>
            <a href="{{ route('notify.scheduled.create') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-calendar-plus"></i> Schedule
            </a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Subscriptions</h5>
                <p class="card-text display-6">{{ $subscriptions->total() }}</p>
                <a href="{{ route('notify.subscriptions.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Topics</h5>
                <p class="card-text display-6">{{ $topics->total() }}</p>
                <a href="{{ route('notify.topics.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Message Templates</h5>
                <p class="card-text display-6">{{ $messages->total() }}</p>
                <a href="{{ route('notify.messages.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Scheduled</h5>
                <p class="card-text display-6">{{ $scheduledNotifications->total() }}</p>
                <a href="{{ route('notify.scheduled.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Notifications Sent (Last 30 days)</h5>
            </div>
            <div class="card-body">
                <canvas id="notificationsChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Devices</h5>
            </div>
            <div class="card-body">
                <canvas id="devicesChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<h2 class="h4 mb-3">Upcoming Scheduled Notifications</h2>
@if($scheduledNotifications->count() > 0)
    <div class="table-responsive small">
        <table class="table table-striped table-hover table-sm">
            <thead>
                <tr>
                    <th>Content</th>
                    <th>Recipients</th>
                    <th>Scheduled For</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($scheduledNotifications as $notification)
                    <tr>
                        <td>
                            @if($notification->message_id)
                                <strong>{{ $notification->message->title ?? 'Unknown' }}</strong>
                                <small class="d-block text-muted">Template</small>
                            @else
                                <strong>{{ $notification->title }}</strong>
                                <small class="d-block text-muted">{{ Str::limit($notification->body, 50) }}</small>
                            @endif
                        </td>
                        <td>
                            @if($notification->subscription_id)
                                <span class="badge bg-primary">Single Subscription</span>
                            @elseif($notification->topic_id)
                                <span class="badge bg-success">Topic: {{ $notification->topic->name ?? 'Unknown' }}</span>
                            @elseif($notification->send_to_all)
                                <span class="badge bg-warning text-dark">All Subscriptions</span>
                            @endif
                        </td>
                        <td>{{ $notification->scheduled_at->format('M d, Y h:i A') }}</td>
                        <td>
                            <span class="badge bg-{{ $notification->status === 'pending' ? 'info' : ($notification->status === 'processing' ? 'warning' : ($notification->status === 'sent' ? 'success' : 'danger')) }}">
                                {{ ucfirst($notification->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('notify.scheduled.show', $notification->id) }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($notification->status === 'pending')
                                    <a href="{{ route('notify.scheduled.edit', $notification->id) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('notify.scheduled.send-now', $notification->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Are you sure you want to send this notification now?')">
                                            <i class="bi bi-send"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('notify.scheduled.cancel', $notification->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this notification?')">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
            Showing {{ $scheduledNotifications->firstItem() ?? 0 }} to {{ $scheduledNotifications->lastItem() ?? 0 }} of {{ $scheduledNotifications->total() }} entries
        </div>
        <div>
            {{ $scheduledNotifications->links() }}
        </div>
    </div>
@else
    <div class="alert alert-info">
        No scheduled notifications found. <a href="{{ route('notify.scheduled.create') }}">Create one now</a>.
    </div>
@endif

<h2 class="h4 mb-3 mt-4">Recent Notifications</h2>
@if($notifications->count() > 0)
    <div class="table-responsive small">
        <table class="table table-striped table-hover table-sm">
            <thead>
                <tr>
                    <th>Content</th>
                    <th>Recipient</th>
                    <th>Sent At</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($notifications as $notification)
                    <tr>
                        <td>
                            @if($notification->message_id)
                                <strong>{{ $notification->message->title ?? 'Unknown' }}</strong>
                                <small class="d-block text-muted">Template</small>
                            @else
                                <strong>{{ $notification->title }}</strong>
                                <small class="d-block text-muted">{{ Str::limit($notification->body, 50) }}</small>
                            @endif
                        </td>
                        <td>
                            @if($notification->subscription_id)
                                <small>Subscription #{{ $notification->subscription_id }}</small>
                            @endif
                            
                            @if($notification->topic_id)
                                <span class="badge bg-success">{{ $notification->topic->name ?? 'Unknown Topic' }}</span>
                            @endif
                        </td>
                        <td>{{ $notification->created_at->format('M d, Y h:i A') }}</td>
                        <td>
                            @if($notification->status)
                                <span class="badge bg-success">Sent</span>
                            @else
                                <span class="badge bg-danger">Failed</span>
                                @if($notification->error)
                                    <span class="d-block small text-danger">{{ Str::limit($notification->error, 30) }}</span>
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
            Showing {{ $notifications->firstItem() ?? 0 }} to {{ $notifications->lastItem() ?? 0 }} of {{ $notifications->total() }} entries
        </div>
        <div>
            {{ $notifications->links() }}
        </div>
    </div>
@else
    <div class="alert alert-info">
        No notifications sent yet.
    </div>
@endif
@endsection

@push('scripts')
<script>
    // Notifications chart
    const notificationsCtx = document.getElementById('notificationsChart').getContext('2d');
    const notificationsChart = new Chart(notificationsCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_map(function($date) { return $date->format('M d'); }, $notificationStats['dates'])) !!},
            datasets: [{
                label: 'Sent',
                data: {!! json_encode($notificationStats['sent']) !!},
                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 2,
                tension: 0.3
            }, {
                label: 'Failed',
                data: {!! json_encode($notificationStats['failed']) !!},
                backgroundColor: 'rgba(220, 53, 69, 0.2)',
                borderColor: 'rgba(220, 53, 69, 1)',
                borderWidth: 2,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Devices chart
    const devicesCtx = document.getElementById('devicesChart').getContext('2d');
    const devicesChart = new Chart(devicesCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_keys($deviceStats)) !!},
            datasets: [{
                data: {!! json_encode(array_values($deviceStats)) !!},
                backgroundColor: [
                    'rgba(0, 123, 255, 0.7)',
                    'rgba(40, 167, 69, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(220, 53, 69, 0.7)',
                    'rgba(111, 66, 193, 0.7)',
                    'rgba(23, 162, 184, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });
</script>
@endpush