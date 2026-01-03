<?php
/**
 * Page détail d'un jeu - Games Store
 */

require_once __DIR__ . '/../includes/functions.php';

$gameId = intval($_GET['id'] ?? 0);

if ($gameId <= 0) {
    redirect('/pages/store.php');
}

// Récupérer le jeu
$game = fetchOne("
    SELECT g.*, c.name as category_name, c.icon as category_icon,
           COALESCE(g.discount_price, g.price) as final_price,
           CASE WHEN g.discount_price IS NOT NULL 
                THEN ROUND((1 - g.discount_price/g.price) * 100) 
                ELSE 0 END as discount_percent
    FROM games g 
    LEFT JOIN categories c ON g.category_id = c.id 
    WHERE g.id = ?
", [$gameId]);

if (!$game) {
    redirect('/pages/store.php');
}

$pageTitle = $game['title'];

// Vérifier si l'utilisateur possède déjà le jeu
$alreadyOwned = false;
$inWishlist = false;
$inCart = false;

if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    $alreadyOwned = fetchOne("SELECT id FROM purchases WHERE user_id = ? AND game_id = ?", [$userId, $gameId]) !== false;
    $inWishlist = fetchOne("SELECT id FROM wishlists WHERE user_id = ? AND game_id = ?", [$userId, $gameId]) !== false;
    $inCart = fetchOne("SELECT id FROM cart WHERE user_id = ? AND game_id = ?", [$userId, $gameId]) !== false;
}

