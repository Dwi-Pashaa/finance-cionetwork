@extends('layouts.auth')

@section('title', 'Login Portal')

@section('content')
    <div class="text-center mb-4">
        <h2 class="card-title">Masuk ke Akun Anda</h2>
        <p class="card-subtitle">Silakan masukkan kredensial akun untuk mengakses sistem {{ config('app.name') }}</p>
    </div>

    <form action="{{route('post.login')}}" method="POST" autocomplete="off" novalidate id="form-login">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="username">
                Username / Email <span class="text-danger">*</span>
            </label>
            <div class="input-icon">
                <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" placeholder="Masukkan username" value="{{ old('username') }}" required autofocus autocomplete="off" />
            </div>
            @error('username')
                <span class="invalid-feedback d-block">
                    {{ $message }}
                </span>
            @enderror
        </div>

        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label mb-0" for="password">
                    Password <span class="text-danger">*</span>
                </label>
            </div>
            <div class="input-icon">
                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••" required autocomplete="off" />
            </div>
            @error('password')
                <span class="invalid-feedback d-block">
                    {{ $message }}
                </span>
            @enderror
        </div>

        <div class="form-footer">
            <button type="submit" class="btn btn-primary w-100 py-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" /><path d="M20 12h-13l3 -3m0 6l-3 -3" /></svg>
                Masuk ke Dashboard
            </button>
        </div>
    </form>
@endsection