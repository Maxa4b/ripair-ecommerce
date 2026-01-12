<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Support\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function pages()
    {
        $pages = Setting::query()
            ->whereIn('key', ['legal_mentions', 'cgv', 'privacy_policy', 'cookies'])
            ->get()
            ->keyBy('key');

        return view('admin.content.pages', compact('pages'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'in:legal_mentions,cgv,privacy_policy,cookies'],
            'value' => ['required', 'string'],
        ]);

        Setting::updateOrCreate(
            ['key' => $data['key']],
            ['group' => 'content', 'value' => ['html' => $data['value']]],
        );

        return back()->with('success', 'Page mise à jour.');
    }
}
