<?php
/**
 * Wishlist - Games Store
 */

$pageTitle = "Ma Wishlist";
require_once __DIR__ . '/../includes/header.php';

requireLogin();

$userId = $_SESSION['user_id'];

// Récupérer la wishlist
$wishlistItems = fetchAll("
    SELECT w.id as wishlist_id, w.added_at, g.*,
           COALESCE(g.discount_price, g.price) as final_price,
           CASE WHEN g.discount_price IS NOT NULL 
                THEN ROUND((1 - g.discount_price/g.price) * 100) 
                ELSE 0 END as discount_percent,
           c.name as category_name
    FROM wishlists w
    JOIN games g ON w.game_id = g.id
    LEFT JOIN categories c ON g.category_id = c.id
    WHERE w.user_id = ?
    ORDER BY w.added_at DESC
", [$userId]);
?>

<section class="wishlist-section">
    <div class="container">
        <h1 class="page-title"><i class="fas fa-heart"></i> Ma Wishlist</h1>
        
        <?php if (empty($wishlistItems)): ?>
        <div class="empty-wishlist">
            <i class="far fa-heart fa-4x"></i>
            <h2>Votre wishlist est vide</h2>
            <p>Ajoutez des jeux à votre liste de souhaits pour les retrouver facilement</p>
            <a href="/pages/store.php" class="btn btn-primary btn-lg">
                <i class="fas fa-store"></i> Explorer la boutique
            </a>
        </div>
        <?php else: ?>
        
        <div class="wishlist-actions-bar">
            <span><?php echo count($wishlistItems); ?> jeu(x) dans votre wishlist</span>
            <button id="addAllToCart" class="btn btn-primary">
                <i class="fas fa-cart-plus"></i> Tout ajouter au panier
            </button>
        </div>
        
        <div class="wishlist-grid">
            <?php foreach ($wishlistItems as $item): ?>
            <div class="wishlist-item" data-wishlist-id="<?php echo $item['wishlist_id']; ?>" data-game-id="<?php echo $item['id']; ?>">
                <a href="/pages/game-detail.php?id=<?php echo $item['id']; ?>" class="wishlist-item-image">
                    <img src="<?php echo getGameImage($item['image']); ?>" 
                         alt="<?php echo escape($item['title']); ?>"
                         onerror="this.src='/assets/images/placeholder.jpg'">
                    <?php if ($item['discount_percent'] > 0): ?>
                    <span class="discount-badge">-<?php echo $item['discount_percent']; ?>%</span>
                    <?php endif; ?>
                </a>
                <div class="wishlist-item-info">
                    <span class="wishlist-item-category"><?php echo escape($item['category_name'] ?? 'Jeu'); ?></span>
                    <h3 class="wishlist-item-title">
                        <a href="/pages/game-detail.php?id=<?php echo $item['id']; ?>">
                            <?php echo escape($item['title']); ?>
                        </a>
                    </h3>
                    <p class="wishlist-item-developer"><?php echo escape($item['developer']); ?></p>
                    <div class="wishlist-item-price">
                        <?php if ($item['discount_percent'] > 0): ?>
                        <span class="price-original"><?php echo number_format($item['price'], 2); ?> €</span>
                        <?php endif; ?>
                        <span class="price-final"><?php echo number_format($item['final_price'], 2); ?> €</span>
                    </div>
                    <div class="wishlist-item-actions">
                        <button class="btn btn-primary btn-sm add-to-cart" data-game-id="<?php echo $item['id']; ?>">
                            <i class="fas fa-cart-plus"></i> Ajouter au panier
                        </button>
                        <button class="btn btn-danger btn-sm remove-from-wishlist" data-game-id="<?php echo $item['id']; ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Supprimer de la wishlist
document.querySelectorAll('.remove-from-wishlist').forEach(btn => {
    btn.addEventListener('click', async function() {
        const gameId = this.dataset.gameId;
        const item = this.closest('.wishlist-item');
        
        try {
            const response = await fetch('/api/users.php?action=wishlist', {
                method: 'POST',
                body: new URLSearchParams({ game_id: gameId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                item.remove();
                
                // Vérifier si la liste est vide
                if (document.querySelectorAll('.wishlist-item').length === 0) {
                    location.reload();
                }
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    });
});

// Ajouter tout au panier
document.getElementById('addAllToCart')?.addEventListener('click', async function() {
    const items = document.querySelectorAll('.wishlist-item');
    
    for (const item of items) {
        const gameId = item.dataset.gameId;
        await fetch('/api/cart.php?action=add', {
            method: 'POST',
            body: new URLSearchParams({ game_id: gameId })
        });
    }
    
    alert('Tous les jeux ont été ajoutés au panier');
    updateCartBadge();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
