<?php
/**
 * Gestion des utilisateurs - Admin Games Store
 */

$pageTitle = "Gestion des utilisateurs";
require_once __DIR__ . '/../includes/header.php';

requireAdmin();

// Pagination et filtres
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role)) {
    $where[] = "role = ?";
    $params[] = $role;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Compter le total
$totalUsers = fetchOne("SELECT COUNT(*) as count FROM users $whereClause", $params)['count'];
$totalPages = ceil($totalUsers / $limit);

// Récupérer les utilisateurs avec leurs stats
$users = fetchAll("
    SELECT u.*,
           (SELECT COUNT(*) FROM purchases WHERE user_id = u.id) as purchases_count,
           (SELECT SUM(purchase_price) FROM purchases WHERE user_id = u.id) as total_spent
    FROM users u
    $whereClause
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset
", $params);
?>

<section class="admin-section">
    <div class="admin-container">
        <!-- Sidebar Admin -->
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <i class="fas fa-gamepad"></i>
                <span>Admin Panel</span>
            </div>
            <nav class="admin-nav">
                <a href="/admin/index.php" class="admin-nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="/admin/games.php" class="admin-nav-link">
                    <i class="fas fa-gamepad"></i> Jeux
                </a>
                <a href="/admin/users.php" class="admin-nav-link active">
                    <i class="fas fa-users"></i> Utilisateurs
                </a>
                <a href="/admin/categories.php" class="admin-nav-link">
                    <i class="fas fa-tags"></i> Catégories
                </a>
                <a href="/admin/orders.php" class="admin-nav-link">
                    <i class="fas fa-shopping-bag"></i> Commandes
                </a>
                <div class="admin-nav-divider"></div>
                <a href="/index.php" class="admin-nav-link">
                    <i class="fas fa-home"></i> Retour au site
                </a>
            </nav>
        </aside>
        
        <!-- Contenu principal -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Gestion des utilisateurs</h1>
            </div>
            
            <!-- Barre de filtres -->
            <div class="admin-toolbar">
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Rechercher..." 
                           value="<?php echo escape($search); ?>" class="form-control">
                    <select name="role" class="form-control">
                        <option value="">Tous les rôles</option>
                        <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>Utilisateur</option>
                        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i>
                    </button>
                </form>
                <span class="results-info"><?php echo $totalUsers; ?> utilisateur(s)</span>
            </div>
            
            <!-- Table des utilisateurs -->
            <div class="admin-card">
                <div class="card-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Avatar</th>
                                <th>Utilisateur</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Achats</th>
                                <th>Total dépensé</th>
                                <th>Inscription</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr data-user-id="<?php echo $user['id']; ?>">
                                <td>
                                    <img src="/assets/images/avatars/<?php echo escape($user['avatar']); ?>" 
                                         alt="" class="table-avatar"
                                         onerror="this.src='/assets/images/avatars/default-avatar.png'">
                                </td>
                                <td><strong><?php echo escape($user['username']); ?></strong></td>
                                <td><?php echo escape($user['email']); ?></td>
                                <td>
                                    <select class="role-select form-control-sm" data-user-id="<?php echo $user['id']; ?>"
                                            <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </td>
                                <td><?php echo $user['purchases_count']; ?></td>
                                <td><?php echo number_format($user['total_spent'] ?? 0, 2); ?> €</td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-sm btn-danger delete-user" 
                                                data-user-id="<?php echo $user['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>" 
                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</section>

<script>
// Changer le rôle
document.querySelectorAll('.role-select').forEach(select => {
    select.addEventListener('change', async function() {
        const userId = this.dataset.userId;
        const newRole = this.value;
        
        try {
            const response = await fetch('/api/users.php?action=role&id=' + userId, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ role: newRole })
            });
            
            const result = await response.json();
            
            if (!result.success) {
                alert(result.error || 'Erreur');
                location.reload();
            }
        } catch (error) {
            console.error(error);
        }
    });
});

// Supprimer un utilisateur
document.querySelectorAll('.delete-user').forEach(btn => {
    btn.addEventListener('click', async function() {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur?')) return;
        
        const userId = this.dataset.userId;
        
        try {
            const response = await fetch('/api/users.php?id=' + userId, {
                method: 'DELETE'
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.closest('tr').remove();
            }
        } catch (error) {
            console.error(error);
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
