@extends('layouts.app')

@section('title')
    Data User
@endsection

@section('content')
@include('components.alert.success')
<div class="card shadow-sm border-0">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 py-3 px-4 bg-white border-bottom">
        <div>
            <h3 class="card-title fw-bold text-dark mb-1">Manajemen Pengguna Sistem</h3>
            <div class="text-muted small">Kelola akses, email, dan hak otorisasi pengguna</div>
        </div>
        @can('buat user')
            <a href="{{ route('user.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                Tambah Pengguna
            </a>
        @endcan
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="filter-toolbar">
        <div class="row g-3 align-items-center justify-content-between">
            <div class="col-auto d-flex align-items-center gap-2">
                <span class="text-muted small fw-medium">Tampilkan:</span>
                <select name="sort" id="sort" class="form-select form-select-sm" style="width: 75px;">
                    @foreach ([10, 25, 50, 100] as $opt)
                        <option value="{{ $opt }}" {{ request('sort') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
                <span class="text-muted small fw-medium">data</span>
            </div>
            <div class="col-md-4 col-12">
                <form method="GET">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari nama, username, email...">
                        <button class="btn btn-primary px-3" type="submit">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead>
                <tr>
                    <th class="text-center" style="width: 50px;">No</th>
                    <th>Nama & Username</th>
                    <th>Email</th>
                    <th>Hak Akses / Role</th>
                    <th>Tanggal Dibuat</th>
                    <th class="text-center" style="width: 100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $item)
                    <tr>
                        <td class="text-center text-muted fw-semibold">
                            {{ $loop->iteration + ($users->currentPage() - 1) * $users->perPage() }}
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm rounded-circle bg-primary-subtle text-primary fw-bold me-2" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                    {{ strtoupper(substr($item->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">{{ $item->name }}</div>
                                    <div class="small text-muted">@<span>{{ $item->username }}</span></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="text-muted">{{ $item->email }}</span>
                        </td>
                        <td>
                            <span class="badge badge-soft-primary px-2.5 py-1">
                                {{ optional($item->roles->first())->name ?? 'User' }}
                            </span>
                        </td>
                        <td class="text-muted small">
                            {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y, H:i') }}
                        </td>
                        <td class="text-center">
                            <div class="action-btn-group">
                                @can('ubah user')
                                    <a href="{{ route('user.edit', ['id' => $item->id]) }}" class="btn-action btn-action-warning" title="Edit Pengguna">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                                    </a>
                                @endcan
                                @can('hapus user')
                                    <button type="button" onclick="return deleteUsers('{{ $item->id }}')" class="btn-action btn-action-danger" title="Hapus Pengguna">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                                    </button>
                                @endcan
                            </div>
                        </td> 
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">Tidak Ada Data Pengguna</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-footer d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
        <p class="m-0 text-muted small">
            Menampilkan <span class="fw-semibold text-dark">{{ $users->firstItem() ?? 0 }}</span> - <span class="fw-semibold text-dark">{{ $users->lastItem() ?? 0 }}</span> dari <span class="fw-semibold text-dark">{{ $users->total() }}</span> total pengguna
        </p>
        <div class="m-0">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    const BASE = "{{ route('user.index') }}";

    let params = new URLSearchParams(window.location.search);
    $("#sort").change(function() {
        params.set('sort', $(this).val());
        window.location.href = BASE + '?' + params.toString();
    });

    const Toast = Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });

    function deleteUsers(id) {
        Swal.fire({
            title: "Konfirmasi Hapus",
            text: "Apakah Anda yakin ingin menghapus user ini?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#1e40af",
            cancelButtonColor: "#ef4444",
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: BASE + '/' + id + '/destroy',
                    method: "DELETE",
                    dataType: "json",
                    success: function(response) {
                        Toast.fire({
                            icon: response.status,
                            title: response.message
                        });

                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    },
                    error: function(err) {
                        Toast.fire({
                            icon: "error",
                            title: "Gagal menghapus data dari server."
                        });
                    }
                })
            }
        });
    }
</script>
@endpush
