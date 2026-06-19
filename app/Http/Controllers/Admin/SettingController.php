<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\AdminValidation as V;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::all()->groupBy('group');
        $cmsPages = CmsPage::all();

        return view('admin.settings.index', compact('settings', 'cmsPages'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'settings' => ['nullable', 'array'],
            'settings.*' => ['array'],
            'settings.*.*' => ['nullable', 'string', V::maxRule('setting_value')],
        ]);

        foreach ($request->input('settings', []) as $group => $items) {
            foreach ($items as $key => $value) {
                Setting::setValue($group, $key, $value);
            }
        }

        return back()->with('success', 'Settings saved.');
    }

    public function updateCms(Request $request, CmsPage $cmsPage): RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', V::maxRule('cms_title')],
            'content' => ['nullable', 'string', V::maxRule('cms_content')],
        ]);
        $cmsPage->update($request->only('title', 'content'));

        return back()->with('success', 'CMS page updated.');
    }
}
