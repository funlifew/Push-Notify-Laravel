@extends("notify::app")

@section("body");
<div class="row">
<div class="col-lg-12">
    <div class="card">
        <div class="card-header">
            <strong>Add new message template</strong>
        </div>
        <div class="card-body card-block">
            <form action="{{ route("message.store") }}" method="post" class="form-horizontal" enctype="multipart/form-data">
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
                        <label for="title" class=" form-control-label">Title</label>
                    </div>
                    <div class="col-12 col-md-9">
                        <input value="{{ old("title") }}" type="text" id="title" name="title" placeholder="Title..." class="form-control">
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col col-md-3">
                        <label for="body" class=" form-control-label">Body</label>
                    </div>
                    <div class="col-12 col-md-9">
                        <textarea name="body" id="body" rows="3" placeholder="Body..." class="form-control">{{ old("body") }}</textarea>
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col col-md-3">
                        <label for="url" class=" form-control-label">URL (Optional)</label>
                    </div>
                    <div class="col-12 col-md-9">
                        <input value="{{ old("url") }}" type="text" id="url" name="url" placeholder="Url..." class="form-control">
                    </div>
                </div>
                <div class="row form-group">
                    <div class="col col-md-3">
                        <label for="icon" class=" form-control-label">Icon (Optional)</label>
                    </div>
                    <div class="col-12 col-md-9">
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