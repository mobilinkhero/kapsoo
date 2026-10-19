@extends('layouts.app')
@section('title', 'Edit Contact')
@section('content')
<div class="fade-in max-w-lg">
    <h1 class="text-2xl font-bold text-slate-900 mb-6">Edit Contact</h1>
    <form method="POST" action="{{ route('contacts.update', $contact) }}" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
        @csrf @method('PUT')
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
            <input name="name" value="{{ old('name', $contact->name) }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
            <input name="phone" value="{{ old('phone', $contact->phone) }}" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
        </div>
        <div class="flex gap-3">
            <button class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white hover:bg-emerald-600 transition">Update</button>
            <a href="{{ route('contacts.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition">Cancel</a>
        </div>
    </form>
</div>
@endsection