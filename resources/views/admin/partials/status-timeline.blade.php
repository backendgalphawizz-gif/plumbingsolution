@php
    $currentStatus = is_object($current) ? $current->value : (string) $current;
    $stepKeys = collect($steps)->pluck('key')->all();
    $currentIndex = array_search($currentStatus, $stepKeys, true);
    $terminalBad = $terminal ?? [];
    $isBadTerminal = in_array($currentStatus, $terminalBad, true);
@endphp

<div class="progress-stepper {{ $isBadTerminal ? 'progress-stepper--bad' : '' }}">
    @foreach($steps as $index => $step)
        @php
            $done = $currentIndex !== false && $index < $currentIndex;
            $active = $step['key'] === $currentStatus;
            $upcoming = $currentIndex !== false && $index > $currentIndex && ! $isBadTerminal;
        @endphp
        <div class="progress-step {{ $done ? 'is-done' : '' }} {{ $active ? 'is-active' : '' }} {{ $upcoming ? 'is-upcoming' : '' }}">
            <div class="progress-step-dot">
                @if($done)
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                @else
                    <span>{{ $index + 1 }}</span>
                @endif
            </div>
            <span class="progress-step-label">{{ $step['label'] }}</span>
        </div>
        @if(! $loop->last)
            <div class="progress-step-line {{ $done ? 'is-done' : '' }}"></div>
        @endif
    @endforeach
</div>

@if($isBadTerminal)
    <div class="progress-terminal-alert">
        @include('admin.partials.status-badge', ['status' => $currentStatus])
        <span class="text-sm text-slate-600">This {{ $entityLabel ?? 'record' }} did not complete the standard flow.</span>
    </div>
@endif

@if(isset($logs) && $logs->isNotEmpty())
    <div class="activity-timeline">
        <h4 class="activity-timeline-title">Status History</h4>
        @foreach($logs as $log)
            @php
                $logStatus = is_object($log->status) ? $log->status->value : (string) $log->status;
            @endphp
            <div class="activity-item">
                <div class="activity-dot"></div>
                <div class="activity-body">
                    <div class="flex flex-wrap items-center gap-2">
                        @include('admin.partials.status-badge', ['status' => $logStatus])
                        <span class="text-xs text-slate-400">{{ $log->created_at->format('M d, Y • g:i A') }}</span>
                    </div>
                    @if($log->notes)
                        <p class="activity-notes">{{ $log->notes }}</p>
                    @endif
                    @if($log->relationLoaded('changedBy') && $log->changedBy)
                        <p class="activity-meta">Updated by {{ $log->changedBy->name ?? 'Admin' }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
