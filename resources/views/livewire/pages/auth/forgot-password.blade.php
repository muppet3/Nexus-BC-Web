<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));
            return;
        }

        $this->reset('email');
        session()->flash('status', __($status));
    }
}; ?>

<div>
    <div class="mb-6 text-sm text-zinc-400 leading-relaxed">
        {{ __('¿Olvidaste tu contraseña? No hay problema. Ingresa tu correo electrónico y te enviaremos un enlace para que puedas elegir una nueva.') }}
    </div>

    <x-auth-session-status class="mb-4 text-emerald-400" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="space-y-6">
        <div>
            <label for="email" class="block text-xs font-bold text-zinc-400 uppercase tracking-wider mb-2">Correo Electrónico</label>
            <input wire:model="email" id="email" type="email" name="email" required autofocus 
                class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm py-3 px-4 transition-all shadow-inner" 
                placeholder="ejemplo@aquaworld.com.mx" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-400 text-xs" />
        </div>

        <div class="flex items-center justify-between mt-8">
            <a href="{{ route('login') }}" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors" wire:navigate>
                ← Volver al login
            </a>
            
            <button type="submit" class="relative inline-flex justify-center items-center px-6 py-2.5 rounded-lg text-sm font-bold text-white bg-gradient-to-r from-cyan-500 via-blue-600 to-fuchsia-600 hover:from-cyan-400 hover:to-fuchsia-500 shadow-[0_0_20px_rgba(192,38,211,0.2)] transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                <span wire:loading.remove wire:target="sendPasswordResetLink">Enviar Enlace</span>
                <span wire:loading wire:target="sendPasswordResetLink" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Enviando...
                </span>
            </button>
        </div>
    </form>
</div>