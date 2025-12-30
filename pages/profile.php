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

<link rel="stylesheet" href="/css/profile.css">

<section class="profile-section">
    <div class="container">
        <div class="profile-header">
            <h1><i class="fas fa-user-circle"></i> Mon Profil</h1>
            <p>Gérez vos informations personnelles et vos préférences</p>
        </div>

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
                    <h3><i class="fas fa-chart-line"></i> Statistiques</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-gamepad"></i>
                            </div>
                            <div class="stat-content">
                                <span class="stat-value"><?php echo $stats['games_owned'] ?? 0; ?></span>
                                <span class="stat-label">Jeux possédés</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="stat-content">
                                <span class="stat-value"><?php echo $stats['wishlist_count'] ?? 0; ?></span>
                                <span class="stat-label">Wishlist</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-content">
                                <span class="stat-value"><?php echo $stats['reviews_count'] ?? 0; ?></span>
                                <span class="stat-label">Avis</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="stat-content">
                                <span class="stat-value"><?php echo number_format($stats['total_spent'] ?? 0, 2); ?> €</span>
                                <span class="stat-label">Total dépensé</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <nav class="profile-nav">
                    <a href="#info" class="profile-nav-link active" data-section="info">
                        <i class="fas fa-user"></i> 
                        <span>Informations</span>
                    </a>
                    <a href="#security" class="profile-nav-link" data-section="security">
                        <i class="fas fa-lock"></i> 
                        <span>Sécurité</span>
                    </a>
                    <a href="/pages/library.php" class="profile-nav-link">
                        <i class="fas fa-gamepad"></i> 
                        <span>Ma bibliothèque</span>
                    </a>
                    <a href="/pages/wishlist.php" class="profile-nav-link">
                        <i class="fas fa-heart"></i> 
                        <span>Ma wishlist</span>
                    </a>
                </nav>
            </aside>
            
            <!-- Contenu principal -->
            <main class="profile-content">
                <!-- Section Informations -->
                <section id="info" class="profile-section-card active">
                    <div class="section-header">
                        <h3><i class="fas fa-user"></i> Informations personnelles</h3>
                        <p>Modifiez vos informations de profil</p>
                    </div>
                    
                    <form id="profileForm" class="profile-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">
                                    <i class="fas fa-user"></i> Nom d'utilisateur
                                </label>
                                <input type="text" id="username" name="username" class="form-control"
                                       value="<?php echo escape($user['username']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">
                                    <i class="fas fa-envelope"></i> Adresse email
                                </label>
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
                    <div class="section-header">
                        <h3><i class="fas fa-lock"></i> Sécurité</h3>
                        <p>Modifiez votre mot de passe</p>
                    </div>
                    
                    <form id="passwordForm" class="profile-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="form-group">
                            <label for="current_password">
                                <i class="fas fa-key"></i> Mot de passe actuel
                            </label>
                            <div class="password-input">
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                                <button type="button" class="toggle-password" data-target="current_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">
                                    <i class="fas fa-lock"></i> Nouveau mot de passe
                                </label>
                                <div class="password-input">
                                    <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
                                    <button type="button" class="toggle-password" data-target="new_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="confirm_new_password">
                                    <i class="fas fa-check-circle"></i> Confirmer
                                </label>
                                <div class="password-input">
                                    <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control" required>
                                    <button type="button" class="toggle-password" data-target="confirm_new_password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="password-requirements">
                            <p><i class="fas fa-info-circle"></i> Le mot de passe doit contenir au moins 8 caractères</p>
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
                    <div class="section-header">
                        <h3><i class="fas fa-history"></i> Derniers achats</h3>
                        <p>Vos 5 dernières acquisitions</p>
                    </div>
                    
                    <div class="recent-purchases">
                        <?php foreach ($recentPurchases as $purchase): ?>
                        <div class="purchase-item">
                            <img src="<?php echo getGameImage($purchase['image']); ?>" alt="<?php echo escape($purchase['title']); ?>"
                                 onerror="this.src='/assets/images/placeholder.jpg'">
                            <div class="purchase-info">
                                <span class="purchase-title"><?php echo escape($purchase['title']); ?></span>
                                <span class="purchase-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d/m/Y', strtotime($purchase['purchase_date'])); ?>
                                </span>
                            </div>
                            <span class="purchase-price"><?php echo number_format($purchase['purchase_price'], 2); ?> €</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="/pages/library.php" class="btn btn-outline btn-block">
                        Voir tous mes jeux <i class="fas fa-arrow-right"></i>
                    </a>
                </section>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>

<script>
// Navigation entre les sections
document.querySelectorAll('.profile-nav-link[data-section]').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Retirer la classe active de tous les liens
        document.querySelectorAll('.profile-nav-link').forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        
        // Masquer toutes les sections
        document.querySelectorAll('.profile-section-card').forEach(s => s.classList.remove('active'));
        
        // Afficher la section ciblée
        const sectionId = this.getAttribute('data-section');
        document.getElementById(sectionId).classList.add('active');
    });
});

// Toggle password visibility
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const passwordInput = document.getElementById(targetId);
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
});

// Mise à jour du profil
document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const messageDiv = document.getElementById('profileMessage');
    const submitBtn = this.querySelector('button[type="submit"]');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
    
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
        
        if (data.success) {
            // Mettre à jour le nom d'utilisateur affiché
            document.querySelector('.profile-username').textContent = formData.get('username');
        }
    } catch (error) {
        messageDiv.className = 'alert alert-danger';
        messageDiv.textContent = 'Erreur de connexion';
        messageDiv.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer les modifications';
    }
});

// Changement de mot de passe
document.getElementById('passwordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const messageDiv = document.getElementById('passwordMessage');
    const submitBtn = this.querySelector('button[type="submit"]');
    
    if (formData.get('new_password') !== formData.get('confirm_new_password')) {
        messageDiv.className = 'alert alert-danger';
        messageDiv.textContent = 'Les mots de passe ne correspondent pas';
        messageDiv.style.display = 'block';
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Modification...';
    
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
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-key"></i> Modifier le mot de passe';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>