@extends('layouts.app')

@section('title')
    Dashboard Overview
@endsection

@push('css')
    @include('pages.finance.partials.styles')
@endpush

@section('content')
@php
    use Illuminate\Support\Str;

    $metrics = [
        [
            'label' => 'Pemasukan Bulan Ini',
            'value' => $summary['monthly_income'] ?? 0,
            'class' => 'success',
            'trend' => 'Pemasukan Terverifikasi',
            'icon' => '<path d="M12 19V5"/><path d="M5 12l7-7l7 7"/>',
        ],
        [
            'label' => 'Pengeluaran Bulan Ini',
            'value' => $summary['monthly_expense'] ?? 0,
            'class' => 'danger',
            'trend' => 'Operasional & Biaya',
            'icon' => '<path d="M12 5v14"/><path d="M5 12l7 7l7-7"/>',
        ],
        [
            'label' => 'Net Bulan Ini',
            'value' => $summary['monthly_net'] ?? 0,
            'class' => (($summary['monthly_net'] ?? 0) >= 0) ? 'success' : 'danger',
            'trend' => (($summary['monthly_net'] ?? 0) >= 0) ? 'Surplus Keuangan' : 'Defisit Keuangan',
            'icon' => '<path d="M4 19h16"/><path d="M7 16l4-4l3 3l5-7"/>',
        ],
        [
            'label' => 'Saldo Bersih Kumulatif',
            'value' => $summary['net_balance'] ?? 0,
            'class' => (($summary['net_balance'] ?? 0) >= 0) ? 'info' : 'warning',
            'trend' => 'Total Akumulasi Saldo',
            'icon' => '<path d="M3 21h18"/><path d="M3 10h18"/><path d="M5 6l7-3l7 3"/><path d="M4 10v11"/><path d="M20 10v11"/><path d="M8 14v3"/><path d="M12 14v3"/><path d="M16 14v3"/>',
        ],
    ];

    $formatMoney = function ($value): string {
        if ($value === null || $value === '') {
            return '-';
        }

        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    };

    $formatActivityMessage = function ($activity) use ($formatMoney): string {
        $event = strtolower($activity->event ?: 'created');
        $subject = $activity->subject;
        $properties = $activity->properties instanceof \Illuminate\Support\Collection
            ? $activity->properties->toArray()
            : ($activity->properties ?? []);
        $causer = $activity->causer?->name 
            ?? $properties['user_name'] 
            ?? $properties['causer_name'] 
            ?? $properties['user_email'] 
            ?? 'Administrator';
        $subjectType = class_basename($subject ?: ($activity->getExtraProperty('subject_type') ?? $properties['subject_type'] ?? ''));
        $subjectKey = strtolower($subjectType);
        $clientName = $properties['client_name']
            ?? $properties['client_code']
            ?? $properties['website_name']
            ?? $properties['client_id']
            ?? null;
        $subjectLabel = match ($subjectType) {
            'Income' => 'pemasukan',
            'Expense' => 'pengeluaran',
            'FinanceCategory' => 'kategori transaksi',
            default => trim(Str::of($subjectType)->snake()->replace('_', ' ')->value()) ?: 'data',
        };
        $amount = data_get($subject, 'amount')
            ?? data_get($subject, 'total_amount')
            ?? $activity->getExtraProperty('amount')
            ?? $properties['amount']
            ?? $properties['total_amount']
            ?? $properties['nominal']
            ?? null;
        $subjectName = data_get($subject, 'name')
            ?? data_get($subject, 'category.name')
            ?? $properties['subject_name']
            ?? $properties['name']
            ?? null;
        $paymentLabel = $activity->getExtraProperty('description')
            ?? $activity->description
            ?? null;

        if ($activity->log_name === 'external_finance') {
            $webLabel = $clientName ? "pada {$clientName}" : '';
            $action = $paymentLabel ?: match ($event) {
                'created' => 'melakukan transaksi',
                'updated' => 'memperbarui transaksi',
                'deleted' => 'menghapus transaksi',
                default => 'melakukan aktivitas',
            };

            // If action string already includes amount or money, don't duplicate
            $amountText = ($amount !== null && !str_contains(strtolower($action), 'rp')) 
                ? " sebesar {$formatMoney($amount)}" 
                : '';

            return trim("{$causer} {$webLabel} {$action}{$amountText}");
        }

        if ($subjectType === 'Income' || str_contains($subjectKey, 'income')) {
            $action = match ($event) {
                'created' => 'menambahkan pemasukan',
                'updated' => 'memperbarui pemasukan',
                'deleted' => 'menghapus pemasukan',
                default => 'mengubah pemasukan',
            };
            $amountText = $amount !== null ? " sebesar {$formatMoney($amount)}" : '';
            $subjectText = $subjectName ? " untuk {$subjectName}" : '';
            return "{$causer} {$action}{$amountText}{$subjectText}";
        }

        if ($subjectType === 'Expense' || str_contains($subjectKey, 'expense')) {
            $action = match ($event) {
                'created' => 'menambahkan pengeluaran',
                'updated' => 'memperbarui pengeluaran',
                'deleted' => 'menghapus pengeluaran',
                default => 'mengubah pengeluaran',
            };
            $amountText = $amount !== null ? " sebesar {$formatMoney($amount)}" : '';
            $subjectText = $subjectName ? " untuk {$subjectName}" : '';
            return "{$causer} {$action}{$amountText}{$subjectText}";
        }

        $action = match ($event) {
            'created' => 'menambahkan',
            'updated' => 'memperbarui',
            'deleted' => 'menghapus',
            default => 'melakukan perubahan pada',
        };

        return "{$causer} {$action} {$subjectLabel}";
    };

    $trendData = $summary['monthly_trend'] ?? ['labels' => [], 'incomes' => [], 'expenses' => [], 'nets' => []];
    $categoryData = $summary['category_breakdown'] ?? ['labels' => [], 'totals' => []];
@endphp

