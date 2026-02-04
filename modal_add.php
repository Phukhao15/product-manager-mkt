<style>
    /* ปรับแต่ง Modal */
    .modal-content {
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .modal-header {
        border-bottom: 1px solid #f3f4f6;
        padding: 20px 24px;
    }
    .modal-body {
        padding: 24px;
    }
    .modal-footer {
        border-top: 1px solid #f3f4f6;
        padding: 20px 24px;
        background-color: #fff;
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
    }
    .form-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }
    .form-control, .form-select {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.625rem 0.875rem;
        font-size: 0.9rem;
        color: #111827;
        transition: all 0.2s;
    }
    .form-control::placeholder { color: #9ca3af; }
    .form-control:focus, .form-select:focus {
        border-color: #111827;
        box-shadow: 0 0 0 2px rgba(17, 24, 39, 0.05);
    }
    .bg-readonly { background-color: #f9fafb; color: #6b7280; cursor: not-allowed; }
    .btn-cancel-custom {
        background-color: #ffffff; border: 1px solid #d1d5db; color: #374151;
        font-weight: 600; border-radius: 8px; padding: 10px 0; width: 100%; transition: all 0.2s;
    }
    .btn-cancel-custom:hover { background-color: #f3f4f6; color: #111827; }
    .btn-save-custom {
        background-color: #111827; border: 1px solid #111827; color: #ffffff;
        font-weight: 600; border-radius: 8px; padding: 10px 0; width: 100%; transition: all 0.2s;
    }
    .btn-save-custom:hover { background-color: #374151; border-color: #374151; }
</style>

<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark" id="modalTitle">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="index.php" method="POST" id="modalForm" onsubmit="cleanInputsBeforeSubmit()">
                <input type="hidden" name="product_id" id="product_id">
                
                <div class="modal-body">
                    
                    <div class="mb-4">
                        <label class="form-label">Product Name * (Supplier)</label>
                        <input type="text" name="product_name" class="form-control" required 
                               placeholder="Type to search Supplier..." 
                               list="supplier_list" 
                               onkeyup="searchERP('Supplier', this.value, 'supplier_list')">
                        <datalist id="supplier_list"></datalist>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <label class="form-label">Sale Price *</label>
                            <input type="text" name="sale_price" id="sale_val" class="form-control" required placeholder="0.00" onblur="formatCurrency(this)" onfocus="removeCommas(this)">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Cost Price *</label>
                            <input type="text" name="cost_price" id="cost_val" class="form-control" required placeholder="0.00" onblur="formatCurrency(this)" onfocus="removeCommas(this)">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <label class="form-label">Quarter</label>
                            <select name="quarter" class="form-select">
                                <option value="" disabled selected>Select quarter</option>
                                <option value="Q1">Q1</option>
                                <option value="Q2">Q2</option>
                                <option value="Q3">Q3</option>
                                <option value="Q4">Q4</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Marketing Cost</label>
                            <input type="text" name="marketing_channel" id="marketing_val" class="form-control" placeholder="0.00 (Optional)" onblur="formatCurrency(this)" onfocus="removeCommas(this)">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <label class="form-label">Margin (%)</label>
                            <input type="text" name="margin_percent" id="m_perc" class="form-control bg-readonly" readonly placeholder="Auto-calculated">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Margin Price</label>
                            <input type="text" name="margin_price" id="m_price" class="form-control bg-readonly" readonly placeholder="0.00">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <label class="form-label">Partner Name (Customer)</label>
                            <input type="text" name="partner_name" class="form-control" 
                                   placeholder="Type to search Customer..."
                                   list="customer_list" 
                                   onkeyup="searchERP('Customer', this.value, 'customer_list')">
                            <datalist id="customer_list"></datalist>
                        </div>
                        <div class="col-6">
                            <label class="form-label">End User Name (End Customer)</label>
                            <input type="text" name="end_user_name" class="form-control" 
                                   placeholder="Type to search End Customer..."
                                   list="end_customer_list" 
                                   onkeyup="searchERP('Endcustomer', this.value, 'end_customer_list')">
                            <datalist id="end_customer_list"></datalist>
                        </div>
                    </div>

                </div>

                <div class="modal-footer border-top-0 pt-0">
                    <div class="row w-100 m-0 gx-3">
                        <div class="col-6 ps-0">
                            <button type="button" class="btn btn-cancel-custom" data-bs-dismiss="modal">Cancel</button>
                        </div>
                        <div class="col-6 pe-0">
                            <button type="submit" name="add_product" id="btnSubmit" class="btn btn-save-custom">Add Product</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const sIn = document.getElementById('sale_val');
    const cIn = document.getElementById('cost_val');
    const mktIn = document.getElementById('marketing_val');

    // --- ERPNext Autocomplete ---
    let timeout = null;
    function searchERP(doctype, query, listId) {
        if (query.length < 2) return;

        clearTimeout(timeout);
        timeout = setTimeout(() => {
            fetch(`api_erp.php?doctype=${encodeURIComponent(doctype)}&search=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    const dataList = document.getElementById(listId);
                    dataList.innerHTML = ''; 

                    if (data.data && data.data.length > 0) {
                        data.data.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.name;
                            dataList.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error fetching ERP data:', error));
        }, 500); 
    }

    // --- Currency Formatter ---
    function formatCurrency(input) {
        let value = input.value.replace(/,/g, '');
        if (value && !isNaN(value)) {
            input.value = parseFloat(value).toLocaleString('en-US', {
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2 
            });
        }
        calculate();
    }

    function removeCommas(input) {
        if (input.value) {
            input.value = input.value.replace(/,/g, '');
        }
    }

    function getNum(val) {
        return parseFloat(val.replace(/,/g, '')) || 0;
    }

    // --- Calculation ---
    function calculate() {
        const s = getNum(sIn.value);
        const c = getNum(cIn.value);
        const mkt = getNum(mktIn.value);

        if (s > 0) {
            const totalCost = c + mkt;
            const marginPrice = s - totalCost;
            const marginPercent = (marginPrice / s) * 100;

            document.getElementById('m_price').value = marginPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('m_perc').value = marginPercent.toFixed(2) + '%';
        } else {
            document.getElementById('m_price').value = '';
            document.getElementById('m_perc').value = '';
        }
    }

    function cleanInputsBeforeSubmit() {
        sIn.value = sIn.value.replace(/,/g, '');
        cIn.value = cIn.value.replace(/,/g, '');
        mktIn.value = mktIn.value.replace(/,/g, '');
        document.getElementById('m_price').value = document.getElementById('m_price').value.replace(/,/g, '');
    }

    sIn.addEventListener('input', calculate);
    cIn.addEventListener('input', calculate);
    mktIn.addEventListener('input', calculate);
</script>