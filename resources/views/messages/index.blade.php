@extends('push-notify::layouts.app')

@section('title', 'Message Templates')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Message Templates</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('notify.messages.create') }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-plus-lg"></i> New Template
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Body</th>
                        <th>URL</th>
                        <th>Icon</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $message)
                        <tr>
                            <td>{{ $message->id }}</td>
                            <td>{{ $message->title }}</td>
                            <td class="text-truncate" style="max-width: 250px;">{{ $message->body }}</td>
                            <td>
                                @if($message->url)
                                    <a href="{{ $message->url }}" target="_blank" class="text-truncate d-inline-block" style="max-width: 150px;">
                                        {{ $message->url }}
                                    </a>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </td>
                            <td>
                            @if($message->icon_path && file_exists(public_path('storage/' . $message->icon_path)))
                                <img src="{{ asset('storage/' . $message->icon_path) }}" alt="Icon" class="img-thumbnail" style="width: 40px; height: 40px;">
                            @else
                                <span class="text-muted">Default</span>
                            @endif
                            </td>
                            <td>{{ $message->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('notify.messages.edit', $message->id) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('notify.messages.destroy', $message->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this template?');">
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
                            <td colspan="7" class="text-center">No message templates found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                Showing {{ $messages->firstItem() ?? 0 }} to {{ $messages->lastItem() ?? 0 }} 
                of {{ $messages->total() }} entries
            </div>
            <div>
                {{ $messages->links() }}
            </div>
        </div>
    </div>
</div>
@endsection