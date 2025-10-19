<div>
@auth
  <span>Вы вошли как: {{ auth()->user()->name }} ({{ auth()->user()->email }})</span>
@endauth
    
@guest
<form wire:submit.prevent="login">
    <input type="email" wire:model="email" placeholder="Email">
    <input type="password" wire:model="password" placeholder="Password">
      <label>
        <input type="checkbox" wire:model="remember"> Запомнить меня
    </label>
    <button type="submit">Login</button>
    @error('email')
    <div><span>{{ $message }}</span></div>
    @enderror
</form>
@endguest


</div>