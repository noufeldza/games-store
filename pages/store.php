<?php
/**
 * Boutique - Games Store
 * Liste des jeux avec filtres
 */

$pageTitle = "Boutique";
require_once __DIR__ . '/../includes/header.php';

// Récupérer les catégories
$categories = fetchAll("SELECT * FROM categories ORDER BY name");

// Paramètres de filtrage
$search = $_GET['search'] ?? '';
$categoryId = $_GET['category'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$sortBy = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;
$discount = $_GET['discount'] ?? '';
$featured = $_GET['featured'] ?? '';

// Construction de la requête
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(g.title LIKE ? OR g.description LIKE ? OR g.developer LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($categoryId)) {
    $where[] = "g.category_id = ?";
    $params[] = $categoryId;
}

if (!empty($minPrice)) {
    $where[] = "COALESCE(g.discount_price, g.price) >= ?";
    $params[] = $minPrice;
}

if (!empty($maxPrice)) {
    $where[] = "COALESCE(g.discount_price, g.price) <= ?";
    $params[] = $maxPrice;
}

if ($discount === '1') {
    $where[] = "g.discount_price IS NOT NULL";
}

if ($featured === '1') {
    $where[] = "g.is_featured = 1";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Tri sécurisé
$allowedSorts = ['title', 'price', 'release_date', 'created_at', 'rating'];
$sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Compter le total
$countSql = "SELECT COUNT(*) as total FROM games g $whereClause";
$countResult = fetchOne($countSql, $params);
$totalGames = $countResult['total'] ?? 0;
$totalPages = ceil($totalGames / $limit);

// Récupérer les jeux
$sql = "
    SELECT g.*, c.name as category_name,
           COALESCE(g.discount_price, g.price) as final_price,
           CASE WHEN g.discount_price IS NOT NULL 
                THEN ROUND((1 - g.discount_price/g.price) * 100) 
                ELSE 0 END as discount_percent
    FROM games g 
    LEFT JOIN categories c ON g.category_id = c.id 
    $whereClause
    ORDER BY g.$sortBy $order
    LIMIT $limit OFFSET $offset
";
$games = fetchAll($sql, $params);

// Récupérer la catégorie sélectionnée
$selectedCategory = null;
if (!empty($categoryId)) {
    $selectedCategory = fetchOne("SELECT * FROM categories WHERE id = ?", [$categoryId]);
}
?>

<section class="store-section">
    <div class="container">
        <div class="store-layout">
            <!-- Sidebar Filtres -->
            <aside class="store-sidebar">
                <div class="sidebar-header">
                    <h3><i class="fas fa-filter"></i> Filtres</h3>
                    <a href="/pages/store.php" class="reset-filters">Réinitialiser</a>
                </div>
                
                <form id="filterForm" method="GET" action="/pages/store.php">
                    <!-- Recherche -->
                    <div class="filter-group">
                        <label>Recherche</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Nom du jeu..." value="<?php echo escape($search); ?>">
                    </div>
                    
                    <!-- Catégories -->
                    <div class="filter-group">
                        <label>Catégorie</label>
                        <select name="category" class="form-control">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo $categoryId == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo escape($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Prix -->
                    <div class="filter-group">
                        <label>Prix</label>
                        <div class="price-range">
                            <input type="number" name="min_price" class="form-control" 
                                   placeholder="Min" min="0" step="0.01" value="<?php echo escape($minPrice); ?>">
                            <span>-</span>
                            <input type="number" name="max_price" class="form-control" 
                                   placeholder="Max" min="0" step="0.01" value="<?php echo escape($maxPrice); ?>">
                        </div>
                    </div>
                    
                    <!-- Options -->
                    <div class="filter-group">
                        <label>Options</label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="discount" value="1" 
                                   <?php echo $discount === '1' ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            En promotion
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="featured" value="1"
                                   <?php echo $featured === '1' ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            En vedette
                        </label>
                    </div>
                    
                    <!-- Tri -->
                    <div class="filter-group">
                        <label>Trier par</label>
                        <select name="sort" class="form-control">
                            <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>Date d'ajout</option>
                            <option value="title" <?php echo $sortBy === 'title' ? 'selected' : ''; ?>>Nom</option>
                            <option value="price" <?php echo $sortBy === 'price' ? 'selected' : ''; ?>>Prix</option>
                            <option value="release_date" <?php echo $sortBy === 'release_date' ? 'selected' : ''; ?>>Date de sortie</option>
                            <option value="rating" <?php echo $sortBy === 'rating' ? 'selected' : ''; ?>>Note</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Ordre</label>
                        <select name="order" class="form-control">
                            <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Décroissant</option>
                            <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Croissant</option>
                        </select>
                    </div>
                </form>
            </aside>
            
            <!-- Contenu principal -->
            <main class="store-content">
                <div class="store-header">
                    <div class="store-title">
                        <?php if ($selectedCategory): ?>
                        <h1><i class="fas <?php echo escape($selectedCategory['icon'] ?? 'fa-gamepad'); ?>"></i> <?php echo escape($selectedCategory['name']); ?></h1>
                        <?php elseif ($discount === '1'): ?>
                        <h1><i class="fas fa-fire"></i> Promotions</h1>
                        <?php elseif ($featured === '1'): ?>
                        <h1><i class="fas fa-star"></i> En vedette</h1>
                        <?php elseif (!empty($search)): ?>
                        <h1><i class="fas fa-search"></i> Résultats pour "<?php echo escape($search); ?>"</h1>
                        <?php else: ?>
                        <h1><i class="fas fa-store"></i> Boutique</h1>
                        <?php endif; ?>
                        <span class="results-count"><?php echo $totalGames; ?> jeu(x) trouvé(s)</span>
                    </div>
                    
                    <div class="view-options">
                        <button class="view-btn active" data-view="grid"><i class="fas fa-th"></i></button>
                        <button class="view-btn" data-view="list"><i class="fas fa-list"></i></button>
                    </div>
                </div>
                
                <?php if (empty($games)): ?>
                <div class="no-results">
                    <i class="fas fa-search fa-3x"></i>
                    <h3>Aucun jeu trouvé</h3>
                    <p>Essayez de modifier vos filtres de recherche</p>
                    <a href="/pages/store.php" class="btn btn-primary">Voir tous les jeux</a>
                </div>
                <?php else: ?>
                
                <!-- Grille des jeux -->
                <div class="games-grid" id="gamesGrid">
                    <?php foreach ($games as $game): ?>
                    <div class="game-card">
                        <a href="/pages/game-detail.php?id=<?php echo $game['id']; ?>" class="game-image">
                            <img src="<?php echo getGameImage($game['image']); ?>" 
                                 alt="<?php echo escape($game['title']); ?>"
                                 onerror="this.src='/assets/images/placeholder.svg'">
                            <?php if ($game['discount_percent'] > 0): ?>
                            <span class="discount-badge">-<?php echo $game['discount_percent']; ?>%</span>
                            <?php endif; ?>
                            <?php if ($game['is_featured']): ?>
                            <span class="featured-badge"><i class="fas fa-star"></i></span>
                            <?php endif; ?>
                        </a>
                        <div class="game-info">
                            <span class="game-category"><?php echo escape($game['category_name'] ?? 'Jeu'); ?></span>
                            <h3 class="game-title">
                                <a href="/pages/game-detail.php?id=<?php echo $game['id']; ?>">
                                    <?php echo escape($game['title']); ?>
                                </a>
                            </h3>
                            <p class="game-developer"><?php echo escape($game['developer']); ?></p>
                            <div class="game-rating">
                                <?php 
                                $rating = $game['rating'] ?? 0;
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                <i class="<?php echo $i <= $rating ? 'fas' : 'far'; ?> fa-star"></i>
                                <?php endfor; ?>
                                <span>(<?php echo number_format($rating, 1); ?>)</span>
                            </div>
                            <div class="game-price">
                                <?php if ($game['discount_percent'] > 0): ?>
                                <span class="price-original"><?php echo number_format($game['price'], 2); ?> €</span>
                                <?php endif; ?>
                                <span class="price-final">
                                    <?php echo $game['final_price'] == 0 ? 'Gratuit' : number_format($game['final_price'], 2) . ' €'; ?>
                                </span>
                            </div>
                            <div class="game-actions">
                                <button class="btn btn-primary btn-sm add-to-cart" data-game-id="<?php echo $game['id']; ?>">
                                    <i class="fas fa-cart-plus"></i> Ajouter
                                </button>
                                <button class="btn btn-outline btn-sm toggle-wishlist" data-game-id="<?php echo $game['id']; ?>">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                       class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </nav>
                <?php endif; ?>
                
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<script src="/js/store.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
