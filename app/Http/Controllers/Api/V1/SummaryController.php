<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Income;
use App\Services\Finance\FinanceSummaryService;
use App\Services\Support\ApiResponse;
use App\Services\Xendit\XenditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class SummaryController extends Controller
{
    public function __construct(
        private FinanceSummaryService ,
        private XenditService 
    ) {}

    public function index(Request )
    {
         = ->query('date_from') ? Carbon::parse(->query('date_from'))->startOfDay() : now()->startOfMonth();
         = ->query('date_to') ? Carbon::parse(->query('date_to'))->endOfDay() : now()->endOfDay();

         = 'cio_finance_summary_' . ->format('Ymd') . '_' . ->format('Ymd');

        return Cache::remember(, 60, function () use (, ) {
            // 1. Pemasukan Kas Internal
             = (float) Income::whereBetween('transaction_date', [->toDateString(), ->toDateString()])->sum('amount');

            // 2. Pengeluaran Kas Internal
             = (float) Expense::whereBetween('transaction_date', [->toDateString(), ->toDateString()])
                ->sum(DB::raw('amount + COALESCE(admin_fee_amount, 0)'));

            // 3. Log External Activity dari Semua Web (Operasional, Slip Gaji, Investor, dll)
             = 0.0;
             = 0.0;
             = 0.0;
             = 0.0;
             = 0.0;

             = Activity::where('log_name', 'external_finance')
                ->whereBetween('created_at', [, ])
                ->get();

            foreach ( as ) {
                 = ->properties ?? [];
                 = (float) (['amount'] ?? 0);
                 = strtolower(['client_code'] ?? (['client_id'] ?? ''));
                 = strtolower(['channel'] ?? (['balance_type'] ?? 'manual'));
                 = strtolower(->event ?? (['type'] ?? ''));

                if ( === 'income' || (['type'] ?? '') === 'income' ||  === 'refund_balance') {
                    if ( === 'xendit') {
                         += ;
                    } else {
                         += ;
                    }
                } else {
                    if (str_contains(, 'operasional')) {
                         += ;
                    } elseif (str_contains(, 'slip') || str_contains(, 'keuangan') || str_contains(, 'gaji')) {
                         += ;
                    } elseif (str_contains(, 'investor')) {
                         += ;
                    } else {
                        if ( === 'xendit') {
                             += ;
                        } else {
                             += ;
                        }
                    }
                }
            }

            // 4. Data Xendit
             = [
                'total' => 0.0,
                'cash' => 0.0,
                'holding' => 0.0,
                'tax' => 0.0,
                'status' => 'offline',
            ];

            if (->xenditService->isConfigured()) {
                 = Cache::remember('xendit_cached_all_balances', 120, fn () => ->xenditService->getAllBalances());
                if () {
                     = [
                        'total' => (float) (['total'] ?? 0),
                        'cash' => (float) (['cash'] ?? 0),
                        'holding' => (float) (['holding'] ?? 0),
                        'tax' => (float) (['tax'] ?? 0),
                        'status' => 'connected',
                    ];
                }

                 = Cache::remember('xendit_cached_monthly_summary', 120, fn () => ->xenditService->getMonthlySummary());
                if () {
                    if ( == 0) {
                         = (float) (['total_inflow'] ?? 0);
                    }
                    if ( == 0) {
                         = (float) ((['total_outflow'] ?? 0) + (['total_fee'] ?? 0));
                    }
                }
            }

             =  + ;
             =  +  +  +  + ;
             =  - ;
             = ( > 0) ? round(( / ) * 100, 2) : 0;

            return ApiResponse::success('Financial summary retrieved successfully', [
                'total_income' => ,
                'manual_income' => ,
                'xendit_inflow' => ,
                'total_expense' => ,
                'operational_expense' => ,
                'payroll_expense' => ,
                'investor_expense' => ,
                'general_finance_expense' => ,
                'xendit_outflow_and_fees' => ,
                'net_profit' => ,
                'profit_margin_percentage' => ,
                'xendit_balances' => ,
                'period' => ->format('d M Y') . ' - ' . ->format('d M Y'),
            ]);
        });
    }
}
