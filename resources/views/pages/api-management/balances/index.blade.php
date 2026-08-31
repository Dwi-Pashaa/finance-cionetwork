@extends('layouts.app')

@section('title')
    Pengaturan Saldo Website
@endsection

@section('content')
@include('components.alert.success')
@include('components.alert.danger')

@if (session('invoice_data'))
    @php $inv = session('invoice_data'); @endphp
    <div class="alert alert-important alert-info alert-dismissible shadow-sm border-0 mb-4" role="alert">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center">
                <span class="me-3 fs-1">💳</span>
                <div>
                    <h4 class="alert-title mb-1 fw-bold text-white">Invoice Top-Up Xendit Berhasil Dibuat</h4>
                    <div class="text-white opacity-90">
                        Client: <strong>{{ $inv['client_name'] }}</strong> | Nominal: <strong>Rp {{ number_format($inv['amount'], 0, ',', '.') }}</strong> | ID: <code>{{ $inv['invoice_id'] }}</code>
                    </div>
                </div>
            </div>
            <div>
                <a href="{{ $inv['invoice_url'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-white text-primary fw-bold shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-external-link me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" /><path d="M11 13l9 -9" /><path d="M15 4h5v5" /></svg>
                    Buka Link Pembayaran Xendit
                </a>
            </div>
        </div>
        <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
    </div>
@endif

