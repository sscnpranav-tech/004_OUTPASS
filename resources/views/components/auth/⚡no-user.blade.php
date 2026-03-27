<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.guest')] class extends Component
{
    public $name, $username, $email, $password;
    public function mount(){
        if(\App\Models\User::count() > 0){
            return $this->redirectRoute('auth.login');
        }
    }
    public function register()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);
        $user = \App\Models\User::create([
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'password' => bcrypt($this->password),
        ]);
        \Illuminate\Support\Facades\Auth::login($user);
        return redirect()->route('admin.dashboard');
    }
};
?>

<div class="min-h-screen flex items-center justify-center bg-base-200">
    <div class="w-full max-w-md">
        <x-card shadow class="bg-base-100 p-10">
            <x-header title="Register User" subtitle="To Continue, At Least One User Must Be Registered" separator size="text-4xl" />
            <x-form wire:submit="register">
                <x-errors title="Oops!" description="Please, fix them." icon="o-face-frown" />
                <x-input label="Name" wire:model="name" icon="o-user" class="rounded-xl py-6" />
                <x-input label="Username" wire:model="username" icon="o-users"   class="rounded-xl py-6" />
                <x-input label="Email" type="email" wire:model="email" icon="o-envelope" class="rounded-xl py-6"  />
                <x-input label="Password" type="password" wire:model="password" icon="o-key" class="rounded-xl py-6"  />
                <x-button label="Register" type="submit" icon="o-paper-airplane" class="btn-primary w-full" spinner="register" />
            </x-form>
        </x-card>
    </div>
</div>
