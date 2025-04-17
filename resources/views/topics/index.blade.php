@extends('push-notify::layouts.app')

@section('title', 'Topics')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Topics</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('notify.topics.create') }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-plus-lg"></i> New Topic
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
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Subscribers</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topics as $topic)
                        <tr>
                            <td>{{ $topic->id }}</td>
                            <td>{{ $topic->name }}</td>
                            <td><code>{{ $topic->slug }}</code></td>
                            <td>
                                <span class="badge bg-primary">{{ $topic->subscriptions->count() }}</span>
                            </td>
                            <td>{{ $topic->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('notify.topics.send', $topic->id) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-send"></i>
                                    </a>
                                    <a href="{{ route('notify.topics.edit', $topic->id) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('notify.topics.destroy', $topic->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this topic?');">
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
                            <td colspan="6" class="text-center">No topics found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                Showing {{ $topics->firstItem() ?? 0 }} to {{ $topics->lastItem() ?? 0 }} 
                of {{ $topics->total() }} entries
            </div>
            <div>
                {{ $topics->links() }}
            </div>
        </div>
    </div>
</div>
@endsection