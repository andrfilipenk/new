<?php $this->layout('layouts/app', ['title' => 'Pages']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Pages</h1>
        <a href="/cms/pages/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Page
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Author</th>
                            <th>Published</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($page['title']) ?></strong></td>
                            <td><code><?= htmlspecialchars($page['slug']) ?></code></td>
                            <td>
                                <span class="badge bg-<?= $page['status'] === 'published' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($page['status']) ?>
                                </span>
                            </td>
                            <td>User #<?= $page['author_id'] ?></td>
                            <td>
                                <?= $page['published_at'] ? date('M d, Y', strtotime($page['published_at'])) : '-' ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($page['status'] === 'draft'): ?>
                                    <button class="btn btn-success" onclick="publishPage(<?= $page['id'] ?>)">Publish</button>
                                    <?php endif; ?>
                                    <a href="/cms/pages/<?= $page['id'] ?>/edit" class="btn btn-outline-primary">Edit</a>
                                    <a href="/<?= $page['slug'] ?>" class="btn btn-outline-secondary" target="_blank">View</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
async function publishPage(id) {
    try {
        await Ajax.post(`/api/cms/pages/${id}/publish`);
        UI.createAlert('Page published successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to publish page', 'danger');
    }
}
</script>
<?php $this->endSection() ?>
