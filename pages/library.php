<?php
/**
 * Bibliothèque - Games Store
 * Liste des jeux achetés par l'utilisateur
 */

$pageTitle = "Ma Bibliothèque";
require_once __DIR__ . '/../includes/header.php';

requireLogin();

$userId = $_SESSION['user_id'];

// Récupérer les jeux achetés
$games = fetchAll("
    SELECT p.id as purchase_id, p.purchase_price, p.purchase_date,
           g.*, c.name as category_name
    FROM purchases p
    JOIN games g ON p.game_id = g.id
    LEFT JOIN categories c ON g.category_id = c.id
    WHERE p.user_id = ?
    ORDER BY p.purchase_date DESC
", [$userId]);

// Statistiques
$stats = fetchOne("
    SELECT 
        COUNT(*) as total_games,
        SUM(purchase_price) as total_spent
    FROM purchases 
    WHERE user_id = ?
", [$userId]);
?>

<section class="library-section">
    <div class="container">
        <div class="library-header">
            <h1 class="page-title"><i class="fas fa-book"></i> Ma Bibliothèque</h1>
            <div class="library-stats">
                <div class="stat-item">
                    <i class="fas fa-gamepad"></i>
                    <span class="stat-value"><?php echo $stats['total_games'] ?? 0; ?></span>
                    <span class="stat-label">Jeux</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-euro-sign"></i>
                    <span class="stat-value"><?php echo number_format($stats['total_spent'] ?? 0, 2); ?></span>
                    <span class="stat-label">Dépensés</span>
                </div>
            </div>
        </div>
        
        <?php if (empty($games)): ?>
        <div class="empty-library">
            <i class="fas fa-book-open fa-4x"></i>
            <h2>Votre bibliothèque est vide</h2>
            <p>Achetez des jeux pour les voir apparaître ici</p>
            <a href="/pages/store.php" class="btn btn-primary btn-lg">
                <i class="fas fa-store"></i> Découvrir la boutique
            </a>
        </div>
        <?php else: ?>
        
        <!-- Filtres et recherche -->
        <div class="library-filters">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="librarySearch" placeholder="Rechercher dans ma bibliothèque...">
            </div>
            <div class="filter-options">
                <select id="sortLibrary" class="form-control">
                    <option value="date_desc">Date d'achat (récent)</option>
                    <option value="date_asc">Date d'achat (ancien)</option>
                    <option value="title_asc">Nom (A-Z)</option>
                    <option value="title_desc">Nom (Z-A)</option>
                </select>
            </div>
        </div>
        
        <!-- Liste des jeux -->
        <div class="library-grid" id="libraryGrid">
            <?php foreach ($games as $game): ?>
            <div class="library-item" data-title="<?php echo strtolower(escape($game['title'])); ?>">
                <a href="/pages/game-detail.php?id=<?php echo $game['id']; ?>" class="library-item-image">
                    <img src="<?php echo getGameImage($game['image']); ?>" 
                         alt="<?php echo escape($game['title']); ?>"
                         onerror="this.src='/assets/images/placeholder.jpg'">
                    <div class="library-item-overlay">
                        <button class="btn btn-primary">
                            <i class="fas fa-play"></i> Jouer
                        </button>
                    </div>
                </a>
                <div class="library-item-info">
                    <h3 class="library-item-title">
                        <a href="/pages/game-detail.php?id=<?php echo $game['id']; ?>">
                            <?php echo escape($game['title']); ?>
                        </a>
                    </h3>
                    <span class="library-item-category"><?php echo escape($game['category_name'] ?? 'Jeu'); ?></span>
                    <div class="library-item-meta">
                        <span><i class="fas fa-calendar"></i> Acheté le <?php echo date('d/m/Y', strtotime($game['purchase_date'])); ?></span>
                        <span><i class="fas fa-tag"></i> <?php echo number_format($game['purchase_price'], 2); ?> €</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Recherche dans la bibliothèque
document.getElementById('librarySearch')?.addEventListener('input', function() {
    const search = this.value.toLowerCase();
    document.querySelectorAll('.library-item').forEach(item => {
        const title = item.dataset.title;
        item.style.display = title.includes(search) ? '' : 'none';
    });
});

// Tri de la bibliothèque
document.getElementById('sortLibrary')?.addEventListener('change', function() {
    const grid = document.getElementById('libraryGrid');
    const items = Array.from(grid.children);
    
    items.sort((a, b) => {
        switch (this.value) {
            case 'title_asc':
                return a.dataset.title.localeCompare(b.dataset.title);
            case 'title_desc':
                return b.dataset.title.localeCompare(a.dataset.title);
            default:
                return 0;
        }
    });
    
    items.forEach(item => grid.appendChild(item));
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
