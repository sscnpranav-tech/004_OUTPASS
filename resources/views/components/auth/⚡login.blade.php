<?php
use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;

new #[Layout('layouts.guest')]
class extends Component {
    public $login = '';
    public $password = '';
    public $remember = true;
    public ?User $user = null;

    public function mount() {
        $this->user = User::first();
        if (!$this->user) { return $this->redirectRoute('auth.no-user', navigate: true); }
    }

    protected function rules() {
        return [
            'login' => 'required|string',
            'password' => 'required|min:6',
        ];
    }

    public function attemptLogin() {
    $this->validate();
    $value = strtolower(trim($this->login));
    $field = filter_var($value, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    $throttleKey = 'login.' . $value;

    if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
        $this->addError('login', 'Too many attempts. Try again in ' . RateLimiter::availableIn($throttleKey) . 's');
        return;
    }

    if (Auth::attempt([$field => $value, 'password' => $this->password], $this->remember)) {
        // Retrieve the authenticated user instance
        $user = Auth::user();

        // 6 Sigma Security Check: Verify active status
        if (!$user->is_active) {
            Auth::logout();
            $this->addError('login', 'This account is inactive. Please contact the Administrator.');
            return;
        }

        RateLimiter::clear($throttleKey);
        session()->regenerate();
        return $this->redirectIntended('/admin', navigate: true);
    }

    RateLimiter::hit($throttleKey);
    $this->addError('login', 'Invalid credentials');
}
}; ?>

<div class="min-h-screen flex items-center justify-center bg-base-200" wire:transition>
    <div class="w-full max-w-md">
        <x-card shadow class="bg-base-100 p-10">
            <x-header title="Login" subtitle="Sign in to your account" separator size="text-4xl" />
            <x-form wire:submit="attemptLogin">
                <x-input label="Username or Email" wire:model="login" icon="o-user" class="rounded-xl py-6" inline />
                <x-input label="Password" type="password" wire:model="password" icon="o-key" class="rounded-xl py-6" inline />
                <div class="flex flex-col gap-3 mt-4">
                    <x-checkbox label="Keep me logged in" wire:model="remember" right tight />
                    <x-button label="Login" type="submit" icon="o-paper-airplane" class="btn-primary w-full" wire:loading.attr="disabled" spinner="attemptLogin" />
                </div>
                @if ($errors->any())
                    <div class="mt-4 p-3 rounded-lg bg-error/10 text-error text-xs italic">
                        {{ $errors->first() }}
                    </div>
                @endif
            </x-form>
        </x-card>
    </div>
</div>
