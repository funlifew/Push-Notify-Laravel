@extends('push-notify::layouts.app')

@section('title', 'Create Message Template')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Create Message Template</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('notify.messages.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Templates
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">New Template</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('notify.messages.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Title of the notification.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="body" class="form-label">Body</label>
                        <textarea class="form-control @error('body') is-invalid @enderror" id="body" name="body" rows="4">{{ old('body') }}</textarea>
                        @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Main content of the notification.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="url" class="form-label">URL (Optional)</label>
                        <input type="url" class="form-control @error('url') is-invalid @enderror" id="url" name="url" value="{{ old('url') }}" placeholder="https://example.com">
                        @error('url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">URL to open when notification is clicked.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="icon" class="form-label">Icon (Optional)</label>
                        <input type="file" class="form-control @error('icon') is-invalid @enderror" id="icon" name="icon">
                        @error('icon')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Image for the notification (recommended size: 64x64px).</div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Create Template
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection