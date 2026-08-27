@extends('layouts.app')

@section('title')
    Edit User - {{ $user->name }}
@endsection

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center py-3 px-4 bg-white border-bottom">
        <div>
            <h3 class="card-title fw-bold text-dark mb-1">Edit Pengguna</h3>
            <div class="text-muted small">Perbarui data profil dan hak akses {{ $user->name }}</div>
        </div>
        <a href="{{ route('user.index') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 shadow-none">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 14l-4 -4l4 -4" /><path d="M5 10h11a4 4 0 1 1 0 8h-1" /></svg>
            Kembali
        </a>
    </div>

    <form action="{{ route('user.update', ['id' => $user->id]) }}" method="POST">
        @csrf
        @method("PUT")
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="username">Username <span class="text-danger">*</span></label>
                    <input value="{{ old('username', $user->username) }}" type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" required>
                    @error('username')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="name">Nama Lengkap <span class="text-danger">*</span></label>
                    <input value="{{ old('name', $user->name) }}" type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="email">Alamat Email <span class="text-danger">*</span></label>
                    <input value="{{ old('email', $user->email) }}" type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" required>
                    @error('email')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="role">Hak Akses / Level <span class="text-danger">*</span></label>
                    <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
                        <option value="">-- Pilih Level Akses --</option>
                        @foreach ($role as $item)
                            <option value="{{ $item->name }}" {{ (isset($user->roles[0]) && $user->roles[0]->name == $item->name) ? 'selected' : '' }}>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="password">Password Baru <span class="text-muted fw-normal">(Opsional)</span></label>
                    <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="Kosongkan jika tidak diubah">
                    @error('password')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="password_confirmation">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" placeholder="Ulangi password baru">
                    @error('password_confirmation')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center py-3 px-4 border-top">
            <a href="{{ route('user.index') }}" class="btn btn-outline-secondary px-3">
                Batal
            </a>
            <div class="d-flex gap-2">
                <button type="reset" class="btn btn-ghost-secondary px-3">
                    Reset
                </button>
                <button type="submit" class="btn btn-primary px-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon me-1"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" /><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M14 4l0 4l-6 0l0 -4" /></svg>
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>
@endsection