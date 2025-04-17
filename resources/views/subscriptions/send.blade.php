@extends('push-notify::layouts.app')

@section('title', 'Send Notification')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Send Notification</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="{{ route('notify.subscriptions.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Subscriptions
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">
                    @if($subscription->user)
                        Send to {{ $subscription->user->name ?? $subscription->user->email }}
                    @else
                        Send to Subscription #{{ $subscription->id }}
                    @endif
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('notify.subscriptions.send.post', $subscription->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="content_type" id="content_type_custom" value="custom" checked>
                            <label class="form-check-label" for="content_type_custom">
                                Custom Content
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="content_type" id="content_type_template" value="template">
                            <label class="form-check-label" for="content_type_template">
                                Use Template
                            </label>
                        </div>
                    </div>
                    
                    <div id="custom_content">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="body" class="form-label">Body</label>
                            <textarea class="form-control @error('body') is-invalid @enderror" id="body" name="body" rows="3">{{ old('body') }}</textarea>
                            @error('body')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="url" class="form-label">URL (Optional)</label>
                            <input type="url" class="form-control @error('url') is-invalid @enderror" id="url" name="url" value="{{ old('url') }}" placeholder="https://example.com">
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="icon" class="form-label">Icon (Optional)</label>
                            <input type="file" class="form-control @error('icon') is-invalid @enderror" id="icon" name="icon">
                            @error('icon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div id="template_content" style="display: none;">
                        <div class="mb-3">
                            <label for="message_id" class="form-label">Select Template</label>
                            <select class="form-select @error('message_id') is-invalid @enderror" id="message_id" name="message_id">
                                <option value="">Select a template...</option>
                                @foreach($messages as $message)
                                    <option value="{{ $message->id }}" {{ old('message_id') == $message->id ? 'selected' : '' }}>
                                        {{ $message->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('message_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="schedule" name="schedule">
                            <label class="form-check-label" for="schedule">
                                Schedule for later
                            </label>
                        </div>
                    </div>
                    
                    <div id="schedule_options" style="display: none;">
                        <div class="mb-3">
                            <label for="scheduled_at" class="form-label">Schedule Date and Time</label>
                            <input type="datetime-local" class="form-control @error('scheduled_at') is-invalid @enderror" id="scheduled_at" name="scheduled_at" value="{{ old('scheduled_at') }}">
                            @error('scheduled_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Send Notification
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
        // Content type switching
        const contentTypeCustom = document.getElementById('content_type_custom');
        const contentTypeTemplate = document.getElementById('content_type_template');
        const customContent = document.getElementById('custom_content');
        const templateContent = document.getElementById('template_content');
        
        contentTypeCustom.addEventListener('change', function() {
            if (this.checked) {
                customContent.style.display = 'block';
                templateContent.style.display = 'none';
            }
        });
        
        contentTypeTemplate.addEventListener('change', function() {
            if (this.checked) {
                customContent.style.display = 'none';
                templateContent.style.display = 'block';
            }
        });
        
        // Schedule options
        const scheduleCheckbox = document.getElementById('schedule');
        const scheduleOptions = document.getElementById('schedule_options');
        
        scheduleCheckbox.addEventListener('change', function() {
            scheduleOptions.style.display = this.checked ? 'block' : 'none';
        });
    });
</script>
@endpush