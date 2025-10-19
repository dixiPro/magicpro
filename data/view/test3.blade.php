@extends('magic::root')

@section('body')
Подвал
<p> result={{ $Var }}</p>

<pre>{{ print_r($Env, true) }}</pre>
<div class="col-3">
</div>
<h2>Текст2</h2>

@endsection