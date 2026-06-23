<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\Faq;
use App\Models\Setting;
use App\Support\AdminValidation as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    private const LEGAL_AUDIENCES = ['user', 'vendor', 'provider'];

    public function index(Request $request): View
    {
        $settings = Setting::all()->groupBy('group');
        $privacyPages = CmsPage::where('slug', 'privacy-policy')->get()->keyBy('audience');
        $termsPages = CmsPage::where('slug', 'terms-and-conditions')->get()->keyBy('audience');
        $userFaqs = Faq::forAudience('user')->orderBy('sort_order')->orderBy('id')->get();
        $vendorFaqs = Faq::forAudience('vendor')->orderBy('sort_order')->orderBy('id')->get();
        $providerFaqs = Faq::forAudience('provider')->orderBy('sort_order')->orderBy('id')->get();

        $allowedTabs = [
            'general', 'payments', 'commission',
            'privacy-policy', 'terms-and-conditions',
            'user-faqs', 'vendor-faqs', 'provider-faqs',
        ];
        $returnTab = $request->query('tab') ?: old('return_tab');
        $activeTab = in_array($returnTab, $allowedTabs, true)
            ? $returnTab
            : 'general';
        $activeAudience = in_array($request->query('audience'), self::LEGAL_AUDIENCES, true)
            ? $request->query('audience')
            : 'user';

        return view('admin.settings.index', compact(
            'settings',
            'privacyPages',
            'termsPages',
            'userFaqs',
            'vendorFaqs',
            'providerFaqs',
            'activeTab',
            'activeAudience',
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'settings' => ['nullable', 'array'],
            'settings.*' => ['array'],
            'settings.*.*' => ['nullable', 'string', V::maxRule('setting_value')],
            'return_tab' => ['nullable', 'string'],
        ]);

        foreach ($request->input('settings', []) as $group => $items) {
            foreach ($items as $key => $value) {
                Setting::setValue($group, $key, $value);
            }
        }

        return $this->redirectToTab($request->input('return_tab', 'general'), 'Settings saved.');
    }

    public function updateCms(Request $request, CmsPage $cmsPage): RedirectResponse
    {
        $request->validate([
            'title' => V::cmsTitleRules(),
            'content' => V::cmsContentRules(),
            'return_tab' => ['nullable', 'string'],
        ]);

        $cmsPage->update($request->only('title', 'content'));

        $tab = $request->input('return_tab', $cmsPage->slug);

        return $this->redirectToTab($tab, 'Page content updated.', $cmsPage->audience);
    }

    public function storeFaq(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'audience' => ['required', 'in:user,vendor,provider'],
            'question' => V::faqQuestionRules(),
            'answer' => V::faqAnswerRules(),
            'status' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'return_tab' => ['nullable', 'string'],
        ]);

        $data['status'] = $request->boolean('status', true);
        $data['sort_order'] = $data['sort_order']
            ?? ((int) Faq::forAudience($data['audience'])->max('sort_order')) + 1;

        unset($data['return_tab']);

        Faq::create($data);

        return $this->redirectToTab($data['audience'].'-faqs', 'FAQ added.');
    }

    public function updateFaq(Request $request, Faq $faq): RedirectResponse
    {
        $data = $request->validate([
            'question' => V::faqQuestionRules(),
            'answer' => V::faqAnswerRules(),
            'status' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'audience' => ['nullable', 'in:user,vendor,provider'],
            '_faq_id' => ['nullable', 'integer'],
            'return_tab' => ['nullable', 'string'],
        ]);

        $data['status'] = $request->boolean('status');

        unset($data['_faq_id'], $data['return_tab'], $data['audience']);

        $faq->update($data);

        return $this->redirectToTab($faq->audience.'-faqs', 'FAQ updated.');
    }

    public function destroyFaq(Faq $faq): RedirectResponse
    {
        $tab = $faq->audience.'-faqs';
        $faq->delete();

        return $this->redirectToTab($tab, 'FAQ removed.');
    }

    private function redirectToTab(string $tab, string $message, ?string $audience = null): RedirectResponse
    {
        $params = ['tab' => $tab];

        if ($audience && in_array($audience, self::LEGAL_AUDIENCES, true)) {
            $params['audience'] = $audience;
        }

        return redirect()
            ->route('admin.settings.index', $params)
            ->with('success', $message);
    }
}
