@extends('push-notify::layouts.app')

@section('title', 'Scheduled Notification Details')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Scheduled Notification Details</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('notify.scheduled.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Scheduled
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        Notification #{{ $notification->id }}
                        <span class="badge bg-{{ $notification->status === 'pending' ? 'primary' : ($notification->status === 'processing' ? 'warning' : ($notification->status === 'sent' ? 'success' : 'danger')) }}">
                            {{ ucfirst($notification->status) }}
                        </span>
                    </h5>
                    <div>
                        @if($notification->status === 'pending')
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('notify.scheduled.edit', $notification->id) }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('notify.scheduled.send-now', $notification->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Are you sure you want to send this notification now?')">
                                        <i class="bi bi-send"></i> Send Now
                                    </button>
                                </form>
                                <form action="{{ route('notify.scheduled.cancel', $notification->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this notification?')">
                                        <i class="bi bi-x-lg"></i> Cancel
                                    </button>
                                </form>
                            </div>
                        @elseif($notification->status === 'failed')
                            <div class="btn-group btn-group-sm">
                                <form action="{{ route('notify.scheduled.send-now', $notification->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Are you sure you want to retry sending this notification?')">
                                        <i class="bi bi-arrow-repeat"></i> Retry
                                    </button>
                                </form>
                                <form action="{{ route('notify.scheduled.cancel', $notification->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this failed notification?')">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-uppercase text-muted mb-2">Notification Details</h6>
                        <dl class="row">
                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-{{ $notification->status === 'pending' ? 'primary' : ($notification->status === 'processing' ? 'warning' : ($notification->status === 'sent' ? 'success' : 'danger')) }}">
                                    {{ ucfirst($notification->status) }}
                                </span>
                            </dd>
                            
                            <dt class="col-sm-4">Scheduled For</dt>
                            <dd class="col-sm-8">{{ $notification->scheduled_at->format('M d, Y h:i A') }}</dd>
                            
                            @if($notification->sent_at)
                                <dt class="col-sm-4">Sent At</dt>
                                <dd class="col-sm-8">{{ $notification->sent_at->format('M d, Y h:i A') }}</dd>
                            @endif
                            
                            @if($notification->status === 'failed')
                                <dt class="col-sm-4">Error</dt>
                                <dd class="col-sm-8 text-danger">{{ $notification->error ?? 'Unknown error' }}</dd>
                            @endif
                            
                            <dt class="col-sm-4">Attempts</dt>
                            <dd class="col-sm-8">{{ $notification->attempts }}</dd>
                            
                            <dt class="col-sm-4">Created By</dt>
                            <dd class="col-sm-8">
                                @if($notification->creator)
                                    {{ $notification->creator->name ?? $notification->created_by }}
                                @else
                                    <span class="text-muted">System</span>
                                @endif
                            </dd>
                            
                            <dt class="col-sm-4">Created At</dt>
                            <dd class="col-sm-8">{{ $notification->created_at->format('M d, Y h:i A') }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-uppercase text-muted mb-2">Recipient Details</h6>
                        <dl class="row">
                            <dt class="col-sm-4">Type</dt>
                            <dd class="col-sm-8">
                                @if($notification->subscription_id)
                                    <span class="badge bg-primary">Single Subscription</span>
                                @elseif($notification->topic_id)
                                    <span class="badge bg-success">Topic</span>
                                @elseif($notification->send_to_all)
                                    <span class="badge bg-warning text-dark">All Subscriptions</span>
                                @endif
                            </dd>
                            
                            @if($notification->subscription_id)
                                <dt class="col-sm-4">Subscription</dt>
                                <dd class="col-sm-8">
                                    #{{ $notification->subscription_id }}
                                    @if($notification->subscription && $notification->subscription->user)
                                        ({{ $notification->subscription->user->name ?? $notification->subscription->user->email }})
                                    @endif
                                </dd>
                            @endif
                            
                            @if($notification->topic_id)
                                <dt class="col-sm-4">Topic</dt>
                                <dd class="col-sm-8">
                                    {{ $notification->topic->name ?? "Topic #$notification->topic_id" }}
                                    @if($notification->topic)
                                        <small class="d-block text-muted">{{ $notification->topic->subscriptions->count() }} subscribers</small>
                                    @endif
                                </dd>
                            @endif
                        </dl>
                    </div>
                </div>
                
                <h6 class="text-uppercase text-muted mb-2">Content</h6>
                <div class="card mb-3">
                    <div class="card-body">
                        @if($notification->message_id)
                            <div class="d-flex align-items-center mb-2">
                                <h5 class="m-0">{{ $notification->message->title ?? 'Unknown Template' }}</h5>
                                <span class="badge bg-secondary ms-2">Template</span>
                            </div>
                            <p class="card-text mb-3">{{ $notification->message->body ?? 'Template content not available' }}</p>
                            
                            @if($notification->message && $notification->message->url)
                                <div class="mb-2">
                                    <strong>URL:</strong> 
                                    <a href="{{ $notification->message->url }}" target="_blank">{{ $notification->message->url }}</a>
                                </div>
                            @endif
                            
                            @if($notification->message && $notification->message->icon_path)
                                <div>
                                    <strong>Icon:</strong>
                                    <div class="mt-2">
                                        <img src="{{ Storage::disk(config('push-notify.storage.disk', 'public'))->url($notification->message->icon_path) }}" 
                                            alt="Icon" class="img-thumbnail" style="width: 64px; height: 64px;">
                                    </div>
                                </div>
                            @endif
                        @else
                            <div class="mb-2">
                                <h5 class="card-title">{{ $notification->title }}</h5>
                                <p class="card-text">{{ $notification->body }}</p>
                            </div>
                            
                            @if($notification->url)
                                <div class="mb-2">
                                    <strong>URL:</strong> 
                                    <a href="{{ $notification->url }}" target="_blank">{{ $notification->url }}</a>
                                </div>
                            @endif
                            
                            @if($notification->icon_path)
                                <div>
                                    <strong>Icon:</strong>
                                    <div class="mt-2">
                                        <img src="{{ Storage::disk(config('push-notify.storage.disk', 'public'))->url($notification->icon_path) }}" 
                                            alt="Icon" class="img-thumbnail" style="width: 64px; height: 64px;">
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection