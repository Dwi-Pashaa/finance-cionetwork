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
                            <div class="d-inline-flex align-items-center gap-1">
                                <!-- Tombol Log Aktivitas Client -->
                                <button type="button"
                                        class="btn btn-outline-secondary btn-sm btn-view-client-logs"
                                        data-bs-toggle="modal"
                                        data-bs-target="#clientLogsModal"
                                        data-client-id="{{ $item->id }}"
                                        data-client-name="{{ $item->name }}"
                                        data-client-code="{{ $item->code }}"
                                        data-client-identifier="{{ $item->client_id }}"
                                        data-manual-bal="{{ $manualBal }}"
                                        data-xendit-bal="{{ $xenditBal }}"
                                        data-total-bal="{{ $totalBal }}"
                                        title="Lihat Log Aktivitas Client">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-activity me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12h4l3 8l4 -16l3 8h4" /></svg>
                                    Log Aktivitas
                                </button>

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
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state-container py-4">
                                <div class="empty-state-icon" style="width: 44px; height: 44px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12" /><path d="M20 12v4h-4a2 2 0 0 1 0 -4h4" /></svg>
                                </div>
                                <h4 class="empty-state-title" style="font-size: 14px;">Belum Ada Saldo Client</h4>
                                <p class="empty-state-text small">Belum ada data client yang terdaftar untuk pengelolaan saldo.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Riwayat Penyesuaian Saldo -->
