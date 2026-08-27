@extends('layouts.app')

@section('title')
    Kategori Keuangan
@endsection

@push('css')
    @include('pages.finance.partials.styles')
@endpush

@section('content')
@include('components.alert.success')
@include('components.alert.danger')
<div class="row g-3">
    @can('tambah kategori keuangan')
        <div class="col-lg-4">
            <form action="{{ route('finance-category.store') }}" method="POST" class="card cio-card">
                @csrf
                <div class="cio-card-header d-flex align-items-center gap-3">
                    <span class="cio-icon-avatar info">
                        <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"/><path d="M7 4v6"/><path d="M17 4v6"/><path d="M4 17h16"/><path d="M7 14v6"/><path d="M17 14v6"/></svg>
                    </span>
                    <div>
                        <h3 class="cio-title">Tambah Kategori</h3>
                        <div class="cio-subtitle">Kelompokkan pemasukan dan pengeluaran</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Tipe</label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="income" {{ old('type') === 'income' ? 'selected' : '' }}>Pemasukan</option>
                            <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="cio-table-footer text-end">
                    <button type="submit" class="btn cio-btn cio-btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h10l2 2v14H6z"/><path d="M10 4v6h6"/><path d="M9 16h6"/></svg>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    @endcan

    <div class="@can('tambah kategori keuangan') col-lg-8 @else col-12 @endcan">
        <div class="card cio-card">
            <div class="cio-card-header">
                <h3 class="cio-title">Daftar Kategori</h3>
                <div class="cio-subtitle">Status dan detail kategori transaksi</div>
            </div>
            <div class="filter-toolbar">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small">Tipe</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Pemasukan</option>
                            <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Pengeluaran</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small">Cari</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Nama kategori...">
                            <button class="btn cio-btn cio-btn-primary" type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16"/><path d="M7 12h10"/><path d="M10 18h4"/></svg>
                                Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter cio-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th>Deskripsi</th>
                            <th class="text-center" style="width: 160px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td class="fw-semibold">{{ $category->name }}</td>
                                <td>{{ $category->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</td>
                                <td>
                                    <span class="badge {{ $category->is_active ? 'bg-success-lt' : 'bg-secondary-lt' }}">
                                        {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $category->description ?? '-' }}</td>
                                <td class="text-center">
                                    <div class="action-btn-group">
                                        @can('edit kategori keuangan')
                                            <button
                                                type="button"
                                                class="cio-action-btn btn-edit btn-edit-category"
                                                data-id="{{ $category->id }}"
                                                data-type="{{ $category->type }}"
                                                data-name="{{ $category->name }}"
                                                data-description="{{ $category->description }}"
                                                data-is-active="{{ $category->is_active ? '1' : '0' }}"
                                                title="Edit Kategori"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3l-11 11L4 19l1.5-4.5z"/></svg>
                                            </button>
                                        @endcan
                                        @can('hapus kategori keuangan')
                                            <button type="button" onclick="deleteCategory('{{ $category->id }}')" class="cio-action-btn btn-delete" title="Hapus Kategori">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="cio-empty-state">Belum Ada Kategori</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="cio-table-footer d-flex justify-content-end">
                {{ $categories->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('modal')
@can('edit kategori keuangan')
<div class="modal modal-blur fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="POST" id="editCategoryForm" class="modal-content cio-card">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center">
                    <span class="cio-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3l-11 11L4 19l1.5-4.5z"/></svg>
                    </span>
                    Edit Kategori
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Tipe</label>
                    <select name="type" id="editCategoryType" class="form-select" required>
                        <option value="income">Pemasukan</option>
                        <option value="expense">Pengeluaran</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" id="editCategoryName" class="form-control" required maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" id="editCategoryDescription" rows="3" class="form-control" maxlength="500"></textarea>
                </div>
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="editCategoryActive" value="1">
                    <span class="form-check-label">Kategori aktif</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light cio-btn" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn cio-btn cio-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h10l2 2v14H6z"/><path d="M10 4v6h6"/><path d="M9 16h6"/></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endcan
@endpush

@push('js')
<script>
    const CATEGORY_BASE = "{{ route('finance-category.index') }}";

    function editCategory(id, type, name, description, isActive) {
        $("#editCategoryForm").attr("action", CATEGORY_BASE + "/" + id + "/update");
        $("#editCategoryType").val(type);
        $("#editCategoryName").val(name);
        $("#editCategoryDescription").val(description || "");
        $("#editCategoryActive").prop("checked", isActive);

        const modal = new bootstrap.Modal(document.getElementById("editCategoryModal"));
        modal.show();
    }

    $(".btn-edit-category").on("click", function() {
        editCategory(
            $(this).data("id"),
            $(this).data("type"),
            $(this).data("name"),
            $(this).data("description"),
            $(this).data("is-active") == 1
        );
    });

    function deleteCategory(id) {
        Swal.fire({
            title: "Konfirmasi Hapus",
            text: "Apakah Anda yakin ingin menghapus kategori ini?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: CATEGORY_BASE + '/' + id + '/destroy',
                    method: "DELETE",
                    dataType: "json",
                    success: function(response) {
                        Swal.fire({ icon: response.status, title: response.message, timer: 1800, showConfirmButton: false });
                        if (response.code === 200) {
                            setTimeout(() => window.location.reload(), 1500);
                        }
                    }
                });
            }
        });
    }
</script>
@endpush
