<?php
/**
 * Page de connexion - Games Store
 */

$pageTitle = "Connexion";
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
                <h1>Connexion</h1>
                <p>Connectez-vous pour accéder à votre compte</p>
            </div>
            
            <form id="loginForm" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
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
                               placeholder="Votre mot de passe" required>
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group form-row">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="checkmark"></span>
                        Se souvenir de moi
                    </label>
                    <a href="#" class="forgot-link">Mot de passe oublié?</a>
                </div>
                
                <div id="loginError" class="alert alert-danger" style="display: none;"></div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Pas encore de compte? <a href="/pages/register.php">Créer un compte</a></p>
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
            <h2>Bienvenue sur Games Store</h2>
            <ul class="promo-features">
                <li><i class="fas fa-check"></i> Accédez à des milliers de jeux</li>
                <li><i class="fas fa-check"></i> Profitez d'offres exclusives</li>
                <li><i class="fas fa-check"></i> Téléchargement instantané</li>
                <li><i class="fas fa-check"></i> Support 24/7</li>
            </ul>
        </div>
    </div>
</section>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'login');
    
    const errorDiv = document.getElementById('loginError');
    const submitBtn = this.querySelector('button[type="submit"]');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connexion...';
    errorDiv.style.display = 'none';
    
    try {
        const response = await fetch('/api/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.href = '/index.php';
        } else {
            errorDiv.textContent = data.error || 'Erreur de connexion';
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        errorDiv.textContent = 'Erreur de connexion au serveur';
        errorDiv.style.display = 'block';
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Se connecter';
    }
});

// Toggle password visibility
document.querySelector('.toggle-password').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
