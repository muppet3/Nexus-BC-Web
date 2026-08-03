<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-6">
        <div>
            <h2 class="text-xl font-bold text-white mb-1">Bienvenido de vuelta</h2>
            <p class="text-sm text-zinc-500 mb-6">Ingresa tus credenciales para acceder al sistema.</p>
        </div>

        <div>
            <label for="email" class="block text-xs font-bold text-zinc-400 uppercase tracking-wider mb-2">Correo Electrónico</label>
            <input wire:model="form.email" id="email" type="email" name="email" required autofocus autocomplete="username" 
                class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm py-3 px-4 transition-all shadow-inner placeholder-zinc-700" 
                placeholder="ejemplo@maramarlin.mx" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2 text-red-400 text-xs" />
        </div>

        <div>
            <div class="flex justify-between items-center mb-2">
                <label for="password" class="block text-xs font-bold text-zinc-400 uppercase tracking-wider">Contraseña</label>
                @if (Route::has('password.request'))
                    <a class="text-xs text-fuchsia-500 hover:text-fuchsia-400 transition-colors" href="{{ route('password.request') }}" wire:navigate>
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>
            
            <input wire:model="form.password" id="password" type="password" name="password" required autocomplete="current-password" 
                class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm py-3 px-4 transition-all shadow-inner placeholder-zinc-700" 
                placeholder="••••••••" />
            <x-input-error :messages="$errors->get('form.password')" class="mt-2 text-red-400 text-xs" />
        </div>

        <div class="flex items-center mt-4">
            <input wire:model="form.remember" id="remember" type="checkbox" class="rounded bg-[#0a0a0a] border-zinc-800 text-fuchsia-600 shadow-sm focus:ring-fuchsia-500 focus:ring-offset-zinc-950" name="remember">
            <label for="remember" class="ms-2 text-sm text-zinc-400 cursor-pointer hover:text-zinc-300">
                Mantener sesión iniciada
            </label>
        </div>

        <div class="mt-10">
            <button type="submit" class="w-full relative inline-flex justify-center items-center px-8 py-4 rounded-lg text-base font-black tracking-widest uppercase text-white bg-gradient-to-r from-cyan-500 via-blue-600 to-fuchsia-600 hover:from-cyan-400 hover:to-fuchsia-500 shadow-[0_0_25px_rgba(192,38,211,0.3)] transition-all transform hover:scale-[1.02] active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-zinc-950 focus:ring-fuchsia-500">
                <span wire:loading.remove wire:target="login">Acceder al Sistema</span>
                <span wire:loading wire:target="login" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Autenticando...
                </span>
            </button>
        </div>
    </form>
</div>