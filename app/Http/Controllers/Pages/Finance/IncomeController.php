<?php

namespace App\Http\Controllers\Pages\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceCategory;
use App\Models\Income;
use App\Services\Finance\IncomeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncomeController extends Controller
{
    public function __construct(private IncomeService $incomeService) {}

    public function index(Request $request)
    {
        $sort = $request->sort ?? 10;
        $search = $request->search;

        $incomes = Income::query()
            ->with(['category', 'creator'])
            ->when($search, function ($query, $search) {
                $query->where('source', 'like', "%{$search}%")
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

        $categories = FinanceCategory::where('type', 'income')->where('is_active', true)->orderBy('name')->get();

        return view('pages.finance.incomes.index', compact('incomes', 'categories'));
    }

    public function create()
    {
        $categories = FinanceCategory::where('type', 'income')->where('is_active', true)->orderBy('name')->get();

        return view('pages.finance.incomes.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->incomeService->create($this->validated($request), auth()->id());

        return redirect()->route('income.index')->with('success', 'Pemasukan berhasil ditambahkan.');
    }

    public function edit(string $id)
    {
        $income = Income::findOrFail($id);
        $categories = FinanceCategory::where('type', 'income')->where('is_active', true)->orderBy('name')->get();

        return view('pages.finance.incomes.edit', compact('income', 'categories'));
    }

    public function update(Request $request, string $id)
    {
        $income = Income::findOrFail($id);

        $this->incomeService->update($income, $this->validated($request), auth()->id());

        return redirect()->route('income.index')->with('success', 'Pemasukan berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $income = Income::find($id);

        if (! $income) {
            return response()->json(['code' => 400, 'status' => 'error', 'message' => 'Data tidak ditemukan.']);
        }

        $this->incomeService->delete($income);

        return response()->json(['code' => 200, 'status' => 'success', 'message' => 'Pemasukan berhasil dihapus.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'transaction_date' => ['required', 'date'],
            'finance_category_id' => [
                'required',
                Rule::exists('finance_categories', 'id')->where('type', 'income')->where('is_active', true),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'source' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
