<?php
/**
 * Page Panier - Games Store
 */

$pageTitle = "Mon Panier";
require_once __DIR__ . '/../includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

<link rel="stylesheet" href="/css/cart.css">

<section class="cart-section">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-shopping-cart"></i> Mon Panier
            </h1>
            <p class="page-subtitle">
                <?php echo count($cartItems); ?> article<?php echo count($cartItems) > 1 ? 's' : ''; ?> dans votre panier
            </p>
        </div>
        
        <?php if (empty($cartItems)): ?>
        <div class="empty-cart">
            <div class="empty-cart-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h2>Votre panier est vide</h2>
            <p>Découvrez nos meilleurs jeux et ajoutez-les à votre panier pour commencer votre aventure gaming!</p>
            <a href="/pages/store.php" class="btn btn-primary btn-lg">
                <i class="fas fa-store"></i> Explorer la boutique
            </a>
        </div>
        <?php else: ?>
        
        <div class="cart-layout">
            <!-- Liste des jeux -->
            <div class="cart-items-container">
                <div class="cart-items-header">
                    <h3><i class="fas fa-gamepad"></i> Articles dans votre panier</h3>
                    <button id="clearCartBtn" class="btn btn-outline btn-sm">
                        <i class="fas fa-trash"></i> Vider le panier
                    </button>
                </div>
                
                <div class="cart-items">
                    <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item" data-cart-id="<?php echo $item['cart_id']; ?>" data-game-id="<?php echo $item['id']; ?>">
                        <a href="/pages/game-detail.php?id=<?php echo $item['id']; ?>" class="cart-item-image">
                            <img src="<?php echo getGameImage($item['image']); ?>" 
                                 alt="<?php echo escape($item['title']); ?>"
                                 onerror="this.src='/assets/images/placeholder.jpg'">
                            <div class="image-hover-overlay">
                                <i class="fas fa-eye"></i>
                            </div>
                        </a>
                        <div class="cart-item-info">
                            <span class="cart-item-category">
                                <i class="fas fa-tag"></i>
                                <?php echo escape($item['category_name'] ?? 'Jeu'); ?>
                            </span>
                            <h3 class="cart-item-title">
                                <a href="/pages/game-detail.php?id=<?php echo $item['id']; ?>">
                                    <?php echo escape($item['title']); ?>
                                </a>
                            </h3>
                            <p class="cart-item-developer">
                                <i class="fas fa-code"></i>
                                <?php echo escape($item['developer']); ?>
                            </p>
                            <span class="cart-item-date">
                                <i class="fas fa-clock"></i>
                                Ajouté le <?php echo date('d/m/Y à H:i', strtotime($item['added_at'])); ?>
                            </span>
                        </div>
                        <div class="cart-item-price">
                            <?php if ($item['discount_percent'] > 0): ?>
                            <span class="discount-badge">-<?php echo $item['discount_percent']; ?>%</span>
                            <div class="price-container">
                                <span class="price-original"><?php echo number_format($item['price'], 2); ?> €</span>
                                <span class="price-final"><?php echo number_format($item['final_price'], 2); ?> €</span>
                            </div>
                            <?php else: ?>
                            <span class="price-final"><?php echo number_format($item['final_price'], 2); ?> €</span>
                            <?php endif; ?>
                        </div>
                        <div class="cart-item-actions">
                            <button class="btn-icon move-to-wishlist" 
                                    data-game-id="<?php echo $item['id']; ?>"
                                    title="Déplacer vers la wishlist">
                                <i class="far fa-heart"></i>
                            </button>
                            <button class="btn-icon btn-icon-danger remove-from-cart" 
                                    data-game-id="<?php echo $item['id']; ?>"
                                    title="Retirer du panier">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Résumé de commande -->
            <div class="cart-summary-container">
                <div class="cart-summary">
                    <h3><i class="fas fa-receipt"></i> Résumé de la commande</h3>
                    
                    <div class="summary-items">
                        <div class="summary-row">
                            <span class="summary-label">Prix total</span>
                            <span class="summary-value" id="originalTotal"><?php echo number_format($originalTotal, 2); ?> €</span>
                        </div>
                        
                        <?php if ($savings > 0): ?>
                        <div class="summary-row discount">
                            <span class="summary-label">
                                <i class="fas fa-tags"></i> Économies
                            </span>
                            <span class="summary-value savings" id="savings">-<?php echo number_format($savings, 2); ?> €</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="summary-divider"></div>
                    
                    <div class="summary-row total">
                        <span class="summary-label">Total</span>
                        <span class="summary-value" id="cartTotal"><?php echo number_format($total, 2); ?> €</span>
                    </div>
                    
                    <button id="checkoutBtn" class="btn btn-success btn-block btn-lg">
                        <i class="fas fa-credit-card"></i> Passer la commande
                    </button>
                    
                    <a href="/pages/store.php" class="btn btn-link btn-block">
                        <i class="fas fa-arrow-left"></i> Continuer les achats
                    </a>
                    
                    <div class="payment-security">
                        <i class="fas fa-shield-alt"></i>
                        <span>Paiement 100% sécurisé</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
    <link rel="stylesheet" href="/css/cart.css">

