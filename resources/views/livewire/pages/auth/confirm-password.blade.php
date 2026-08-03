<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-6 text-sm text-zinc-400 leading-relaxed">
        {{ __('Esta es un área segura de la aplicación. Por favor, confirma tu contraseña antes de continuar.') }}
    </div>

    <form wire:submit="confirmPassword" class="space-y-6">
        <div>
            <label for="password" class="block text-xs font-bold text-zinc-400 uppercase tracking-wider mb-2">Contraseña</label>
            <input wire:model="password" id="password" type="password" name="password" required autocomplete="current-password" 
                class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm py-3 px-4 transition-all shadow-inner" 
                placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-400 text-xs" />
        </div>

        <div class="flex justify-end mt-8">
            <button type="submit" class="relative inline-flex justify-center items-center px-6 py-2.5 rounded-lg text-sm font-bold text-white bg-gradient-to-r from-cyan-500 via-blue-600 to-fuchsia-600 hover:from-cyan-400 hover:to-fuchsia-500 shadow-[0_0_20px_rgba(192,38,211,0.2)] transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                Confirmar
            </button>
        </div>
    </form>
</div>