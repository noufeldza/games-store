<?php
/**
 * Page d'accueil - Games Store
 * Affiche les jeux en vedette et les promotions
 */

$pageTitle = "Accueil";
require_once __DIR__ . '/includes/header.php';

// Récupérer les jeux en vedette
$featuredGames = fetchAll("
    SELECT g.*, c.name as category_name,
           COALESCE(g.discount_price, g.price) as final_price,
           CASE WHEN g.discount_price IS NOT NULL 
                THEN ROUND((1 - g.discount_price/g.price) * 100) 
                ELSE 0 END as discount_percent
    FROM games g 
    LEFT JOIN categories c ON g.category_id = c.id 
    WHERE g.is_featured = 1 
    ORDER BY g.created_at DESC 
    LIMIT 6
");

// Récupérer les jeux en promotion
$discountedGames = fetchAll("
    SELECT g.*, c.name as category_name,
           COALESCE(g.discount_price, g.price) as final_price,
           ROUND((1 - g.discount_price/g.price) * 100) as discount_percent
    FROM games g 
    LEFT JOIN categories c ON g.category_id = c.id 
    WHERE g.discount_price IS NOT NULL 
    ORDER BY discount_percent DESC 
    LIMIT 8
");

// Récupérer les dernières sorties
$newReleases = fetchAll("
    SELECT g.*, c.name as category_name,
           COALESCE(g.discount_price, g.price) as final_price
    FROM games g 
    LEFT JOIN categories c ON g.category_id = c.id 
    ORDER BY g.release_date DESC 
    LIMIT 8
");

// Récupérer les catégories
$categories = fetchAll("SELECT * FROM categories ORDER BY name");
?>

<!-- Hero Section avec Carrousel -->
<section class="hero-section">
    <div class="hero-carousel">
        <?php if (!empty($featuredGames)): ?>
            <?php foreach ($featuredGames as $index => $game): ?>
            <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>" 
                 style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.8)), url('<?php echo getBannerImage($game['image'], $game['banner_image'] ?? null); ?>');">
                <div class="hero-content">
                    <span class="hero-badge">En vedette</span>
                    <h1 class="hero-title"><?php echo escape($game['title']); ?></h1>
                    <p class="hero-description"><?php echo escape(substr($game['description'], 0, 200)); ?>...</p>
                    <div class="hero-meta">
                        <span class="hero-category"><i class="fas fa-tag"></i> <?php echo escape($game['category_name'] ?? 'Non catégorisé'); ?></span>
                        <span class="hero-developer"><i class="fas fa-code"></i> <?php echo escape($game['developer']); ?></span>
                    </div>
                    <div class="hero-price">
                        <?php if ($game['discount_price']): ?>
                            <span class="price-discount">-<?php echo $game['discount_percent']; ?>%</span>
                            <span class="price-original"><?php echo number_format($game['price'], 2); ?> €</span>
                        <?php endif; ?>
                        <span class="price-final"><?php echo number_format($game['final_price'], 2); ?> €</span>
                    </div>
                    <div class="hero-actions">
                        <a href="/pages/game-detail.php?id=<?php echo $game['id']; ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-info-circle"></i> Voir le jeu
                        </a>
                        <button class="btn btn-success btn-lg add-to-cart" data-game-id="<?php echo $game['id']; ?>">
                            <i class="fas fa-cart-plus"></i> Ajouter au panier
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="hero-slide active" style="background: linear-gradient(135deg, #1b2838 0%, #2a475e 100%);">
                <div class="hero-content">
                    <h1 class="hero-title">Bienvenue sur Games Store</h1>
                    <p class="hero-description">Découvrez les meilleurs jeux vidéo à des prix imbattables</p>
                    <a href="/pages/store.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-store"></i> Explorer la boutique
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Navigation du carrousel -->
    <?php if (count($featuredGames) > 1): ?>
    <div class="hero-nav">
        <button class="hero-nav-btn prev"><i class="fas fa-chevron-left"></i></button>
        <div class="hero-dots">
            <?php for ($i = 0; $i < count($featuredGames); $i++): ?>
            <span class="hero-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></span>
            <?php endfor; ?>
        </div>
        <button class="hero-nav-btn next"><i class="fas fa-chevron-right"></i></button>
    </div>
    <?php endif; ?>
</section>

<!-- Catégories -->
<section class="categories-section">
    <div class="container">
        <h2 class="section-title"><i class="fas fa-th-large"></i> Parcourir par catégorie</h2>
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
            <a href="/pages/store.php?category=<?php echo $category['id']; ?>" class="category-card">
                <i class="fas <?php echo escape($category['icon'] ?? 'fa-gamepad'); ?>"></i>
                <span><?php echo escape($category['name']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Promotions -->
<?php if (!empty($discountedGames)): ?>
<section class="deals-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-fire"></i> Promotions du moment</h2>
            <a href="/pages/store.php?discount=1" class="see-all-link">Voir toutes les promos <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="games-grid">
            <?php foreach ($discountedGames as $game): ?>
            <div class="game-card">
                <a href="/pages/game-detail.php?id=<?php echo $game['id']; ?>" class="game-image">
                    <img src="<?php echo getGameImage($game['image']); ?>" alt="<?php echo escape($game['title']); ?>" onerror="this.src='/assets/images/placeholder.svg'">
                    <span class="discount-badge">-<?php echo $game['discount_percent']; ?>%</span>
                </a>
                <div class="game-info">
                    <span class="game-category"><?php echo escape($game['category_name'] ?? 'Jeu'); ?></span>
                    <h3 class="game-title">
                        <a href="/pages/game-detail.php?id=<?php echo $game['id']; ?>"><?php echo escape($game['title']); ?></a>
                    </h3>
                    <div class="game-price">
                        <span class="price-original"><?php echo number_format($game['price'], 2); ?> €</span>
                        <span class="price-final"><?php echo number_format($game['final_price'], 2); ?> €</span>
                    </div>
                    <div class="game-actions">
                        <button class="btn btn-primary btn-sm add-to-cart" data-game-id="<?php echo $game['id']; ?>">
                            <i class="fas fa-cart-plus"></i>
                        </button>
                        <button class="btn btn-outline btn-sm toggle-wishlist" data-game-id="<?php echo $game['id']; ?>">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Nouvelles sorties -->
<?php if (!empty($newReleases)): ?>
<section class="new-releases-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-star"></i> Nouvelles sorties</h2>
            <a href="/pages/store.php?sort=release_date&order=DESC" class="see-all-link">Voir tout <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="games-grid">
            <?php foreach ($newReleases as $game): ?>
            <div class="game-card">
                <a href="/pages/game-detail.php?id=<?php echo $game['id']; ?>" class="game-image">
                    <img src="<?php echo getGameImage($game['image']); ?>" alt="<?php echo escape($game['title']); ?>" onerror="this.src='/assets/images/placeholder.svg'">
                    <?php if ($game['discount_price']): ?>
                    <span class="discount-badge">-<?php echo round((1 - $game['discount_price']/$game['price']) * 100); ?>%</span>
                    <?php endif; ?>
                </a>
                <div class="game-info">
                    <span class="game-category"><?php echo escape($game['category_name'] ?? 'Jeu'); ?></span>
                    <h3 class="game-title">
                        <a href="/pages/game-detail.php?id=<?php echo $game['id']; ?>"><?php echo escape($game['title']); ?></a>
                    </h3>
                    <div class="game-meta">
                        <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($game['release_date'])); ?></span>
                    </div>
                    <div class="game-price">
                        <?php if ($game['discount_price']): ?>
                        <span class="price-original"><?php echo number_format($game['price'], 2); ?> €</span>
                        <?php endif; ?>
                        <span class="price-final"><?php echo number_format($game['final_price'], 2); ?> €</span>
                    </div>
                    <div class="game-actions">
                        <button class="btn btn-primary btn-sm add-to-cart" data-game-id="<?php echo $game['id']; ?>">
                            <i class="fas fa-cart-plus"></i>
                        </button>
                        <button class="btn btn-outline btn-sm toggle-wishlist" data-game-id="<?php echo $game['id']; ?>">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Call to Action -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Rejoignez Games Store</h2>
            <p>Créez un compte gratuit pour accéder à des offres exclusives et suivre vos jeux préférés</p>
            <?php if (!isLoggedIn()): ?>
            <div class="cta-buttons">
                <a href="/pages/register.php" class="btn btn-primary btn-lg">Créer un compte</a>
                <a href="/pages/login.php" class="btn btn-outline btn-lg">Se connecter</a>
            </div>
            <?php else: ?>
            <a href="/pages/store.php" class="btn btn-primary btn-lg">Explorer la boutique</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<script src="/js/carousel.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
