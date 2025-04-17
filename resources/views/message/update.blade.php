@extends("notify::app")

@section("body");
<div class="row">
<div class="col-lg-12">
    <div class="card">
        <div class="card-header">
            <strong>Update Message</strong> No. {{ $message->id }}
        </div>
        <div class="card-body card-block">
            <form action="{{ route("message.update", $message->id) }}" method="post" class="form-horizontal", enctype="multipart/form-data">
                @if($errors->any())
                <div class="alert alert-warning">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                </div>
                @endif
                @csrf
                @method("PUT")
                <div class="row form-group">
                    <div class="col col-md-3">
                        <label for="title" class=" form-control-label">Title</label>
                    </div>
                    <div class="col-12 col-md-9">
                        <input value="{{ old("title", $message->title) }}" type="text" id="title" name="title" placeholder="Title..." class="form-control">
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col col-md-3">
                        <label for="body" class=" form-control-label">Body</label>
                    </div>
                    <div class="col-12 col-md-9">
                        <textarea name="body" id="body" rows="3" placeholder="Body..." class="form-control">{{ old("body", $message->body) }}</textarea>
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col col-md-3">
                        <label for="url" class=" form-control-label">URL (Optional)</label>
                    </div>
                    <div class="col-12 col-md-9">
                        <input value="{{ old("url", $message->url) }}" type="text" id="url" name="url" placeholder="Url..." class="form-control">
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col col-md-3">
                        <label for="icon" class=" form-control-label">Icon (Optional)</label>
                    </div>
                    <div class="col-12 col-md-2">
                        <img src="{{ Storage::url(path: $message->icon_path) }}" alt="Icon">
                    </div>
                    <div class="col-12 col-md-7">
                        <input type="file" id="icon" name="icon" class="form-control">
                    </div>
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