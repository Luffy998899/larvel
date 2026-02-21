@extends('layouts.app')

@section('content')
<section class="bg-slate-900 border border-slate-800 rounded-xl p-5 mb-6">
    <h2 class="font-semibold mb-3">Create Redeem Code</h2>
    <form action="{{ route('admin.redeem-codes.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-3">
        @csrf
        <input name="code" placeholder="CODE2026" class="rounded bg-slate-800 border border-slate-700 px-3 py-2" required>
        <input name="reward_value" type="number" min="1" placeholder="Reward" class="rounded bg-slate-800 border border-slate-700 px-3 py-2" required>
        <input name="max_uses" type="number" min="1" placeholder="Max uses" class="rounded bg-slate-800 border border-slate-700 px-3 py-2" required>
        <input name="per_user_limit" type="number" min="1" placeholder="Per user" class="rounded bg-slate-800 border border-slate-700 px-3 py-2" required>
        <input name="expires_at" type="datetime-local" class="rounded bg-slate-800 border border-slate-700 px-3 py-2">
        <select name="is_active" class="rounded bg-slate-800 border border-slate-700 px-3 py-2"><option value="1">Active</option><option value="0">Inactive</option></select>
        <div class="md:col-span-3"><button class="rounded bg-indigo-600 px-4 py-2">Create</button></div>
    </form>
</section>

<section class="bg-slate-900 border border-slate-800 rounded-xl p-5">
    <h2 class="font-semibold mb-3">Existing Codes</h2>
    <div class="space-y-3">
        @foreach($codes as $code)
            <div class="bg-slate-800 border border-slate-700 rounded p-3 space-y-2">
                <form method="POST" action="{{ route('admin.redeem-codes.update', $code) }}" class="grid grid-cols-1 md:grid-cols-6 gap-2">
                    @csrf @method('PUT')
                    <input value="{{ $code->code }}" disabled class="rounded bg-slate-700 border border-slate-600 px-2 py-1">
                    <input name="reward_value" type="number" min="1" value="{{ $code->reward_value }}" class="rounded bg-slate-700 border border-slate-600 px-2 py-1">
                    <input name="max_uses" type="number" min="1" value="{{ $code->max_uses }}" class="rounded bg-slate-700 border border-slate-600 px-2 py-1">
                    <input name="per_user_limit" type="number" min="1" value="{{ $code->per_user_limit }}" class="rounded bg-slate-700 border border-slate-600 px-2 py-1">
                    <input name="expires_at" type="datetime-local" value="{{ optional($code->expires_at)->format('Y-m-d\TH:i') }}" class="rounded bg-slate-700 border border-slate-600 px-2 py-1">
                    <select name="is_active" class="rounded bg-slate-700 border border-slate-600 px-2 py-1">
                        <option value="1" @selected($code->is_active)>Active</option>
                        <option value="0" @selected(!$code->is_active)>Inactive</option>
                    </select>
                    <div class="md:col-span-6 flex gap-2">
                        <button class="px-3 py-1 rounded bg-indigo-600">Update</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('admin.redeem-codes.destroy', $code) }}">
                    @csrf @method('DELETE')
                    <button class="px-3 py-1 rounded bg-rose-700">Delete</button>
                </form>
                </div>
        @endforeach
    </div>
</section>
@endsection
