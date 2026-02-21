@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <section class="bg-slate-900 rounded-xl p-5 border border-slate-800">
        <h2 class="font-semibold mb-3">Credits</h2>
        <p class="text-3xl font-bold">{{ $credit->balance ?? 0 }}</p>
        <p class="text-sm text-slate-400 mt-2">Credits per ad: {{ $credits_per_ad }}</p>
        <p class="text-sm text-slate-400">Max ads/day: {{ $max_ads_per_day }}</p>
        <p class="text-sm text-slate-400">Today watched: {{ $today_ad_count }}</p>
        <form action="{{ route('dashboard.daily-login') }}" method="POST" class="mt-4">
            @csrf
            <button class="w-full rounded bg-emerald-600 py-2">Claim daily login (+{{ $daily_login_credits }})</button>
        </form>
    </section>

    <section class="bg-slate-900 rounded-xl p-5 border border-slate-800">
        <h2 class="font-semibold mb-3">Ad Reward</h2>
        <p class="text-sm text-slate-400 mb-4">Use your ad provider URL and ensure callback hits /api/ad/reward.</p>
        <a href="#" class="block text-center rounded bg-indigo-600 py-2">Watch Ad</a>

        <h3 class="font-semibold mt-6 mb-2">Redeem Code</h3>
        <form action="{{ route('redeem') }}" method="POST" class="flex gap-2">
            @csrf
            <input name="code" type="text" placeholder="Enter code" class="flex-1 rounded bg-slate-800 border border-slate-700 px-3 py-2"/>
            <button class="rounded bg-indigo-600 px-4">Redeem</button>
        </form>
    </section>

    <section class="bg-slate-900 rounded-xl p-5 border border-slate-800">
        <h2 class="font-semibold mb-3">Free Server</h2>
        <form action="{{ route('dashboard.claim-server') }}" method="POST">
            @csrf
            <button class="w-full rounded bg-indigo-600 py-2">Claim Free Server</button>
        </form>
    </section>
</div>

<section class="mt-6 bg-slate-900 rounded-xl p-5 border border-slate-800">
    <h2 class="font-semibold mb-3">Your Servers</h2>
    <div class="space-y-3">
        @forelse($servers as $server)
            <article class="p-4 rounded bg-slate-800/80 border border-slate-700">
                <div class="flex flex-wrap justify-between gap-2">
                    <p><span class="text-slate-400">Status:</span> {{ strtoupper($server->status) }}</p>
                    <p><span class="text-slate-400">Daily Cost:</span> {{ $server->cost_per_day }}</p>
                    <p><span class="text-slate-400">Next Billing:</span> {{ $server->next_billing_at?->diffForHumans() }}</p>
                </div>
                <div class="mt-2 text-sm text-slate-300">RAM {{ $server->ram_allocated }} MB · CPU {{ $server->cpu_allocated }}% · Disk {{ $server->disk_allocated }} MB</div>
            </article>
        @empty
            <p class="text-slate-400">No servers yet.</p>
        @endforelse
    </div>
</section>
@endsection
