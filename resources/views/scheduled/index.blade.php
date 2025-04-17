@extends('push-notify::layouts.app')

@section('title', 'Scheduled Notifications')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Scheduled Notifications</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('notify.scheduled.create') }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-calendar-plus"></i> Schedule New
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <ul class="nav nav-tabs mb-3" id="scheduledTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                    Pending <span class="badge bg-primary">{{ $pending->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button" role="tab">
                    Sent <span class="badge bg-success">{{ $sent->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="failed-tab" data-bs-toggle="tab" data-bs-target="#failed" type="button" role="tab">
                    Failed <span class="badge bg-danger">{{ $failed->count() }}</span>
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="scheduledTabsContent">
            <!-- Pending Notifications -->
            <div class="tab-pane fade show active" id="pending" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Content</th>
                                <th>Recipient</th>
                                <th>Scheduled For</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pending as $notification)
                                <tr>
                                    <td>{{ $notification->id }}</td>
                                    <td>
                                        @if($notification->message_id)
                                            <strong>{{ $notification->message->title ?? 'Unknown' }}</strong>
                                            <small class="d-block text-muted">Template</small>
                                        @else
                                            <strong>{{ $notification->title }}</strong>
                                            <small class="d-block text-muted text-truncate" style="max-width: 200px;">{{ $notification->body }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($notification->subscription_id)
                                            <span class="badge bg-primary">
                                                Single Subscription #{{ $notification->subscription_id }}
                                            </span>
                                        @elseif($notification->topic_id)
                                            <span class="badge bg-success">
                                                Topic: {{ $notification->topic->name ?? 'Unknown' }}
                                            </span>
                                        @elseif($notification->send_to_all)
                                            <span class="badge bg-warning text-dark">All Subscriptions</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $notification->scheduled_at->format('M d, Y') }}<br>
                                        <small class="text-muted">{{ $notification->scheduled_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        @if($notification->creator)
                                            {{ $notification->creator->name ?? $notification->created_by }}
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('notify.scheduled.show', $notification->id) }}" class="btn btn-outline-secondary">
                                                <i class="bi bi-eye"></i>
                                            </a>
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
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No pending scheduled notifications found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Sent Notifications -->
            <div class="tab-pane fade" id="sent" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Content</th>
                                <th>Recipient</th>
                                <th>Scheduled For</th>
                                <th>Sent At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sent as $notification)
                                <tr>
                                    <td>{{ $notification->id }}</td>
                                    <td>
                                        @if($notification->message_id)
                                            <strong>{{ $notification->message->title ?? 'Unknown' }}</strong>
                                            <small class="d-block text-muted">Template</small>
                                        @else
                                            <strong>{{ $notification->title }}</strong>
                                            <small class="d-block text-muted text-truncate" style="max-width: 200px;">{{ $notification->body }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($notification->subscription_id)
                                            <span class="badge bg-primary">
                                                Single Subscription #{{ $notification->subscription_id }}
                                            </span>
                                        @elseif($notification->topic_id)
                                            <span class="badge bg-success">
                                                Topic: {{ $notification->topic->name ?? 'Unknown' }}
                                            </span>
                                        @elseif($notification->send_to_all)
                                            <span class="badge bg-warning text-dark">All Subscriptions</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $notification->scheduled_at->format('M d, Y') }}<br>
                                        <small class="text-muted">{{ $notification->scheduled_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        {{ $notification->sent_at->format('M d, Y') }}<br>
                                        <small class="text-muted">{{ $notification->sent_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('notify.scheduled.show', $notification->id) }}" class="btn btn-outline-secondary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No sent scheduled notifications found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Failed Notifications -->
            <div class="tab-pane fade" id="failed" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Content</th>
                                <th>Recipient</th>
                                <th>Scheduled For</th>
                                <th>Error</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($failed as $notification)
                                <tr>
                                    <td>{{ $notification->id }}</td>
                                    <td>
                                        @if($notification->message_id)
                                            <strong>{{ $notification->message->title ?? 'Unknown' }}</strong>
                                            <small class="d-block text-muted">Template</small>
                                        @else
                                            <strong>{{ $notification->title }}</strong>
                                            <small class="d-block text-muted text-truncate" style="max-width: 200px;">{{ $notification->body }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($notification->subscription_id)
                                            <span class="badge bg-primary">
                                                Single Subscription #{{ $notification->subscription_id }}
                                            </span>
                                        @elseif($notification->topic_id)
                                            <span class="badge bg-success">
                                                Topic: {{ $notification->topic->name ?? 'Unknown' }}
                                            </span>
                                        @elseif($notification->send_to_all)
                                            <span class="badge bg-warning text-dark">All Subscriptions</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $notification->scheduled_at->format('M d, Y') }}<br>
                                        <small class="text-muted">{{ $notification->scheduled_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        <span class="text-danger">{{ $notification->error ?? 'Unknown error' }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('notify.scheduled.show', $notification->id) }}" class="btn btn-outline-secondary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <form action="{{ route('notify.scheduled.send-now', $notification->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Are you sure you want to retry sending this notification?')">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('notify.scheduled.cancel', $notification->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this failed notification?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No failed scheduled notifications found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection