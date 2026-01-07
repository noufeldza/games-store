<?php
/**
 * Gestion des commandes - Admin Games Store
 */

$pageTitle = "Gestion des commandes";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/header.php';
echo '<link rel="stylesheet" href="/css/orders.css">';
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

// Stats par période
$todayRevenue = fetchOne("
    SELECT SUM(purchase_price) as revenue 
    FROM purchases 
    WHERE DATE(purchase_date) = CURDATE()
")['revenue'] ?? 0;

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

<link rel="stylesheet" href="/css/admin.css">

<section class="admin-section">
    <div class="admin-container">
        <!-- Sidebar Admin -->
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <i class="fas fa-gamepad"></i>
                <div class="logo-text">
                    <span class="logo-title">Admin Panel</span>
                </div>
            </div>
            
            <nav class="admin-nav">
                <a href="/admin/index.php" class="admin-nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="/admin/games.php" class="admin-nav-link">
                    <i class="fas fa-gamepad"></i>
                    <span>Jeux</span>
                </a>
                <a href="/admin/users.php" class="admin-nav-link">
                    <i class="fas fa-users"></i>
                    <span>Utilisateurs</span>
                </a>
                <a href="/admin/categories.php" class="admin-nav-link">
                    <i class="fas fa-tags"></i>
                    <span>Catégories</span>
                </a>
                <a href="/admin/orders.php" class="admin-nav-link active">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Commandes</span>
                    <span class="nav-badge"><?php echo $totalOrders; ?></span>
                </a>
                <div class="admin-nav-divider"></div>
                <a href="/index.php" class="admin-nav-link">
                    <i class="fas fa-home"></i>
                    <span>Retour au site</span>
                </a>
            </nav>
            
            <div class="admin-user-info">
                <img src="/assets/images/avatars/<?php echo escape($_SESSION['avatar'] ?? 'default-avatar.png'); ?>" 
                     alt="Avatar" class="admin-avatar"
                     onerror="this.src='/assets/images/avatars/default-avatar.png'">
                <div class="admin-user-details">
                    <span class="admin-username"><?php echo escape($_SESSION['username'] ?? 'Admin'); ?></span>
                    <span class="admin-role">Administrateur</span>
                </div>
            </div>
        </aside>
        
        <!-- Contenu principal -->
        <main class="admin-content">
            <div class="admin-header">
                <div class="header-content">
                    <h1><i class="fas fa-shopping-bag"></i> Gestion des commandes</h1>
                    <p class="header-subtitle">Consultez et analysez toutes les transactions</p>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo number_format($stats['total_orders'] ?? 0); ?></span>
                        <span class="stat-label">Total commandes</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo number_format($stats['total_revenue'] ?? 0, 2); ?> €</span>
                        <span class="stat-label">Revenus totaux</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo number_format($stats['avg_order'] ?? 0, 2); ?> €</span>
                        <span class="stat-label">Panier moyen</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo number_format($todayRevenue, 2); ?> €</span>
                        <span class="stat-label">Aujourd'hui</span>
                    </div>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="admin-toolbar">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label for="dateFrom">
                            <i class="fas fa-calendar-alt"></i> Du:
                        </label>
                        <input type="date" id="dateFrom" name="from" 
                               value="<?php echo escape($dateFrom); ?>" class="form-control">
                    </div>
                    <div class="filter-group">
                        <label for="dateTo">
                            <i class="fas fa-calendar-alt"></i> Au:
                        </label>
                        <input type="date" id="dateTo" name="to" 
                               value="<?php echo escape($dateTo); ?>" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                    <?php if (!empty($dateFrom) || !empty($dateTo)): ?>
                    <a href="/admin/orders.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Réinitialiser
                    </a>
                    <?php endif; ?>
                </form>
                <div class="toolbar-actions">
                    <button class="btn btn-success" onclick="exportOrders()">
                        <i class="fas fa-download"></i> Exporter
                    </button>
                </div>
            </div>
            
            <!-- Table des commandes -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Liste des commandes</h3>
                    <span class="results-info">
                        <?php echo $totalOrders; ?> commande<?php echo $totalOrders > 1 ? 's' : ''; ?>
                        <?php if (!empty($dateFrom) || !empty($dateTo)): ?>
                        <span class="filter-active">
                            <i class="fas fa-filter"></i> Filtrée<?php echo $totalOrders > 1 ? 's' : ''; ?>
                        </span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="admin-table orders-table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag"></i> ID</th>
                                    <th><i class="fas fa-gamepad"></i> Jeu</th>
                                    <th><i class="fas fa-user"></i> Client</th>
                                    <th><i class="fas fa-euro-sign"></i> Prix</th>
                                    <th><i class="fas fa-clock"></i> Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p>Aucune commande trouvée</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                <tr class="order-row">
                                    <td>
                                        <span class="order-id">#<?php echo $order['id']; ?></span>
                                    </td>
                                    <td>
                                        <div class="table-game">
                                            <img src="<?php echo getGameImage($order['game_image']); ?>" alt=""
                                                 onerror="this.src='/assets/images/placeholder.jpg'">
                                            <span class="game-name"><?php echo escape($order['game_title']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="customer-info">
                                            <strong class="customer-name"><?php echo escape($order['username']); ?></strong>
                                            <span class="customer-email"><?php echo escape($order['email']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="price-badge"><?php echo number_format($order['purchase_price'], 2); ?> €</span>
                                    </td>
                                    <td>
                                        <div class="date-info">
                                            <span class="date-main"><?php echo date('d/m/Y', strtotime($order['purchase_date'])); ?></span>
                                            <span class="date-time"><?php echo date('H:i', strtotime($order['purchase_date'])); ?></span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="pagination">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&from=<?php echo urlencode($dateFrom); ?>&to=<?php echo urlencode($dateTo); ?>" 
                   class="page-link page-prev">
                    <i class="fas fa-chevron-left"></i> Précédent
                </a>
                <?php endif; ?>
                
                <div class="page-numbers">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="?page=<?php echo $i; ?>&from=<?php echo urlencode($dateFrom); ?>&to=<?php echo urlencode($dateTo); ?>" 
                           class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                        <span class="page-ellipsis">...</span>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&from=<?php echo urlencode($dateFrom); ?>&to=<?php echo urlencode($dateTo); ?>" 
                   class="page-link page-next">
                    Suivant <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</section>

<script>
function exportOrders() {
    // Fonction d'export - à implémenter selon vos besoins
    const from = '<?php echo $dateFrom; ?>';
    const to = '<?php echo $dateTo; ?>';
    window.location.href = `/api/export-orders.php?from=${from}&to=${to}`;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>