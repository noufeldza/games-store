<?php
/**
 * Page d'inscription - Games Store
 */
require_once __DIR__ . '/../api/auth_bootstrap.php';
$pageTitle = "Inscription";
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/css/register.css">

<?php
// Redirection si déjà connecté
if (isLoggedIn()) {
    redirect('/index.php');
}
?>

<section class="auth-section">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-gamepad auth-logo"></i>
                <h1>Créer un compte</h1>
                <p>Rejoignez la communauté Games Store</p>
            </div>
            
            <form id="registerForm" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Nom d'utilisateur
                    </label>
                    <input type="text" id="username" name="username" class="form-control" 
                           placeholder="Votre pseudo" required minlength="3" maxlength="50">
                    <small class="form-hint">Entre 3 et 50 caractères</small>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Adresse email
                    </label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="votre@email.com" required>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Mot de passe
                    </label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Créer un mot de passe" required minlength="8">
                        <button type="button" class="toggle-password" data-target="password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar"></div>
                        <span class="strength-text"></span>
                    </div>
                    <small class="form-hint">Minimum 8 caractères, inclure majuscules, chiffres et caractères spéciaux</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirmer le mot de passe
                    </label>
                    <div class="password-input">
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="form-control" placeholder="Confirmer votre mot de passe" required>
                        <button type="button" class="toggle-password" data-target="confirm_password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="password-match" style="display: none;"></small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" id="terms" required>
                        <span class="checkmark"></span>
                        J'accepte les conditions d'utilisationet la politique de confidentialité
                    </label>
                </div>
                
                <div id="registerError" class="alert alert-danger" style="display: none;"></div>
                <div id="registerSuccess" class="alert alert-success" style="display: none;"></div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-user-plus"></i> Créer mon compte
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Déjà un compte? <a href="/pages/login.php">Se connecter</a></p>
            </div>
            
            <div class="auth-divider">
                <span>ou</span>
            </div>
            
            <div class="social-login">
                <button type="button" class="btn btn-social btn-steam">
                    <i class="fab fa-steam"></i> Continuer avec Steam
                </button>
                <button type="button" class="btn btn-social btn-google">
                    <i class="fab fa-google"></i> Continuer avec Google
                </button>
            </div>
        </div>
        
        <div class="auth-promo">
            <h2>Pourquoi nous rejoindre?</h2>
            <ul class="promo-features">
                <li>
                    <i class="fas fa-gift"></i>
                    <div>
                        <strong>Offres exclusives</strong>
                        <p>Promotions réservées aux nouveaux membres</p>
                    </div>
                </li>
                <li>
                    <i class="fas fa-bookmark"></i>
                    <div>
                        <strong>Wishlist personnalisée</strong>
                        <p>Sauvegardez vos jeux favoris</p>
                    </div>
                </li>
                <li>
                    <i class="fas fa-history"></i>
                    <div>
                        <strong>Historique complet</strong>
                        <p>Suivez tous vos achats</p>
                    </div>
                </li>
                <li>
                    <i class="fas fa-star"></i>
                    <div>
                        <strong>Avis et notes</strong>
                        <p>Partagez votre expérience</p>
                    </div>
                </li>
                <li>
                    <i class="fas fa-cloud"></i>
                    <div>
                        <strong>Cloud gaming</strong>
                        <p>Jouez partout, tout le temps</p>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</section>

<script>
// Form submission
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'register');
    
    const errorDiv = document.getElementById('registerError');
    const successDiv = document.getElementById('registerSuccess');
    const submitBtn = this.querySelector('button[type="submit"]');
    
    // Validation côté client
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        errorDiv.textContent = 'Les mots de passe ne correspondent pas';
        errorDiv.style.display = 'block';
        return;
    }
    
    if (!document.getElementById('terms').checked) {
        errorDiv.textContent = 'Vous devez accepter les conditions d\'utilisation';
        errorDiv.style.display = 'block';
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création en cours...';
    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';
    
    try {
        const response = await fetch('/api/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            successDiv.textContent = 'Compte créé avec succès! Redirection...';
            successDiv.style.display = 'block';
            this.reset();
            setTimeout(() => {
                window.location.href = '/pages/login.php';
            }, 2000);
        } else {
            errorDiv.textContent = data.error || data.errors?.join(', ') || 'Erreur lors de l\'inscription';
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        errorDiv.textContent = 'Erreur de connexion au serveur';
        errorDiv.style.display = 'block';
    } finally {
        if (!document.getElementById('registerSuccess').style.display !== 'block') {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Créer mon compte';
        }
    }
});

// Toggle password visibility
document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const icon = this.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
});

// Password strength indicator
document.getElementById('password').addEventListener('input', function() {
    const strength = calculatePasswordStrength(this.value);
    const bar = document.querySelector('.strength-bar');
    const text = document.querySelector('.strength-text');
    
    bar.style.width = strength.percentage + '%';
    bar.className = 'strength-bar ' + strength.class;
    text.textContent = strength.text;
    text.className = 'strength-text ' + strength.class;
});

// Confirm password match indicator
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    const matchIndicator = document.querySelector('.password-match');
    
    if (confirmPassword.length === 0) {
        matchIndicator.style.display = 'none';
        return;
    }
    
    matchIndicator.style.display = 'block';
    
    if (password === confirmPassword) {
        matchIndicator.textContent = '✓ Les mots de passe correspondent';
        matchIndicator.className = 'password-match match';
    } else {
        matchIndicator.textContent = '✗ Les mots de passe ne correspondent pas';
        matchIndicator.className = 'password-match no-match';
    }
});

function calculatePasswordStrength(password) {
    let strength = 0;
    let feedback = [];
    
    if (password.length === 0) {
        return { percentage: 0, class: '', text: '' };
    }
    
    // Length check
    if (password.length >= 8) strength += 20;
    if (password.length >= 12) strength += 15;
    if (password.length >= 16) strength += 10;
    
    // Character variety
    if (/[a-z]/.test(password)) strength += 15;
    if (/[A-Z]/.test(password)) strength += 15;
    if (/[0-9]/.test(password)) strength += 15;
    if (/[^a-zA-Z0-9]/.test(password)) strength += 20;
    
    const percentage = Math.min(100, strength);
    
    let strengthClass = '';
    let strengthText = '';
    
    if (percentage < 40) {
        strengthClass = 'weak';
        strengthText = 'Faible';
    } else if (percentage < 70) {
        strengthClass = 'medium';
        strengthText = 'Moyen';
    } else {
        strengthClass = 'strong';
        strengthText = 'Fort';
    }
    
    return { percentage, class: strengthClass, text: strengthText };
}

// Real-time username validation
document.getElementById('username').addEventListener('blur', async function() {
    const username = this.value.trim();
    
    if (username.length < 3) return;
    
    // Optional: Check username availability via API
    // You can implement this if you have an endpoint
});

// Real-time email validation
document.getElementById('email').addEventListener('blur', function() {
    const email = this.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email && !emailRegex.test(email)) {
        this.classList.add('invalid');
    } else {
        this.classList.remove('invalid');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>