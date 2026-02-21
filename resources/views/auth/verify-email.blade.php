@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto bg-slate-900 rounded-xl p-6 border border-slate-800">
    <h2 class="text-lg font-semibold mb-4">Verify your email</h2>
    <p class="text-slate-300 mb-4">Please verify your email address before accessing the dashboard.</p>
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button class="rounded bg-indigo-600 px-4 py-2">Resend Verification Email</button>
    </form>
</div>
@endsection
