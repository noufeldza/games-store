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


    <link rel="stylesheet" href="/css/library.css">

<section class="library-section">
    <div class="container">
        <div class="library-hero-card modern-card">
            <div class="library-hero-icon"><i class="fas fa-book"></i></div>
            <div class="library-hero-content">
                <h1 class="library-title">Ma Bibliothèque</h1>
                <div class="library-stats-grid">
                    <div class="library-stat">
                        <i class="fas fa-gamepad"></i>
                        <span class="stat-value"><?php echo $stats['total_games'] ?? 0; ?></span>
                        <span class="stat-label">Jeux</span>
                    </div>
                    <div class="library-stat">
                        <i class="fas fa-euro-sign"></i>
                        <span class="stat-value"><?php echo number_format($stats['total_spent'] ?? 0, 2); ?></span>
                        <span class="stat-label">Dépensés</span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($games)): ?>
        <div class="empty-library-card modern-card">
            <i class="fas fa-book-open fa-4x"></i>
            <h2>Votre bibliothèque est vide</h2>
            <p>Achetez des jeux pour les voir apparaître ici</p>
            <a href="/pages/store.php" class="btn btn-primary btn-lg">
                <i class="fas fa-store"></i> Découvrir la boutique
            </a>
        </div>
        <?php else: ?>

        <div class="library-filters-bar modern-card">
            <div class="library-search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="librarySearch" class="form-control" placeholder="Rechercher dans ma bibliothèque...">
            </div>
            <div class="library-sort-select">
                <select id="sortLibrary" class="form-control">
                    <option value="date_desc">Date d'achat (récent)</option>
                    <option value="date_asc">Date d'achat (ancien)</option>
                    <option value="title_asc">Nom (A-Z)</option>
                    <option value="title_desc">Nom (Z-A)</option>
                </select>
            </div>
        </div>

        <div class="library-cards-grid" id="libraryGrid">
            <?php foreach ($games as $game): ?>
            <div class="library-card modern-card" data-title="<?php echo strtolower(escape($game['title'])); ?>">
                <a href="/pages/game-detail.php?id=<?php echo $game['id']; ?>" class="library-card-image">
                    <img src="<?php echo getGameImage($game['image']); ?>"
                         alt="<?php echo escape($game['title']); ?>"
                         onerror="this.src='/assets/images/placeholder.jpg'">
                    <div class="library-card-overlay">
                        <button class="btn btn-primary btn-play">
                            <i class="fas fa-play"></i> Jouer
                        </button>
                    </div>
                </a>
                <div class="library-card-info">
                    <h3 class="library-card-title">
                        <a href="/pages/game-detail.php?id=<?php echo $game['id']; ?>">
                            <?php echo escape($game['title']); ?>
                        </a>
                    </h3>
                    <span class="library-card-category"><?php echo escape($game['category_name'] ?? 'Jeu'); ?></span>
                    <div class="library-card-meta">
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
// Recherche dans la bibliothèque (modern)
document.getElementById('librarySearch')?.addEventListener('input', function() {
    const search = this.value.toLowerCase();
    document.querySelectorAll('.library-card').forEach(item => {
        const title = item.dataset.title;
        item.style.display = title.includes(search) ? '' : 'none';
    });
});

// Tri de la bibliothèque (modern)
document.getElementById('sortLibrary')?.addEventListener('change', function() {
    const grid = document.getElementById('libraryGrid');
    const items = Array.from(grid.children);
    const sortValue = this.value;

    items.sort((a, b) => {
        if (sortValue === 'title_asc') {
            return a.dataset.title.localeCompare(b.dataset.title);
        } else if (sortValue === 'title_desc') {
            return b.dataset.title.localeCompare(a.dataset.title);
        } else if (sortValue === 'date_asc') {
            return new Date(a.querySelector('.library-card-meta span').textContent.split('le ')[1].split(' ')[0].split('/').reverse().join('-')) - new Date(b.querySelector('.library-card-meta span').textContent.split('le ')[1].split(' ')[0].split('/').reverse().join('-'));
        } else if (sortValue === 'date_desc') {
            return new Date(b.querySelector('.library-card-meta span').textContent.split('le ')[1].split(' ')[0].split('/').reverse().join('-')) - new Date(a.querySelector('.library-card-meta span').textContent.split('le ')[1].split(' ')[0].split('/').reverse().join('-'));
        }
        return 0;
    });
    items.forEach(item => grid.appendChild(item));
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
