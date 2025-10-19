@extends('magic::root')

@section('body')
    @if($status === 'ok')
        <div class="alert alert-success">{{ $msg }}</div>
    @else
        <div class="alert alert-danger">{{ $msg }}</div>
    @endif
@endsection