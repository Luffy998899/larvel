<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Revactyl Host') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-6">
        <header class="flex justify-between items-center mb-6">
            <h1 class="text-xl font-semibold">Revactyl Free Host</h1>
            <nav class="flex gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded bg-slate-800">Dashboard</a>
                    @if(auth()->user()->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 rounded bg-slate-800">Admin</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="px-3 py-2 rounded bg-rose-700">Logout</button>
                    </form>
                @endauth
            </nav>
        </header>

        @if(session('status'))
            <div class="mb-4 bg-emerald-800/40 border border-emerald-500 px-4 py-3 rounded">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="mb-4 bg-rose-800/40 border border-rose-500 px-4 py-3 rounded">
                <ul class="list-disc ml-6">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $slot ?? '' }}
        @yield('content')
    </div>
</body>
</html>
