<?php
/**
 * Page de connexion - Games Store
 * Version optimisée avec sécurité améliorée
 */

$pageTitle = "Connexion - Games Store";
require_once __DIR__ . '/../includes/header.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    redirect('/index.php');
}
?>

<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="/css/auth.css">
    <script src="/js/utils.js" defer></script>
</head>
<body class="auth-page">
    <div class="auth-background">
        <div class="particles"></div>
    </div>

    <main class="auth-container">
        <!-- Logo et navigation retour -->
        <header class="auth-header">
            <a href="/" class="logo-link">
                <i class="fas fa-gamepad logo-icon"></i>
                <span class="logo-text">Games Store</span>
            </a>
            <a href="/" class="back-link">
                <i class="fas fa-arrow-left"></i> Retour à l'accueil
            </a>
        </header>

        <div class="auth-grid">
            <!-- Formulaire de connexion -->
            <div class="auth-form-container">
                <div class="form-card animated-card">
                    <div class="form-header">
                        <div class="avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h1>Bon retour !</h1>
                        <p class="subtitle">Connectez-vous à votre compte</p>
                    </div>

                    <!-- Alertes système -->
                    <div class="alerts-container">
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger animated-fadein">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success animated-fadein">
                                <i class="fas fa-check-circle"></i>
                                <?php echo htmlspecialchars(urldecode($_GET['success'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Formulaire principal -->
                    <form id="loginForm" class="auth-form" data-form="login">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken('login'); ?>">

                        <!-- Champ Email -->
                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i>
                                <span>Adresse email</span>
                            </label>
                            <div class="input-with-icon">
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       class="form-control"
                                       placeholder="exemple@domaine.com"
                                       required
                                       autocomplete="email"
                                       data-validate="email">
                                <i class="fas fa-check validation-icon"></i>
                            </div>
                            <div class="form-hint">Entrez votre adresse email professionnelle</div>
                        </div>

                        <!-- Champ Mot de passe -->
                        <div class="form-group">
                            <div class="form-label-group">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i>
                                    <span>Mot de passe</span>
                                </label>
                                <a href="/pages/forgot-password.php" class="forgot-link">
                                    Mot de passe oublié ?
                                </a>
                            </div>
                            <div class="password-input">
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       class="form-control"
                                       placeholder="Votre mot de passe"
                                       required
                                       autocomplete="current-password"
                                       minlength="8">
                                <button type="button" 
                                        class="toggle-password" 
                                        aria-label="Afficher le mot de passe">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="strength-bar"></div>
                            </div>
                        </div>

                        <!-- Options supplémentaires -->
                        <div class="form-options">
                            <label class="checkbox-custom">
                                <input type="checkbox" 
                                       name="remember" 
                                       id="remember"
                                       checked>
                                <span class="checkmark"></span>
                                <span class="checkbox-label">
                                    <i class="fas fa-history"></i>
                                    Se souvenir de moi pendant 30 jours
                                </span>
                            </label>
                            
                            <label class="checkbox-custom">
                                <input type="checkbox" 
                                       name="cookie_consent" 
                                       id="cookieConsent"
                                       data-consent="cookies">
                                <span class="checkmark"></span>
                                <span class="checkbox-label">
                                    <i class="fas fa-cookie-bite"></i>
                                    Accepter les cookies de connexion
                                </span>
                            </label>
                        </div>

                        <!-- Message d'erreur AJAX -->
                        <div id="loginError" class="alert alert-danger" role="alert" style="display: none;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span id="errorMessage"></span>
                        </div>

                        <!-- Bouton de soumission -->
                        <button type="submit" 
                                class="btn btn-primary btn-block btn-lg btn-login"
                                data-loading-text="Connexion en cours...">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Se connecter</span>
                        </button>

                        <!-- Alternative OTP -->
                        <div class="otp-option">
                            <a href="/pages/login-otp.php" class="otp-link">
                                <i class="fas fa-mobile-alt"></i>
                                Se connecter avec un code à usage unique
                            </a>
                        </div>
                    </form>

                    <!-- Séparateur -->
                    <div class="divider">
                        <span class="divider-text">Ou continuer avec</span>
                    </div>

                    <!-- Connexion sociale -->
                    <div class="social-login">
                        <button type="button" class="btn-social btn-steam" data-provider="steam">
                            <i class="fab fa-steam"></i>
                            <span>Steam</span>
                        </button>
                        <button type="button" class="btn-social btn-google" data-provider="google">
                            <i class="fab fa-google"></i>
                            <span>Google</span>
                        </button>
                        <button type="button" class="btn-social btn-microsoft" data-provider="microsoft">
                            <i class="fab fa-microsoft"></i>
                            <span>Microsoft</span>
                        </button>
                    </div>

                    <!-- Liens de navigation -->
                    <div class="auth-links">
                        <p class="register-link">
                            Nouveau sur Games Store ?
                            <a href="/pages/register.php" class="highlight-link">
                                Créer un compte
                            </a>
                        </p>
                        <p class="demo-link">
                            <a href="#" class="demo-account-link">
                                <i class="fas fa-user-secret"></i>
                                Essayer le compte démo
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Informations légales -->
                <div class="legal-notice">
                    <p>
                        En vous connectant, vous acceptez nos
                        <a href="/pages/terms.php">Conditions d'utilisation</a> et notre
                        <a href="/pages/privacy.php">Politique de confidentialité</a>.
                    </p>
                </div>
            </div>

            <!-- Panneau de présentation -->
            <div class="auth-sidebar">
                <div class="sidebar-content">
                    <h2 class="sidebar-title">Pourquoi nous rejoindre ?</h2>
                    
                    <div class="features-list">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-gamepad"></i>
                            </div>
                            <div class="feature-content">
                                <h3>+10,000 jeux</h3>
                                <p>La plus grande bibliothèque de jeux indépendants</p>
                            </div>
                        </div>

                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div class="feature-content">
                                <h3>Jusqu'à -90%</h3>
                                <p>Des promotions exclusives chaque semaine</p>
                            </div>
                        </div>

                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="feature-content">
                                <h3>Achat sécurisé</h3>
                                <p>Garantie remboursement 14 jours</p>
                            </div>
                        </div>

                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-cloud-download-alt"></i>
                            </div>
                            <div class="feature-content">
                                <h3>Téléchargement illimité</h3>
                                <p>Accès à vos jeux sur tous vos appareils</p>
                            </div>
                        </div>
                    </div>

                    <!-- Témoignages -->
                    <div class="testimonials">
                        <div class="testimonial">
                            <p class="testimonial-text">
                                "La meilleure plateforme pour les gamers passionnés !"
                            </p>
                            <div class="testimonial-author">
                                <img src="/assets/avatars/user1.jpg" alt="Alexandre" class="avatar-sm">
                                <div>
                                    <strong>Alexandre M.</strong>
                                    <span>Membre depuis 2018</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Badges de sécurité -->
                    <div class="security-badges">
                        <div class="badge">
                            <i class="fas fa-lock"></i>
                            <span>SSL Sécurisé</span>
                        </div>
                        <div class="badge">
                            <i class="fas fa-shield-alt"></i>
                            <span>2FA Supporté</span>
                        </div>
                        <div class="badge">
                            <i class="fas fa-user-shield"></i>
                            <span>RGPD Compliant</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer minimal -->
    <footer class="auth-footer">
        <div class="container">
            <p class="copyright">
                &copy; <?php echo date('Y'); ?> Games Store. Tous droits réservés.
                <a href="/pages/help.php"><i class="fas fa-question-circle"></i> Aide</a>
            </p>
        </div>
    </footer>

    <!-- Scripts JavaScript -->
    <script>
    // Module de connexion
    class LoginManager {
        constructor() {
            this.form = document.getElementById('loginForm');
            this.errorDiv = document.getElementById('loginError');
            this.errorMessage = document.getElementById('errorMessage');
            this.submitBtn = this.form.querySelector('.btn-login');
            this.originalBtnText = this.submitBtn.innerHTML;
            this.rememberMe = document.getElementById('remember');
            
            this.init();
        }
        
        init() {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
            
            // Toggle password visibility
            document.querySelector('.toggle-password').addEventListener('click', () => 
                this.togglePasswordVisibility());
            
            // Validation en temps réel
            document.getElementById('email').addEventListener('blur', () => 
                this.validateEmail());
            
            // Gestion des boutons sociaux
            document.querySelectorAll('.btn-social').forEach(btn => {
                btn.addEventListener('click', () => this.handleSocialLogin(btn));
            });
            
            // Auto-remplissage démo
            document.querySelector('.demo-account-link')?.addEventListener('click', (e) => {
                e.preventDefault();
                this.fillDemoCredentials();
            });
        }
        
        async handleSubmit(e) {
            e.preventDefault();
            
            if (!this.validateForm()) {
                return;
            }
            
            this.showLoading(true);
            this.hideError();
            
            try {
                const formData = new FormData(this.form);
                
                // Ajouter le consentement cookies
                const cookieConsent = document.getElementById('cookieConsent').checked;
                if (!cookieConsent) {
                    formData.append('no_cookies', '1');
                }
                
                const response = await fetch('/api/auth.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showSuccess('Connexion réussie ! Redirection...');
                    
                    // Redirection avec délai pour voir le message
                    setTimeout(() => {
                        window.location.href = data.redirect || '/index.php';
                    }, 1500);
                    
                    // Migration panier invité si nécessaire
                    if (localStorage.getItem('guest_cart')) {
                        this.migrateGuestCart();
                    }
                } else {
                    this.showError(data.message || 'Erreur de connexion');
                    
                    // Tentative de reconnexion automatique pour certains erreurs
                    if (data.code === 'session_expired') {
                        setTimeout(() => this.retryLogin(), 2000);
                    }
                }
            } catch (error) {
                console.error('Login error:', error);
                this.showError('Erreur de connexion au serveur');
            } finally {
                this.showLoading(false);
            }
        }
        
        validateForm() {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !this.isValidEmail(email)) {
                this.showError('Veuillez entrer une adresse email valide');
                return false;
            }
            
            if (password.length < 8) {
                this.showError('Le mot de passe doit contenir au moins 8 caractères');
                return false;
            }
            
            return true;
        }
        
        isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        validateEmail() {
            const emailInput = document.getElementById('email');
            const validationIcon = emailInput.parentNode.querySelector('.validation-icon');
            
            if (emailInput.value && this.isValidEmail(emailInput.value)) {
                validationIcon.style.opacity = '1';
                emailInput.classList.add('is-valid');
                emailInput.classList.remove('is-invalid');
            } else {
                validationIcon.style.opacity = '0';
                emailInput.classList.remove('is-valid');
            }
        }
        
        togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('.toggle-password i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        
        async migrateGuestCart() {
            try {
                const guestCart = JSON.parse(localStorage.getItem('guest_cart') || '[]');
                
                if (guestCart.length > 0) {
                    const response = await fetch('/api/cart.php?action=migrate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    });
                    
                    localStorage.removeItem('guest_cart');
                }
            } catch (error) {
                console.error('Cart migration failed:', error);
            }
        }
        
        async handleSocialLogin(button) {
            const provider = button.dataset.provider;
            button.disabled = true;
            
            // Simuler l'authentification sociale
            setTimeout(() => {
                this.showError(`Connexion ${provider} non implémentée dans cette démo`);
                button.disabled = false;
            }, 1000);
        }
        
        fillDemoCredentials() {
            document.getElementById('email').value = 'demo@gamestore.com';
            document.getElementById('password').value = 'Demo123!';
            document.getElementById('cookieConsent').checked = true;
            
            this.showSuccess('Identifiants démo pré-remplis. Cliquez sur "Se connecter"');
        }
        
        showLoading(show) {
            if (show) {
                this.submitBtn.disabled = true;
                this.submitBtn.innerHTML = `
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>${this.submitBtn.dataset.loadingText || 'Connexion...'}</span>
                `;
            } else {
                this.submitBtn.disabled = false;
                this.submitBtn.innerHTML = this.originalBtnText;
            }
        }
        
        showError(message) {
            this.errorMessage.textContent = message;
            this.errorDiv.style.display = 'flex';
            
            // Animation
            this.errorDiv.classList.remove('animated-fadein');
            void this.errorDiv.offsetWidth; // Trigger reflow
            this.errorDiv.classList.add('animated-fadein');
            
            // Auto-hide after 5 seconds
            setTimeout(() => this.hideError(), 5000);
        }
        
        showSuccess(message) {
            const successDiv = document.createElement('div');
            successDiv.className = 'alert alert-success animated-fadein';
            successDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
            
            this.errorDiv.parentNode.insertBefore(successDiv, this.errorDiv);
            
            setTimeout(() => successDiv.remove(), 3000);
        }
        
        hideError() {
            this.errorDiv.style.display = 'none';
        }
        
        retryLogin() {
            this.showSuccess('Tentative de reconnexion...');
            this.form.requestSubmit();
        }
    }
    
    // Initialisation
    document.addEventListener('DOMContentLoaded', () => {
        const loginManager = new LoginManager();
        
        // Auto-focus sur le champ email
        document.getElementById('email')?.focus();
        
        // Vérifier la disponibilité du service
        checkServiceStatus();
    });
    
    async function checkServiceStatus() {
        try {
            const response = await fetch('/api/health.php');
            if (!response.ok) {
                console.warn('Service health check failed');
            }
        } catch (error) {
            console.warn('Cannot reach authentication service');
        }
    }
    </script>
</body>
</html>