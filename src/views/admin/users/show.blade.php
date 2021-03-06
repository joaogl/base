@extends('layouts.admin')

{{-- Page title --}}
@section('title')
    User details
    @parent
@endsection

{{-- page level styles --}}
@section('header_styles')

    <link href="{{ asset('assets/vendors/jasny-bootstrap/css/jasny-bootstrap.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/user_profile.css') }}" rel="stylesheet" type="text/css"/>

    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/datatables/extensions/bootstrap/dataTables.bootstrap.css') }}" />
    <link href="{{ asset('css/tables.css') }}" rel="stylesheet" type="text/css" />

@endsection

{{-- Page content --}}
@section('content')

    <div id="page-wrapper">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">List of users <small>- {{ $user->first_name }} {{ $user->last_name }}</small></h1>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <!-- /.row -->
        <div class="row">
            <div class="col-lg-12">
                <div id="tab1" class="tab-pane fade active in">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="panel">
                                <div class="panel-body">
                                    <div class="col-md-4" style="text-align:center">
                                        @if($user->pic)
                                            <img src="{!! url('/').'/uploads/users/'.$user->pic !!}" alt="profile pic" class="img-max img-rounded">
                                        @else
                                            <img src="http://placehold.it/200x200" alt="profile pic" class="img-rounded">
                                        @endif
                                    </div>

                                    <div class="col-md-8">
                                        <ul class="panel-body">

                                        <!-- Nav tabs -->
                                        <ul class="nav nav-tabs">
                                            <li class="active">
                                                <a href="#userdata" data-toggle="tab" aria-expanded="true">User information</a>
                                            </li>
                                            <li class="">
                                                <a href="#usergroups" data-toggle="tab" aria-expanded="false">User groups</a>
                                            </li>
                                            <li class="">
                                                <a href="#userlogs" data-toggle="tab" aria-expanded="false">User logs</a>
                                            </li>
                                            <li class="">
                                                <a href="#userips" data-toggle="tab" aria-expanded="false">User recent ip's</a>
                                            </li>
                                        </ul>

                                            <!-- Tab panes -->
                                            <div class="tab-content">
                                                <div class="tab-pane fade active in" id="userdata">
                                                    <table class="table table-striped" id="users">
                                                        <tr>
                                                            <td>First name</td>
                                                            <td>{{ $user->first_name }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Last name</td>
                                                            <td>{{ $user->last_name }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Username</td>
                                                            <td>{{ $user->username }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>User e-mail</td>
                                                            <td>{{ $user->email }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Birthday</td>
                                                            @if ($user->birthday != null)
                                                                <td>{{ $user->birthday->format('d-m-Y') }} ({{ $user->birthday->diff(Carbon\Carbon::now())->format('%y years old') }} - {{ Carbon\Carbon::createFromDate(Carbon\Carbon::now()->year, $user->birthday->month, $user->birthday->day)->diff(Carbon\Carbon::now())->format('%m months and %d days until next birthday') }})</td>
                                                            @else
                                                                <td></td>
                                                            @endif
                                                        </tr>
                                                        <tr>
                                                            <td>Status</td>
                                                            <td>
                                                                @if($user->deleted_at)
                                                                    Deleted
                                                                @else
                                                                    {{ $possibleStatus[$user->status] }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Last IP</td>
                                                            <td>
                                                                {!! $user->ip !!}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Created at</td>
                                                            <td>
                                                                {!! $user->created_at->diffForHumans() !!}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Last login</td>
                                                            <td>
                                                                {!! $user->last_login != null ? $user->last_login->diffForHumans() : 'Never' !!}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Description</td>
                                                            <td>
                                                                {!! $user->description !!}
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="tab-pane fade" id="usergroups">
                                                    <table class="table table-bordered" id="table3">
                                                        <thead>
                                                            <tr class="filters">
                                                                <th>Group name</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>

                                                            @foreach ($user->roles as $role)
                                                                <tr>
                                                                    <td>{!! $role->name !!}</td>
                                                                </tr>
                                                            @endforeach

                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="tab-pane fade" id="userlogs">
                                                    <table class="table table-bordered" id="table">
                                                        <thead>
                                                        <tr class="filters">
                                                            <th>IP</th>
                                                            <th>Message</th>
                                                            <th>Who acted</th>
                                                            <th>Upon whom</th>
                                                            <th>Date</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>

                                                        @foreach ($logs as $log)
                                                            <tr>
                                                                <td>{!! $log->ip !!}</td>
                                                                <td>{!! $log->log !!}</td>
                                                                <td>{!! $log->created_by !!}</td>
                                                                <td>{!! $log->target !!}</td>
                                                                <td>{!! $log->created_at !!}</td>
                                                            </tr>
                                                        @endforeach

                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="tab-pane fade" id="userips">
                                                    <table class="table table-bordered" id="table2">
                                                        <thead>
                                                        <tr class="filters">
                                                            <th>Counter</th>
                                                            <th>IP</th>
                                                            <th>Date</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>

                                                        @foreach ($ips as $log)
                                                            <tr>
                                                                <td>{!! $log->counter !!}</td>
                                                                <td>{!! $log->ip !!}</td>
                                                                <td>{!! $log->created_at !!}</td>
                                                            </tr>
                                                        @endforeach

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- page level scripts --}}
@section('footer_scripts')

    <!-- Bootstrap WYSIHTML5 -->
    <script  src="{{ asset('assets/vendors/jasny-bootstrap/js/jasny-bootstrap.js') }}" type="text/javascript"></script>

    <!-- Bootstrap DataTables -->
    <script type="text/javascript" src="{{ asset('assets/vendors/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendors/datatables/extensions/bootstrap/dataTables.bootstrap.js') }}"></script>

    <div class="modal fade" id="delete_confirm" tabindex="-1" role="dialog" aria-labelledby="user_delete_confirm_title" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content"></div>
        </div>
    </div>

    <script>
        $(function () {
            $('body').on('hidden.bs.modal', '.modal', function () {
                $(this).removeData('bs.modal');
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#table').DataTable({
                "order": [[ 4, "desc" ]]
            });
        });

        $(document).ready(function() {
            $('#table2').DataTable({
                "order": [[ 1, "desc" ]]
            });
        });

        $(document).ready(function() {
            $('#table3').DataTable({
                "order": [[ 0, "desc" ]]
            });
        });
    </script>

@endsection