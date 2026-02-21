@extends('layouts.app')

@section('content')
<section class="bg-slate-900 border border-slate-800 rounded-xl p-5 max-w-4xl">
    <h2 class="font-semibold mb-4">System Settings</h2>
    <form action="{{ route('admin.settings.update') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @csrf
        @foreach($settings as $key => $value)
            <label class="text-sm">
                <span class="block text-slate-300 mb-1">{{ $key }}</span>
                <input type="number" min="0" name="settings[{{ $key }}]" value="{{ $value }}" class="w-full rounded bg-slate-800 border border-slate-700 px-3 py-2">
            </label>
        @endforeach
        <div class="md:col-span-2">
            <button class="rounded bg-indigo-600 px-4 py-2">Save Settings</button>
        </div>
    </form>
</section>
@endsection