<!-- Welcome Hero Banner -->
<div class="cio-hero-banner d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
    <div>
        <h2 class="cio-hero-title">Selamat Datang Kembali, {{ Auth::user()->name ?? 'User' }}</h2>
        <p class="cio-hero-subtitle">
            Ringkasan performa finansial bulan <strong>{{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</strong>. 
            Rasio pengeluaran saat ini: <span class="badge bg-white text-dark fw-bold px-2 py-1 ms-1">{{ $summary['expense_ratio'] ?? 0 }}%</span>
        </p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        {{-- <form action="{{ route('dashboard.seed-dummy') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="cio-hero-btn-outline" style="background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.4);" title="Generate Data Dummy Pemasukan & Pengeluaran">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v-3a3 3 0 0 1 3 -3h13"/><path d="M17 3l3 3l-3 3"/><path d="M20 12v3a3 3 0 0 1 -3 3h-13"/><path d="M7 21l-3 -3l3 -3"/></svg>
                Generate Data Dummy
            </button>
        </form> --}}
        @can('tambah pemasukan')
            <a href="{{ route('income.index') }}" class="cio-hero-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                Pemasukan
            </a>
        @endcan
        @can('tambah pengeluaran')
            <a href="{{ route('expense.index') }}" class="cio-hero-btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Pengeluaran
            </a>
        @endcan
    </div>
</div>

<!-- Section Header: Keuangan Internal Sistem -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="cio-title d-flex align-items-center gap-2 mb-0" style="font-size: 16px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M3 21h18"/><path d="M3 10h18"/><path d="M5 6l7-3l7 3"/><path d="M4 10v11"/><path d="M20 10v11"/></svg>
            Ringkasan Pembukuan Internal
        </h4>
        <div class="cio-subtitle" style="font-size: 12px;">Catatan pembukuan kas pemasukan & pengeluaran operasional di sistem</div>
    </div>
    <span class="badge bg-primary-subtle text-primary border border-primary px-3 py-1.5 rounded-pill fw-bold" style="font-size: 11px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 4px; vertical-align: middle;"><circle cx="12" cy="12" r="12"/></svg>
        PEMBUKUAN INTERNAL
    </span>
</div>

<!-- KPI Metric Cards -->
<div class="row g-3 mb-4">
    @foreach ($metrics as $metric)
        <div class="col-sm-6 col-xl-3">
            <div class="card cio-card cio-metric-card {{ $metric['class'] }} h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <div class="cio-metric-label">{{ $metric['label'] }}</div>
                            <div class="cio-metric-value">Rp {{ number_format($metric['value'], 0, ',', '.') }}</div>
                            <div class="cio-metric-trend text-muted">
                                <span class="badge bg-light text-secondary border fw-medium px-2 py-1">{{ $metric['trend'] }}</span>
                            </div>
                        </div>
                        <span class="cio-icon-avatar {{ $metric['class'] }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $metric['icon'] !!}</svg>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Xendit Balance Widget --}}
@if($xenditConfigured && $xenditBalances)
<div class="card cio-card mb-4">
    <div class="cio-card-header d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <span class="cio-icon-avatar info">
                <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 11l19 -9l-9 19l-2 -8l-8 -2z"/>
                </svg>
            </span>
            <div>
                <h3 class="cio-title">Saldo Xendit</h3>
                <div class="cio-subtitle">Realtime balance akun Xendit payment gateway</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge" style="background: #0ea5e9; color:#fff; font-size: 11px; padding: 5px 12px; border-radius: 9999px; font-weight: 700; letter-spacing: 0.03em;">
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="currentColor" style="margin-right:4px;vertical-align:middle;"><circle cx="12" cy="12" r="12"/></svg>
                XENDIT LIVE
            </span>
            <form method="POST" action="{{ route('xendit.balance.refresh') }}" class="d-inline m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-light d-inline-flex align-items-center gap-1" style="font-size: 12px; font-weight: 600; border: 1px solid #e2e8f0; padding: 5px 12px; border-radius: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v-3a3 3 0 0 1 3 -3h13"/><path d="M17 3l3 3l-3 3"/><path d="M20 12v3a3 3 0 0 1 -3 3h-13"/><path d="M7 21l-3 -3l3 -3"/></svg>
                    Refresh
                </button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- CASH --}}
            <div class="col-sm-4">
                <div class="p-3 rounded-3 h-100 d-flex align-items-center gap-3" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #bfdbfe;">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;background:#2563eb;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12l4 6l-10 13l-10 -13z"/><path d="M8 9l4 10l4 -10"/><path d="M3 9h18"/></svg>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #1e40af;">Cash</div>
                        <div style="font-size: 20px; font-weight: 800; color: #1e3a8a; font-variant-numeric: tabular-nums; letter-spacing: -0.02em;">
                            Rp {{ number_format($xenditBalances['cash'], 0, ',', '.') }}
                        </div>
                        <div style="font-size: 11px; color: #3b82f6; font-weight: 600;">Saldo siap tarik</div>
                    </div>
                </div>
            </div>

            {{-- HOLDING --}}
            <div class="col-sm-4">
                <div class="p-3 rounded-3 h-100 d-flex align-items-center gap-3" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid #fde68a;">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;background:#d97706;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #92400e;">Holding</div>
                        <div style="font-size: 20px; font-weight: 800; color: #78350f; font-variant-numeric: tabular-nums; letter-spacing: -0.02em;">
                            Rp {{ number_format($xenditBalances['holding'], 0, ',', '.') }}
                        </div>
                        <div style="font-size: 11px; color: #d97706; font-weight: 600;">Dana ditahan sementara</div>
                    </div>
                </div>
            </div>

            {{-- TAX --}}
            <div class="col-sm-4">
                <div class="p-3 rounded-3 h-100 d-flex align-items-center gap-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #a7f3d0;">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;background:#059669;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M9 15l2 2l4 -4"/></svg>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #065f46;">Tax</div>
                        <div style="font-size: 20px; font-weight: 800; color: #064e3b; font-variant-numeric: tabular-nums; letter-spacing: -0.02em;">
                            Rp {{ number_format($xenditBalances['tax'], 0, ',', '.') }}
                        </div>
                        <div style="font-size: 11px; color: #059669; font-weight: 600;">Cadangan pajak</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total --}}
        <div class="mt-3 pt-3 d-flex align-items-center justify-content-between" style="border-top: 1px solid #e2e8f0;">
            <div class="d-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M3 10h18"/><path d="M5 6l7-3l7 3"/><path d="M4 10v11"/><path d="M20 10v11"/><path d="M8 14v3"/><path d="M12 14v3"/><path d="M16 14v3"/></svg>
                <span style="font-size: 13px; font-weight: 600; color: #475569;">Total Saldo Xendit</span>
            </div>
            <div style="font-size: 18px; font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; letter-spacing: -0.02em;">
                Rp {{ number_format($xenditBalances['total'], 0, ',', '.') }}
            </div>
        </div>
    </div>
