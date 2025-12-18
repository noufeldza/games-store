<?php
/**
 * Page Panier - Games Store
 */

$pageTitle = "Mon Panier";
require_once __DIR__ . '/../includes/header.php';

requireLogin();

$userId = $_SESSION['user_id'];

// Récupérer le panier
$cartItems = fetchAll("
    SELECT c.id as cart_id, c.added_at, g.*,
           COALESCE(g.discount_price, g.price) as final_price,
           CASE WHEN g.discount_price IS NOT NULL 
                THEN ROUND((1 - g.discount_price/g.price) * 100) 
                ELSE 0 END as discount_percent,
           cat.name as category_name
    FROM cart c 
    JOIN games g ON c.game_id = g.id 
    LEFT JOIN categories cat ON g.category_id = cat.id
    WHERE c.user_id = ?
    ORDER BY c.added_at DESC
", [$userId]);

// Calculer les totaux
$total = 0;
$originalTotal = 0;
foreach ($cartItems as $item) {
    $total += $item['final_price'];
    $originalTotal += $item['price'];
}
$savings = $originalTotal - $total;
?>

<section class="cart-section">
    <div class="container">
        <h1 class="page-title"><i class="fas fa-shopping-cart"></i> Mon Panier</h1>
        
        <?php if (empty($cartItems)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart fa-4x"></i>
            <h2>Votre panier est vide</h2>
            <p>Découvrez nos meilleurs jeux et ajoutez-les à votre panier</p>
            <a href="/pages/store.php" class="btn btn-primary btn-lg">
                <i class="fas fa-store"></i> Explorer la boutique
            </a>
        </div>
        <?php else: ?>
        
        <div class="cart-layout">
            <!-- Liste des jeux -->
            <div class="cart-items">
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item" data-cart-id="<?php echo $item['cart_id']; ?>" data-game-id="<?php echo $item['id']; ?>">
                    <a href="/pages/game-detail.php?id=<?php echo $item['id']; ?>" class="cart-item-image">
                        <img src="<?php echo getGameImage($item['image']); ?>" 
                             alt="<?php echo escape($item['title']); ?>"
                             onerror="this.src='/assets/images/placeholder.jpg'">
                    </a>
                    <div class="cart-item-info">
                        <span class="cart-item-category"><?php echo escape($item['category_name'] ?? 'Jeu'); ?></span>
                        <h3 class="cart-item-title">
                            <a href="/pages/game-detail.php?id=<?php echo $item['id']; ?>">
                                <?php echo escape($item['title']); ?>
                            </a>
                        </h3>
                        <p class="cart-item-developer"><?php echo escape($item['developer']); ?></p>
                        <span class="cart-item-date">
                            Ajouté le <?php echo date('d/m/Y à H:i', strtotime($item['added_at'])); ?>
                        </span>
                    </div>
                    <div class="cart-item-price">
                        <?php if ($item['discount_percent'] > 0): ?>
                        <span class="discount-badge">-<?php echo $item['discount_percent']; ?>%</span>
                        <span class="price-original"><?php echo number_format($item['price'], 2); ?> €</span>
                        <?php endif; ?>
                        <span class="price-final"><?php echo number_format($item['final_price'], 2); ?> €</span>
                    </div>
                    <div class="cart-item-actions">
                        <button class="btn btn-outline btn-sm move-to-wishlist" data-game-id="<?php echo $item['id']; ?>">
                            <i class="far fa-heart"></i>
                        </button>
                        <button class="btn btn-danger btn-sm remove-from-cart" data-game-id="<?php echo $item['id']; ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Résumé de commande -->
            <div class="cart-summary">
                <h3>Résumé de la commande</h3>
                
                <div class="summary-row">
                    <span>Prix total</span>
                    <span id="originalTotal"><?php echo number_format($originalTotal, 2); ?> €</span>
                </div>
                
                <?php if ($savings > 0): ?>
                <div class="summary-row discount">
                    <span>Économies</span>
                    <span id="savings">-<?php echo number_format($savings, 2); ?> €</span>
                </div>
                <?php endif; ?>
                
                <div class="summary-divider"></div>
                
                <div class="summary-row total">
                    <span>Total</span>
                    <span id="cartTotal"><?php echo number_format($total, 2); ?> €</span>
                </div>
                
                <button id="checkoutBtn" class="btn btn-success btn-block btn-lg">
                    <i class="fas fa-credit-card"></i> Passer la commande
                </button>
                
                <button id="clearCartBtn" class="btn btn-outline btn-block">
                    <i class="fas fa-trash"></i> Vider le panier
                </button>
                
                <a href="/pages/store.php" class="btn btn-link btn-block">
                    <i class="fas fa-arrow-left"></i> Continuer les achats
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Modal de paiement -->
<div id="checkoutModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-credit-card"></i> Finaliser l'achat</h2>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="checkoutForm">
                <div class="payment-methods">
                    <h4>Méthode de paiement</h4>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="card" checked>
                        <span class="payment-label">
                            <i class="fas fa-credit-card"></i> Carte bancaire
                        </span>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="paypal">
                        <span class="payment-label">
                            <i class="fab fa-paypal"></i> PayPal
                        </span>
                    </label>
                </div>
                
                <div class="card-details" id="cardDetails">
                    <div class="form-group">
                        <label>Numéro de carte</label>
                        <input type="text" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date d'expiration</label>
                            <input type="text" class="form-control" placeholder="MM/AA" maxlength="5">
                        </div>
                        <div class="form-group">
                            <label>CVV</label>
                            <input type="text" class="form-control" placeholder="123" maxlength="3">
                        </div>
                    </div>
                </div>
                
                <div class="order-summary-modal">
                    <div class="summary-row total">
                        <span>Total à payer</span>
                        <span id="modalTotal"><?php echo number_format($total, 2); ?> €</span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success btn-block btn-lg">
                    <i class="fas fa-lock"></i> Confirmer le paiement
                </button>
            </form>
        </div>
    </div>
</div>

<script src="/js/cart.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
