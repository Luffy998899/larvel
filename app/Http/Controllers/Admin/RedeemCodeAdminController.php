<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RedeemCode;
use Illuminate\Http\Request;

class RedeemCodeAdminController extends Controller
{
    public function index()
    {
        return view('admin.redeem-codes', [
            'codes' => RedeemCode::query()->orderByDesc('id')->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'unique:redeem_codes,code'],
            'reward_value' => ['required', 'integer', 'min:1'],
            'max_uses' => ['required', 'integer', 'min:1'],
            'per_user_limit' => ['required', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        RedeemCode::query()->create([
            'code' => strtoupper((string) $data['code']),
            'reward_type' => 'credits',
            'reward_value' => (int) $data['reward_value'],
            'max_uses' => (int) $data['max_uses'],
            'used_count' => 0,
            'per_user_limit' => (int) $data['per_user_limit'],
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'created_by_admin' => $request->user()->id,
            'created_at' => now(),
        ]);

        return redirect()->route('admin.redeem-codes')->with('status', 'Code created.');
    }

    public function update(Request $request, RedeemCode $redeemCode)
    {
        $data = $request->validate([
            'reward_value' => ['required', 'integer', 'min:1'],
            'max_uses' => ['required', 'integer', 'min:1'],
            'per_user_limit' => ['required', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
        ]);

        $redeemCode->update($data);

        return redirect()->route('admin.redeem-codes')->with('status', 'Code updated.');
    }

    public function destroy(RedeemCode $redeemCode)
    {
        $redeemCode->delete();

        return redirect()->route('admin.redeem-codes')->with('status', 'Code deleted.');
    }
}
