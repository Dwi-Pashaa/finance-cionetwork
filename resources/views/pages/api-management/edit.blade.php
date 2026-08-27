@extends('layouts.app')

@section('title')
    Edit API Client
@endsection

@section('content')
@include('components.alert.danger')
<form method="POST" action="{{ route('api-management.update', $client->id) }}">
    @csrf
    @method('PUT')
    <div class="card shadow-sm border-0">
        <div class="card-header py-3 px-4 bg-white border-bottom">
            <div class="d-flex align-items-center">
                <span class="avatar avatar-sm bg-yellow-lt me-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg>
                </span>
                <div>
                    <h3 class="card-title fw-bold text-dark mb-1">Edit API Client: {{ $client->name }}</h3>
                    <div class="text-muted small">Client ID dan Code tidak dapat diubah</div>
                </div>
            </div>
        </div>

        <div class="card-body px-4 py-4">
            <!-- Section: Informasi Client -->
            <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-primary me-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>
                <h4 class="fw-bold text-dark mb-0">Informasi Client</h4>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <label class="form-label required d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-xs text-muted me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg>
                        Nama Client
                    </label>
                    <input type="text" name="name" value="{{ old('name', $client->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-xs text-muted me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 8l-4 4l4 4" /><path d="M17 8l4 4l-4 4" /><path d="M14 4l-4 16" /></svg>
                        Code &amp; Client ID (read-only)
                    </label>
                    <div class="row g-2">
                        <div class="col-5">
                            <input type="text" class="form-control font-monospace" value="{{ $client->code }}" readonly disabled>
                        </div>
                        <div class="col-7">
                            <input type="text" class="form-control font-monospace" value="{{ $client->client_id }}" readonly disabled>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section: Konfigurasi Akses -->
            <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-primary me-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 8v-2a2 2 0 0 0 -2 -2h-10v12h4" /><path d="M14 8h6l4 4v4h-2" /><path d="M8 16a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M18 16a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M10 16h8" /></svg>
                <h4 class="fw-bold text-dark mb-0">Konfigurasi Akses</h4>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label required d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-xs text-muted me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 3l0 7l6 0l-8 11l0 -7l-6 0l8 -11" /></svg>
                        Rate Limit (request/menit)
                    </label>
                    <div class="input-group">
                        <input type="number" name="rate_limit_per_minute" value="{{ old('rate_limit_per_minute', $client->rate_limit_per_minute) }}" min="1" max="10000" class="form-control @error('rate_limit_per_minute') is-invalid @enderror" required>
                        <span class="input-group-text">req/menit</span>
                    </div>
                    @error('rate_limit_per_minute') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-xs text-muted me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 9h8" /><path d="M8 13h4" /><path d="M12 21l-3 -3h-2a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h10a3 3 0 0 1 3 3v5" /><path d="M16 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M21 22l-1.2 -1.2" /></svg>
                        Deskripsi
                    </label>
                    <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $client->description) }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="card-footer py-3 px-4 bg-white border-top d-flex justify-content-end gap-2">
            <a href="{{ route('api-management.show', $client->id) }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l-4 -4l4 -4" /><path d="M5 10h11a4 4 0 1 1 0 8h-1" /></svg>
                Batal
            </a>
            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" /><path d="M13.5 6.5l4 4" /></svg>
                Simpan Perubahan
            </button>
        </div>
    </div>
</form>
@endsection
