@props([
    'current',
    'statuses',
    'selectClass' => 'admin-input',
    'selectExtraClass' => '',
])

<select name="status" class="{{ trim($selectClass.' '.$selectExtraClass) }}" required>
    @foreach($statuses as $status)
        @php
            $isCurrent = $current === $status;
            $allowed = $current->canTransitionTo($status);
            $suffix = $isCurrent ? ' (current)' : (! $allowed ? ' — not allowed' : '');
        @endphp
        <option
            value="{{ $status->value }}"
            data-allowed="{{ $allowed ? '1' : '0' }}"
            @selected($isCurrent)
        >
            {{ $status->label() }}{{ $suffix }}
        </option>
    @endforeach
</select>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('form[data-status-update-guard]').forEach((form) => {
                const select = form.querySelector('select[name="status"]');
                if (!select) {
                    return;
                }

                const alertMessage = 'This status change is not allowed. Follow the correct step-by-step flow.';
                const showBlockedStatusPopup = () => {
                    if (typeof window.showAdminFlashToast === 'function') {
                        window.showAdminFlashToast('error', alertMessage, 'Status not allowed');
                        return;
                    }
                    alert(alertMessage);
                };

                let lastAllowedIndex = select.selectedIndex;

                select.addEventListener('change', () => {
                    const option = select.options[select.selectedIndex];
                    if (option && option.dataset.allowed !== '1') {
                        showBlockedStatusPopup();
                        select.selectedIndex = lastAllowedIndex;
                        return;
                    }

                    lastAllowedIndex = select.selectedIndex;
                });

                form.addEventListener('submit', (event) => {
                    const option = select.options[select.selectedIndex];
                    if (!option || option.dataset.allowed !== '1') {
                        event.preventDefault();
                        showBlockedStatusPopup();
                    }
                });
            });
        </script>
    @endpush
@endonce
