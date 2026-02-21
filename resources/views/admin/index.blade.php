@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">Active Servers: <strong>{{ $activeCount }}</strong></div>
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">Suspended Servers: <strong>{{ $suspendedCount }}</strong></div>
    <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">Daily Revenue Estimate: <strong>{{ $dailyRevenueEstimate }}</strong></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <section class="bg-slate-900 border border-slate-800 rounded-xl p-5">
        <h2 class="font-semibold mb-3">Adjust User Credits</h2>
        <form action="{{ route('admin.credits.adjust') }}" method="POST" class="grid grid-cols-1 gap-3">
            @csrf
            <input name="user_id" type="number" placeholder="User ID" class="rounded bg-slate-800 border border-slate-700 px-3 py-2" required>
            <input name="amount" type="number" placeholder="Amount (+/-)" class="rounded bg-slate-800 border border-slate-700 px-3 py-2" required>
            <input name="description" type="text" placeholder="Description" class="rounded bg-slate-800 border border-slate-700 px-3 py-2">
            <button class="rounded bg-indigo-600 py-2">Apply</button>
        </form>
    </section>

    <section class="bg-slate-900 border border-slate-800 rounded-xl p-5">
        <h2 class="font-semibold mb-3">Quick Links</h2>
        <div class="flex gap-2">
            <a class="px-3 py-2 rounded bg-slate-800" href="{{ route('admin.settings') }}">System Settings</a>
            <a class="px-3 py-2 rounded bg-slate-800" href="{{ route('admin.redeem-codes') }}">Redeem Codes</a>
        </div>
        <p class="text-sm text-slate-400 mt-4">Ad rewards today: {{ $adStatsToday }}</p>
    </section>
</div>

<section class="mt-6 bg-slate-900 border border-slate-800 rounded-xl p-5">
    <h2 class="font-semibold mb-3">Servers</h2>
    <div class="space-y-3">
        @foreach($servers as $server)
            <div class="bg-slate-800/80 rounded p-3 border border-slate-700 flex flex-wrap gap-2 justify-between">
                <div>#{{ $server->id }} · User {{ $server->user_id }} · {{ $server->status }}</div>
                <div class="flex gap-2">
                    <form action="{{ route('admin.servers.suspend', $server) }}" method="POST">@csrf<button class="px-3 py-1 rounded bg-amber-600">Suspend</button></form>
                    <form action="{{ route('admin.servers.delete', $server) }}" method="POST">@csrf @method('DELETE')<button class="px-3 py-1 rounded bg-rose-700">Delete</button></form>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endsection
