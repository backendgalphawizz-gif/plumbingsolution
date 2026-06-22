@extends('admin.layouts.app')
@section('title', $service->name)
@section('page-title', 'Service Details')
@section('page-subtitle', 'Service information and images')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="form-card lg:col-span-2">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-slate-900">{{ $service->name }}</h2>
            @include('admin.partials.status-badge', ['status' => $service->status ? 'active' : 'inactive'])
        </div>

        @if($service->images->isNotEmpty())
            <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach($service->images as $image)
                    <a href="{{ asset('storage/'.$image->image_path) }}" target="_blank">
                        <img src="{{ asset('storage/'.$image->image_path) }}" alt="" class="h-24 w-full rounded-lg object-cover">
                    </a>
                @endforeach
            </div>
        @elseif($service->image)
            <div class="mb-6">
                <img src="{{ asset('storage/'.$service->image) }}" alt="" class="h-40 rounded-lg object-cover">
            </div>
        @endif

        <dl class="grid gap-4 text-sm sm:grid-cols-2">
            <div><dt class="admin-label">Category</dt><dd class="mt-1">{{ $service->category?->name ?? '—' }}</dd></div>
            <div><dt class="admin-label">Price</dt><dd class="mt-1 font-semibold">₹{{ number_format($service->starting_price, 2) }}</dd></div>
            <div><dt class="admin-label">Provider</dt><dd class="mt-1">{{ $service->serviceProvider?->name ?? 'Platform catalog' }}</dd></div>
            <div><dt class="admin-label">Rating</dt><dd class="mt-1">{{ $service->rating }}</dd></div>
            <div class="sm:col-span-2"><dt class="admin-label">Description</dt><dd class="mt-1">{{ $service->description ?? '—' }}</dd></div>
        </dl>

        <div class="mt-6 flex flex-wrap gap-2 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.services.index') }}" class="btn btn-secondary btn-sm">Back to list</a>
            @if($service->serviceProvider)
                <a href="{{ route('admin.service-providers.show', $service->serviceProvider) }}" class="btn btn-secondary btn-sm">View Provider</a>
            @endif
            <form action="{{ route('admin.services.destroy', $service) }}" method="POST" onsubmit="return confirm('Delete this service?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm bg-red-600 text-white">Delete</button>
            </form>
        </div>
    </div>

    <div class="detail-panel">
        <h3 class="detail-panel-title">Linked Providers</h3>
        @forelse($service->providers as $provider)
            <div class="detail-row">
                <span>{{ $provider->name }}</span>
                <span class="text-sm font-semibold">₹{{ number_format($provider->pivot->price ?? $service->starting_price, 2) }}</span>
            </div>
        @empty
            <p class="text-sm text-slate-400">No providers linked.</p>
        @endforelse
    </div>
</div>
@endsection
