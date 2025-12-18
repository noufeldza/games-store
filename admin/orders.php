<?php
/**
 * Gestion des commandes - Admin Games Store
 */

$pageTitle = "Gestion des commandes";
require_once __DIR__ . '/../includes/header.php';

requireAdmin();

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 30;
$offset = ($page - 1) * $limit;

// Filtres
$dateFrom = $_GET['from'] ?? '';
$dateTo = $_GET['to'] ?? '';

$where = [];
$params = [];

if (!empty($dateFrom)) {
    $where[] = "p.purchase_date >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $where[] = "p.purchase_date <= ?";
    $params[] = $dateTo . ' 23:59:59';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Stats
$stats = fetchOne("
    SELECT 
        COUNT(*) as total_orders,
        SUM(purchase_price) as total_revenue,
        AVG(purchase_price) as avg_order
    FROM purchases p
    $whereClause
", $params);

// Total pour pagination
$totalOrders = fetchOne("SELECT COUNT(*) as count FROM purchases p $whereClause", $params)['count'];
$totalPages = ceil($totalOrders / $limit);

// Récupérer les commandes
$orders = fetchAll("
    SELECT p.*, u.username, u.email, g.title as game_title, g.image as game_image
    FROM purchases p
    JOIN users u ON p.user_id = u.id
    JOIN games g ON p.game_id = g.id
    $whereClause
    ORDER BY p.purchase_date DESC
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
                <a href="/admin/users.php" class="admin-nav-link">
                    <i class="fas fa-users"></i> Utilisateurs
                </a>
                <a href="/admin/categories.php" class="admin-nav-link">
                    <i class="fas fa-tags"></i> Catégories
                </a>
                <a href="/admin/orders.php" class="admin-nav-link active">
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
                <h1>Gestion des commandes</h1>
            </div>
            
            <!-- Stats -->
            <div class="stats-grid stats-small">
                <div class="stat-card">
                    <div class="stat-icon sales">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo number_format($stats['total_orders'] ?? 0); ?></span>
                        <span class="stat-label">Commandes</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo number_format($stats['total_revenue'] ?? 0, 2); ?> €</span>
                        <span class="stat-label">Revenus totaux</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo number_format($stats['avg_order'] ?? 0, 2); ?> €</span>
                        <span class="stat-label">Panier moyen</span>
                    </div>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="admin-toolbar">
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label>Du:</label>
                        <input type="date" name="from" value="<?php echo escape($dateFrom); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Au:</label>
                        <input type="date" name="to" value="<?php echo escape($dateTo); ?>" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                    <a href="/admin/orders.php" class="btn btn-outline">Réinitialiser</a>
                </form>
            </div>
            
            <!-- Table des commandes -->
            <div class="admin-card">
                <div class="card-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Jeu</th>
                                <th>Client</th>
                                <th>Prix</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td>
                                    <div class="table-game">
                                        <img src="<?php echo getGameImage($order['game_image']); ?>" alt=""
                                             onerror="this.src='/assets/images/placeholder.jpg'">
                                        <span><?php echo escape($order['game_title']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo escape($order['username']); ?></strong>
                                    <br><small><?php echo escape($order['email']); ?></small>
                                </td>
                                <td><?php echo number_format($order['purchase_price'], 2); ?> €</td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['purchase_date'])); ?></td>
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
                <a href="?page=<?php echo $i; ?>&from=<?php echo urlencode($dateFrom); ?>&to=<?php echo urlencode($dateTo); ?>" 
                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
