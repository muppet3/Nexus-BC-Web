<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-zinc-100 antialiased bg-zinc-950 selection:bg-fuchsia-500 selection:text-white relative z-0">
        
        <div class="fixed top-0 inset-x-0 h-[500px] bg-gradient-to-b from-fuchsia-600/10 via-blue-900/5 to-transparent pointer-events-none -z-10 blur-3xl"></div>

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            
            <div class="mb-8 transform hover:scale-105 transition-transform duration-300 pointer-events-none w-full flex justify-center">
                <img src="{{ asset('img/logo_mara.webp') }}" alt="Mara Marlin Logo" class="w-56 sm:w-64 h-auto object-contain filter drop-shadow-[0_0_15px_rgba(59,130,246,0.3)]">
            </div>

            <div class="w-full sm:max-w-md px-8 py-10 bg-[#141414]/80 backdrop-blur-md border border-zinc-800/80 shadow-[0_0_50px_rgba(0,0,0,0.5)] overflow-hidden sm:rounded-2xl relative group">
                <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-fuchsia-500/50 to-transparent opacity-50 group-hover:opacity-100 transition-opacity duration-500"></div>
                
                {{ $slot }}
            </div>
            
            </div>
    </body>
</html>