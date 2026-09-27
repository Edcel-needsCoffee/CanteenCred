@extends('layouts.cashier')

@section('page-header')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Faculty Lookup</h1>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <div class="mb-3">
            <label class="form-label">Search Faculty</label>
            <div class="input-group">
                <input type="text" id="faculty-lookup-search" class="form-control form-control-lg" placeholder="Enter Faculty ID or Name..." autocomplete="off">
                <button type="button" class="btn btn-outline-secondary" id="lookup-search-btn">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div id="lookup-results"></div>
    </div>
</div>

@endsection

@section('page-scripts')
<script>
    let searchDebounce;
    document.getElementById('faculty-lookup-search')?.addEventListener('input', function() {
        clearTimeout(searchDebounce);
        const query = this.value.trim();
        
        if (query.length < 2) {
            document.getElementById('lookup-results').innerHTML = '';
            return;
        }
        
        searchDebounce = setTimeout(() => {
            fetch(`/cashier/faculty-lookup?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('lookup-results');
                    if (data.data && data.data.length > 0) {
                        container.innerHTML = `
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Faculty #</th>
                                            <th>Name</th>
                                            <th>Department</th>
                                            <th>Credit Limit</th>
                                            <th>Balance</th>
                                            <th>Remaining</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.data.map(f => `
                                            <tr class="${!f.is_active ? 'table-secondary' : ''}">
                                                <td>${f.faculty_number}</td>
                                                <td>${f.name}</td>
                                                <td>${f.department}</td>
                                                <td>{{ config('app.facu_cred_currency_symbol', 'P') }}${Number(f.credit_limit).toFixed(2)}</td>
                                                <td>{{ config('app.facu_cred_currency_symbol', 'P') }}${Number(f.balance).toFixed(2)}</td>
                                                <td class="${Number(f.remaining_credit) < 200 ? 'text-danger fw-bold' : ''}">{{ config('app.facu_cred_currency_symbol', 'P') }}${Number(f.remaining_credit).toFixed(2)}</td>
                                                <td><span class="badge ${f.is_active ? 'bg-success' : 'bg-secondary'}">${f.is_active ? 'Active' : 'Inactive'}</span></td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    } else {
                        container.innerHTML = '<div class="text-center py-4 text-muted">No faculty found</div>';
                    }
                });
        }, 300);
    });
</script>
@endsection