<!-- Modal de paiement -->
<div id="checkoutModal" class="modal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-credit-card"></i> Finaliser l'achat</h2>
            <button class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="checkoutForm">
                <div class="payment-methods">
                    <h4><i class="fas fa-wallet"></i> Méthode de paiement</h4>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="card" checked>
                            <span class="payment-label">
                                <i class="fas fa-credit-card"></i>
                                <span>Carte bancaire</span>
                            </span>
                            <span class="payment-checkmark">
                                <i class="fas fa-check"></i>
                            </span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="paypal">
                            <span class="payment-label">
                                <i class="fab fa-paypal"></i>
                                <span>PayPal</span>
                            </span>
                            <span class="payment-checkmark">
                                <i class="fas fa-check"></i>
                            </span>
                        </label>
                    </div>
                </div>
                
                <div class="card-details" id="cardDetails">
                    <h4><i class="fas fa-lock"></i> Informations de paiement</h4>
                    <div class="form-group">
                        <label for="cardNumber">
                            <i class="fas fa-credit-card"></i> Numéro de carte
                        </label>
                        <input type="text" id="cardNumber" class="form-control" 
                               placeholder="1234 5678 9012 3456" maxlength="19" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expiryDate">
                                <i class="fas fa-calendar"></i> Date d'expiration
                            </label>
                            <input type="text" id="expiryDate" class="form-control" 
                                   placeholder="MM/AA" maxlength="5" required>
                        </div>
                        <div class="form-group">
                            <label for="cvv">
                                <i class="fas fa-lock"></i> CVV
                            </label>
                            <input type="text" id="cvv" class="form-control" 
                                   placeholder="123" maxlength="3" required>
                        </div>
                    </div>
                </div>
                
                <div class="order-summary-modal">
                    <h4><i class="fas fa-file-invoice-dollar"></i> Récapitulatif</h4>
                    <div class="summary-items-modal">
                        <div class="summary-row">
                            <span>Articles (<?php echo count($cartItems); ?>)</span>
                            <span><?php echo number_format($originalTotal, 2); ?> €</span>
                        </div>
                        <?php if ($savings > 0): ?>
                        <div class="summary-row savings-row">
                            <span>Économies</span>
                            <span>-<?php echo number_format($savings, 2); ?> €</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="summary-divider"></div>
                    <div class="summary-row total">
                        <span>Total à payer</span>
                        <span id="modalTotal"><?php echo number_format($total, 2); ?> €</span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success btn-block btn-lg">
                    <i class="fas fa-lock"></i> Confirmer le paiement
                </button>
                
                <div class="payment-security-modal">
                    <i class="fas fa-shield-alt"></i>
                    <span>Vos informations de paiement sont cryptées et sécurisées</span>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/js/cart.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>