<!-- Tabel Saldo Client dengan Saklar ON/OFF Per-Client -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 py-3 px-4 bg-white border-bottom">
        <div>
            <h3 class="card-title fw-bold text-dark mb-1">Daftar Saldo Website Client</h3>
            <div class="text-muted small">Kelola saldo dan status aktif jalur penambahan saldo (Manual & Xendit) untuk setiap website client</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table card-table table-vcenter table-hover">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Status Client</th>
                    <th style="min-width: 170px;">Saldo Manual</th>
                    <th style="min-width: 170px;">Saldo Xendit</th>
                    <th class="text-end">Total Saldo</th>
                    <th>Terakhir Update</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clients as $item)
                    @php
                        $manualBal = (float) ($item->balance?->balance_manual ?? 0);
                        $xenditBal = (float) ($item->balance?->balance_xendit ?? 0);
                        $totalBal  = (float) ($item->balance?->balance ?? ($manualBal + $xenditBal));
                        $isManualActive = $item->isManualBalanceEnabled();
                        $isXenditActive = $item->isXenditBalanceEnabled();
                    @endphp
                    <tr id="client-row-{{ $item->id }}">
                        <td>
                            <div class="fw-bold text-dark fs-3">{{ $item->name }}</div>
                            <div class="small text-muted font-monospace">{{ $item->code }}</div>
                        </td>
                        <td>
                            @if ($item->status->value === 'active')
                                <span class="badge bg-success-lt px-2.5 py-1">Active</span>
                            @elseif ($item->status->value === 'inactive')
                                <span class="badge bg-warning-lt px-2.5 py-1">Disabled</span>
                            @else
                                <span class="badge bg-danger-lt px-2.5 py-1">Revoked</span>
                            @endif
                        </td>

                        <!-- Saldo Manual Column with Toggle Switch -->
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <div class="fw-bold fs-3 text-dark font-monospace">
                                    Rp {{ number_format($manualBal, 0, ',', '.') }}
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input client-channel-toggle"
                                               type="checkbox"
                                               role="switch"
                                               data-client-id="{{ $item->id }}"
                                               data-channel="manual"
                                               id="toggle-manual-{{ $item->id }}"
                                               {{ $isManualActive ? 'checked' : '' }}>
                                    </div>
                                    <span id="badge-manual-{{ $item->id }}" class="badge {{ $isManualActive ? 'bg-success-lt' : 'bg-secondary-lt' }} px-1.5 py-0.5" style="font-size: 11px;">
                                        {{ $isManualActive ? 'Manual ON' : 'Manual OFF' }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- Saldo Xendit Column with Toggle Switch -->
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <div class="fw-bold fs-3 text-primary font-monospace">
                                    Rp {{ number_format($xenditBal, 0, ',', '.') }}
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input client-channel-toggle"
                                               type="checkbox"
                                               role="switch"
                                               data-client-id="{{ $item->id }}"
                                               data-channel="xendit"
                                               id="toggle-xendit-{{ $item->id }}"
                                               {{ $isXenditActive ? 'checked' : '' }}>
                                    </div>
                                    <span id="badge-xendit-{{ $item->id }}" class="badge {{ $isXenditActive ? 'bg-primary-lt' : 'bg-secondary-lt' }} px-1.5 py-0.5" style="font-size: 11px;">
                                        {{ $isXenditActive ? 'Xendit ON' : 'Xendit OFF' }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- Total Saldo -->
                        <td class="text-end fw-bold fs-2 text-dark font-monospace">
                            Rp {{ number_format($totalBal, 0, ',', '.') }}
                        </td>
                        <td class="text-muted small">
                            {{ $item->balance?->updated_at ? \Carbon\Carbon::parse($item->balance->updated_at)->format('d M Y, H:i') : '-' }}
                        </td>
                        <td class="text-end">
                            <!-- Tombol Atur Saldo -->
                            <button type="button"
                                    class="btn btn-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#adjustModal"
                                    data-client-id="{{ $item->id }}"
                                    data-client-name="{{ $item->name }}"
                                    data-balance-manual="{{ $manualBal }}"
                                    data-balance-xendit="{{ $xenditBal }}"
                                    data-balance-total="{{ $totalBal }}"
                                    data-manual-active="{{ $isManualActive ? '1' : '0' }}"
                                    data-xendit-active="{{ $isXenditActive ? '1' : '0' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-adjustments me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 10a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M6 4v4" /><path d="M6 12v8" /><path d="M12 4v10" /><path d="M12 18v2" /><path d="M16 7a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M18 4v1" /><path d="M18 9v11" /></svg>
                                Atur Saldo
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">Belum Ada API Client Terdaftar</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Riwayat Penyesuaian Saldo -->
<div class="card shadow-sm border-0">
    <div class="card-header py-3 px-4 bg-white border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h3 class="card-title fw-bold text-dark mb-1">Riwayat Penyesuaian & Top-Up Saldo</h3>
            <div class="text-muted small">Semua aktivitas penambahan dan pemotongan saldo tercatat di sini</div>
        </div>
        <form method="GET" class="d-flex flex-wrap gap-2">
            <select name="balance_type" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua Jalur Saldo</option>
                <option value="manual" {{ request('balance_type') === 'manual' ? 'selected' : '' }}>Saldo Manual</option>
                <option value="xendit" {{ request('balance_type') === 'xendit' ? 'selected' : '' }}>Saldo Xendit</option>
            </select>
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
                    <th>Jalur Saldo</th>
                    <th>Aksi</th>
                    <th>Jumlah</th>
                    <th>Sebelum → Sesudah</th>
                    <th>Status / Ref</th>
                    <th>Alasan / Keterangan</th>
                    <th>Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($adjustments as $adj)
                    <tr>
                        <td class="text-muted small">{{ \Carbon\Carbon::parse($adj->created_at)->format('d M Y, H:i') }}</td>
                        <td class="fw-semibold">{{ $adj->client?->name ?? '-' }}</td>
                        <td>
                            @if ($adj->balance_type === 'xendit' || $adj->source === 'xendit')
                                <span class="badge bg-primary-lt px-2 py-0.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-bolt me-0.5" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 3l0 7l6 0l-8 11l0 -7l-6 0z" /></svg>
                                    Xendit
                                </span>
                            @else
                                <span class="badge bg-success-lt px-2 py-0.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-wallet me-0.5" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12" /><path d="M20 12v4h-4a2 2 0 0 1 0 -4h4" /></svg>
                                    Manual
                                </span>
                            @endif
                        </td>
                        <td>
                            @if ($adj->type === 'adjust_in')
                                <span class="badge bg-success-lt">Tambah (+)</span>
                            @else
                                <span class="badge bg-danger-lt">Kurang (−)</span>
                            @endif
                        </td>
                        <td class="{{ $adj->type === 'adjust_in' ? 'text-success' : 'text-danger' }} fw-semibold font-monospace">
                            {{ $adj->type === 'adjust_in' ? '+' : '-' }} Rp {{ number_format($adj->amount, 0, ',', '.') }}
                        </td>
                        <td class="small text-muted font-monospace">
                            Rp {{ number_format($adj->balance_before, 0, ',', '.') }} → Rp {{ number_format($adj->balance_after, 0, ',', '.') }}
                        </td>
                        <td>
                            @if ($adj->payment_status === 'pending')
                                <span class="badge bg-warning-lt">Pending</span>
                            @elseif ($adj->payment_status === 'completed')
                                <span class="badge bg-success-lt">Lunas</span>
                            @elseif ($adj->xendit_invoice_id)
                                <span class="badge bg-info-lt">Inv: {{ \Illuminate\Support\Str::limit($adj->xendit_invoice_id, 10) }}</span>
                            @else
                                <span class="badge bg-secondary-lt">Selesai</span>
                            @endif
                        </td>
                        <td class="small">{{ $adj->reason }}</td>
                        <td class="small text-muted">{{ $adj->adjustedBy?->name ?? 'Sistem / Webhook' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">Belum Ada Riwayat Penyesuaian Saldo</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-footer d-flex justify-content-end px-4 pb-3">
        {{ $adjustments->links() }}
    </div>
</div>

<!-- Modal Atur Saldo Manual / Penyesuaian -->
<div class="modal modal-blur fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="adjustForm" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Atur Saldo — <span id="modalClientName" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Info Saldo Saat Ini -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-muted mb-1">Saldo Manual Saat Ini</label>
                            <input type="text" id="modalCurrentManual" class="form-control form-control-sm font-monospace fw-bold text-success" readonly disabled>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted mb-1">Saldo Xendit Saat Ini</label>
                            <input type="text" id="modalCurrentXendit" class="form-control form-control-sm font-monospace fw-bold text-primary" readonly disabled>
                        </div>
                    </div>

                    <!-- Pilihan Kantong Saldo -->
                    <div class="mb-3">
                        <label class="form-label required fw-bold">Pilih Kantong Saldo</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-selectgroup-item flex-fill w-100">
                                    <input type="radio" name="balance_type" value="manual" class="form-selectgroup-input" checked>
                                    <div class="form-selectgroup-label d-flex align-items-center p-2">
                                        <span class="me-2">💰</span>
                                        <div class="text-start">
                                            <div class="font-weight-medium">Saldo Manual</div>
                                            <div class="text-muted small" id="manualPocketStatus">Penyesuaian internal</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="form-selectgroup-item flex-fill w-100">
                                    <input type="radio" name="balance_type" value="xendit" class="form-selectgroup-input">
                                    <div class="form-selectgroup-label d-flex align-items-center p-2">
                                        <span class="me-2">⚡</span>
                                        <div class="text-start">
                                            <div class="font-weight-medium">Saldo Xendit</div>
                                            <div class="text-muted small" id="xenditPocketStatus">Penyesuaian gateway</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Tipe Penyesuaian -->
                    <div class="mb-3">
                        <label class="form-label required fw-bold">Tipe Penyesuaian</label>
                        <div class="d-flex gap-4">
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

                    <!-- Jumlah -->
                    <div class="mb-3">
                        <label class="form-label required fw-bold">Jumlah (Rp)</label>
                        <input type="hidden" name="amount" id="adjustAmount" value="{{ old('amount') }}">
                        <input type="text" id="adjustAmountDisplay" value="{{ old('amount') !== null && old('amount') !== '' ? 'Rp '.number_format((float) old('amount'), 0, ',', '.') : '' }}" class="form-control @error('amount') is-invalid @enderror" inputmode="numeric" autocomplete="off" placeholder="Rp 0" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Alasan -->
                    <div class="mb-2">
                        <label class="form-label required fw-bold">Alasan Penyesuaian</label>
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

    // Format input nominal penyesuaian manual
    const adjustDisplay = document.getElementById('adjustAmountDisplay');
    const adjustHidden  = document.getElementById('adjustAmount');
    if (adjustDisplay && adjustHidden) {
        adjustDisplay.addEventListener('input', function() {
            const digits = this.value.replace(/\D/g, '');
            adjustHidden.value = digits;
            this.value = formatRupiahInput(digits);
        });
    }

    // Modal Penyesuaian Saldo Manual
    const adjustModal = document.getElementById('adjustModal');
    if (adjustModal) {
        adjustModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const clientId = button.getAttribute('data-client-id');
            const clientName = button.getAttribute('data-client-name');
            const manualBal = parseFloat(button.getAttribute('data-balance-manual')) || 0;
            const xenditBal = parseFloat(button.getAttribute('data-balance-xendit')) || 0;
            const isManualActive = button.getAttribute('data-manual-active') === '1';
            const isXenditActive = button.getAttribute('data-xendit-active') === '1';

            document.getElementById('modalClientName').textContent = clientName;
            document.getElementById('modalCurrentManual').value = 'Rp ' + new Intl.NumberFormat('id-ID').format(manualBal);
            document.getElementById('modalCurrentXendit').value = 'Rp ' + new Intl.NumberFormat('id-ID').format(xenditBal);
            document.getElementById('adjustForm').action = "{{ url('saldo-website') }}" + '/' + clientId + '/adjust';
            if (adjustHidden) adjustHidden.value = '';
            if (adjustDisplay) adjustDisplay.value = '';

            const manualPocketStatus = document.getElementById('manualPocketStatus');
            if (manualPocketStatus) {
                manualPocketStatus.textContent = isManualActive ? 'Status: ON' : 'Status: OFF (Dinonaktifkan)';
                manualPocketStatus.className = isManualActive ? 'text-success small' : 'text-danger small';
            }

            const xenditPocketStatus = document.getElementById('xenditPocketStatus');
            if (xenditPocketStatus) {
                xenditPocketStatus.textContent = isXenditActive ? 'Status: ON' : 'Status: OFF (Dinonaktifkan)';
                xenditPocketStatus.className = isXenditActive ? 'text-primary small' : 'text-danger small';
            }
        });
    }

    // AJAX Toggle Channel ON / OFF Switch Per-Client
    document.querySelectorAll('.client-channel-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const clientId = this.getAttribute('data-client-id');
            const channel = this.getAttribute('data-channel');
            const isActive = this.checked ? 1 : 0;
            const self = this;

            const url = "{{ url('saldo-website') }}/" + clientId + "/toggle-channel";

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    channel: channel,
                    is_active: isActive
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById('badge-' + channel + '-' + clientId);
                    if (badge) {
                        if (channel === 'manual') {
                            badge.className = 'badge ' + (isActive ? 'bg-success-lt' : 'bg-secondary-lt') + ' px-1.5 py-0.5';
                            badge.textContent = isActive ? 'Manual ON' : 'Manual OFF';
                        } else {
                            badge.className = 'badge ' + (isActive ? 'bg-primary-lt' : 'bg-secondary-lt') + ' px-1.5 py-0.5';
                            badge.textContent = isActive ? 'Xendit ON' : 'Xendit OFF';
                        }
                    }
                } else {
                    alert('Gagal mengubah status: ' + (data.message || 'Terjadi kesalahan'));
                    self.checked = !isActive;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kesalahan koneksi saat mengubah status channel.');
                self.checked = !isActive;
            });
        });
    });
</script>
@endpush
