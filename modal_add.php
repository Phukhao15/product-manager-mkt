<style>
    /* ปรับแต่ง Modal ให้เหมือนในรูป */
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
        background-color: #fff; /* พื้นหลังขาวตามรูป */
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
    }
    
    /* Style Input Fields */
    .form-label {
        font-size: 0.875rem; /* 14px */
        font-weight: 600;
        color: #374151; /* เทาเข้ม */
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
    .form-control::placeholder {
        color: #9ca3af; /* สี Placeholder จางๆ */
    }
    .form-control:focus, .form-select:focus {
        border-color: #111827; /* โฟกัสเป็นสีดำ */
        box-shadow: 0 0 0 2px rgba(17, 24, 39, 0.05);
    }
    
    /* Readonly Inputs (Margin) */
    .bg-readonly {
        background-color: #f9fafb;
        color: #6b7280;
        cursor: not-allowed;
    }

    /* Buttons */
    .btn-cancel-custom {
        background-color: #ffffff;
        border: 1px solid #d1d5db;
        color: #374151;
        font-weight: 600;
        border-radius: 8px;
        padding: 10px 0;
        width: 100%;
        transition: all 0.2s;
    }
    .btn-cancel-custom:hover {
        background-color: #f3f4f6;
        color: #111827;
    }
    
    .btn-save-custom {
        background-color: #111827; /* สีดำเข้ม */
        border: 1px solid #111827;
        color: #ffffff;
        font-weight: 600;
        border-radius: 8px;
        padding: 10px 0;
        width: 100%;
        transition: all 0.2s;
    }
    .btn-save-custom:hover {
        background-color: #374151;
        border-color: #374151;
    }
</style>

<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered"> <div class="modal-content">
            
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark" id="modalTitle">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="index.php" method="POST" id="modalForm">
                <input type="hidden" name="product_id" id="product_id">
                
                <div class="modal-body">
                    
                    <div class="mb-4">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="product_name" class="form-control" required placeholder="Enter product name">
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <label class="form-label">Sale Price *</label>
                            <input type="number" step="0.01" name="sale_price" id="sale_val" class="form-control" required placeholder="0.00">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Cost Price *</label>
                            <input type="number" step="0.01" name="cost_price" id="cost_val" class="form-control" required placeholder="0.00">
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
                            <label class="form-label">Marketing</label>
                            <input type="text" name="marketing_channel" class="form-control" placeholder="Marketing channel or campaign">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <label class="form-label">Partner Name</label>
                            <input type="text" name="partner_name" class="form-control" placeholder="Partner/Distributor">
                        </div>
                        <div class="col-6">
                            <label class="form-label">End User Name</label>
                            <input type="text" name="end_user_name" class="form-control" placeholder="Customer name">
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
    
    function calculate() {
        const s = parseFloat(sIn.value) || 0;
        const c = parseFloat(cIn.value) || 0;
        if (s > 0) {
            document.getElementById('m_price').value = (s - c).toFixed(2);
            document.getElementById('m_perc').value = (((s - c) / s) * 100).toFixed(2) + '%';
        } else {
            document.getElementById('m_price').value = '';
            document.getElementById('m_perc').value = '';
        }
    }
    sIn.addEventListener('input', calculate);
    cIn.addEventListener('input', calculate);
</script>