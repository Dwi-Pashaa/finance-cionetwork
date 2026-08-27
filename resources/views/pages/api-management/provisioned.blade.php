@extends('layouts.app')

@section('title')
    Credential Baru — {{ $client->name }}
@endsection

@section('content')
@include('components.alert.success')
@include('components.alert.danger')
<div class="row justify-content-center">
    <div class="col-12 col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header py-3 px-4 bg-white border-bottom">
                <h3 class="card-title fw-bold text-dark mb-1">Credential Baru: {{ $client->name }}</h3>
                <div class="text-warning small">
                    <strong>PENTING:</strong> Secret hanya ditampilkan SEKALI ini. Tidak akan pernah ditampilkan lagi.
                </div>
            </div>
            <div class="card-body px-4">
                <div class="mb-3">
                    <label class="form-label">Client ID</label>
                    <input type="text" class="form-control font-monospace" value="{{ $client->client_id }}" readonly>
                    <small class="form-hint text-muted">Dikirim pada header <code>X-Client-ID</code></small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Key ID</label>
                    @php
                        $latestCredential = $client->credentials()->orderByDesc('id')->first();
                    @endphp
                    <input type="text" id="keyIdField" class="form-control font-monospace" value="{{ $latestCredential?->key_id }}" readonly>
                    <small class="form-hint text-muted">Dikirim pada header <code>X-Key-ID</code></small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Client Secret</label>
                    <div class="input-group">
                        <input type="password" id="secretField" class="form-control font-monospace" value="{{ $secret }}" readonly>
                        <button type="button" class="btn btn-outline-secondary" id="toggleSecret" title="Tampilkan/Sembunyikan">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="copySecret" title="Copy Secret">
                            Copy
                        </button>
                    </div>
                    <small class="form-hint text-muted">Digunakan untuk menghitung HMAC-SHA256 signature. Simpan di sisi server client, jangan pernah di frontend.</small>
                </div>

                <div class="alert alert-warning mb-0 mt-4">
                    <label class="form-check">
                        <input type="checkbox" class="form-check-input" id="confirmStored">
                        <span class="form-check-label">Saya sudah menyimpan secret ini di tempat yang aman</span>
                    </label>
                </div>
            </div>
            <div class="card-footer py-3 px-4 bg-white border-top">
                <a href="{{ route('api-management.show', $client->id) }}"
                   id="doneButton"
                   class="btn btn-primary disabled"
                   onclick="return document.getElementById('confirmStored').checked;">
                    Saya Sudah Menyimpan — Selesai
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    const Toast = Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });

    $("#confirmStored").change(function() {
        $("#doneButton").toggleClass("disabled", !this.checked);
    });

    $("#toggleSecret").click(function() {
        const field = $("#secretField");
        field.attr("type", field.attr("type") === "password" ? "text" : "password");
    });

    $("#copySecret").click(function() {
        navigator.clipboard.writeText($("#secretField").val()).then(function() {
            Toast.fire({ icon: "success", title: "Secret dicopy ke clipboard" });
        });
    });
</script>
@endpush
