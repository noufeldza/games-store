<?php
/**
 * Dashboard Admin - Games Store
 */

$pageTitle = "Administration";
require_once __DIR__ . '/../includes/header.php';

requireAdmin();

// Statistiques générales
$stats = [
    'users' => fetchOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0,
    'games' => fetchOne("SELECT COUNT(*) as count FROM games")['count'] ?? 0,
    'purchases' => fetchOne("SELECT COUNT(*) as count FROM purchases")['count'] ?? 0,
    'revenue' => fetchOne("SELECT SUM(purchase_price) as total FROM purchases")['total'] ?? 0,
];

// Dernières ventes
$recentSales = fetchAll("
    SELECT p.*, u.username, g.title as game_title, g.image as game_image
    FROM purchases p
    JOIN users u ON p.user_id = u.id
    JOIN games g ON p.game_id = g.id
    ORDER BY p.purchase_date DESC
    LIMIT 10
");

// Derniers utilisateurs
$recentUsers = fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

// Top jeux vendus
$topGames = fetchAll("
    SELECT g.*, COUNT(p.id) as sales_count, SUM(p.purchase_price) as revenue
    FROM games g
    LEFT JOIN purchases p ON g.id = p.game_id
    GROUP BY g.id
    ORDER BY sales_count DESC
    LIMIT 5
");
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
                <a href="/admin/index.php" class="admin-nav-link active">
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
                <h1>Dashboard</h1>
                <span class="admin-date"><?php echo date('d F Y'); ?></span>
            </div>
            
            <!-- Cartes statistiques -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo number_format($stats['users']); ?></span>
                        <span class="stat-label">Utilisateurs</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon games">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo number_format($stats['games']); ?></span>
                        <span class="stat-label">Jeux</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon sales">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo number_format($stats['purchases']); ?></span>
                        <span class="stat-label">Ventes</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo number_format($stats['revenue'], 2); ?> €</span>
                        <span class="stat-label">Revenus</span>
                    </div>
                </div>
            </div>
            
            <!-- Grille de contenu -->
            <div class="admin-grid">
                <!-- Dernières ventes -->
                <div class="admin-card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Dernières ventes</h3>
                        <a href="/admin/orders.php" class="btn btn-sm btn-outline">Voir tout</a>
                    </div>
                    <div class="card-body">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Jeu</th>
                                    <th>Client</th>
                                    <th>Prix</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSales as $sale): ?>
                                <tr>
                                    <td>
                                        <div class="table-game">
                                            <img src="<?php echo getGameImage($sale['game_image']); ?>" alt="">
                                            <span><?php echo escape($sale['game_title']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo escape($sale['username']); ?></td>
                                    <td><?php echo number_format($sale['purchase_price'], 2); ?> €</td>
                                    <td><?php echo date('d/m/Y', strtotime($sale['purchase_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Top jeux -->
                <div class="admin-card">
                    <div class="card-header">
                        <h3><i class="fas fa-trophy"></i> Top Jeux</h3>
                    </div>
                    <div class="card-body">
                        <div class="top-games-list">
                            <?php foreach ($topGames as $index => $game): ?>
                            <div class="top-game-item">
                                <span class="rank">#<?php echo $index + 1; ?></span>
                                <img src="<?php echo getGameImage($game['image']); ?>" alt="">
                                <div class="game-info">
                                    <span class="game-title"><?php echo escape($game['title']); ?></span>
                                    <span class="game-stats"><?php echo $game['sales_count']; ?> ventes</span>
                                </div>
                                <span class="game-revenue"><?php echo number_format($game['revenue'] ?? 0, 2); ?> €</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Derniers utilisateurs -->
                <div class="admin-card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-plus"></i> Nouveaux membres</h3>
                        <a href="/admin/users.php" class="btn btn-sm btn-outline">Voir tout</a>
                    </div>
                    <div class="card-body">
                        <div class="users-list">
                            <?php foreach ($recentUsers as $user): ?>
                            <div class="user-item">
                                <img src="/assets/images/avatars/<?php echo escape($user['avatar']); ?>" alt="" 
                                     onerror="this.src='/assets/images/avatars/default-avatar.png'">
                                <div class="user-info">
                                    <span class="user-name"><?php echo escape($user['username']); ?></span>
                                    <span class="user-email"><?php echo escape($user['email']); ?></span>
                                </div>
                                <span class="user-role <?php echo $user['role']; ?>">
                                    <?php echo $user['role']; ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
