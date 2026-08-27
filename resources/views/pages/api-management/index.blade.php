@extends('layouts.app')

@section('title')
    API Management
@endsection

@section('content')
@include('components.alert.success')
@include('components.alert.danger')
<div class="card shadow-sm border-0">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 py-3 px-4 bg-white border-bottom">
        <div>
            <h3 class="card-title fw-bold text-dark mb-1">API Management</h3>
            <div class="text-muted small">Kelola koneksi dan credential website client terhadap API Web Finance CIO Network</div>
        </div>
        <a href="{{ route('api-management.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
            Register Client
        </a>
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
                        <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Cari nama, kode, client id...">
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
                    <th>Client</th>
                    <th>Client ID</th>
                    <th>Status</th>
                    <th>Saldo</th>
                    <th>Last Used</th>
                    <th>Last IP</th>
                    <th class="text-center" style="width: 100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clients as $item)
                    <tr>
                        <td class="text-center text-muted fw-semibold">
                            {{ $loop->iteration + ($clients->currentPage() - 1) * $clients->perPage() }}
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $item->name }}</div>
                            <div class="small text-muted">{{ $item->code }}</div>
                        </td>
                        <td><code>{{ $item->client_id }}</code></td>
                        <td>
                            @if ($item->status->value === 'active')
                                <span class="badge bg-success-lt px-2.5 py-1">Active</span>
                            @elseif ($item->status->value === 'inactive')
                                <span class="badge bg-warning-lt px-2.5 py-1">Disabled</span>
                            @else
                                <span class="badge bg-danger-lt px-2.5 py-1">Revoked</span>
                            @endif
                        </td>
                        <td class="fw-semibold">Rp {{ number_format($item->balance?->balance ?? 0, 0, ',', '.') }}</td>
                        <td class="text-muted small">
                            {{ $item->last_used_at ? \Carbon\Carbon::parse($item->last_used_at)->format('d M Y, H:i') : '-' }}
                        </td>
                        <td class="text-muted small">{{ $item->last_ip ?? '-' }}</td>
                        <td class="text-center">
                            <a href="{{ route('api-management.show', ['id' => $item->id]) }}" class="btn-action btn-action-warning" title="Detail Client">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">Belum Ada API Client Terdaftar</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-footer d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
        <p class="m-0 text-muted small">
            Menampilkan <span class="fw-semibold text-dark">{{ $clients->firstItem() ?? 0 }}</span> - <span class="fw-semibold text-dark">{{ $clients->lastItem() ?? 0 }}</span> dari <span class="fw-semibold text-dark">{{ $clients->total() }}</span> total client
        </p>
        <div class="m-0">
            {{ $clients->links() }}
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    const BASE = "{{ route('api-management.index') }}";

    let params = new URLSearchParams(window.location.search);
    $("#sort").change(function() {
        params.set('sort', $(this).val());
        window.location.href = BASE + '?' + params.toString();
    });
</script>
@endpush