</div>
@elseif(!$xenditConfigured)
<div class="alert d-flex align-items-center gap-3 mb-4" style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 14px 18px;">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.871l-8.106 -13.534a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/></svg>
    <div>
        <div style="font-weight: 700; font-size: 13px; color: #92400e;">Xendit belum dikonfigurasi</div>
        <div style="font-size: 12px; color: #b45309; margin-top: 2px;">Tambahkan <code style="background:#fef3c7;padding:1px 6px;border-radius:4px;font-weight:600;">XENDIT_SECRET_KEY</code> pada berkas <code style="background:#fef3c7;padding:1px 6px;border-radius:4px;font-weight:600;">.env</code> untuk menampilkan saldo Xendit secara realtime.</div>
    </div>
</div>
@endif

<!-- ApexCharts Section with 2 Tabs -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
    <div>
        <h3 class="cio-title d-flex align-items-center gap-2 mb-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
            Analitik & Grafik Finansial
        </h3>
        <div class="cio-subtitle">Visualisasi performa arus kas internal dan mutasi gateway Xendit</div>
    </div>
    
    <ul class="nav nav-pills cio-log-tabs" id="dashboardChartTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-chart-internal-btn" data-bs-toggle="pill" data-bs-target="#tab-chart-internal-pane" type="button" role="tab" aria-controls="tab-chart-internal-pane" aria-selected="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19h16"/><path d="M7 16l4-4l3 3l5-7"/></svg>
                Grafik Internal
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-chart-xendit-btn" data-bs-toggle="pill" data-bs-target="#tab-chart-xendit-pane" type="button" role="tab" aria-controls="tab-chart-xendit-pane" aria-selected="false">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19 -9l-9 19l-2 -8l-8 -2z"/></svg>
                Grafik Xendit
                @if($xenditConfigured)
                    <span class="badge-tab" style="background:#e0f2fe;color:#0284c7;">LIVE</span>
                @endif
            </button>
        </li>
    </ul>
</div>

<div class="tab-content mb-4" id="dashboardChartTabContent">
    {{-- ================= TAB 1: GRAFIK TRANSAKSI INTERNAL ================= --}}
    <div class="tab-pane fade show active" id="tab-chart-internal-pane" role="tabpanel" aria-labelledby="tab-chart-internal-btn" tabindex="0">
        <div class="row g-3">
            <!-- Cash Flow Trend Chart -->
            <div class="col-lg-8">
                <div class="card cio-card h-100">
                    <div class="cio-card-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                        <div>
                            <h3 class="cio-title">Tren Arus Kas & Keuntungan Perusahaan</h3>
                            <div class="cio-subtitle" id="chart-subtitle">Perbandingan Pemasukan, Pengeluaran, & Keuntungan Bersih (6 Bulan Terakhir)</div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="cio-trading-filter" role="tablist" aria-label="Filter Periode Grafik">
                                <button type="button" class="btn-tab" data-period="1hr">1HR</button>
                                <button type="button" class="btn-tab" data-period="7hr">7HR</button>
                                <button type="button" class="btn-tab" data-period="1bln">1BLN</button>
                                <button type="button" class="btn-tab active" data-period="6bln">6BLN</button>
                                <button type="button" class="btn-tab" data-period="ytd">YTD</button>
                                <button type="button" class="btn-tab" data-period="1th">1TH</button>
                                <button type="button" class="btn-tab" data-period="5th">5TH</button>
                                <button type="button" class="btn-tab" data-period="maks">Maks</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-3 flex-wrap mb-2 px-2 pt-1">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-semibold" style="font-size: 11px;">
                                ● Pemasukan
                            </span>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 fw-semibold" style="font-size: 11px;">
                                ● Pengeluaran
                            </span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 fw-semibold" style="font-size: 11px;">
                                ● Keuntungan Perusahaan
                            </span>
                        </div>
                        <div id="chart-cashflow-trend" style="min-height: 320px;"></div>
                    </div>
                </div>
            </div>

            <!-- Category Breakdown Chart -->
            <div class="col-lg-4">
                <div class="card cio-card h-100">
                    <div class="cio-card-header d-flex justify-content-between align-items-center gap-3">
                        <div>
                            <h3 class="cio-title">Distribusi Kategori</h3>
                            <div class="cio-subtitle">Proporsi Pengeluaran per Kategori</div>
                        </div>
                        <span class="cio-icon-avatar warning" style="height: 34px; width: 34px;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 12l4.3 4.3"/><path d="M12 12v-9"/></svg>
                        </span>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center p-3">
                        @if(!empty($categoryData['labels']))
                            <div id="chart-category-pie" class="w-100" style="min-height: 320px;"></div>
                        @else
                            <div class="cio-empty-state">
                                <svg xmlns="http://www.w3.org/2000/svg" class="text-muted mb-2" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 3"/></svg>
                                <div>Belum Ada Data Transaksi Kategori</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= TAB 2: GRAFIK TRANSAKSI XENDIT ================= --}}
    <div class="tab-pane fade" id="tab-chart-xendit-pane" role="tabpanel" aria-labelledby="tab-chart-xendit-btn" tabindex="0">
        @if(!$xenditConfigured)
            <div class="alert d-flex align-items-center gap-3" style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 14px 18px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.871l-8.106 -13.534a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/></svg>
                <div>
                    <div style="font-weight: 700; font-size: 13px; color: #92400e;">Xendit belum dikonfigurasi</div>
                    <div style="font-size: 12px; color: #b45309; margin-top: 2px;">Tambahkan <code style="background:#fef3c7;padding:1px 6px;border-radius:4px;font-weight:600;">XENDIT_SECRET_KEY</code> pada berkas <code style="background:#fef3c7;padding:1px 6px;border-radius:4px;font-weight:600;">.env</code> untuk melihat analitik dan grafik transaksi realtime.</div>
                </div>
            </div>
        @else
            <div class="row g-3">
                <!-- Xendit Inflow vs Outflow Trend Chart -->
                <div class="col-lg-8">
                    <div class="card cio-card h-100">
                        <div class="cio-card-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                            <div>
                                <h3 class="cio-title">Tren Mutasi Dana Xendit</h3>
                                <div class="cio-subtitle" id="chart-xendit-subtitle">Perbandingan Dana Masuk (Payment), Dana Keluar (Payout), & Saldo Bersih (6 Bulan Terakhir)</div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="cio-trading-filter cio-xendit-filter" role="tablist" aria-label="Filter Periode Grafik Xendit">
                                    <button type="button" class="btn-tab" data-period="1hr">1HR</button>
                                    <button type="button" class="btn-tab" data-period="7hr">7HR</button>
                                    <button type="button" class="btn-tab" data-period="1bln">1BLN</button>
                                    <button type="button" class="btn-tab active" data-period="6bln">6BLN</button>
                                    <button type="button" class="btn-tab" data-period="ytd">YTD</button>
                                    <button type="button" class="btn-tab" data-period="1th">1TH</button>
                                    <button type="button" class="btn-tab" data-period="5th">5TH</button>
                                    <button type="button" class="btn-tab" data-period="maks">Maks</button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3 flex-wrap mb-2 px-2 pt-1">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-semibold" style="font-size: 11px;">
                                    ● Dana Masuk (Payment)
                                </span>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 fw-semibold" style="font-size: 11px;">
                                    ● Dana Keluar (Disbursement)
                                </span>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 fw-semibold" style="font-size: 11px;">
                                    ● Net Saldo
                                </span>
                            </div>
                            <div id="chart-xendit-trend" style="min-height: 320px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Xendit Channel Breakdown Donut Chart -->
                <div class="col-lg-4">
                    <div class="card cio-card h-100">
                        <div class="cio-card-header d-flex justify-content-between align-items-center gap-3">
                            <div>
                                <h3 class="cio-title">Distribusi Channel Xendit</h3>
                                <div class="cio-subtitle">Proporsi Transaksi per Channel Pembayaran</div>
                            </div>
                            <span class="cio-icon-avatar info" style="height: 34px; width: 34px;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19 -9l-9 19l-2 -8l-8 -2z"/></svg>
                            </span>
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center p-3">
                            @if(!empty($xenditChartData['channel_breakdown']['labels']))
                                <div id="chart-xendit-channel-pie" class="w-100" style="min-height: 320px;"></div>
                            @else
                                <div class="cio-empty-state">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="text-muted mb-2" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 3"/></svg>
                                    <div>Belum Ada Transaksi Channel Xendit</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Recent Transactions Section -->
