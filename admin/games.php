<?php
/**
 * Gestion des jeux - Admin Games Store
 */

$pageTitle = "Gestion des jeux";
require_once __DIR__ . '/../includes/header.php';

requireAdmin();

// Récupérer les catégories pour le formulaire
$categories = fetchAll("SELECT * FROM categories ORDER BY name");

// Pagination et filtres
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';

$where = '';
$params = [];
if (!empty($search)) {
    $where = "WHERE g.title LIKE ? OR g.developer LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Compter le total
$totalGames = fetchOne("SELECT COUNT(*) as count FROM games g $where", $params)['count'];
$totalPages = ceil($totalGames / $limit);

// Récupérer les jeux
$games = fetchAll("
    SELECT g.*, c.name as category_name
    FROM games g
    LEFT JOIN categories c ON g.category_id = c.id
    $where
    ORDER BY g.created_at DESC
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
                <a href="/admin/games.php" class="admin-nav-link active">
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
                <h1>Gestion des jeux</h1>
                <button class="btn btn-primary" id="addGameBtn">
                    <i class="fas fa-plus"></i> Ajouter un jeu
                </button>
            </div>
            
            <!-- Barre de recherche -->
            <div class="admin-toolbar">
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Rechercher un jeu..." 
                           value="<?php echo escape($search); ?>" class="form-control">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <span class="results-info"><?php echo $totalGames; ?> jeu(x)</span>
            </div>
            
            <!-- Table des jeux -->
            <div class="admin-card">
                <div class="card-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Titre</th>
                                <th>Catégorie</th>
                                <th>Prix</th>
                                <th>Promo</th>
                                <th>Vedette</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($games as $game): ?>
                            <tr data-game-id="<?php echo $game['id']; ?>">
                                <td>
                                    <img src="<?php echo getGameImage($game['image']); ?>" 
                                         alt="" class="table-image"
                                         onerror="this.src='/assets/images/placeholder.jpg'">
                                </td>
                                <td>
                                    <strong><?php echo escape($game['title']); ?></strong>
                                    <br><small><?php echo escape($game['developer']); ?></small>
                                </td>
                                <td><?php echo escape($game['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format($game['price'], 2); ?> €</td>
                                <td>
                                    <?php if ($game['discount_price']): ?>
                                    <span class="badge badge-success"><?php echo number_format($game['discount_price'], 2); ?> €</span>
                                    <?php else: ?>
                                    <span class="badge badge-secondary">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="toggle-featured btn btn-sm <?php echo $game['is_featured'] ? 'btn-warning' : 'btn-outline'; ?>"
                                            data-game-id="<?php echo $game['id']; ?>">
                                        <i class="fas fa-star"></i>
                                    </button>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-primary edit-game" 
                                                data-game='<?php echo htmlspecialchars(json_encode($game), ENT_QUOTES); ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-game" 
                                                data-game-id="<?php echo $game['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                   class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</section>

<!-- Modal Ajout/Édition de jeu -->
<div id="gameModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-gamepad"></i> Ajouter un jeu</h2>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="gameForm">
                <input type="hidden" name="id" id="gameId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Titre *</label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Catégorie</label>
                        <select id="category_id" name="category_id" class="form-control">
                            <option value="">Sélectionner...</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo escape($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Prix *</label>
                        <input type="number" id="price" name="price" class="form-control" 
                               step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="discount_price">Prix promo</label>
                        <input type="number" id="discount_price" name="discount_price" class="form-control" 
                               step="0.01" min="0">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="developer">Développeur</label>
                        <input type="text" id="developer" name="developer" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="publisher">Éditeur</label>
                        <input type="text" id="publisher" name="publisher" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="release_date">Date de sortie</label>
                        <input type="date" id="release_date" name="release_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="image">Image (nom du fichier)</label>
                        <input type="text" id="image" name="image" class="form-control" 
                               placeholder="game-image.jpg">
                        <small class="form-hint">Dossier: /assets/images/</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="banner_image"><i class="fas fa-image"></i> Bannière (nom du fichier)</label>
                    <input type="text" id="banner_image" name="banner_image" class="form-control" 
                           placeholder="banner-image.jpg">
                    <small class="form-hint">Dossier: /assets/images/banners/ - Image large pour le carrousel et fond de page</small>
                </div>
                
                <div class="form-group">
                    <label for="video_url">URL de la vidéo</label>
                    <input type="url" id="video_url" name="video_url" class="form-control" 
                           placeholder="https://www.youtube.com/embed/...">
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="is_featured" name="is_featured" value="1">
                        <span class="checkmark"></span>
                        Mettre en vedette
                    </label>
                </div>
                
                <div id="gameFormMessage" class="alert" style="display: none;"></div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline modal-cancel">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/js/admin-games.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
