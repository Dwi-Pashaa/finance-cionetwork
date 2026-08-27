@extends('layouts.app')

@section('title')
    Pemasukan
@endsection

@push('css')
    @include('pages.finance.partials.styles')
@endpush

@section('content')
@include('components.alert.success')
@include('components.alert.danger')
<div class="card cio-card">
    <div class="cio-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h3 class="cio-title">Data Pemasukan</h3>
            <div class="cio-subtitle">Kelola transaksi dana masuk dan pendapatan</div>
        </div>
        @can('tambah pemasukan')
            <a href="{{ route('income.create') }}" class="btn cio-btn cio-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                Tambah Pemasukan
            </a>
        @endcan
    </div>

    <div class="filter-toolbar">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small">Tampilkan</label>
                <select name="sort" id="sort" class="form-select form-select-sm">
                    @foreach ([10, 25, 50, 100] as $opt)
                        <option value="{{ $opt }}" {{ request('sort', 10) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Kategori</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Dari</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Sampai</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Cari</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Sumber, kategori, catatan...">
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
                    <th class="text-center" style="width: 50px;">No</th>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Sumber</th>
                    <th>Catatan</th>
                    <th class="text-end">Nominal</th>
                    <th>Dibuat Oleh</th>
                    <th class="text-center" style="width: 100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($incomes as $item)
                    <tr>
                        <td class="text-center text-muted fw-semibold">{{ $loop->iteration + ($incomes->currentPage() - 1) * $incomes->perPage() }}</td>
                        <td>{{ $item->transaction_date->format('d M Y') }}</td>
                        <td><span class="badge bg-success-lt">{{ $item->category?->name ?? '-' }}</span></td>
                        <td>{{ $item->source ?? '-' }}</td>
                        <td class="text-muted">{{ $item->description ?? '-' }}</td>
                        <td class="text-end cio-currency">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                        <td class="text-muted small">{{ $item->creator?->name ?? '-' }}</td>
                        <td class="text-center">
                            <div class="action-btn-group">
                                @can('edit pemasukan')
                                    <a href="{{ route('income.edit', $item->id) }}" class="cio-action-btn btn-edit" title="Edit Pemasukan">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3l-11 11L4 19l1.5-4.5z"/></svg>
                                    </a>
                                @endcan
                                @can('hapus pemasukan')
                                    <button type="button" onclick="deleteIncome('{{ $item->id }}')" class="cio-action-btn btn-delete" title="Hapus Pemasukan">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="cio-empty-state">Belum Ada Data Pemasukan</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="cio-table-footer d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
        <p class="m-0 text-muted small">Menampilkan {{ $incomes->firstItem() ?? 0 }} - {{ $incomes->lastItem() ?? 0 }} dari {{ $incomes->total() }} data</p>
        <div class="m-0">{{ $incomes->links() }}</div>
    </div>
</div>
@endsection

@push('js')
<script>
    const INCOME_BASE = "{{ route('income.index') }}";
    $("#sort").change(function() {
        const params = new URLSearchParams(window.location.search);
        params.set('sort', $(this).val());
        window.location.href = INCOME_BASE + '?' + params.toString();
    });

    function deleteIncome(id) {
        Swal.fire({
            title: "Konfirmasi Hapus",
            text: "Apakah Anda yakin ingin menghapus pemasukan ini?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Ya, Hapus",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: INCOME_BASE + '/' + id + '/destroy',
                    method: "DELETE",
                    dataType: "json",
                    success: function(response) {
                        Swal.fire({ icon: response.status, title: response.message, timer: 1500, showConfirmButton: false });
                        setTimeout(() => window.location.reload(), 1500);
                    }
                });
            }
        });
    }
</script>
@endpush