<div class="row g-3 mb-4">
    <!-- Pemasukan Terbaru -->
    <div class="col-lg-6">
        <div class="card cio-card h-100">
            <div class="cio-card-header d-flex justify-content-between align-items-center gap-3">
                <div>
                    <h3 class="cio-title text-success d-flex align-items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                        Pemasukan Terbaru
                    </h3>
                    <div class="cio-subtitle">5 transaksi dana masuk terakhir</div>
                </div>
                @can('lihat pemasukan')
                    <a href="{{ route('income.index') }}" class="btn btn-light cio-action-btn" title="Lihat Semua Pemasukan">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6l-6 6"/></svg>
                    </a>
                @endcan
            </div>
            <div class="card-body">
                <div class="cio-recent-list">
                    @forelse (($summary['latest_incomes'] ?? []) as $income)
                        <div class="cio-recent-item">
                            <span class="cio-icon-avatar success">
                                <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                            </span>
                            <div class="flex-fill">
                                <div class="cio-recent-title">{{ $income->category?->name ?? 'Tanpa Kategori' }}</div>
                                <div class="text-muted small">
                                    <span class="badge bg-light text-dark border me-1">{{ $income->source ?? 'Internal' }}</span>
                                    {{ $income->transaction_date->format('d M Y') }}
                                </div>
                            </div>
                            <div class="cio-currency text-success text-end">+ Rp {{ number_format($income->amount, 0, ',', '.') }}</div>
                        </div>
                    @empty
                        <div class="cio-empty-state">Belum Ada Data Pemasukan</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Pengeluaran Terbaru -->
    <div class="col-lg-6">
        <div class="card cio-card h-100">
            <div class="cio-card-header d-flex justify-content-between align-items-center gap-3">
                <div>
                    <h3 class="cio-title text-danger d-flex align-items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/></svg>
                        Pengeluaran Terbaru
                    </h3>
                    <div class="cio-subtitle">5 transaksi biaya terakhir</div>
                </div>
                @can('lihat pengeluaran')
                    <a href="{{ route('expense.index') }}" class="btn btn-light cio-action-btn" title="Lihat Semua Pengeluaran">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6l-6 6"/></svg>
                    </a>
                @endcan
            </div>
            <div class="card-body">
                <div class="cio-recent-list">
                    @forelse (($summary['latest_expenses'] ?? []) as $expense)
                        <div class="cio-recent-item">
                            <span class="cio-icon-avatar danger">
                                <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/></svg>
                            </span>
                            <div class="flex-fill">
                                <div class="cio-recent-title">{{ $expense->category?->name ?? 'Tanpa Kategori' }}</div>
                                <div class="text-muted small">
                                    <span class="badge bg-light text-dark border me-1">{{ $expense->payee ?? 'Penerima -' }}</span>
                                    {{ $expense->transaction_date->format('d M Y') }}
                                </div>
                            </div>
                            <div class="cio-currency text-danger text-end">- Rp {{ number_format($expense->total_amount, 0, ',', '.') }}</div>
                        </div>
                    @empty
                        <div class="cio-empty-state">Belum Ada Data Pengeluaran</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Activity Log & Xendit Feed Section (Tabbed) -->
