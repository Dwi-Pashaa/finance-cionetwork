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

<!-- ApexCharts Section -->
<div class="row g-3 mb-4">
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

<!-- Activity Log Feed Section -->
@can('log history')
    <div class="row g-3">
        <div class="col-12">
            <div class="card cio-card">
                <div class="cio-card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div>
                        <h3 class="cio-title d-flex align-items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
                            Log Aktivitas Real-Time & Audit Trail
                        </h3>
                        <div class="cio-subtitle">Riwayat lengkap aktivitas internal sistem dan transaksi integrasi API client</div>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary px-3 py-2 rounded-pill fw-bold">
                        Total {{ $latestActivities->total() }} Aktivitas
                    </span>
                </div>
                <div class="card-body p-3">
                    <div class="cio-log-list">
                        @forelse ($latestActivities as $activity)
                            @php
                                $event = strtolower($activity->event ?: 'created');
                                $isCreated = $event === 'created' || str_contains($event, 'create');
                                $isUpdated = $event === 'updated' || str_contains($event, 'update');
                                $isDeleted = $event === 'deleted' || str_contains($event, 'delete');

                                $eventClass = $isCreated ? 'event-created' : ($isUpdated ? 'event-updated' : ($isDeleted ? 'event-deleted' : 'event-other'));
                                $badgeClass = $isCreated ? 'badge-created' : ($isUpdated ? 'badge-updated' : ($isDeleted ? 'badge-deleted' : 'badge-other'));
                                $avatarClass = $isCreated ? 'success' : ($isUpdated ? 'warning' : ($isDeleted ? 'danger' : 'info'));
                                $eventLabel = $isCreated ? 'TAMBAH' : ($isUpdated ? 'UBAH' : ($isDeleted ? 'HAPUS' : strtoupper($event)));

                                $isExternal = $activity->log_name === 'external_finance';
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
                                    <span class="cio-icon-avatar {{ $avatarClass }}" style="height: 42px; width: 42px;">
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
                                        <!-- Top Row: Action Title & Amount Pill -->
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                                            <div class="fw-bold text-dark fs-6">{{ $activityAction }}</div>
                                            @if ($amount !== null)
                                                <span class="cio-log-amount-pill {{ $isCreated ? 'text-success' : ($isDeleted ? 'text-danger' : 'text-primary') }}">
                                                    {{ $formatMoney($amount) }}
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Middle Row: Event & Source Badges -->
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                            <span class="cio-log-badge {{ $badgeClass }}">{{ $eventLabel }}</span>
                                            <span class="badge {{ $isExternal ? 'bg-info text-white' : 'bg-secondary text-white' }} px-2 py-1" style="font-size: 11px;">
                                                {{ $activitySource }}
                                            </span>
                                            @if($subjectLabel)
                                                <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 11px;">
                                                    Modul: <strong>{{ $subjectLabel }}</strong>
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Bottom Row: Detailed Metadata Grid -->
                                        <div class="d-flex align-items-center gap-2 flex-wrap text-muted small">
                                            <span class="cio-log-meta-item">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/></svg>
                                                Pelaku: <strong class="text-dark">{{ $causer }}</strong>
                                            </span>

                                            @if ($clientName && $clientName !== '-')
                                                <span class="cio-log-meta-item">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21l18 0"/><path d="M9 8l1 0"/><path d="M9 12l1 0"/><path d="M9 16l1 0"/><path d="M14 8l1 0"/><path d="M14 12l1 0"/><path d="M14 16l1 0"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16"/></svg>
                                                    Web Client: <strong class="text-dark">{{ $clientName }}</strong>
                                                </span>
                                            @endif

                                            @if ($subjectId)
                                                <span class="cio-log-meta-item">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"/><path d="M5 12h14"/><path d="M7 17h10"/></svg>
                                                    Ref ID: <code class="text-primary font-monospace">{{ $subjectId }}</code>
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Right Column: Exact Date & Relative Time -->
                                    <div class="text-end text-nowrap ms-md-2">
                                        <div class="badge bg-light text-muted border px-2 py-1 font-monospace" style="font-size: 11px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><rect x="4" y="5" width="16" height="16" rx="2"/><line x1="16" y1="3" x2="16" y2="7"/><line x1="8" y1="3" x2="8" y2="7"/><line x1="4" y1="11" x2="20" y2="11"/></svg>
                                            {{ $activity->created_at?->translatedFormat('d M Y, H:i:s') ?? '-' }}
                                        </div>
                                        <div class="text-muted small fw-medium mt-1">
                                            {{ $activity->created_at?->diffForHumans() ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="cio-empty-state">Belum Ada Log Aktivitas Real-Time</div>
                        @endforelse
                    </div>
                </div>

                <!-- Footer Showing Info & Pagination Links -->
                <div class="card-footer bg-light border-top p-3 d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                    <p class="m-0 text-muted small fw-medium">
                        Menampilkan <strong>{{ $latestActivities->firstItem() ?? 0 }}</strong> - <strong>{{ $latestActivities->lastItem() ?? 0 }}</strong> dari <strong>{{ $latestActivities->total() }}</strong> data log
                    </p>
                    <div class="m-0">
                        {{ $latestActivities->appends(request()->except('log_page'))->links() }}
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

        // ApexChart: Category Breakdown Donut
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
    });
</script>
@endpush

