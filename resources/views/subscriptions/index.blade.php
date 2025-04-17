@extends('push-notify::layouts.app')

@section('title', 'Subscriptions')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Subscriptions</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="{{ route('notify.subscriptions.send-all') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-broadcast"></i> Send to All
            </a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Device</th>
                        <th>OS</th>
                        <th>Last Used</th>
                        <th>Topics</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscriptions as $subscription)
                        <tr>
                            <td>{{ $subscription->id }}</td>
                            <td>
                                @if($subscription->user)
                                    {{ $subscription->user->name ?? $subscription->user->email }}
                                @else
                                    <span class="text-muted">Anonymous</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-truncate d-inline-block" style="max-width: 200px;">
                                    {{ $subscription->device }}
                                </small>
                            </td>
                            <td>{{ $subscription->os }}</td>
                            <td>{{ $subscription->last_used_at->diffForHumans() }}</td>
                            <td>
                                @if($subscription->topics->count() > 0)
                                    @foreach($subscription->topics as $topic)
                                        <span class="badge bg-info">{{ $topic->name }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('notify.subscriptions.send', $subscription->id) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-send"></i>
                                    </a>
                                    <form action="{{ route('notify.subscriptions.destroy', $subscription->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this subscription?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No subscriptions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                Showing {{ $subscriptions->firstItem() ?? 0 }} to {{ $subscriptions->lastItem() ?? 0 }} 
                of {{ $subscriptions->total() }} entries
            </div>
            <div>
                {{ $subscriptions->links() }}
            </div>
        </div>
    </div>
</div>
@endsection