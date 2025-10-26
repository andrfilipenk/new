<?php 
use Core\View\ViewEngine;
ViewEngine::extend('layouts/app'); 
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-gear-fill"></i> System Settings</h2>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="/admin/settings">
                <div class="mb-3">
                    <label class="form-label">Application Name</label>
                    <input type="text" class="form-control" name="app_name" value="<?php echo htmlspecialchars($settings['app_name']); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Environment</label>
                    <input type="text" class="form-control" name="app_env" value="<?php echo htmlspecialchars($settings['app_env']); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Timezone</label>
                    <input type="text" class="form-control" name="timezone" value="<?php echo htmlspecialchars($settings['timezone']); ?>" readonly>
                </div>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Settings are configured in <code>config/app.php</code>
                </div>
            </form>
        </div>
    </div>
</div>
