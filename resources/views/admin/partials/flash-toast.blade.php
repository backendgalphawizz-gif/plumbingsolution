@php
    $flashType = null;
    $flashMessage = null;
    $flashErrors = [];

    if (session('success')) {
        $flashType = 'success';
        $flashMessage = session('success');
    } elseif (session('error')) {
        $flashType = 'error';
        $flashMessage = session('error');
    } elseif ($errors->any()) {
        $flashType = 'error';
        $flashErrors = $errors->all();
        $flashMessage = $flashErrors[0] ?? 'Please fix the errors below.';
    }
@endphp

@if($flashType)
    <div class="flash-toast-backdrop" id="flash-toast-backdrop" role="presentation">
        <div
            class="flash-toast flash-toast-{{ $flashType }}"
            id="flash-toast"
            role="alertdialog"
            aria-modal="true"
            aria-labelledby="flash-toast-title"
        >
            <button type="button" class="flash-toast-close" data-flash-toast-close aria-label="Close">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <div class="flash-toast-icon">
                @if($flashType === 'success')
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @else
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                @endif
            </div>

            <div class="flash-toast-body">
                <p class="flash-toast-title" id="flash-toast-title">
                    {{ $flashType === 'success' ? 'Success' : 'Something went wrong' }}
                </p>
                <p class="flash-toast-message">{{ $flashMessage }}</p>
                @if(count($flashErrors) > 1)
                    <ul class="flash-toast-list">
                        @foreach($flashErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <button type="button" class="flash-toast-btn" data-flash-toast-close>OK</button>
        </div>
    </div>
@endif