<div class="card shadow-sm border-0" id="riwayat-saldo">
    <div class="card-header py-3 px-4 bg-white border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h3 class="card-title fw-bold text-dark mb-1">Riwayat Penyesuaian & Top-Up Saldo</h3>
            <div class="text-muted small">Semua aktivitas penambahan dan pemotongan saldo tercatat di sini</div>
        </div>
        <form method="GET" class="d-flex flex-wrap gap-2">
            <select name="balance_type" class="form-select form-select-sm" style="min-width: 160px;" onchange="this.form.submit()">
                <option value="">Semua Jalur Saldo</option>
                <option value="manual" {{ request('balance_type') === 'manual' ? 'selected' : '' }}>Saldo Manual</option>
                <option value="xendit" {{ request('balance_type') === 'xendit' ? 'selected' : '' }}>Saldo Xendit</option>
            </select>
            <select name="client_id" class="form-select form-select-sm" style="min-width: 160px;" onchange="this.form.submit()">
                <option value="">Semua Client</option>
                @foreach ($clients as $c)
                    <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter table-hover">
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
                        <td class="text-muted small text-nowrap">{{ \Carbon\Carbon::parse($adj->created_at)->format('d M Y, H:i') }}</td>
                        <td class="fw-semibold text-dark">{{ $adj->client?->name ?? '-' }}</td>
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
                        <td class="{{ $adj->type === 'adjust_in' ? 'text-success' : 'text-danger' }} fw-bold font-monospace text-nowrap">
                            {{ $adj->type === 'adjust_in' ? '+' : '-' }} Rp {{ number_format($adj->amount, 0, ',', '.') }}
                        </td>
                        <td class="small text-muted font-monospace text-nowrap">
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
                        <td class="small text-muted text-nowrap">{{ $adj->adjustedBy?->name ?? 'Sistem / Webhook' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state-container py-4">
                                <div class="empty-state-icon" style="width: 44px; height: 44px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 8v4l2 2" /><path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5" /></svg>
                                </div>
                                <h4 class="empty-state-title" style="font-size: 14px;">Belum Ada Riwayat Penyesuaian Saldo</h4>
                                <p class="empty-state-text small">Log perubahan saldo manual atau mutasi Xendit akan tampil otomatis di sini.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="table-footer d-flex justify-content-end px-4 py-3">
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

<!-- Modal Log Aktivitas Client (AJAX) -->
<div class="modal modal-blur fade" id="clientLogsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header py-3 px-4 bg-white border-bottom">
                <div>
                    <h4 class="modal-title fw-bold text-dark d-flex align-items-center gap-2 mb-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon text-primary icon-tabler icon-tabler-activity" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12h4l3 8l4 -16l3 8h4" /></svg>
                        Log Aktivitas Client: <span id="clientLogModalTitleName" class="text-primary"></span>
                    </h4>
                    <div class="text-muted small d-flex align-items-center gap-2 flex-wrap">
                        <span>Kode: <code id="clientLogModalTitleCode" class="font-monospace text-primary bg-primary-subtle px-1.5 py-0.5 rounded"></code></span>
                        <span>•</span>
                        <span>Client ID: <code id="clientLogModalTitleClientId" class="font-monospace text-dark bg-secondary-subtle px-1.5 py-0.5 rounded"></code></span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Info Ringkasan Saldo & Info Client -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-4">
                        <div class="card card-sm bg-light-subtle border shadow-none">
                            <div class="card-body p-3">
                                <div class="text-muted small fw-semibold mb-1">Saldo Manual</div>
                                <div class="fs-2 fw-bold text-success font-monospace" id="modalLogManualBal">Rp 0</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card card-sm bg-light-subtle border shadow-none">
                            <div class="card-body p-3">
                                <div class="text-muted small fw-semibold mb-1">Saldo Xendit</div>
                                <div class="fs-2 fw-bold text-primary font-monospace" id="modalLogXenditBal">Rp 0</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card card-sm bg-light-subtle border shadow-none">
                            <div class="card-body p-3">
                                <div class="text-muted small fw-semibold mb-1">Total Saldo Terkini</div>
                                <div class="fs-2 fw-bold text-dark font-monospace" id="modalLogTotalBal">Rp 0</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter & Judul Tabel Modal -->
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-list-details" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 5h8" /><path d="M13 9h5" /><path d="M13 15h8" /><path d="M13 19h5" /><path d="M3 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /><path d="M3 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /></svg>
                        Riwayat Aktivitas & Request API Client
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <label for="modalLogFilterEvent" class="small text-muted mb-0">Filter Event:</label>
                        <select id="modalLogFilterEvent" class="form-select form-select-sm" style="width: auto; min-width: 180px;">
                            <option value="">Semua Aktivitas</option>
                            <option value="deduct_balance">Potong Saldo (deduct_balance)</option>
                            <option value="refund_balance">Refund Saldo (refund_balance)</option>
                            <option value="create_history">Catat Finance (create_history)</option>
                        </select>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="modalLogLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="text-muted small mt-2">Memuat log aktivitas client...</div>
                </div>

                <!-- Empty State -->
                <div id="modalLogEmpty" class="empty-state-container py-5 d-none">
                    <div class="empty-state-icon" style="width: 44px; height: 44px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12h4l3 8l4 -16l3 8h4" /></svg>
                    </div>
                    <h4 class="empty-state-title" style="font-size: 14px;">Belum Ada Log Aktivitas</h4>
                    <p class="empty-state-text small">Client ini belum memiliki riwayat request transaksi atau aktivitas API yang tercatat.</p>
                </div>

                <!-- Table Content -->
                <div id="modalLogTableWrapper" class="table-responsive d-none border rounded">
                    <table class="table table-vcenter card-table table-hover table-sm">
                        <thead class="bg-light">
                            <tr>
                                <th style="min-width: 150px;">Waktu</th>
                                <th style="min-width: 140px;">Jenis Aktivitas</th>
                                <th>Deskripsi Aktivitas</th>
                                <th style="min-width: 140px;">Nominal / Mutasi</th>
                                <th>Ref ID / Request</th>
                                <th class="text-center" style="width: 90px;">Detail Data</th>
                            </tr>
                        </thead>
                        <tbody id="modalLogTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2 px-4 bg-white border-top d-flex justify-content-between align-items-center">
                <a href="{{ route('dashboard') }}" class="btn btn-link link-primary p-0 text-decoration-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-dashboard me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 13m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M13.45 11.55l2.05 -2.05" /><path d="M6.4 20a9 9 0 1 1 11.2 0z" /></svg>
                    Lihat Seluruh Aktivitas di Dashboard
                </a>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Payload Properties -->
<div class="modal modal-blur fade" id="logPropertiesModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header py-2.5 px-3">
                <h5 class="modal-title fw-bold">Detail Data Aktivitas (Payload)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <pre id="logPropertiesContent" class="bg-dark text-light p-3 rounded font-monospace small mb-0" style="max-height: 350px; overflow-y: auto;"></pre>
            </div>
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

    // Modal Log Aktivitas Client (AJAX)
    let currentLogClientId = null;
    let cachedActivitiesData = [];
    const clientLogsModal = document.getElementById('clientLogsModal');
    const modalFilterEvent = document.getElementById('modalLogFilterEvent');

    function fetchClientLogs(clientId, eventFilter = '') {
        const loadingEl = document.getElementById('modalLogLoading');
        const emptyEl   = document.getElementById('modalLogEmpty');
        const tableWrap = document.getElementById('modalLogTableWrapper');
        const tbody     = document.getElementById('modalLogTableBody');

        loadingEl.classList.remove('d-none');
        emptyEl.classList.add('d-none');
        tableWrap.classList.add('d-none');
        tbody.innerHTML = '';

        let url = "{{ url('saldo-website') }}/" + clientId + "/logs";
        if (eventFilter) {
            url += "?event=" + encodeURIComponent(eventFilter);
        }

        fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(res => {
            if (!res.ok) throw new Error('Gagal memuat log aktivitas client');
            return res.json();
        })
        .then(data => {
            loadingEl.classList.add('d-none');

            if (data.client) {
                document.getElementById('modalLogManualBal').textContent = data.client.manual_balance_formatted || 'Rp 0';
                document.getElementById('modalLogXenditBal').textContent = data.client.xendit_balance_formatted || 'Rp 0';
                document.getElementById('modalLogTotalBal').textContent  = data.client.total_balance_formatted || 'Rp 0';
            }

            cachedActivitiesData = data.activities || [];

            if (!cachedActivitiesData || cachedActivitiesData.length === 0) {
                emptyEl.classList.remove('d-none');
                return;
            }

            tableWrap.classList.remove('d-none');
            tbody.innerHTML = cachedActivitiesData.map((act, index) => {
                let eventBadge = `<span class="badge bg-secondary-lt px-2 py-0.5">${escapeHtml(act.event)}</span>`;
                if (act.event === 'deduct_balance') {
                    eventBadge = `<span class="badge bg-danger-lt px-2 py-0.5"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-minus me-0.5" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0" /></svg>Potong Saldo</span>`;
                } else if (act.event === 'refund_balance') {
                    eventBadge = `<span class="badge bg-success-lt px-2 py-0.5"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus me-0.5" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>Refund Saldo</span>`;
                } else if (act.event === 'create_history') {
                    eventBadge = `<span class="badge bg-info-lt px-2 py-0.5"><svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-text me-0.5" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /></svg>Catat Finance</span>`;
                }

                let amountHtml = '<span class="text-muted small">-</span>';
                if (act.amount_formatted) {
                    const isDeduct = act.event === 'deduct_balance';
                    const amountClass = isDeduct ? 'text-danger' : 'text-success';
                    const prefix = isDeduct ? '-' : '+';
                    const pocketBadge = act.balance_type ? `<span class="badge bg-secondary-subtle text-secondary px-1 py-0" style="font-size: 10px;">${act.balance_type}</span>` : '';

                    let balanceAfterHtml = '';
                    if (act.balance_after_formatted) {
                        balanceAfterHtml = `<div class="text-muted" style="font-size: 11px;">Sisa: ${act.balance_after_formatted}</div>`;
                    }

                    amountHtml = `
                        <div>
                            <div class="${amountClass} fw-bold font-monospace">${prefix} ${act.amount_formatted} ${pocketBadge}</div>
                            ${balanceAfterHtml}
                        </div>
                    `;
                }

                let refHtml = '<span class="text-muted small">-</span>';
                if (act.reference_id) {
                    refHtml = `<div><code class="font-monospace text-primary bg-primary-subtle px-1.5 py-0.5 rounded" style="font-size: 11px;">${escapeHtml(act.reference_id)}</code></div>`;
                }
                if (act.category) {
                    refHtml += `<div class="small text-muted" style="font-size: 11px;">Kat: ${escapeHtml(act.category)}</div>`;
                }

                const propsJson = encodeURIComponent(JSON.stringify(act.properties, null, 2));

                return `
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark small">${act.created_at}</div>
                            <div class="text-muted" style="font-size: 11px;">${act.created_at_human}</div>
                        </td>
                        <td>${eventBadge}</td>
                        <td>
                            <div class="fw-medium text-dark small">${escapeHtml(act.description || '-')}</div>
                            ${act.note ? `<div class="text-muted" style="font-size: 11px;">Catatan: ${escapeHtml(act.note)}</div>` : ''}
                        </td>
                        <td>${amountHtml}</td>
                        <td>${refHtml}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-0.5" onclick="showLogProperties(${index})" style="font-size: 11px;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-code me-0.5" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 8l-4 4l4 4" /><path d="M17 8l4 4l-4 4" /><path d="M14 4l-4 16" /></svg>
                                JSON
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        })
        .catch(err => {
            console.error(err);
            loadingEl.classList.add('d-none');
            emptyEl.classList.remove('d-none');
            emptyEl.querySelector('.empty-state-text').textContent = 'Gagal memuat log aktivitas client. Silakan coba lagi.';
        });
    }

    function showLogProperties(index) {
        if (!cachedActivitiesData[index]) return;
        const props = cachedActivitiesData[index].properties || {};
        document.getElementById('logPropertiesContent').textContent = JSON.stringify(props, null, 2);
        const modal = new bootstrap.Modal(document.getElementById('logPropertiesModal'));
        modal.show();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    if (clientLogsModal) {
        clientLogsModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            currentLogClientId = button.getAttribute('data-client-id');
            const clientName = button.getAttribute('data-client-name') || '';
            const clientCode = button.getAttribute('data-client-code') || '';
            const clientIdentifier = button.getAttribute('data-client-identifier') || '';
            const manualBal = parseFloat(button.getAttribute('data-manual-bal')) || 0;
            const xenditBal = parseFloat(button.getAttribute('data-xendit-bal')) || 0;
            const totalBal  = parseFloat(button.getAttribute('data-total-bal')) || 0;

            document.getElementById('clientLogModalTitleName').textContent = clientName;
            document.getElementById('clientLogModalTitleCode').textContent = clientCode;
            document.getElementById('clientLogModalTitleClientId').textContent = clientIdentifier;
            document.getElementById('modalLogManualBal').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(manualBal);
            document.getElementById('modalLogXenditBal').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(xenditBal);
            document.getElementById('modalLogTotalBal').textContent  = 'Rp ' + new Intl.NumberFormat('id-ID').format(totalBal);

            if (modalFilterEvent) {
                modalFilterEvent.value = '';
            }

            fetchClientLogs(currentLogClientId, '');
        });

        if (modalFilterEvent) {
            modalFilterEvent.addEventListener('change', function() {
                if (currentLogClientId) {
                    fetchClientLogs(currentLogClientId, this.value);
                }
            });
        }
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
