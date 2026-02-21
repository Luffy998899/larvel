@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-slate-900 rounded-xl p-6 border border-slate-800">
    <h2 class="text-lg font-semibold mb-4">Register</h2>
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block mb-1 text-sm">Name</label>
            <input name="name" type="text" required class="w-full rounded bg-slate-800 border border-slate-700 px-3 py-2" />
        </div>
        <div>
            <label class="block mb-1 text-sm">Email</label>
            <input name="email" type="email" required class="w-full rounded bg-slate-800 border border-slate-700 px-3 py-2" />
        </div>
        <div>
            <label class="block mb-1 text-sm">Password</label>
            <input name="password" type="password" required class="w-full rounded bg-slate-800 border border-slate-700 px-3 py-2" />
        </div>
        <div>
            <label class="block mb-1 text-sm">Confirm Password</label>
            <input name="password_confirmation" type="password" required class="w-full rounded bg-slate-800 border border-slate-700 px-3 py-2" />
        </div>
        <button class="w-full rounded bg-indigo-600 py-2 font-medium">Create account</button>
        <a href="{{ route('login.form') }}" class="text-sm text-indigo-300">Already have an account</a>
    </form>
</div>
@endsection
