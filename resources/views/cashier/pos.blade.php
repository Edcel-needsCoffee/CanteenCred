@extends('layouts.cashier')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Point of Sale</h1>
</div>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Faculty Lookup</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Search Faculty <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" id="faculty-search" class="form-control form-control-lg" placeholder="Enter Faculty ID or Name..." autocomplete="off">
                        <button type="button" class="btn btn-outline-secondary" id="search-btn">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    <div id="search-results" class="mt-2"></div>
                </div>
                
                <div id="selected-faculty" class="d-none">
                    <div class="alert alert-info">
                        <h6 class="mb-1"><strong>Selected:</strong> <span id="faculty-name"></span></h6>
                        <div class="row g-2 mb-0">
                            <div class="col-md-4">
                                <strong>Faculty #:</strong> <span id="faculty-number"></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Department:</strong> <span id="faculty-dept"></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Credit Limit:</strong> <span id="faculty-credit-limit"></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Current Balance:</strong> <span id="faculty-balance" class="text-primary"></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Remaining Credit:</strong> <span id="faculty-remaining" class="text-success fw-bold"></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="clear-faculty">
                        <i class="bi bi-x-circle me-1"></i>Change Faculty
                    </button>
                </div>
            </div>
        </div>

        <div id="transaction-form-container" class="d-none">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Transaction Items</h5>
                </div>
                <div class="card-body">
                    <form id="transaction-form">
                        <div id="line-items">
                            <div class="line-item row g-2 align-items-end mb-2" data-index="0">
                                <div class="col-md-5">
                                    <label class="form-label">Product</label>
                                    <select name="items[0][product_id]" class="form-select" required>
                                        <option value="">Select Product...</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                                                {{ $product->name }} - {{ config('app.facu_cred_currency_symbol', 'P') }}{{ number_format($product->price, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" name="items[0][quantity]" class="form-control" value="1" min="1" required>
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
                                <textarea name="override_reason" class="form-control" rows="2" placeholder="Explain why this transaction should exceed the credit limit"></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-4 d-flex gap-2">
                            <button type="button" class="btn btn-primary btn-lg" id="submit-transaction">
                                <i class="bi bi-check-circle me-1"></i>Confirm Credit Transaction
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-lg" id="cancel-transaction">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Quick Reference</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small">Products Available</label>
                    <div class="fs-4 fw-bold">{{ $products->count() }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small">Faculty Accounts</label>
                    <div class="fs-4 fw-bold">{{ \App\Models\Faculty::active()->count() }}</div>
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
    let selectedFacultyId = null;

    // Faculty search
    let searchDebounce;
    document.getElementById('faculty-search')?.addEventListener('input', function() {
        clearTimeout(searchDebounce);
        const query = this.value.trim();
        
        if (query.length < 2) {
            document.getElementById('search-results').innerHTML = '';
            return;
        }
        
        searchDebounce = setTimeout(() => {
            fetch(`/cashier/faculty-lookup?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('search-results');
                    if (data.data && data.data.length > 0) {
                        container.innerHTML = data.data.map(f => `
                            <div class="list-group-item list-group-item-action" onclick="selectFaculty(${f.id}, '${f.faculty_number}', '${f.name}', '${f.department}', ${f.credit_limit}, ${f.balance}, ${f.remaining_credit}, ${f.is_active})">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">${f.name}</h6>
                                    <small>${f.faculty_number}</small>
                                </div>
                                <p class="mb-1">${f.department}</p>
                                <small>Credit Limit: {{ config('app.facu_cred_currency_symbol', 'P') }}${Number(f.credit_limit).toFixed(2)} | Remaining: {{ config('app.facu_cred_currency_symbol', 'P') }}${Number(f.remaining_credit).toFixed(2)}</small>
                                ${!f.is_active ? '<span class="badge bg-danger">INACTIVE</span>' : ''}
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<div class="text-muted small p-2">No faculty found</div>';
                    }
                });
        }, 300);
    });

    function selectFaculty(id, number, name, dept, creditLimit, balance, remaining, isActive) {
        if (!isActive) {
            alert('This faculty account is inactive.');
            return;
        }
        
        selectedFacultyId = id;
        document.getElementById('faculty-number').textContent = number;
        document.getElementById('faculty-name').textContent = name;
        document.getElementById('faculty-dept').textContent = dept;
        document.getElementById('faculty-credit-limit').textContent = '{{ config('app.facu_cred_currency_symbol', 'P') }}' + Number(creditLimit).toFixed(2);
        document.getElementById('faculty-balance').textContent = '{{ config('app.facu_cred_currency_symbol', 'P') }}' + Number(balance).toFixed(2);
        document.getElementById('faculty-remaining').textContent = '{{ config('app.facu_cred_currency_symbol', 'P') }}' + Number(remaining).toFixed(2);
        
        document.getElementById('search-results').innerHTML = '';
        document.getElementById('faculty-search').value = '';
        document.getElementById('selected-faculty').classList.remove('d-none');
        document.getElementById('transaction-form-container').classList.remove('d-none');
    }

    document.getElementById('clear-faculty')?.addEventListener('click', function() {
        selectedFacultyId = null;
        document.getElementById('selected-faculty').classList.add('d-none');
        document.getElementById('transaction-form-container').classList.add('d-none');
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
    document.getElementById('submit-transaction')?.addEventListener('click', function() {
        const btn = this;
        const form = document.getElementById('transaction-form');
        
        if (!selectedFacultyId) {
            alert('Please select a faculty member first.');
            return;
        }
        
        const formData = new FormData(form);
        formData.append('faculty_id', selectedFacultyId);
        formData.append('recorded_by', '{{ auth()->id() }}');
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        
        fetch('/api/v1/transactions', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.data) {
                alert('Transaction recorded successfully!');
                form.reset();
                document.getElementById('line-items').innerHTML = '';
                itemIndex = 1;
                document.getElementById('add-item').click(); // Re-add first item
                document.getElementById('running-total').value = '{{ config('app.facu_cred_currency_symbol', 'P') }}0.00';
                document.getElementById('clear-faculty').click();
            } else if (data.error) {
                if (data.error.code === 'CREDIT_LIMIT_EXCEEDED') {
                    alert(`Credit limit exceeded!\nRemaining: ${data.error.fields.remaining_credit}\nAttempted: ${data.error.fields.attempted_total}`);
                } else {
                    alert(data.error.message || 'Transaction failed');
                }
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred. Please try again.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Confirm Credit Transaction';
        });
    });

    document.getElementById('cancel-transaction')?.addEventListener('click', function() {
        document.getElementById('clear-faculty').click();
    });
</script>
@endsection