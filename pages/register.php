<?php
/**
 * Page d'inscription - Games Store
 */

$pageTitle = "Inscription";
require_once __DIR__ . '/../includes/header.php';

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
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar"></div>
                    </div>
                    <small class="form-hint">Minimum 8 caractères</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirmer le mot de passe
                    </label>
                    <div class="password-input">
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="form-control" placeholder="Confirmer votre mot de passe" required>
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" id="terms" required>
                        <span class="checkmark"></span>
                        J'accepte les <a href="#">conditions d'utilisation</a> et la <a href="#">politique de confidentialité</a>
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
                <button class="btn btn-social btn-steam">
                    <i class="fab fa-steam"></i> Continuer avec Steam
                </button>
                <button class="btn btn-social btn-google">
                    <i class="fab fa-google"></i> Continuer avec Google
                </button>
            </div>
        </div>
        
        <div class="auth-promo">
            <h2>Pourquoi nous rejoindre?</h2>
            <ul class="promo-features">
                <li><i class="fas fa-gift"></i> Offres exclusives pour les nouveaux membres</li>
                <li><i class="fas fa-bookmark"></i> Sauvegardez vos jeux favoris</li>
                <li><i class="fas fa-history"></i> Historique de vos achats</li>
                <li><i class="fas fa-star"></i> Laissez des avis sur vos jeux</li>
            </ul>
        </div>
    </div>
</section>

<script>
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
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création...';
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
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Créer mon compte';
    }
});

// Toggle password visibility
document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function() {
        const input = this.parentElement.querySelector('input');
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
    
    bar.style.width = strength + '%';
    bar.className = 'strength-bar';
    
    if (strength < 30) bar.classList.add('weak');
    else if (strength < 60) bar.classList.add('medium');
    else bar.classList.add('strong');
});

function calculatePasswordStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength += 25;
    if (password.length >= 12) strength += 15;
    if (/[a-z]/.test(password)) strength += 15;
    if (/[A-Z]/.test(password)) strength += 15;
    if (/[0-9]/.test(password)) strength += 15;
    if (/[^a-zA-Z0-9]/.test(password)) strength += 15;
    return Math.min(100, strength);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
