@extends('push-notify::layouts.app')

@section('title', 'Schedule Notification')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Schedule New Notification</h1>
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
                <h5 class="mb-0">Schedule Notification</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('notify.scheduled.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-4">
                        <h6 class="mb-3">Recipient</h6>
                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="recipient_type" id="recipient_type_all" value="all" {{ old('recipient_type') == 'all' ? 'checked' : '' }}>
                                <label class="form-check-label" for="recipient_type_all">
                                    All Subscribers
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="recipient_type" id="recipient_type_topic" value="topic" {{ old('recipient_type') == 'topic' ? 'checked' : '' }}>
                                <label class="form-check-label" for="recipient_type_topic">
                                    Topic
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="recipient_type" id="recipient_type_subscription" value="subscription" {{ old('recipient_type') == 'subscription' ? 'checked' : '' }}>
                                <label class="form-check-label" for="recipient_type_subscription">
                                    Single Subscription
                                </label>
                            </div>
                        </div>
                        
                        <div id="topic_selection" style="display: none;" class="mb-3">
                            <label for="topic_id" class="form-label">Select Topic</label>
                            <select class="form-select @error('topic_id') is-invalid @enderror" id="topic_id" name="topic_id">
                                <option value="">Select a topic...</option>
                                @foreach($topics as $topic)
                                    <option value="{{ $topic->id }}" {{ old('topic_id') == $topic->id ? 'selected' : '' }}>
                                        {{ $topic->name }} ({{ $topic->subscriptions->count() }} subscribers)
                                    </option>
                                @endforeach
                            </select>
                            @error('topic_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div id="subscription_selection" style="display: none;" class="mb-3">
                            <label for="subscription_id" class="form-label">Select Subscription</label>
                            <select class="form-select @error('subscription_id') is-invalid @enderror" id="subscription_id" name="subscription_id">
                                <option value="">Select a subscription...</option>
                                @foreach($subscriptions as $subscription)
                                    <option value="{{ $subscription->id }}" {{ old('subscription_id') == $subscription->id ? 'selected' : '' }}>
                                        #{{ $subscription->id }} - 
                                        @if($subscription->user)
                                            {{ $subscription->user->name ?? $subscription->user->email }}
                                        @else
                                            Anonymous ({{ $subscription->os }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('subscription_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="mb-3">Content</h6>
                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="content_type" id="content_type_custom" value="custom" {{ old('content_type', 'custom') == 'custom' ? 'checked' : '' }}>
                                <label class="form-check-label" for="content_type_custom">
                                    Custom Content
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="content_type" id="content_type_template" value="template" {{ old('content_type') == 'template' ? 'checked' : '' }}>
                                <label class="form-check-label" for="content_type_template">
                                    Use Template
                                </label>
                            </div>
                        </div>
                        
                        <div id="custom_content" class="mb-3">
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
                        
                        <div id="template_content" style="display: none;" class="mb-3">
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
                    
                    <div class="mb-4">
                        <h6 class="mb-3">Schedule</h6>
                        <div class="mb-3">
                            <label for="scheduled_at" class="form-label">Date and Time</label>
                            <input type="datetime-local" class="form-control @error('scheduled_at') is-invalid @enderror" id="scheduled_at" name="scheduled_at" value="{{ old('scheduled_at') }}">
                            @error('scheduled_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-calendar-plus"></i> Schedule Notification
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
        // Recipient type switching
        const recipientTypeAll = document.getElementById('recipient_type_all');
        const recipientTypeTopic = document.getElementById('recipient_type_topic');
        const recipientTypeSubscription = document.getElementById('recipient_type_subscription');
        const topicSelection = document.getElementById('topic_selection');
        const subscriptionSelection = document.getElementById('subscription_selection');
        
        function updateRecipientFields() {
            if (recipientTypeAll.checked) {
                topicSelection.style.display = 'none';
                subscriptionSelection.style.display = 'none';
            } else if (recipientTypeTopic.checked) {
                topicSelection.style.display = 'block';
                subscriptionSelection.style.display = 'none';
            } else if (recipientTypeSubscription.checked) {
                topicSelection.style.display = 'none';
                subscriptionSelection.style.display = 'block';
            }
        }
        
        recipientTypeAll.addEventListener('change', updateRecipientFields);
        recipientTypeTopic.addEventListener('change', updateRecipientFields);
        recipientTypeSubscription.addEventListener('change', updateRecipientFields);
        
        // Set initial state
        updateRecipientFields();
        
        // Content type switching
        const contentTypeCustom = document.getElementById('content_type_custom');
        const contentTypeTemplate = document.getElementById('content_type_template');
        const customContent = document.getElementById('custom_content');
        const templateContent = document.getElementById('template_content');
        
        function updateContentFields() {
            if (contentTypeCustom.checked) {
                customContent.style.display = 'block';
                templateContent.style.display = 'none';
            } else if (contentTypeTemplate.checked) {
                customContent.style.display = 'none';
                templateContent.style.display = 'block';
            }
        }
        
        contentTypeCustom.addEventListener('change', updateContentFields);
        contentTypeTemplate.addEventListener('change', updateContentFields);
        
        // Set initial state
        updateContentFields();
    });
</script>
@endpush