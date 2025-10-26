<?php $this->layout('layouts/app', ['title' => 'Create Lead']) ?>

<?php $this->section('content') ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Create New Lead</h3>
                </div>
                <div class="card-body">
                    <form id="leadForm" method="POST" action="/crm/leads">
                        <?= csrf_field() ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Company *</label>
                            <input type="text" name="company" class="form-control" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Source *</label>
                                <select name="source" class="form-select" required>
                                    <option value="">Select Source</option>
                                    <option value="website">Website</option>
                                    <option value="referral">Referral</option>
                                    <option value="advertising">Advertising</option>
                                    <option value="trade_show">Trade Show</option>
                                    <option value="cold_call">Cold Call</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status *</label>
                                <select name="status" class="form-select" required>
                                    <option value="new">New</option>
                                    <option value="contacted">Contacted</option>
                                    <option value="qualified">Qualified</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="4"></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/crm/leads" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Lead</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('leadForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    try {
        const result = await Ajax.post('/api/crm/leads', data);
        UI.createAlert('Lead created successfully', 'success');
        setTimeout(() => window.location.href = '/crm/leads/' + result.lead.id, 1000);
    } catch (error) {
        UI.createAlert('Failed to create lead', 'danger');
    }
});
</script>
<?php $this->endSection() ?>
