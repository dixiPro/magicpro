<div>
@include('x', ['label' => trans('auth.login') . ' )', 'cond' => ($a ? 'y' : 'n')])
@include('y', [
'msg' => "text with ) paren and 'quote'",
'n' => count($items),
])
</div>
