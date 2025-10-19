{{-- без параметра зашита  статья topMenu --}}

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Лого</a>

        <!-- Кнопка-бургер -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainMenu" aria-controls="mainMenu" aria-expanded="false" aria-label="Переключить меню">
      <span class="navbar-toggler-icon"></span>
    </button>

        <div class="collapse navbar-collapse" id="mainMenu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

{{-- вызов хелпера --}}            
  @foreach( TreeHelper::getChildrenByName( $nameArt ?? 'topMenu') as $child)
            <li class="nav-item">
                <a class="nav-link active" href="/{{ $child['name'] }}">{{$child['title'] }}</a>
            </li>
@endforeach
            </ul>
        </div>
    </div>
</nav>