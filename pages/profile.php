<?php
/**
 * Profil utilisateur - Games Store
 */

$pageTitle = "Mon Profil";
require_once __DIR__ . '/../includes/header.php';

requireLogin();

$userId = $_SESSION['user_id'];

// Récupérer les informations de l'utilisateur
$user = fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

// Statistiques
$stats = fetchOne("
    SELECT 
        (SELECT COUNT(*) FROM purchases WHERE user_id = ?) as games_owned,
        (SELECT COUNT(*) FROM wishlists WHERE user_id = ?) as wishlist_count,
        (SELECT COUNT(*) FROM reviews WHERE user_id = ?) as reviews_count,
        (SELECT SUM(purchase_price) FROM purchases WHERE user_id = ?) as total_spent
", [$userId, $userId, $userId, $userId]);

// Derniers achats
$recentPurchases = fetchAll("
    SELECT p.*, g.title, g.image 
    FROM purchases p 
    JOIN games g ON p.game_id = g.id 
    WHERE p.user_id = ? 
    ORDER BY p.purchase_date DESC 
    LIMIT 5
", [$userId]);
?>

<section class="profile-section">
    <div class="container">
        <div class="profile-layout">
            <!-- Sidebar profil -->
            <aside class="profile-sidebar">
                <div class="profile-card">
                    <div class="profile-avatar">
                        <img src="/assets/images/avatars/<?php echo escape($user['avatar']); ?>" 
                             alt="Avatar" id="avatarPreview"
                             onerror="this.src='/assets/images/avatars/default-avatar.png'">
                        <button class="avatar-edit-btn" id="changeAvatarBtn">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    <h2 class="profile-username"><?php echo escape($user['username']); ?></h2>
                    <span class="profile-role <?php echo $user['role']; ?>">
                        <i class="fas <?php echo $user['role'] === 'admin' ? 'fa-shield-alt' : 'fa-user'; ?>"></i>
                        <?php echo $user['role'] === 'admin' ? 'Administrateur' : 'Membre'; ?>
                    </span>
                    <p class="profile-joined">
                        <i class="fas fa-calendar-alt"></i>
                        Membre depuis <?php echo date('F Y', strtotime($user['created_at'])); ?>
                    </p>
                </div>
                
                <div class="profile-stats-card">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats['games_owned'] ?? 0; ?></span>
                        <span class="stat-label">Jeux possédés</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats['wishlist_count'] ?? 0; ?></span>
                        <span class="stat-label">Wishlist</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $stats['reviews_count'] ?? 0; ?></span>
                        <span class="stat-label">Avis</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo number_format($stats['total_spent'] ?? 0, 2); ?> €</span>
                        <span class="stat-label">Total dépensé</span>
                    </div>
                </div>
                
                <nav class="profile-nav">
                    <a href="#info" class="profile-nav-link active">
                        <i class="fas fa-user"></i> Informations
                    </a>
                    <a href="#security" class="profile-nav-link">
                        <i class="fas fa-lock"></i> Sécurité
                    </a>
                    <a href="/pages/library.php" class="profile-nav-link">
                        <i class="fas fa-gamepad"></i> Ma bibliothèque
                    </a>
                    <a href="/pages/wishlist.php" class="profile-nav-link">
                        <i class="fas fa-heart"></i> Ma wishlist
                    </a>
                </nav>
            </aside>
            
            <!-- Contenu principal -->
            <main class="profile-content">
                <!-- Section Informations -->
                <section id="info" class="profile-section-card">
                    <h3><i class="fas fa-user"></i> Informations personnelles</h3>
                    
                    <form id="profileForm" class="profile-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Nom d'utilisateur</label>
                                <input type="text" id="username" name="username" class="form-control"
                                       value="<?php echo escape($user['username']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Adresse email</label>
                                <input type="email" id="email" name="email" class="form-control"
                                       value="<?php echo escape($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div id="profileMessage" class="alert" style="display: none;"></div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                    </form>
                </section>
                
                <!-- Section Sécurité -->
                <section id="security" class="profile-section-card">
                    <h3><i class="fas fa-lock"></i> Sécurité</h3>
                    
                    <form id="passwordForm" class="profile-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="form-group">
                            <label for="current_password">Mot de passe actuel</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">Nouveau mot de passe</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
                            </div>
                            <div class="form-group">
                                <label for="confirm_new_password">Confirmer</label>
                                <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control" required>
                            </div>
                        </div>
                        
                        <div id="passwordMessage" class="alert" style="display: none;"></div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key"></i> Modifier le mot de passe
                        </button>
                    </form>
                </section>
                
                <!-- Derniers achats -->
                <?php if (!empty($recentPurchases)): ?>
                <section class="profile-section-card">
                    <h3><i class="fas fa-history"></i> Derniers achats</h3>
                    <div class="recent-purchases">
                        <?php foreach ($recentPurchases as $purchase): ?>
                        <div class="purchase-item">
                            <img src="<?php echo getGameImage($purchase['image']); ?>" alt="<?php echo escape($purchase['title']); ?>"
                                 onerror="this.src='/assets/images/placeholder.jpg'">
                            <div class="purchase-info">
                                <span class="purchase-title"><?php echo escape($purchase['title']); ?></span>
                                <span class="purchase-date"><?php echo date('d/m/Y', strtotime($purchase['purchase_date'])); ?></span>
                            </div>
                            <span class="purchase-price"><?php echo number_format($purchase['purchase_price'], 2); ?> €</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="/pages/library.php" class="btn btn-outline btn-sm">
                        Voir tous mes jeux <i class="fas fa-arrow-right"></i>
                    </a>
                </section>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<script>
// Mise à jour du profil
document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const messageDiv = document.getElementById('profileMessage');
    
    try {
        const response = await fetch('/api/users.php', {
            method: 'PUT',
            body: JSON.stringify({
                username: formData.get('username'),
                email: formData.get('email')
            }),
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        messageDiv.className = 'alert ' + (data.success ? 'alert-success' : 'alert-danger');
        messageDiv.textContent = data.message || data.error;
        messageDiv.style.display = 'block';
    } catch (error) {
        messageDiv.className = 'alert alert-danger';
        messageDiv.textContent = 'Erreur de connexion';
        messageDiv.style.display = 'block';
    }
});

// Changement de mot de passe
document.getElementById('passwordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const messageDiv = document.getElementById('passwordMessage');
    
    if (formData.get('new_password') !== formData.get('confirm_new_password')) {
        messageDiv.className = 'alert alert-danger';
        messageDiv.textContent = 'Les mots de passe ne correspondent pas';
        messageDiv.style.display = 'block';
        return;
    }
    
    try {
        const response = await fetch('/api/users.php?action=password', {
            method: 'PUT',
            body: JSON.stringify({
                current_password: formData.get('current_password'),
                new_password: formData.get('new_password')
            }),
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        messageDiv.className = 'alert ' + (data.success ? 'alert-success' : 'alert-danger');
        messageDiv.textContent = data.message || data.error;
        messageDiv.style.display = 'block';
        
        if (data.success) {
            this.reset();
        }
    } catch (error) {
        messageDiv.className = 'alert alert-danger';
        messageDiv.textContent = 'Erreur de connexion';
        messageDiv.style.display = 'block';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
