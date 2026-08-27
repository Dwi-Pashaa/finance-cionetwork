@extends('layouts.app')

@section('title')
    Edit Pengeluaran
@endsection

@push('css')
    @include('pages.finance.partials.styles')
@endpush

@section('content')
@include('components.alert.danger')
<form action="{{ route('expense.update', $expense->id) }}" method="POST" class="card cio-card">
    @csrf
    @method('PUT')
    <div class="cio-card-header d-flex align-items-center gap-3">
        <span class="cio-icon-avatar danger">
            <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        </span>
        <div>
            <h3 class="cio-title">Form Pengeluaran</h3>
            <div class="cio-subtitle">Perbarui transaksi biaya dan vendor</div>
        </div>
    </div>
    <div class="card-body">
        @include('pages.finance.expenses.form', ['expense' => $expense])
    </div>
    <div class="cio-table-footer text-end">
        <a href="{{ route('expense.index') }}" class="btn btn-light cio-btn">Batal</a>
        <button type="submit" class="btn cio-btn cio-btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="cio-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h10l2 2v14H6z"/><path d="M10 4v6h6"/><path d="M9 16h6"/></svg>
            Simpan Perubahan
        </button>
    </div>
</form>
@endsection
