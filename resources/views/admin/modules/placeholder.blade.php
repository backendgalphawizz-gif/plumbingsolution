@extends('admin.layouts.app')

@section('title', $title)
@section('page-title', $title)

@section('content')
<div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
    <div class="mx-auto max-w-lg text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
        </div>
        <h2 class="text-xl font-semibold text-slate-800">{{ $title }}</h2>
        <p class="mt-2 text-sm text-slate-500">
            This module is connected to the REST API at
            <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">/api/admin/{{ $module }}</code>.
            Use the API endpoints to manage data from your frontend or API client.
        </p>
        <div class="mt-6 rounded-lg bg-slate-50 p-4 text-left text-sm text-slate-600">
            <p class="font-medium text-slate-700">API Authentication</p>
            <p class="mt-1">POST <code>/api/admin/login</code> with email & password to receive a Sanctum token.</p>
            <p class="mt-1">Include header: <code>Authorization: Bearer {token}</code></p>
        </div>
    </div>
</div>
@endsection
