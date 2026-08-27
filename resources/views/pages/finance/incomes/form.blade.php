@php
    $amountValue = old('amount', $income?->amount);
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
        <input type="date" name="transaction_date" value="{{ old('transaction_date', optional($income?->transaction_date)->format('Y-m-d') ?? now()->toDateString()) }}" class="form-control @error('transaction_date') is-invalid @enderror" required>
        @error('transaction_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Kategori <span class="text-danger">*</span></label>
        <select name="finance_category_id" class="form-select @error('finance_category_id') is-invalid @enderror" required>
            <option value="">Pilih Kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" {{ old('finance_category_id', $income?->finance_category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('finance_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Nominal <span class="text-danger">*</span></label>
        <input type="hidden" name="amount" id="amount" value="{{ $amountValue }}">
        <input type="text" id="amountDisplay" value="{{ $amountValue !== null && $amountValue !== '' ? 'Rp '.number_format((float) $amountValue, 0, ',', '.') : '' }}" class="form-control cio-rupiah-input @error('amount') is-invalid @enderror" inputmode="numeric" autocomplete="off" placeholder="Rp 0" data-rupiah-target="amount" required>
        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Sumber</label>
        <input type="text" name="source" maxlength="150" value="{{ old('source', $income?->source) }}" class="form-control @error('source') is-invalid @enderror">
        @error('source')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Catatan</label>
        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $income?->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@push('js')
<script>
    function formatRupiahInput(value) {
        const digits = String(value || "").replace(/\D/g, "");
        return digits ? "Rp " + new Intl.NumberFormat("id-ID").format(Number(digits)) : "";
    }

    function bindRupiahInputs() {
        $(".cio-rupiah-input").each(function() {
            const $display = $(this);
            const targetId = $display.data("rupiah-target");
            const $target = $("#" + targetId);

            $display.on("input", function() {
                const digits = $display.val().replace(/\D/g, "");
                $target.val(digits);
                $display.val(formatRupiahInput(digits));
            });
        });
    }

    bindRupiahInputs();
</script>
@endpush
