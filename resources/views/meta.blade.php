@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading">Welcome</div>

                <div class="panel-body">
                    Legend Meta Since Whispers of the Old Gods
                    {{ Html::ul($classes) }}
                    {{ Form::open(['method' => 'get']) }}
                    How many past days? {{ Form::selectRange('days', 1, 3, $days) }}
                    {{ Form::submit() }}
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
