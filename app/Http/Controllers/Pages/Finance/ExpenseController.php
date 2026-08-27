<?php

namespace App\Http\Controllers\Pages\Finance;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\FinanceCategory;
use App\Services\Finance\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $expenseService) {}

    public function index(Request $request)
    {
        $sort = $request->sort ?? 10;
        $search = $request->search;

        $expenses = Expense::query()
            ->with(['category', 'creator'])
            ->when($search, function ($query, $search) {
                $query->where('payee', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('category', fn ($category) => $category->where('name', 'like', "%{$search}%"));
            })
            ->when($request->category_id, fn ($query, $categoryId) => $query->where('finance_category_id', $categoryId))
            ->when($request->date_from, fn ($query, $date) => $query->whereDate('transaction_date', '>=', $date))
            ->when($request->date_to, fn ($query, $date) => $query->whereDate('transaction_date', '<=', $date))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate($sort)
            ->withQueryString();

        $categories = FinanceCategory::where('type', 'expense')->where('is_active', true)->orderBy('name')->get();

        return view('pages.finance.expenses.index', compact('expenses', 'categories'));
    }

    public function create()
    {
        $categories = FinanceCategory::where('type', 'expense')->where('is_active', true)->orderBy('name')->get();

        return view('pages.finance.expenses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        try {
            $this->expenseService->create($this->validated($request), auth()->id());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('expense.index')->with('success', 'Pengeluaran berhasil ditambahkan.');
    }

    public function edit(string $id)
    {
        $expense = Expense::findOrFail($id);
        $categories = FinanceCategory::where('type', 'expense')->where('is_active', true)->orderBy('name')->get();

        return view('pages.finance.expenses.edit', compact('expense', 'categories'));
    }

    public function update(Request $request, string $id)
    {
        $expense = Expense::findOrFail($id);

        try {
            $this->expenseService->update($expense, $this->validated($request), auth()->id());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('expense.index')->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $expense = Expense::find($id);

        if (! $expense) {
            return response()->json(['code' => 400, 'status' => 'error', 'message' => 'Data tidak ditemukan.']);
        }

        $this->expenseService->delete($expense);

        return response()->json(['code' => 200, 'status' => 'success', 'message' => 'Pengeluaran berhasil dihapus.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'transaction_date' => ['required', 'date'],
            'finance_category_id' => [
                'required',
                Rule::exists('finance_categories', 'id')->where('type', 'expense')->where('is_active', true),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'has_admin_fee' => ['required', 'boolean'],
            'admin_fee_amount' => [
                'exclude_unless:has_admin_fee,1',
                'required',
                'numeric',
                'min:0.01',
            ],
            'payee' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
