@extends('layouts.app')

@section('title')
    Register API Client
@endsection

@section('content')
@include('components.alert.danger')
<form method="POST" action="{{ route('api-management.store') }}">
    @csrf
    <div class="card shadow-sm border-0">
        <div class="card-header py-3 px-4 bg-white border-bottom">
            <div class="d-flex align-items-center">
                <span class="avatar avatar-sm bg-blue-lt me-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M12 2a4 4 0 0 1 3.95 3.44a4 4 0 0 1 4.05 4.06a4 4 0 0 1 -3.55 3.97a4 4 0 0 1 -4.45 4.53a4 4 0 0 1 -4.45 -4.53a4 4 0 0 1 -3.55 -3.97a4 4 0 0 1 4.05 -4.06a4 4 0 0 1 3.95 -3.44z" /></svg>
                </span>
                <div>
                    <h3 class="card-title fw-bold text-dark mb-1">Register API Client</h3>
                    <div class="text-muted small">Daftarkan website client baru untuk mengakses API Web Finance CIO Network</div>
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
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="Contoh: Website 2" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label required d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-xs text-muted me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l-2 0l9 -9l9 9l-2 0" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg>
                        Kode Client
                    </label>
                    <input type="text" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" placeholder="Contoh: WEB2" required>
                    <small class="form-hint text-muted d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-xs me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16l0 .01" /></svg>
                        Huruf, angka, dash, underscore — menjadi bagian dari Client ID (web2_xxx). Tidak bisa diubah setelah dibuat.
                    </small>
                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <!-- Section: Konfigurasi Akses -->
            <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-primary me-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 8v-2a2 2 0 0 0 -2 -2h-10v12h4" /><path d="M14 8h6l4 4v4h-2" /><path d="M8 16a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M18 16a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M10 16h8" /></svg>
                <h4 class="fw-bold text-dark mb-0">Konfigurasi Akses</h4>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-12">
                    <label class="form-label required d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-xs text-muted me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 3l0 7l6 0l-8 11l0 -7l-6 0l8 -11" /></svg>
                        Rate Limit (request/menit)
                    </label>
                    <div class="input-group">
                        <input type="number" name="rate_limit_per_minute" value="{{ old('rate_limit_per_minute', 60) }}" min="1" max="10000" class="form-control @error('rate_limit_per_minute') is-invalid @enderror" required>
                        <span class="input-group-text">req/menit</span>
                    </div>
                    <small class="form-hint text-muted d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-xs me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16l0 .01" /></svg>
                        Batas jumlah request yang boleh dilakukan client setiap menit.
                    </small>
                    @error('rate_limit_per_minute') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-xs text-muted me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 9h8" /><path d="M8 13h4" /><path d="M12 21l-3 -3h-2a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h10a3 3 0 0 1 3 3v5" /><path d="M16 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M21 22l-1.2 -1.2" /></svg>
                        Deskripsi
                    </label>
                    <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Deskripsi client...">{{ old('description') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="card-footer py-3 px-4 bg-white border-top d-flex justify-content-end gap-2">
            <a href="{{ route('api-management.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l-4 -4l4 -4" /><path d="M5 10h11a4 4 0 1 1 0 8h-1" /></svg>
                Batal
            </a>
            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                Daftarkan Client
            </button>
        </div>
    </div>
</form>
@endsection
