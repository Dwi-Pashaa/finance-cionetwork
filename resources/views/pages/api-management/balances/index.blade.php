@extends('layouts.app')

@section('title')
    Pengaturan Saldo Website
@endsection

@section('content')
@include('components.alert.success')
@include('components.alert.danger')
<div class="card shadow-sm border-0 mb-3">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 py-3 px-4 bg-white border-bottom">
        <div>
            <h3 class="card-title fw-bold text-dark mb-1">Pengaturan Saldo Website</h3>
            <div class="text-muted small">Atur saldo setiap website client secara manual (penyesuaian oleh admin Web Finance CIO Network)</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Client ID</th>
                    <th>Status</th>
                    <th>Saldo Saat Ini</th>
                    <th>Terakhir Diupdate</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clients as $item)
                    <tr>
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
                        <td class="fw-bold fs-3">Rp {{ number_format($item->balance?->balance ?? 0, 0, ',', '.') }}</td>
                        <td class="text-muted small">{{ $item->balance?->updated_at ? \Carbon\Carbon::parse($item->balance->updated_at)->format('d M Y, H:i') : '-' }}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#adjustModal"
                                    data-client-id="{{ $item->id }}"
                                    data-client-name="{{ $item->name }}"
                                    data-client-balance="{{ $item->balance?->balance ?? 0 }}">
                                Atur Saldo
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">Belum Ada API Client Terdaftar</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Riwayat Penyesuaian -->
<div class="card shadow-sm border-0">
    <div class="card-header py-3 px-4 bg-white border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h3 class="card-title fw-bold text-dark mb-1">Riwayat Penyesuaian Saldo</h3>
            <div class="text-muted small">Semua penyesuaian manual tercatat di sini</div>
        </div>
        <form method="GET" class="col-md-3">
            <select name="client_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua Client</option>
                @foreach ($clients as $c)
                    <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Client</th>
                    <th>Tipe</th>
                    <th>Jumlah</th>
                    <th>Saldo Sebelum → Sesudah</th>
                    <th>Alasan</th>
                    <th>Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($adjustments as $adj)
                    <tr>
                        <td class="text-muted small">{{ \Carbon\Carbon::parse($adj->created_at)->format('d M Y, H:i') }}</td>
                        <td class="fw-semibold">{{ $adj->client?->name ?? '-' }}</td>
                        <td>
                            @if ($adj->type === 'adjust_in')
                                <span class="badge bg-success-lt">Tambah</span>
                            @else
                                <span class="badge bg-danger-lt">Kurang</span>
                            @endif
                        </td>
                        <td class="{{ $adj->type === 'adjust_in' ? 'text-success' : 'text-danger' }} fw-semibold">
                            {{ $adj->type === 'adjust_in' ? '+' : '-' }} Rp {{ number_format($adj->amount, 0, ',', '.') }}
                        </td>
                        <td class="small text-muted">
                            Rp {{ number_format($adj->balance_before, 0, ',', '.') }} → Rp {{ number_format($adj->balance_after, 0, ',', '.') }}
                        </td>
                        <td class="small">{{ $adj->reason }}</td>
                        <td class="small text-muted">{{ $adj->adjustedBy?->name ?? 'Sistem' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">Belum Ada Penyesuaian Saldo</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-footer d-flex justify-content-end px-4 pb-3">
        {{ $adjustments->links() }}
    </div>
</div>

<!-- Modal Atur Saldo -->
<div class="modal modal-blur fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="adjustForm" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Atur Saldo — <span id="modalClientName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Saldo Saat Ini</label>
                        <input type="text" id="modalCurrentBalance" class="form-control font-monospace" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Tipe Penyesuaian</label>
                        <div>
                            <label class="form-check form-check-inline">
                                <input type="radio" name="type" value="adjust_in" class="form-check-input" checked>
                                <span class="form-check-label text-success fw-semibold">Tambah Saldo (+)</span>
                            </label>
                            <label class="form-check form-check-inline">
                                <input type="radio" name="type" value="adjust_out" class="form-check-input">
                                <span class="form-check-label text-danger fw-semibold">Kurangi Saldo (−)</span>
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Jumlah (Rp)</label>
                        <input type="hidden" name="amount" id="adjustAmount" value="{{ old('amount') }}">
                        <input type="text" id="adjustAmountDisplay" value="{{ old('amount') !== null && old('amount') !== '' ? 'Rp '.number_format((float) old('amount'), 0, ',', '.') : '' }}" class="form-control @error('amount') is-invalid @enderror" inputmode="numeric" autocomplete="off" placeholder="Rp 0" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label required">Alasan Penyesuaian</label>
                        <input type="text" name="reason" class="form-control @error('reason') is-invalid @enderror" placeholder="Contoh: Koreksi saldo awal, top up modal" required>
                        @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Penyesuaian</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    function formatRupiahInput(value) {
        const digits = String(value || '').replace(/\D/g, '');
        return digits ? 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(digits)) : '';
    }

    document.getElementById('adjustAmountDisplay').addEventListener('input', function() {
        const digits = this.value.replace(/\D/g, '');
        document.getElementById('adjustAmount').value = digits;
        this.value = formatRupiahInput(digits);
    });

    document.getElementById('adjustModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const clientId = button.getAttribute('data-client-id');
        const clientName = button.getAttribute('data-client-name');
        const clientBalance = parseFloat(button.getAttribute('data-client-balance')) || 0;

        document.getElementById('modalClientName').textContent = clientName;
        document.getElementById('modalCurrentBalance').value = 'Rp ' + new Intl.NumberFormat('id-ID').format(clientBalance);
        document.getElementById('adjustForm').action = "{{ url('saldo-website') }}" + '/' + clientId + '/adjust';
        document.getElementById('adjustAmount').value = '';
        document.getElementById('adjustAmountDisplay').value = '';
    });
</script>
@endpush
