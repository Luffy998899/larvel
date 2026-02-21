@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-slate-900 rounded-xl p-6 border border-slate-800">
    <h2 class="text-lg font-semibold mb-4">Login</h2>
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block mb-1 text-sm">Email</label>
            <input name="email" type="email" required class="w-full rounded bg-slate-800 border border-slate-700 px-3 py-2" />
        </div>
        <div>
            <label class="block mb-1 text-sm">Password</label>
            <input name="password" type="password" required class="w-full rounded bg-slate-800 border border-slate-700 px-3 py-2" />
        </div>
        <button class="w-full rounded bg-indigo-600 py-2 font-medium">Login</button>
        <a href="{{ route('register.form') }}" class="text-sm text-indigo-300">Create account</a>
    </form>
</div>
@endsection