// Récupérer les avis
$reviews = fetchAll("
    SELECT r.*, u.username, u.avatar 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.game_id = ? 
    ORDER BY r.created_at DESC 
    LIMIT 10
", [$gameId]);

$avgRating = fetchOne("SELECT AVG(rating) as avg, COUNT(*) as count FROM reviews WHERE game_id = ?", [$gameId]);

// Jeux similaires
$similarGames = fetchAll("
    SELECT g.*, COALESCE(g.discount_price, g.price) as final_price
    FROM games g 
    WHERE g.category_id = ? AND g.id != ? 
    ORDER BY RAND() 
    LIMIT 4
", [$game['category_id'], $gameId]);

// Enregistrer en cookie la liste des derniers jeux consultés (non-essentiel)
// Respecte le consentement via safe_setcookie()
try {
    $recent = [];
    if (!empty($_COOKIE['last_viewed'])) {
        $decoded = json_decode($_COOKIE['last_viewed'], true);
        if (is_array($decoded)) $recent = $decoded;
    }
    // Préfixe l'ID courant et enlève les doublons
    array_unshift($recent, $gameId);
    $recent = array_values(array_unique($recent));
    // Limite à 10 éléments
    $recent = array_slice($recent, 0, 10);
    safe_setcookie('last_viewed', $recent, 30);
} catch (Exception $e) {
    // ne pas bloquer la page si cookie non disponible
}

require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/css/game-detail.css">

<section class="game-detail-section">
    <div class="container">
        <!-- Header du jeu avec background -->
        <div class="game-detail-header" style="background-image: linear-gradient(rgba(15, 20, 25, 0.85), rgba(15, 20, 25, 0.95)), url('<?php echo getBannerImage($game['image'], $game['banner_image'] ?? null); ?>');">
            <div class="game-detail-info">
                <div class="game-detail-image">
                    <img src="<?php echo getGameImage($game['image']); ?>" 
                         alt="<?php echo escape($game['title']); ?>"
                         onerror="this.src='/assets/images/placeholder.jpg'">
                    <div class="image-overlay"></div>
                </div>
                <div class="game-detail-content">
                    <span class="game-category-badge">
                        <i class="fas <?php echo escape($game['category_icon'] ?? 'fa-gamepad'); ?>"></i>
                        <?php echo escape($game['category_name'] ?? 'Jeu'); ?>
                    </span>
                    <h1 class="game-title-hero"><?php echo escape($game['title']); ?></h1>
                    <div class="game-meta-info">
                        <span><i class="fas fa-code"></i> <?php echo escape($game['developer']); ?></span>
                        <span><i class="fas fa-building"></i> <?php echo escape($game['publisher']); ?></span>
                        <span><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($game['release_date'])); ?></span>
                    </div>
                    <div class="game-rating-large">
                        <div class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="<?php echo $i <= round($avgRating['avg'] ?? 0) ? 'fas' : 'far'; ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-text"><?php echo number_format($avgRating['avg'] ?? 0, 1); ?> / 5 (<?php echo $avgRating['count'] ?? 0; ?> avis)</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="game-detail-layout">
            <!-- Contenu principal -->
            <main class="game-detail-main">
                <!-- Galerie / Trailer -->
                <?php if ($game['video_url']): 
                    // Convertir l'URL YouTube au format embed
                    $videoUrl = $game['video_url'];
                    $videoId = '';
                    
                    // Format: youtu.be/VIDEO_ID
                    if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $videoUrl, $matches)) {
                        $videoId = $matches[1];
                    }
                    // Format: youtube.com/watch?v=VIDEO_ID
                    elseif (preg_match('/[?&]v=([a-zA-Z0-9_-]+)/', $videoUrl, $matches)) {
                        $videoId = $matches[1];
                    }
                    // Format: youtube.com/embed/VIDEO_ID
                    elseif (preg_match('/embed\/([a-zA-Z0-9_-]+)/', $videoUrl, $matches)) {
                        $videoId = $matches[1];
                    }
                    
                    if ($videoId) {
                        $videoUrl = 'https://www.youtube-nocookie.com/embed/' . $videoId;
                    }
                ?>
                <div class="game-trailer">
                    <h3 class="section-title"><i class="fas fa-play-circle"></i> Bande-annonce</h3>
                    <div class="video-container">
                        <iframe src="<?php echo escape($videoUrl); ?>" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                allowfullscreen></iframe>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Description -->
                <div class="game-description">
                    <h3 class="section-title"><i class="fas fa-info-circle"></i> À propos du jeu</h3>
                    <div class="description-content">
                        <?php echo nl2br(escape($game['description'])); ?>
                    </div>
                </div>
                
                <!-- Configuration requise -->
                <?php if (!empty($game['system_requirements'])): ?>
                <div class="system-requirements">
                    <h3 class="section-title"><i class="fas fa-desktop"></i> Configuration requise</h3>
                    <div class="requirements-content">
                        <?php echo nl2br(escape($game['system_requirements'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Avis -->
                <div class="game-reviews">
                    <h3 class="section-title"><i class="fas fa-comments"></i> Avis des joueurs</h3>
                    
                    <?php if (isLoggedIn() && $alreadyOwned): ?>
                    <form id="reviewForm" class="review-form">
                        <input type="hidden" name="game_id" value="<?php echo $gameId; ?>">
                        <div class="rating-input">
                            <span class="rating-label">Votre note:</span>
                            <div class="star-rating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                                <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <textarea name="comment" class="form-control" placeholder="Partagez votre avis sur ce jeu..." rows="4" required></textarea>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Publier mon avis
                        </button>
                    </form>
                    <?php elseif (isLoggedIn() && !$alreadyOwned): ?>
                    <div class="review-notice">
                        <i class="fas fa-lock"></i>
                        <p>Vous devez posséder ce jeu pour laisser un avis.</p>
                    </div>
                    <?php elseif (!isLoggedIn()): ?>
                    <div class="review-notice">
                        <i class="fas fa-user"></i>
                        <p><a href="/pages/login.php">Connectez-vous</a> pour laisser un avis.</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="reviews-list">
                        <?php if (empty($reviews)): ?>
                        <p class="no-reviews">
                            <i class="fas fa-comment-slash"></i>
                            Aucun avis pour le moment. Soyez le premier à donner votre avis!
                        </p>
                        <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <img src="/assets/images/avatars/<?php echo escape($review['avatar']); ?>" 
                                     alt="Avatar" class="review-avatar"
                                     onerror="this.src='/assets/images/avatars/default-avatar.png'">
                                <div class="review-meta">
                                    <span class="review-author"><?php echo escape($review['username']); ?></span>
                                    <span class="review-date">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="review-comment"><?php echo nl2br(escape($review['comment'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
            
            <!-- Sidebar achat -->
            <aside class="game-detail-sidebar">
                <div class="purchase-card">
                    <div class="price-display">
                        <?php if ($game['discount_percent'] > 0): ?>
                        <div class="discount-badge-large">-<?php echo $game['discount_percent']; ?>%</div>
                        <div class="price-info">
                            <span class="original-price"><?php echo number_format($game['price'], 2); ?> €</span>
                            <span class="final-price"><?php echo number_format($game['final_price'], 2); ?> €</span>
                        </div>
                        <?php else: ?>
                        <span class="final-price-large">
                            <?php echo $game['final_price'] == 0 ? 'Gratuit' : number_format($game['final_price'], 2) . ' €'; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($alreadyOwned): ?>
                    <div class="owned-badge">
                        <i class="fas fa-check-circle"></i> Vous possédez ce jeu
                    </div>
                    <a href="/pages/library.php" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-gamepad"></i> Voir dans ma bibliothèque
                    </a>
                    <?php else: ?>
                    <button class="btn btn-success btn-block btn-lg add-to-cart <?php echo $inCart ? 'in-cart' : ''; ?>" 
                            data-game-id="<?php echo $gameId; ?>">
                        <i class="fas <?php echo $inCart ? 'fa-check' : 'fa-cart-plus'; ?>"></i>
                        <?php echo $inCart ? 'Dans le panier' : 'Ajouter au panier'; ?>
                    </button>
                    <button class="btn btn-outline btn-block toggle-wishlist <?php echo $inWishlist ? 'in-wishlist' : ''; ?>"
                            data-game-id="<?php echo $gameId; ?>">
                        <i class="<?php echo $inWishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                        <?php echo $inWishlist ? 'Dans la wishlist' : 'Ajouter à la wishlist'; ?>
                    </button>
                    <?php endif; ?>
                </div>
                
                <!-- Infos du jeu -->
                <div class="game-info-card">
                    <h4><i class="fas fa-info-circle"></i> Informations</h4>
                    <ul class="info-list">
                        <li>
                            <span class="info-label"><i class="fas fa-code"></i> Développeur</span>
                            <span class="info-value"><?php echo escape($game['developer']); ?></span>
                        </li>
                        <li>
                            <span class="info-label"><i class="fas fa-building"></i> Éditeur</span>
                            <span class="info-value"><?php echo escape($game['publisher']); ?></span>
                        </li>
                        <li>
                            <span class="info-label"><i class="fas fa-calendar-alt"></i> Date de sortie</span>
                            <span class="info-value"><?php echo date('d/m/Y', strtotime($game['release_date'])); ?></span>
                        </li>
                        <li>
                            <span class="info-label"><i class="fas fa-tag"></i> Genre</span>
                            <span class="info-value"><?php echo escape($game['category_name'] ?? 'Non défini'); ?></span>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
        
        <!-- Jeux similaires -->
        <?php if (!empty($similarGames)): ?>
        <section class="similar-games">
            <h3 class="section-title"><i class="fas fa-gamepad"></i> Jeux similaires</h3>
            <div class="games-grid">
                <?php foreach ($similarGames as $similar): ?>
                <div class="game-card">
                    <a href="/pages/game-detail.php?id=<?php echo $similar['id']; ?>" class="game-image">
                        <img src="<?php echo getGameImage($similar['image']); ?>" 
                             alt="<?php echo escape($similar['title']); ?>"
                             onerror="this.src='/assets/images/placeholder.jpg'">
                        <div class="game-overlay">
                            <i class="fas fa-eye"></i>
                        </div>
                    </a>
                    <div class="game-info">
                        <h4 class="game-title">
                            <a href="/pages/game-detail.php?id=<?php echo $similar['id']; ?>">
                                <?php echo escape($similar['title']); ?>
                            </a>
                        </h4>
                        <span class="game-price"><?php echo number_format($similar['final_price'], 2); ?> €</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</section>

<script>
// Formulaire d'avis
document.getElementById('reviewForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publication...';
    
    try {
        const response = await fetch('/api/users.php?action=review', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Erreur lors de la publication');
        }
    } catch (error) {
        alert('Erreur de connexion');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Publier mon avis';
    }
});

// Add to cart
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', async function() {
        const gameId = this.getAttribute('data-game-id');
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ajout...';
        
        try {
            const response = await fetch('/api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ game_id: gameId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.classList.add('in-cart');
                this.innerHTML = '<i class="fas fa-check"></i> Dans le panier';
            } else {
                alert(data.error || 'Erreur');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-cart-plus"></i> Ajouter au panier';
            }
        } catch (error) {
            alert('Erreur de connexion');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-cart-plus"></i> Ajouter au panier';
        }
    });
});

// Toggle wishlist
document.querySelectorAll('.toggle-wishlist').forEach(button => {
    button.addEventListener('click', async function() {
        const gameId = this.getAttribute('data-game-id');
        const isInWishlist = this.classList.contains('in-wishlist');
        
        try {
            const response = await fetch('/api/wishlist.php', {
                method: isInWishlist ? 'DELETE' : 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ game_id: gameId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.classList.toggle('in-wishlist');
                const icon = this.querySelector('i');
                if (this.classList.contains('in-wishlist')) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    this.innerHTML = '<i class="fas fa-heart"></i> Dans la wishlist';
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    this.innerHTML = '<i class="far fa-heart"></i> Ajouter à la wishlist';
                }
            } else {
                alert(data.error || 'Erreur');
            }
        } catch (error) {
            alert('Erreur de connexion');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>