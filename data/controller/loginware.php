<?php
namespace   MagicProControllers;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;   // 👈 вот это нужно

class loginware  extends Component
{
    public string $email = '';
    public string $password = '';
    public string $message = '';
    public bool $remember = false;

    public function render()
    {
        return view('magic::loginware', []);
    }

    public function login()
    {
        if (Auth::attempt([
                'email' => $this->email, 
                'password' => $this->password,
                 $this->remember
                ])){
            session()->regenerate();
            $this->loggedIn = true;
        }
        $this->addError('email', 'Invalid credentials.');
    } 
}