@can('log history')
    <div class="row g-3">
        <div class="col-12">
            <div class="card cio-card">
                <div class="cio-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h3 class="cio-title d-flex align-items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
                            Log Aktivitas & Transaksi
                        </h3>
                        <div class="cio-subtitle">Pantau aktivitas internal sistem dan riwayat transaksi gateway Xendit</div>
                    </div>
                    
                    {{-- Nav Tabs Switcher --}}
                    <ul class="nav nav-pills cio-log-tabs" id="dashboardLogTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-internal-btn" data-bs-toggle="pill" data-bs-target="#tab-internal-pane" type="button" role="tab" aria-controls="tab-internal-pane" aria-selected="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6"/><path d="M12 9v6"/><path d="M4 6v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-12a2 2 0 0 0 -2 2z"/></svg>
                                Log Internal
                                <span class="badge-tab">{{ $latestActivities->total() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-xendit-btn" data-bs-toggle="pill" data-bs-target="#tab-xendit-pane" type="button" role="tab" aria-controls="tab-xendit-pane" aria-selected="false">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19 -9l-9 19l-2 -8l-8 -2z"/></svg>
                                Transaksi Xendit
                                @if($xenditConfigured)
                                    <span class="badge-tab" style="background:#e0f2fe;color:#0284c7;">{{ $xenditTransactions->count() }}</span>
                                @endif
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content" id="dashboardLogTabContent">
                    {{-- ================= TAB 1: LOG INTERNAL ================= --}}
                    <div class="tab-pane fade show active" id="tab-internal-pane" role="tabpanel" aria-labelledby="tab-internal-btn" tabindex="0">
                        <div class="card-body p-3">
                            <div class="cio-log-list">
                                @forelse ($latestActivities as $activity)
                                    @php
                                        $event = strtolower($activity->event ?: 'created');
                                        $isCreated = $event === 'created' || str_contains($event, 'create');
                                        $isUpdated = $event === 'updated' || str_contains($event, 'update');
                                        $isDeleted = $event === 'deleted' || str_contains($event, 'delete');
                                        $eventClass  = $isCreated ? 'event-created' : ($isUpdated ? 'event-updated' : ($isDeleted ? 'event-deleted' : 'event-other'));
                                        $badgeClass  = $isCreated ? 'badge-created' : ($isUpdated ? 'badge-updated' : ($isDeleted ? 'badge-deleted' : 'badge-other'));
                                        $avatarClass = $isCreated ? 'success' : ($isUpdated ? 'warning' : ($isDeleted ? 'danger' : 'info'));
                                        $eventLabel  = $isCreated ? 'TAMBAH' : ($isUpdated ? 'UBAH' : ($isDeleted ? 'HAPUS' : strtoupper($event)));
                                        $isExternal  = $activity->log_name === 'external_finance';
                                        $activitySource = $isExternal ? 'Website Client (API)' : 'Internal App';
                                        $properties = $activity->properties instanceof \Illuminate\Support\Collection
                                            ? $activity->properties->toArray()
                                            : ($activity->properties ?? []);
                                        $causer = $activity->causer?->name
                                            ?? $properties['user_name']
                                            ?? $properties['causer_name']
                                            ?? $properties['user_email']
                                            ?? 'Sistem / API';
                                        $clientName = $properties['client_name']
                                            ?? $properties['client_code']
                                            ?? $properties['website_name']
                                            ?? $properties['client_id']
                                            ?? ($isExternal ? 'Client External' : null);
                                        $subjectType = class_basename($activity->subject ?: ($activity->getExtraProperty('subject_type') ?? ''));
                                        $subjectLabel = match ($subjectType) {
                                            'Income' => 'Pemasukan',
                                            'Expense' => 'Pengeluaran',
                                            'FinanceCategory' => 'Kategori Keuangan',
                                            'User' => 'Pengguna',
                                            'Role' => 'Role / Level',
                                            default => $subjectType ?: 'Data',
                                        };
                                        $subjectId = $activity->getExtraProperty('subject_external_id')
                                            ?? $activity->properties['subject_external_id']
                                            ?? $activity->subject_id
                                            ?? null;
                                        $amount = data_get($activity->subject, 'amount')
                                            ?? data_get($activity->subject, 'total_amount')
                                            ?? $activity->getExtraProperty('amount')
                                            ?? $properties['attributes']['amount']
                                            ?? $properties['attributes']['total_amount']
                                            ?? $properties['amount']
                                            ?? $properties['total_amount']
                                            ?? $properties['nominal']
                                            ?? null;
                                        $activityAction = $formatActivityMessage($activity);
                                    @endphp
                                    <div class="cio-log-item {{ $eventClass }}">
                                        <div class="d-flex align-items-start gap-3">
                                            <span class="cio-icon-avatar {{ $avatarClass }}" style="height:42px;width:42px;">
                                                @if($isCreated)
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                                                @elseif($isUpdated)
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4"/></svg>
                                                @elseif($isDeleted)
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/></svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 3"/></svg>
                                                @endif
                                            </span>
                                            <div class="flex-fill">
                                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                                                    <div class="fw-bold text-dark fs-6">{{ $activityAction }}</div>
                                                    @if($amount !== null)
                                                        <span class="cio-log-amount-pill {{ $isCreated ? 'text-success' : ($isDeleted ? 'text-danger' : 'text-primary') }}">
                                                            {{ $formatMoney($amount) }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                                    <span class="cio-log-badge {{ $badgeClass }}">{{ $eventLabel }}</span>
                                                    <span class="badge {{ $isExternal ? 'bg-info text-white' : 'bg-secondary text-white' }} px-2 py-1" style="font-size:11px;">{{ $activitySource }}</span>
                                                    @if($subjectLabel)
                                                        <span class="badge bg-light text-dark border px-2 py-1" style="font-size:11px;">Modul: <strong>{{ $subjectLabel }}</strong></span>
                                                    @endif
                                                </div>
                                                <div class="d-flex align-items-center gap-2 flex-wrap text-muted small">
                                                    <span class="cio-log-meta-item">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/></svg>
                                                        Pelaku: <strong class="text-dark">{{ $causer }}</strong>
                                                    </span>
                                                    @if($clientName && $clientName !== '-')
                                                        <span class="cio-log-meta-item">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21l18 0"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16"/></svg>
                                                            Web Client: <strong class="text-dark">{{ $clientName }}</strong>
                                                        </span>
                                                    @endif
                                                    @if($subjectId)
                                                        <span class="cio-log-meta-item">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"/><path d="M5 12h14"/><path d="M7 17h10"/></svg>
                                                            Ref ID: <code class="text-primary font-monospace">{{ $subjectId }}</code>
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="text-end text-nowrap ms-md-2">
                                                <div class="badge bg-light text-muted border px-2 py-1 font-monospace" style="font-size:11px;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><rect x="4" y="5" width="16" height="16" rx="2"/><line x1="16" y1="3" x2="16" y2="7"/><line x1="8" y1="3" x2="8" y2="7"/><line x1="4" y1="11" x2="20" y2="11"/></svg>
                                                    {{ $activity->created_at?->translatedFormat('d M Y, H:i:s') ?? '-' }}
                                                </div>
                                                <div class="text-muted small fw-medium mt-1">{{ $activity->created_at?->diffForHumans() ?? '-' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="cio-empty-state">Belum Ada Log Aktivitas Internal</div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Footer Pagination -->
                        <div class="card-footer bg-light border-top p-3 d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                            <p class="m-0 text-muted small fw-medium">
                                Menampilkan <strong>{{ $latestActivities->firstItem() ?? 0 }}</strong> - <strong>{{ $latestActivities->lastItem() ?? 0 }}</strong> dari <strong>{{ $latestActivities->total() }}</strong> log internal
                            </p>
                            <div class="m-0">
                                {{ $latestActivities->appends(request()->except('log_page'))->links() }}
                            </div>
                        </div>
                    </div>

                    {{-- ================= TAB 2: TRANSAKSI XENDIT ================= --}}
                    <div class="tab-pane fade" id="tab-xendit-pane" role="tabpanel" aria-labelledby="tab-xendit-btn" tabindex="0">
                        <div class="card-body p-3">
                            @if(!$xenditConfigured)
                                <div class="alert d-flex align-items-center gap-3 my-2" style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 14px 18px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.871l-8.106 -13.534a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/></svg>
                                    <div>
                                        <div style="font-weight: 700; font-size: 13px; color: #92400e;">Xendit belum dikonfigurasi</div>
                                        <div style="font-size: 12px; color: #b45309; margin-top: 2px;">Tambahkan <code style="background:#fef3c7;padding:1px 6px;border-radius:4px;font-weight:600;">XENDIT_SECRET_KEY</code> pada berkas <code style="background:#fef3c7;padding:1px 6px;border-radius:4px;font-weight:600;">.env</code> untuk memuat riwayat transaksi realtime.</div>
                                    </div>
                                </div>
                            @else
                                <div class="d-flex align-items-center justify-content-between mb-3 px-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge" style="background: #0ea5e9; color:#fff; font-size: 11px; padding: 5px 12px; border-radius: 9999px; font-weight: 700; letter-spacing: 0.03em;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="currentColor" style="margin-right:4px;vertical-align:middle;"><circle cx="12" cy="12" r="12"/></svg>
                                            XENDIT LIVE FEED
                                        </span>
                                        <span class="text-muted small">Riwayat 10 transaksi terakhir</span>
                                    </div>
                                    <form method="POST" action="{{ route('xendit.balance.refresh') }}" class="d-inline m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-light d-inline-flex align-items-center gap-1" style="font-size: 12px; font-weight: 600; border: 1px solid #e2e8f0; padding: 5px 12px; border-radius: 8px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v-3a3 3 0 0 1 3 -3h13"/><path d="M17 3l3 3l-3 3"/><path d="M20 12v3a3 3 0 0 1 -3 3h-13"/><path d="M7 21l-3 -3l3 -3"/></svg>
                                            Refresh Data
                                        </button>
                                    </form>
                                </div>

                                <div class="cio-log-list">
                                    @forelse($xenditTransactions as $xTrx)
                                        @php
                                            $xIsIncome    = $xTrx->is_income;
                                            $xStatus      = $xTrx->status;
                                            $xSuccess     = $xStatus === 'SUCCESS';
                                            $xPending     = $xStatus === 'PENDING';
                                            $xFailed      = in_array($xStatus, ['FAILED','VOIDED','EXPIRED']);
                                            $xEventClass  = $xSuccess ? ($xIsIncome ? 'event-created' : 'event-updated') : ($xPending ? 'event-other' : 'event-deleted');
                                            $xAvatarClass = $xSuccess ? ($xIsIncome ? 'success' : 'info') : ($xPending ? 'warning' : 'danger');
                                            $xBadgeClass  = $xSuccess ? ($xIsIncome ? 'badge-created' : 'badge-updated') : ($xPending ? 'badge-other' : 'badge-deleted');
                                        @endphp
                                        <div class="cio-log-item {{ $xEventClass }}" style="border-left-color: {{ $xIsIncome ? '#10B981' : '#EF4444' }};">
                                            <div class="d-flex align-items-start gap-3">
                                                <span class="cio-icon-avatar {{ $xAvatarClass }}" style="height:42px;width:42px;">
                                                    @if($xIsIncome)
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="M5 12l7-7l7 7"/></svg>
                                                    @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12l7 7l7-7"/></svg>
                                                    @endif
                                                </span>
                                                <div class="flex-fill">
                                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                                                        <div class="fw-bold text-dark fs-6">{{ $xTrx->message ?? $xTrx->description }}</div>
                                                        <span class="cio-log-amount-pill {{ $xIsIncome ? 'text-success' : 'text-danger' }}">
                                                            {{ $xIsIncome ? '+' : '-' }} Rp {{ number_format($xTrx->net_amount, 0, ',', '.') }}
                                                        </span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                                        <span class="cio-log-badge {{ $xBadgeClass }}">{{ $xTrx->action_label ?? $xTrx->type }}</span>
                                                        <span class="badge" style="background:#0ea5e9;color:#fff;font-size:11px;padding:3px 8px;">Gateway Xendit</span>
                                                        <span class="badge px-2 py-1" style="font-size:11px;background:{{ $xSuccess ? '#ecfdf5' : ($xPending ? '#fffbeb' : '#fef2f2') }};color:{{ $xSuccess ? '#059669' : ($xPending ? '#d97706' : '#dc2626') }};border:1px solid {{ $xSuccess ? '#a7f3d0' : ($xPending ? '#fde68a' : '#fecaca') }}">
                                                            Status: <strong>{{ $xStatus }}</strong>
                                                        </span>
                                                        @if(!empty($xTrx->channel) && $xTrx->channel !== '-')
                                                            <span class="badge bg-light text-dark border px-2 py-1" style="font-size:11px;">
                                                                Channel: <strong>{{ $xTrx->channel }}</strong>
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 flex-wrap text-muted small">
                                                        <span class="cio-log-meta-item">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19 -9l-9 19l-2 -8l-8 -2z"/></svg>
                                                            Sumber: <strong class="text-dark">Xendit API</strong>
                                                        </span>
                                                        @if($xTrx->reference_id)
                                                            <span class="cio-log-meta-item">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"/><path d="M5 12h14"/><path d="M7 17h10"/></svg>
                                                                Ref ID: <code class="text-primary font-monospace">{{ $xTrx->reference_id }}</code>
                                                            </span>
                                                        @endif
                                                        @if($xTrx->fee > 0)
                                                            <span class="cio-log-meta-item">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M10 14.5s.5 1.5 2 1.5 2.5-1 2.5-2.5-1-2-2.5-2-2-1-2-2 .5-2.5 2-2.5 2 1.5 2 1.5"/><path d="M12 7v1m0 8v1"/></svg>
                                                                Fee: <strong class="text-dark">Rp {{ number_format($xTrx->fee, 0, ',', '.') }}</strong>
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="text-end text-nowrap ms-md-2">
                                                    <div class="badge bg-light text-muted border px-2 py-1 font-monospace" style="font-size:11px;">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><rect x="4" y="5" width="16" height="16" rx="2"/><line x1="16" y1="3" x2="16" y2="7"/><line x1="8" y1="3" x2="8" y2="7"/><line x1="4" y1="11" x2="20" y2="11"/></svg>
                                                        {{ $xTrx->created_at->translatedFormat('d M Y, H:i') }}
                                                    </div>
                                                    <div class="text-muted small fw-medium mt-1">{{ $xTrx->created_at->diffForHumans() }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="cio-empty-state">Belum Ada Transaksi Xendit Terbaru</div>
                                    @endforelse
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endcan

@endsection

@push('js')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // ApexChart: Cash Flow & Profit Trend (Trading Multi-Period Filter)
        var chartTrends = @json($summary['chart_trends'] ?? []);
        
        var getSeriesData = function(period) {
            var data = chartTrends[period] || chartTrends['6bln'] || { labels: [], incomes: [], expenses: [], profits: [] };
            return {
                labels: data.labels || [],
                series: [
                    { name: 'Pemasukan', data: data.incomes || [] },
                    { name: 'Pengeluaran', data: data.expenses || [] },
                    { name: 'Keuntungan Perusahaan', data: data.profits || [] }
                ]
            };
        };

        var initialData = getSeriesData('6bln');
        
        var optionsCashflow = {
            series: initialData.series,
            chart: {
                type: 'area',
                height: 330,
                toolbar: { show: false },
                fontFamily: 'Plus Jakarta Sans, sans-serif',
                animations: { enabled: true, easing: 'easeinout', speed: 600 }
            },
            colors: ['#10B981', '#EF4444', '#2563EB'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            xaxis: {
                categories: initialData.labels,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: { style: { colors: '#64748B', fontSize: '12px', fontWeight: 600 } }
            },
            yaxis: {
                labels: {
                    style: { colors: '#64748B', fontSize: '12px' },
                    formatter: function (val) {
                        if (Math.abs(val) >= 1000000000) return 'Rp ' + (val / 1000000000).toFixed(1) + ' B';
                        if (Math.abs(val) >= 1000000) return 'Rp ' + (val / 1000000).toFixed(1) + ' M';
                        if (Math.abs(val) >= 1000) return 'Rp ' + (val / 1000).toFixed(0) + ' k';
                        return 'Rp ' + val;
                    }
                }
            },
            grid: { strokeDashArray: 4, borderColor: '#E2E8F0' },
            tooltip: {
                theme: 'light',
                y: {
                    formatter: function (val) {
                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                    }
                }
            },
            legend: { show: false }
        };

        var chartCashflow = new ApexCharts(document.querySelector("#chart-cashflow-trend"), optionsCashflow);
        chartCashflow.render();

        // Trading Period Tab click handler
        $(".cio-trading-filter .btn-tab").on("click", function () {
            $(".cio-trading-filter .btn-tab").removeClass("active");
            $(this).addClass("active");

            var period = $(this).data("period");
            var periodData = getSeriesData(period);

            var subtitles = {
                '1hr': 'Perbandingan Pemasukan, Pengeluaran, & Keuntungan Bersih (Hari Ini)',
                '7hr': 'Perbandingan Pemasukan, Pengeluaran, & Keuntungan Bersih (7 Hari Terakhir)',
                '1bln': 'Perbandingan Pemasukan, Pengeluaran, & Keuntungan Bersih (30 Hari Terakhir)',
                '6bln': 'Perbandingan Pemasukan, Pengeluaran, & Keuntungan Bersih (6 Bulan Terakhir)',
                'ytd': 'Perbandingan Pemasukan, Pengeluaran, & Keuntungan Bersih (Tahun Ini - YTD)',
                '1th': 'Perbandingan Pemasukan, Pengeluaran, & Keuntungan Bersih (12 Bulan Terakhir)',
                '5th': 'Perbandingan Pemasukan, Pengeluaran, & Keuntungan Bersih (5 Tahun Terakhir)',
                'maks': 'Perbandingan Pemasukan, Pengeluaran, & Keuntungan Bersih (Keseluruhan / All-Time)'
            };

            $("#chart-subtitle").text(subtitles[period] || subtitles['6bln']);

            chartCashflow.updateOptions({
                xaxis: { categories: periodData.labels }
            }, false, true);

            chartCashflow.updateSeries(periodData.series, true);
        });

        // ApexChart: Category Breakdown Donut (Internal)
        var catLabels = @json($categoryData['labels']);
        var catTotals = @json($categoryData['totals']);

        if (catLabels && catLabels.length > 0) {
            var optionsCategory = {
                series: catTotals,
                labels: catLabels,
                chart: {
                    type: 'donut',
                    height: 300,
                    fontFamily: 'Plus Jakarta Sans, sans-serif'
                },
                colors: ['#2563EB', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
                legend: {
                    position: 'bottom',
                    fontSize: '12px',
                    fontWeight: 600,
                    markers: { width: 10, height: 10, radius: 12 }
                },
                dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: 700 } },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '68%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total Top',
                                    formatter: function (w) {
                                        const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        if (sum >= 1000000) return (sum / 1000000).toFixed(1) + ' Jt';
                                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(sum);
                                    }
                                }
                            }
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                        }
                    }
                }
            };

            var chartCategory = new ApexCharts(document.querySelector("#chart-category-pie"), optionsCategory);
            chartCategory.render();
        }

        // ================= XENDIT CHARTS =================
        @if($xenditConfigured && !empty($xenditChartData))
            var xenditChartTrends = @json($xenditChartData['chart_trends'] ?? []);
            
            var getXenditSeriesData = function(period) {
                var data = xenditChartTrends[period] || xenditChartTrends['6bln'] || { labels: [], inflow: [], outflow: [], net: [] };
                return {
                    labels: data.labels || [],
                    series: [
                        { name: 'Dana Masuk (Payment)', data: data.inflow || [] },
                        { name: 'Dana Keluar (Disbursement)', data: data.outflow || [] },
                        { name: 'Net Saldo', data: data.net || [] }
                    ]
                };
            };

            var initialXenditData = getXenditSeriesData('6bln');
            
            var optionsXenditTrend = {
                series: initialXenditData.series,
                chart: {
                    type: 'area',
                    height: 330,
                    toolbar: { show: false },
                    fontFamily: 'Plus Jakarta Sans, sans-serif',
                    animations: { enabled: true, easing: 'easeinout', speed: 600 }
                },
                colors: ['#10B981', '#EF4444', '#0EA5E9'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.35,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                xaxis: {
                    categories: initialXenditData.labels,
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: { style: { colors: '#64748B', fontSize: '12px', fontWeight: 600 } }
                },
                yaxis: {
                    labels: {
                        style: { colors: '#64748B', fontSize: '12px' },
                        formatter: function (val) {
                            if (Math.abs(val) >= 1000000000) return 'Rp ' + (val / 1000000000).toFixed(1) + ' B';
                            if (Math.abs(val) >= 1000000) return 'Rp ' + (val / 1000000).toFixed(1) + ' M';
                            if (Math.abs(val) >= 1000) return 'Rp ' + (val / 1000).toFixed(0) + ' k';
                            return 'Rp ' + val;
                        }
                    }
                },
                grid: { strokeDashArray: 4, borderColor: '#E2E8F0' },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function (val) {
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                        }
                    }
                },
                legend: { show: false }
            };

            var xenditTrendEl = document.querySelector("#chart-xendit-trend");
            if (xenditTrendEl) {
                var chartXenditTrend = new ApexCharts(xenditTrendEl, optionsXenditTrend);
                chartXenditTrend.render();

                // Xendit Trading Period Tab click handler
                $(".cio-xendit-filter .btn-tab").on("click", function () {
                    $(".cio-xendit-filter .btn-tab").removeClass("active");
                    $(this).addClass("active");

                    var period = $(this).data("period");
                    var periodData = getXenditSeriesData(period);

                    var xenditSubtitles = {
                        '1hr': 'Perbandingan Dana Masuk, Keluar, & Saldo Bersih Xendit (Hari Ini)',
                        '7hr': 'Perbandingan Dana Masuk, Keluar, & Saldo Bersih Xendit (7 Hari Terakhir)',
                        '1bln': 'Perbandingan Dana Masuk, Keluar, & Saldo Bersih Xendit (30 Hari Terakhir)',
                        '6bln': 'Perbandingan Dana Masuk, Keluar, & Saldo Bersih Xendit (6 Bulan Terakhir)',
                        'ytd': 'Perbandingan Dana Masuk, Keluar, & Saldo Bersih Xendit (Tahun Ini - YTD)',
                        '1th': 'Perbandingan Dana Masuk, Keluar, & Saldo Bersih Xendit (12 Bulan Terakhir)',
                        '5th': 'Perbandingan Dana Masuk, Keluar, & Saldo Bersih Xendit (5 Tahun Terakhir)',
                        'maks': 'Perbandingan Dana Masuk, Keluar, & Saldo Bersih Xendit (Keseluruhan / All-Time)'
                    };

                    $("#chart-xendit-subtitle").text(xenditSubtitles[period] || xenditSubtitles['6bln']);

                    chartXenditTrend.updateOptions({
                        xaxis: { categories: periodData.labels }
                    }, false, true);

                    chartXenditTrend.updateSeries(periodData.series, true);
                });
            }

            // Xendit Channel Breakdown Donut
            var xenditChanLabels = @json($xenditChartData['channel_breakdown']['labels'] ?? []);
            var xenditChanTotals = @json($xenditChartData['channel_breakdown']['totals'] ?? []);

            if (xenditChanLabels && xenditChanLabels.length > 0) {
                var optionsXenditChannel = {
                    series: xenditChanTotals,
                    labels: xenditChanLabels,
                    chart: {
                        type: 'donut',
                        height: 300,
                        fontFamily: 'Plus Jakarta Sans, sans-serif'
                    },
                    colors: ['#0EA5E9', '#2563EB', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'],
                    legend: {
                        position: 'bottom',
                        fontSize: '12px',
                        fontWeight: 600,
                        markers: { width: 10, height: 10, radius: 12 }
                    },
                    dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: 700 } },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '68%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'Total Xendit',
                                        formatter: function (w) {
                                            const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                            if (sum >= 1000000) return (sum / 1000000).toFixed(1) + ' Jt';
                                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(sum);
                                        }
                                    }
                                }
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                            }
                        }
                    }
                };

                var xenditChannelEl = document.querySelector("#chart-xendit-channel-pie");
                if (xenditChannelEl) {
                    var chartXenditChannel = new ApexCharts(xenditChannelEl, optionsXenditChannel);
                    chartXenditChannel.render();
                }
            }
        @endif

        // Trigger chart resize on Tab Switch to prevent ApexChart 0px width issues
        document.querySelectorAll('button[data-bs-toggle="pill"]').forEach(function(btn) {
            btn.addEventListener('shown.bs.tab', function() {
                window.dispatchEvent(new Event('resize'));
            });
        });
    });
</script>
@endpush

