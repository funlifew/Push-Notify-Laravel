@extends('push-notify::layouts.app')

@section('title', 'Edit Message Template')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Message Template</h1>
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
                <h5 class="mb-0">{{ $message->title }}</h5>
                <small class="text-muted">Created {{ $message->created_at->format('M d, Y') }}</small>
            </div>
            <div class="card-body">
                <form action="{{ route('notify.messages.update', $message->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $message->title) }}">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="body" class="form-label">Body</label>
                        <textarea class="form-control @error('body') is-invalid @enderror" id="body" name="body" rows="4">{{ old('body', $message->body) }}</textarea>
                        @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="url" class="form-label">URL (Optional)</label>
                        <input type="url" class="form-control @error('url') is-invalid @enderror" id="url" name="url" value="{{ old('url', $message->url) }}" placeholder="https://example.com">
                        @error('url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="icon" class="form-label">Icon</label>
                        <div class="row align-items-center mb-2">
                            <div class="col-auto">
                                @if($message->icon_path)
                                    <img src="{{ Storage::disk(config('push-notify.storage.disk', 'public'))->url($message->icon_path) }}" 
                                        alt="Current Icon" class="img-thumbnail" style="width: 64px; height: 64px;">
                                @else
                                    <span class="text-muted">No custom icon set</span>
                                @endif
                            </div>
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remove_icon" name="remove_icon" value="1">
                                    <label class="form-check-label" for="remove_icon">
                                        Remove current icon
                                    </label>
                                </div>
                            </div>
                        </div>
                        <input type="file" class="form-control @error('icon') is-invalid @enderror" id="icon" name="icon">
                        @error('icon')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Upload a new icon to replace the current one.</div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Template
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection