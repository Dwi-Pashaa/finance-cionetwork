<?php

namespace App\Http\Controllers\Pages\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = FinanceCategory::query()
            ->when($request->type, fn ($query, $type) => $query->where('type', $type))
            ->when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('type')
            ->orderBy('name')
            ->paginate($request->sort ?? 10)
            ->withQueryString();

        return view('pages.finance.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['income', 'expense'])],
            'name' => ['required', 'string', 'max:100', Rule::unique('finance_categories', 'name')->where('type', $request->type)],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        FinanceCategory::create($validated + ['is_active' => true]);

        return redirect()->route('finance-category.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $category = FinanceCategory::findOrFail($id);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['income', 'expense'])],
            'name' => ['required', 'string', 'max:100', Rule::unique('finance_categories', 'name')->where('type', $request->type)->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($category->type !== $validated['type'] && ($category->incomes()->exists() || $category->expenses()->exists())) {
            return back()
                ->with('error', 'Tipe kategori yang sudah dipakai transaksi tidak dapat diubah.')
                ->withInput();
        }

        $category->update($validated + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('finance-category.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $category = FinanceCategory::find($id);

        if (! $category) {
            return response()->json(['code' => 400, 'status' => 'error', 'message' => 'Data tidak ditemukan.']);
        }

        if ($category->incomes()->exists() || $category->expenses()->exists()) {
            return response()->json(['code' => 422, 'status' => 'error', 'message' => 'Kategori sudah dipakai transaksi. Nonaktifkan kategori sebagai gantinya.']);
        }

        $category->delete();

        return response()->json(['code' => 200, 'status' => 'success', 'message' => 'Kategori berhasil dihapus.']);
    }
}
