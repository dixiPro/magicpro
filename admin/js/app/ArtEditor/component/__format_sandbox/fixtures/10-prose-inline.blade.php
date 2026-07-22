<p>Цена: {{ $price }} руб, скидка {{ $sale }}%</p>
@if($ok) Готово @endif
@foreach($xs as $x) <span>{{ $x }}</span> @endforeach
