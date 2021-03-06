@extends('layouts.admin')

{{-- Page title --}}
@section('title')
    Groups
    @parent
@endsection

{{-- Page content --}}
@section('content')

    <div id="page-wrapper">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Create group</h1>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <!-- /.row -->
        <div class="row">

            {!! Form::model(null, array('route' => array('create.group'))) !!}

            @include('admin.groups.partials.form', ['submitButton' => 'Create group'])

            {!! Form::close() !!}

        </div>

    </div>

@endsection
