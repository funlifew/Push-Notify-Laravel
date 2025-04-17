@extends('push-notify::layouts.app')

@section('title', 'Create Topic')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Create New Topic</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('notify.topics.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Topics
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="{{ route('notify.topics.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Topic Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">A descriptive name for this topic.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug') }}">
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">URL-friendly identifier (lowercase, no spaces).</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subscriptions" class="form-label">Add Subscriptions (Optional)</label>
                        <select class="form-select @error('subscriptions') is-invalid @enderror" id="subscriptions" name="subscriptions[]" multiple size="5">
                            @foreach($subscriptions as $subscription)
                                <option value="{{ $subscription->id }}" {{ in_array($subscription->id, old('subscriptions', [])) ? 'selected' : '' }}>
                                    #{{ $subscription->id }} - 
                                    @if($subscription->user)
                                        {{ $subscription->user->name ?? $subscription->user->email }}
                                    @else
                                        Anonymous ({{ $subscription->os }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('subscriptions')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Hold Ctrl/Cmd to select multiple subscriptions.</div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Create Topic
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const nameInput = document.getElementById('name');
        const slugInput = document.getElementById('slug');
        
        // Auto-generate slug from name
        nameInput.addEventListener('input', function() {
            const slug = this.value
                .toLowerCase()
                .replace(/[^\w\s-]/g, '') // Remove special characters
                .replace(/\s+/g, '-')     // Replace spaces with dashes
                .replace(/-+/g, '-');     // Replace multiple dashes with single dash
            
            slugInput.value = slug;
        });
    });
</script>
@endpush