@extends("notify::app")

@section("body");
<div class="row">
<div class="col-lg-12">
    <div class="card">
        <div class="card-header">
            <strong>Add new message template</strong>
        </div>
        <div class="card-body card-block">
            <form action="{{ route("topic.store") }}" method="post" class="form-horizontal">
                @if($errors->any())
                <div class="alert alert-warning">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                </div>
                @endif
                @csrf
                <div class="row form-group">
                    <div class="col col-md-3">
                        <label for="name" class=" form-control-label">Name</label>
                    </div>
                    <div class="col-12 col-md-9">
                        <input value="{{ old("name") }}" type="text" id="name" name="name" placeholder="Name..." class="form-control">
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col col-md-3">
                        <label for="slug" class=" form-control-label">Slug</label>
                    </div>
                    <div class="col-12 col-md-9">
                        <input value="{{ old("slug") }}" type="text" id="slug" name="slug" placeholder="Slug..." class="form-control">
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col col-md-3">
                        <label for="subscriptions" class=" form-control-label">Subscriptions (Optional)</label>
                    </div>
                    <div class="col-12 col-md-9">
                        <select multiple name="subscriptions[]" id="subscriptions" class="form-group w-100 p-2" >
                            @foreach($subscriptions as $subscription)
                                <option {{ in_array($subscription->id, old('subscriptions', [])) ? 'selected' : '' }} value="{{ $subscription->id }}">{{ $subscription->id }} - {{ $subscription->user->email ?? "No User Attached" }}</option>
                            @endforeach
                        </select>
                    </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa fa-dot-circle-o"></i> Submit
            </button>
        </div>
    </div>
</div>
</div>
@endsection