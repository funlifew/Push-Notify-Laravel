@extends("notify::app")

@section("body")
<div class="row">
    <div class="col-md-12">
                                <!-- DATA TABLE -->
            <h3 class="title-5 m-b-35">Subscriptions</h3>
            <div class="table-data__tool center">
                <div class="table-data__tool-right right">
                    <a href="{{ route("notify.sendAll") }}" class="au-btn au-btn-icon au-btn--green au-btn--small">
                        <i class="zmdi zmdi-plus"></i>Notify All</a>
                    </div>
                </div>
            </div>
            <div class="table-responsive table-responsive-data2">
                <table class="table table-data2 text-center">
                    <thead class="text-center">
                        <tr>
                            <th>#</th>
                            <th>User Belongs to</th>
                            <th>Topics</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions as $subscription)
                        <tr class="tr-shadow">
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $subscription->user->name ?? "No Users" }}</td>
                        <td>
                        {{ $subscription->topics->isNotEmpty() ? $subscription->topics->pluck('name')->join(' - ') : 'No Topic Joined' }}
                        <td>
                                <div class="table-data-feature">
                                    <a href="{{ route("send", $subscription->id) }}" class="item" data-toggle="tooltip" data-placement="top" title="" data-original-title="Send Notification">
                                        <i class="zmdi zmdi-mail-send"></i>
                                    </a>
                                    <form id="subscription-{{ $subscription->id }}" action="{{ route("notify.delete", $subscription->id) }}" method="POST">
                                        @csrf
                                        @method("DELETE")
                                    </form>
                                    <button onclick="if(confirm('Do you want to delete this subscriber?')) document.querySelector('#subscription-{{ $subscription->id }}').submit()" class="item" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete">
                                        <i class="zmdi zmdi-delete"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    @empty
                    <h3>The Subscriptions are empty</h3>
                    @endforelse
                </table>
                {{ $subscriptions->links() }}
            </div>
            <!-- END DATA TABLE -->
        </div>
        <div class="col-12-md mt-4">
        <h3 class="title-5 m-b-35">Notifications</h3>
            <div class="table-responsive table-responsive-data2">
                <table class="table table-data2 text-center">
                    <thead class="text-center">
                        <tr>
                            <th>#</th>
                            <th>Subscription Belongs To</th>
                            <th>Message Belongs To</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notifications as $notification)
                        <tr class="tr-shadow">
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $notification->subscription->id }}</td>
                        <td>{{ $notification->message->id ?? "None" }}</td>
                        <td>
                                @if($notification->status)
                                    <span class="text-primary">Sent</span>
                                @else
                                    <span class="text-danger">Failed</span>
                                @endif
                        </td>
                        </tr>
                    </tbody>
                    @empty
                    <h3>The Notifications are empty</h3>
                    @endforelse
                </table>
                {{ $notifications->links() }}
            </div>
            <!-- END DATA TABLE -->
        </div>

        <div class="col-12-md mt-4">
        <div class="table-data__tool center">
                <div class="table-data__tool-right right">
                    <a href="{{ route("topic.create") }}" class="au-btn au-btn-icon au-btn--green au-btn--small">
                        <i class="zmdi zmdi-plus"></i>Add New Topic</a>
                    </div>
                </div>
            </div>
        <h3 class="title-5 m-b-35">Topics</h3>
            <div class="table-responsive table-responsive-data2">
                <table class="table table-data2 text-center">
                    <thead class="text-center">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Users Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topics as $topic)
                        <tr class="tr-shadow">
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $topic->name }}</td>
                        <td>{{ $topic->slug }}</td>
                        <td>{{ $topic->subscriptions->count() }}</td>
                        <td>
                            <div class="table-data-feature text-center">
                                    <a href="{{ route("topic.send", $topic->id) }}" class="item" data-toggle="tooltip" data-placement="top" title="" data-original-title="Send Notification">
                                        <i class="zmdi zmdi-mail-send"></i>
                                    </a>
                                    <a href="{{ route("topic.edit", $topic->id) }}" class="item" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit Topic">
                                        <i class="zmdi zmdi-edit"></i>
                                    </a>
                                    <form id="topic-{{ $topic->id }}" action="{{ route("topic.destroy", $topic->id) }}" method="POST">
                                        @csrf
                                        @method("DELETE")
                                    </form>
                                    <button onclick="if(confirm('Do you want to delete this topic?')) document.querySelector('#topic-{{ $topic->id }}').submit()" class="item" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete">
                                        <i class="zmdi zmdi-delete"></i>
                                    </button>
                            </div>
                        </td>
                        </tr>
                    </tbody>
                    @empty
                    <h3>The Topics are empty</h3>
                    @endforelse
                </table>
                {{ $topics->links() }}
            </div>
            <!-- END DATA TABLE -->
            <div class="col-12-md mt-4">
        <h3 class="title-5 m-b-35">Message Templates</h3>
        <div class="table-data__tool center">
                <div class="table-data__tool-right right">
                    <a href="{{ route("message.create") }}" class="au-btn au-btn-icon au-btn--green au-btn--small">
                        <i class="zmdi zmdi-plus"></i>Add new template</a>
                    </div>
                </div>
            </div>
            <div class="table-responsive table-responsive-data2">
                <table class="table table-data2 text-center">
                    <thead class="text-center">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Body</th>
                            <th>Url</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($messages as $message)
                        <tr class="tr-shadow">
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $message->title }}</td>
                        <td>{{ $message->body }}</td>
                        <td>{{ $message->url ?? "None" }}</td>
                        <td>
                            <div class="table-data-feature text-center">
                                    <a href="{{ route("message.edit", $message->id) }}" class="item" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit Message Template">
                                        <i class="zmdi zmdi-edit"></i>
                                    </a>
                                    <form id="message-{{ $message->id }}" action="{{ route("message.destroy", $message->id) }}" method="POST">
                                        @csrf
                                        @method("DELETE")
                                    </form>
                                    <button onclick="if(confirm('Do you want to delete this message?')) document.querySelector('#message-{{ $message->id }}').submit()" class="item" data-toggle="tooltip" data-placement="top" title="" data-original-title="Delete">
                                        <i class="zmdi zmdi-delete"></i>
                                    </button>
                            </div>
                        </td>
                        </tr>
                    </tbody>
                    @empty
                    <h3>The Messages are empty</h3>
                    @endforelse
                </table>
                {{ $messages->links() }}
            </div>
        </div>
        </div>
@endsection
