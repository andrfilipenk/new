<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Visual Workflow Designer' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/workflow-designer.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Left Sidebar - Tools & Properties -->
            <div class="col-md-3 sidebar-left bg-light p-3">
                <h5 class="mb-3">
                    <i class="fas fa-toolbox"></i> Workflow Tools
                </h5>

                <!-- Workflow Properties -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Workflow Properties</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="workflowName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="workflowName" placeholder="Enter workflow name">
                        </div>
                        <div class="mb-3">
                            <label for="workflowEntity" class="form-label">Entity Type</label>
                            <input type="text" class="form-control" id="workflowEntity" placeholder="e.g., PurchaseOrder">
                        </div>
                        <div class="mb-3">
                            <label for="workflowDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="workflowDescription" rows="3" placeholder="Describe the workflow"></textarea>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="workflowActive" checked>
                            <label class="form-check-label" for="workflowActive">
                                Active
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Step Types Palette -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Step Types</h6>
                    </div>
                    <div class="card-body p-2" id="stepTypePalette">
                        <!-- Step types will be loaded dynamically -->
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" id="saveWorkflowBtn">
                        <i class="fas fa-save"></i> Save Workflow
                    </button>
                    <button class="btn btn-secondary" id="loadWorkflowBtn">
                        <i class="fas fa-folder-open"></i> Load Workflow
                    </button>
                    <button class="btn btn-warning" id="validateWorkflowBtn">
                        <i class="fas fa-check-circle"></i> Validate
                    </button>
                    <button class="btn btn-danger" id="clearCanvasBtn">
                        <i class="fas fa-trash"></i> Clear Canvas
                    </button>
                </div>
            </div>

            <!-- Center Canvas Area -->
            <div class="col-md-6 p-0">
                <div class="canvas-header bg-dark text-white p-3">
                    <h4 class="mb-0">
                        <i class="fas fa-project-diagram"></i> 
                        <span id="canvasTitle">New Workflow</span>
                    </h4>
                    <div class="canvas-controls mt-2">
                        <button class="btn btn-sm btn-outline-light" id="zoomInBtn">
                            <i class="fas fa-search-plus"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-light" id="zoomOutBtn">
                            <i class="fas fa-search-minus"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-light" id="fitToScreenBtn">
                            <i class="fas fa-expand"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-light" id="autoLayoutBtn">
                            <i class="fas fa-magic"></i> Auto Layout
                        </button>
                    </div>
                </div>
                <div id="workflowCanvas" class="workflow-canvas">
                    <!-- Canvas content will be rendered here -->
                    <div class="canvas-grid"></div>
                    <div id="stepsContainer" class="steps-container">
                        <!-- Workflow steps will be added here -->
                    </div>
                    <svg id="connectionsLayer" class="connections-layer">
                        <!-- Connections between steps will be drawn here -->
                    </svg>
                </div>
            </div>

            <!-- Right Sidebar - Step Properties & Preview -->
            <div class="col-md-3 sidebar-right bg-light p-3">
                <h5 class="mb-3">
                    <i class="fas fa-cog"></i> Step Properties
                </h5>

                <!-- Step Properties Form -->
                <div class="card mb-3" id="stepPropertiesPanel">
                    <div class="card-body">
                        <p class="text-muted text-center">
                            <i class="fas fa-hand-pointer"></i><br>
                            Select a step to edit its properties
                        </p>
                    </div>
                </div>

                <!-- Workflow Preview -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Workflow Steps</h6>
                    </div>
                    <div class="card-body p-2">
                        <div id="stepsList" class="list-group list-group-flush">
                            <p class="text-muted text-center m-3">No steps added yet</p>
                        </div>
                    </div>
                </div>

                <!-- Validation Results -->
                <div class="card mt-3" id="validationPanel" style="display: none;">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-check-circle"></i> Validation
                        </h6>
                    </div>
                    <div class="card-body" id="validationResults">
                        <!-- Validation results will appear here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Load Workflow Modal -->
    <div class="modal fade" id="loadWorkflowModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-folder-open"></i> Load Workflow
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-hover" id="workflowsTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Entity Type</th>
                                <th>Steps</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Workflows will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Context Menu -->
    <div id="contextMenu" class="context-menu" style="display: none;">
        <div class="context-menu-item" data-action="edit">
            <i class="fas fa-edit"></i> Edit Step
        </div>
        <div class="context-menu-item" data-action="duplicate">
            <i class="fas fa-copy"></i> Duplicate
        </div>
        <div class="context-menu-item" data-action="delete">
            <i class="fas fa-trash text-danger"></i> Delete
        </div>
        <div class="context-menu-divider"></div>
        <div class="context-menu-item" data-action="move-up">
            <i class="fas fa-arrow-up"></i> Move Up
        </div>
        <div class="context-menu-item" data-action="move-down">
            <i class="fas fa-arrow-down"></i> Move Down
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Pass data to JavaScript
        window.WORKFLOW_DESIGNER_CONFIG = {
            roles: <?= json_encode($roles ?? []) ?>,
            entityTypes: <?= json_encode($entityTypes ?? []) ?>,
            apiBaseUrl: '/api/workflow-designer'
        };
    </script>
    <script src="/assets/js/workflow-designer.js"></script>
</body>
</html>
