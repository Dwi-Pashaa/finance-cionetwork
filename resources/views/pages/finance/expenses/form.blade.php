@php
    $hasAdminFee = old('has_admin_fee', $expense?->has_admin_fee ? '1' : '0');
    $amountValue = old('amount', $expense?->amount);
    $adminFeeValue = old('admin_fee_amount', $expense?->admin_fee_amount);
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
        <input type="date" name="transaction_date" value="{{ old('transaction_date', optional($expense?->transaction_date)->format('Y-m-d') ?? now()->toDateString()) }}" class="form-control @error('transaction_date') is-invalid @enderror" required>
        @error('transaction_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Kategori <span class="text-danger">*</span></label>
        <select name="finance_category_id" class="form-select @error('finance_category_id') is-invalid @enderror" required>
            <option value="">Pilih Kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" {{ old('finance_category_id', $expense?->finance_category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
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
        <label class="form-label">Penerima/Vendor</label>
        <input type="text" name="payee" maxlength="150" value="{{ old('payee', $expense?->payee) }}" class="form-control @error('payee') is-invalid @enderror">
        @error('payee')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Ada Biaya Admin? <span class="text-danger">*</span></label>
        <select name="has_admin_fee" id="hasAdminFee" class="form-select @error('has_admin_fee') is-invalid @enderror" required>
            <option value="0" {{ $hasAdminFee == '0' ? 'selected' : '' }}>Tidak</option>
            <option value="1" {{ $hasAdminFee == '1' ? 'selected' : '' }}>Ya</option>
        </select>
        @error('has_admin_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3" id="adminFeeWrapper">
        <label class="form-label">Biaya Admin</label>
        <input type="hidden" name="admin_fee_amount" id="adminFeeAmount" value="{{ $adminFeeValue }}">
        <input type="text" id="adminFeeAmountDisplay" value="{{ $adminFeeValue !== null && $adminFeeValue !== '' ? 'Rp '.number_format((float) $adminFeeValue, 0, ',', '.') : '' }}" class="form-control cio-rupiah-input @error('admin_fee_amount') is-invalid @enderror" inputmode="numeric" autocomplete="off" placeholder="Rp 0" data-rupiah-target="adminFeeAmount">
        @error('admin_fee_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">Catatan</label>
        <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $expense?->description) }}</textarea>
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

    function syncAdminFeeField() {
        const enabled = $("#hasAdminFee").val() === "1";
        $("#adminFeeWrapper").toggle(enabled);
        if (!enabled) {
            $("#adminFeeAmount").val("0");
            $("#adminFeeAmountDisplay").val("");
        }
    }

    bindRupiahInputs();
    $("#hasAdminFee").on("change", syncAdminFeeField);
    syncAdminFeeField();
</script>
@endpush
