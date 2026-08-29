<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceSummaryService;
use App\Services\Xendit\XenditService;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function __construct(
        private FinanceSummaryService $summaryService,
        private XenditService $xenditService
    ) {}

    public function index()
    {
        $xenditConfigured   = $this->xenditService->isConfigured();
        $xenditBalances     = $xenditConfigured ? $this->xenditService->getAllBalances() : null;
        $xenditTransactions = $xenditConfigured ? $this->xenditService->getRecentTransactions(10) : collect();
        $xenditChartData    = $xenditConfigured ? $this->xenditService->getXenditChartData() : null;

        // Paginated activities (for pagination UI)
        $latestActivities = Activity::query()
            ->whereIn('log_name', ['finance', 'external_finance'])
            ->with(['causer', 'subject'])
            ->latest('id')
            ->paginate(10, ['*'], 'log_page');

        return view('pages.dashboard', [
            'summary'            => $this->summaryService->dashboard(),
            'xenditConfigured'   => $xenditConfigured,
            'xenditBalances'     => $xenditBalances,
            'xenditTransactions' => $xenditTransactions,
            'xenditChartData'    => $xenditChartData,
            'latestActivities'   => $latestActivities,
        ]);
    }

    /**
     * Hapus cache saldo & transaksi Xendit dan redirect kembali ke dashboard.
     */
    public function refreshXenditBalance()
    {
        $this->xenditService->clearCache();

        return redirect()->route('dashboard')->with('success', 'Data & Saldo Xendit berhasil diperbarui!');
    }

    public function seedDummyData()
    {
        $incomeCategories = \App\Models\FinanceCategory::where('type', 'income')->pluck('id')->toArray();
        $expenseCategories = \App\Models\FinanceCategory::where('type', 'expense')->pluck('id')->toArray();

        if (empty($incomeCategories)) {
            $cat = \App\Models\FinanceCategory::create(['type' => 'income', 'name' => 'Pendapatan Client', 'is_active' => true]);
            $incomeCategories = [$cat->id];
        }
        if (empty($expenseCategories)) {
            $cat = \App\Models\FinanceCategory::create(['type' => 'expense', 'name' => 'Operasional Server', 'is_active' => true]);
            $expenseCategories = [$cat->id];
        }

        $now = \Carbon\Carbon::now();

        // 1. Generate hourly dummy data for Today (1HR filter)
        $hourlySamples = [
            ['hour' => 8, 'inc' => 1500000, 'exp' => 250000, 'inc_desc' => 'Pembayaran Tagihan Client Web', 'exp_desc' => 'Biaya Server Cloud Storage'],
            ['hour' => 10, 'inc' => 3200000, 'exp' => 600000, 'inc_desc' => 'Top Up Saldo API Client', 'exp_desc' => 'Pembelian Lisensi Domain & SSL'],
            ['hour' => 13, 'inc' => 5000000, 'exp' => 1200000, 'inc_desc' => 'Termin Project Web Development', 'exp_desc' => 'Pembayaran Konsultan IT'],
            ['hour' => 16, 'inc' => 2800000, 'exp' => 450000, 'inc_desc' => 'Langganan Service Integrasi', 'exp_desc' => 'Operasional Internet & Listrik'],
            ['hour' => 19, 'inc' => 4100000, 'exp' => 750000, 'inc_desc' => 'Pembayaran Maintenance Bulanan', 'exp_desc' => 'Biaya Admin Gateway & Transfer'],
        ];

        foreach ($hourlySamples as $sample) {
            $time = $now->copy()->setTime($sample['hour'], rand(10, 50), 0);

            // Income
            $incModel = \App\Models\Income::create([
                'finance_category_id' => $incomeCategories[array_rand($incomeCategories)],
                'amount' => $sample['inc'],
                'source' => 'Transfer Bank',
                'description' => $sample['inc_desc'],
                'transaction_date' => $time->toDateString(),
                'created_at' => $time,
                'updated_at' => $time,
            ]);

            activity('external_finance')
                ->performedOn($incModel)
                ->createdAt($time)
                ->withProperties([
                    'client_name' => 'Web Client CIO',
                    'amount' => $sample['inc'],
                    'subject_external_id' => 'INC-' . rand(1000, 9999),
                    'source' => 'Transfer',
                ])
                ->log($sample['inc_desc']);

            // Expense
            $expModel = \App\Models\Expense::create([
                'finance_category_id' => $expenseCategories[array_rand($expenseCategories)],
                'amount' => $sample['exp'],
                'admin_fee_amount' => 6500,
                'payee' => 'Vendor Infrastructure',
                'description' => $sample['exp_desc'],
                'transaction_date' => $time->toDateString(),
                'created_at' => $time,
                'updated_at' => $time,
            ]);

            activity('finance')
                ->performedOn($expModel)
                ->createdAt($time)
                ->withProperties([
                    'amount' => $sample['exp'],
                    'user_name' => \Illuminate\Support\Facades\Auth::user()->name ?? 'Administrator',
                ])
                ->log($sample['exp_desc']);
        }

        // 2. Generate daily data for past 7 days (7HR filter)
        for ($d = 1; $d <= 7; $d++) {
            $dTime = $now->copy()->subDays($d);
            $incAmt = rand(25, 60) * 100000;
            $expAmt = rand(10, 30) * 100000;

            \App\Models\Income::create([
                'finance_category_id' => $incomeCategories[array_rand($incomeCategories)],
                'amount' => $incAmt,
                'source' => 'Virtual Account',
                'description' => 'Pendapatan Transaksi Harian tgl ' . $dTime->format('d M'),
                'transaction_date' => $dTime->toDateString(),
                'created_at' => $dTime,
                'updated_at' => $dTime,
            ]);

            \App\Models\Expense::create([
                'finance_category_id' => $expenseCategories[array_rand($expenseCategories)],
                'amount' => $expAmt,
                'admin_fee_amount' => 5000,
                'payee' => 'Mitra Layanan',
                'description' => 'Pengeluaran Operasional Harian tgl ' . $dTime->format('d M'),
                'transaction_date' => $dTime->toDateString(),
                'created_at' => $dTime,
                'updated_at' => $dTime,
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Berhasil membuat 10+ data dummy Pemasukan & Pengeluaran!');
    }
}
