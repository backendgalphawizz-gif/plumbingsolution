@props(['audience', 'faqs', 'title', 'description'])

<div x-data="{
    openFaq: @json($errors->any() && old('audience') === $audience && ! old('_faq_id') ? 'new' : null),
    editFaq: @json($errors->any() && old('audience') === $audience && old('_faq_id') ? (int) old('_faq_id') : null),
}">
    <div class="settings-faq-toolbar">
        <div>
            <h2 class="form-section-title">{{ $title }}</h2>
            <p class="form-section-desc">{{ $description }}</p>
        </div>
        <button type="button" @click="openFaq = 'new'" class="btn btn-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add FAQ
        </button>
    </div>

    @component('admin.partials.data-card', [
        'title' => $title.' List',
        'meta' => number_format($faqs->count()).' entries',
    ])
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Question</th>
                    <th>Answer</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($faqs as $faq)
                    <tr>
                        <td><span class="font-semibold text-slate-700">#{{ $faq->sort_order }}</span></td>
                        <td>
                            <div class="font-medium text-slate-800 cell-truncate settings-faq-question" title="{{ $faq->question }}">
                                {{ $faq->question }}
                            </div>
                        </td>
                        <td>
                            <div class="text-sm text-slate-500 cell-truncate settings-faq-answer" title="{{ $faq->answer }}">
                                {{ $faq->answer }}
                            </div>
                        </td>
                        <td>@include('admin.partials.status-badge', ['status' => $faq->status ? 'active' : 'inactive'])</td>
                        <td>
                            <div class="action-group">
                                <button type="button" @click="editFaq = {{ $faq->id }}" class="action-btn">Edit</button>
                                <form
                                    action="{{ route('admin.settings.faqs.destroy', $faq) }}"
                                    method="POST"
                                    onsubmit="return confirm('Remove this FAQ?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <p>No FAQs yet. Click <strong>Add FAQ</strong> to create the first one.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endcomponent

    @foreach($faqs as $faq)
        <div x-show="editFaq === {{ $faq->id }}" x-cloak class="modal-backdrop">
            <div @click.outside="editFaq = null" class="modal-card settings-faq-modal">
                <h3 class="modal-title">Edit FAQ</h3>
                <p class="modal-sub">Update question, answer and display order.</p>
                <form action="{{ route('admin.settings.faqs.update', $faq) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    @include('admin.settings.partials.faq-fields', ['faq' => $faq, 'audience' => $audience])
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="editFaq = null" class="btn btn-secondary btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Update FAQ</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <div x-show="openFaq === 'new'" x-cloak class="modal-backdrop">
        <div @click.outside="openFaq = null" class="modal-card settings-faq-modal">
            <h3 class="modal-title">Add FAQ</h3>
            <p class="modal-sub">{{ $description }}</p>
            <form action="{{ route('admin.settings.faqs.store') }}" method="POST" class="space-y-4">
                @csrf
                @include('admin.settings.partials.faq-fields', [
                    'audience' => $audience,
                    'faq' => (object) ['sort_order' => ($faqs->max('sort_order') ?? 0) + 1, 'status' => true],
                ])
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openFaq = null" class="btn btn-secondary btn-sm">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Add FAQ</button>
                </div>
            </form>
        </div>
    </div>
</div>
