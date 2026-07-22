@if(!$res['status'] ?? false)

  @include('magic::authForm', [
    'email' => $params['email'] ?? '',
  ])

  {{ $params['errorMsg'] ?? 'Неизвестная ошибка' }}

@else
  Письмо выслано проверьте
@endif
