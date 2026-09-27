@extends('layouts.admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Record Transaction</h1>
    <a href="{{ route('admin.transactions.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to List
    </a>
</div>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Transaction Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.transactions.store') }}" id="transaction-form">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Faculty <span class="text-danger">*</span></label>
                        <select name="faculty_id" class="form-select @error('faculty_id') is-invalid @enderror" required>
                            <option value="">Select Faculty...</option>
                            @foreach ($facultyList as $f)
                                <option value="{{ $f->id }}" {{ old('faculty_id') == $f->id ? 'selected' : '' }}>
                                    {{ $f->faculty_number }} - {{ $f->name }} ({{ $f->department }})
                                </option>
                            @endforeach
                        </select>
                        @error('faculty_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="faculty-info" class="mb-3 d-none">
                        <div class="alert alert-info">
                            <strong>Selected:</strong> <span id="faculty-name"></span>
                            <br>
                            <strong>Department:</strong> <span id="faculty-dept"></span>
                            <br>
                            <strong>Credit Limit:</strong> <span id="faculty-credit-limit"></span>
                            <br>
                            <strong>Remaining Credit:</strong> <span id="faculty-remaining" class="text-success fw-bold"></span>
                        </div>
                    </div>

                    <hr>

                    <h6 class="mb-3">Line Items</h6>
                    
                    <div id="line-items">
                        <div class="line-item row g-2 align-items-end mb-2" data-index="0">
                            <div class="col-md-5">
                                <label class="form-label">Product</label>
                                <select name="items[0][product_id]" class="form-select @error('items.0.product_id') is-invalid @enderror" required>
                                    <option value="">Select Product...</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                                            {{ $product->name }} - {{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($product->price, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('items.0.product_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" name="items[0][quantity]" class="form-control @error('items.0.quantity') is-invalid @enderror" 
                                       value="1" min="1" required>
                                @error('items.0.quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Subtotal</label>
                                <input type="text" class="form-control subtotal-display" readonly value="{{ config('app.facu_cred_currency_symbol', 'P') }}0.00">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger w-100 remove-item" disabled>
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-item">
                        <i class="bi bi-plus-circle me-1"></i>Add Item
                    </button>

                    <hr>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Running Total</label>
                            <input type="text" class="form-control fw-bold fs-5 text-end" id="running-total" readonly value="{{ config('app.facu_cred_currency_symbol', 'P') }}0.00">
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="override_credit_limit" class="form-check-input" id="override_credit_limit" value="1">
                                <label class="form-check-label" for="override_credit_limit">Override Credit Limit (requires reason)</label>
                            </div>
                        </div>
                        <div class="col-md-12" id="override-reason-field" style="display: none;">
                            <label class="form-label">Override Reason <span class="text-danger">*</span></label>
                            <textarea name="override_reason" class="form-control @error('override_reason') is-invalid @enderror" rows="2" placeholder="Explain why this transaction should exceed the credit limit"></textarea>
                            @error('override_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
                            <i class="bi bi-save me-1"></i>Record Transaction
                        </button>
                        <a href="{{ route('admin.transactions.index') }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Quick Info</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small">Products Available</label>
                    <div class="fs-4 fw-bold">{{ $products->count() }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Faculty Accounts</label>
                    <div class="fs-4 fw-bold">{{ $facultyList->count() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('page-scripts')
<script>
    let itemIndex = 1;
    const products = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'price' => $p->price]));

    // Faculty selection handler
    document.querySelector('select[name="faculty_id"]')?.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (this.value) {
            document.getElementById('faculty-info').classList.remove('d-none');
            document.getElementById('faculty-name').textContent = selected.textContent.split(' - ')[1]?.split(' (')[0] || '';
            document.getElementById('faculty-dept').textContent = selected.textContent.split('(')[1]?.replace(')', '') || '';
            // We'd need to fetch faculty details via AJAX for real credit info
        } else {
            document.getElementById('faculty-info').classList.add('d-none');
        }
    });

    // Add line item
    document.getElementById('add-item')?.addEventListener('click', function() {
        const container = document.getElementById('line-items');
        const newItem = document.createElement('div');
        newItem.className = 'line-item row g-2 align-items-end mb-2';
        newItem.dataset.index = itemIndex;
        
        let options = '<option value="">Select Product...</option>';
        products.forEach(p => {
            options += `<option value="${p.id}" data-price="${p.price}">${p.name} - {{ config('app.facu_cred_currency_symbol', 'P') }}${Number(p.price).toFixed(2)}</option>`;
        });

        newItem.innerHTML = `
            <div class="col-md-5">
                <label class="form-label">Product</label>
                <select name="items[${itemIndex}][product_id]" class="form-select" required>${options}</select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Quantity</label>
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control" value="1" min="1" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Subtotal</label>
                <input type="text" class="form-control subtotal-display" readonly value="{{ config('app.facu_cred_currency_symbol', 'P') }}0.00">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger w-100 remove-item"><i class="bi bi-trash"></i></button>
            </div>
        `;
        
        container.appendChild(newItem);
        updateRemoveButtons();
        itemIndex++;
    });

    // Remove line item
    document.getElementById('line-items')?.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            e.target.closest('.line-item').remove();
            updateRemoveButtons();
            calculateTotal();
        }
    });

    // Update subtotals when product or quantity changes
    document.getElementById('line-items')?.addEventListener('change', function(e) {
        if (e.target.matches('select[name*="[product_id]"], input[name*="[quantity]"]')) {
            calculateTotal();
        }
    });

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.line-item').forEach(item => {
            const select = item.querySelector('select[name*="[product_id]"]');
            const qty = item.querySelector('input[name*="[quantity]"]');
            const subtotalDisplay = item.querySelector('.subtotal-display');
            
            if (select && select.value && qty && qty.value) {
                const price = parseFloat(select.options[select.selectedIndex].dataset.price) || 0;
                const quantity = parseInt(qty.value) || 0;
                const subtotal = price * quantity;
                subtotalDisplay.value = '{{ config('app.facu_cred_currency_symbol', 'P') }}' + subtotal.toFixed(2);
                total += subtotal;
            } else {
                subtotalDisplay.value = '{{ config('app.facu_cred_currency_symbol', 'P') }}0.00';
            }
        });
        document.getElementById('running-total').value = '{{ config('app.facu_cred_currency_symbol', 'P') }}' + total.toFixed(2);
    }

    function updateRemoveButtons() {
        const items = document.querySelectorAll('.line-item');
        items.forEach((item, index) => {
            const btn = item.querySelector('.remove-item');
            btn.disabled = items.length <= 1;
        });
    }

    // Override reason field toggle
    document.getElementById('override_credit_limit')?.addEventListener('change', function() {
        document.getElementById('override-reason-field').style.display = this.checked ? 'block' : 'none';
    });

    // Form submission
    document.getElementById('transaction-form')?.addEventListener('submit', function() {
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Recording...';
    });
</script>
@endsection