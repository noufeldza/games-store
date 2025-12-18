/**
 * GameStore - Main JavaScript
 * Handles cart, wishlist, authentication and UI interactions
 */

// ===== CONFIGURATION =====
const API_BASE = '/api/';

// ===== UTILITY FUNCTIONS =====
const Utils = {
    // Format price
    formatPrice(price) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(price);
    },

    // Show notification
    showNotification(message, type = 'success') {
        const container = document.getElementById('notification-container') || this.createNotificationContainer();
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;
        container.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    },

    createNotificationContainer() {
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = 'position: fixed; top: 100px; right: 20px; z-index: 9999; max-width: 350px;';
        document.body.appendChild(container);
        return container;
    },

    // CSRF Token
    getCSRFToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    },

    // API Request helper
    async apiRequest(endpoint, method = 'GET', data = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.getCSRFToken()
            },
            credentials: 'same-origin'
        };

        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(API_BASE + endpoint, options);
            const result = await response.json();
            return result;
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, message: 'Erreur de connexion au serveur' };
        }
    },

    // Sanitize HTML to prevent XSS
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// ===== CART MANAGEMENT =====
const Cart = {
    // Add item to cart
    async add(gameId) {
        const result = await Utils.apiRequest('cart.php', 'POST', {
            action: 'add',
            game_id: gameId
        });

        if (result.success) {
            Utils.showNotification('Jeu ajouté au panier !', 'success');
            this.updateCounter(result.cart_count);
        } else {
            Utils.showNotification(result.message || 'Erreur lors de l\'ajout', 'danger');
        }
        return result;
    },

    // Remove item from cart
    async remove(gameId) {
        const result = await Utils.apiRequest('cart.php', 'POST', {
            action: 'remove',
            game_id: gameId
        });

        if (result.success) {
            Utils.showNotification('Jeu retiré du panier', 'success');
            this.updateCounter(result.cart_count);
            this.refreshCartDisplay();
        } else {
            Utils.showNotification(result.message || 'Erreur lors de la suppression', 'danger');
        }
        return result;
    },

    // Clear cart
    async clear() {
        const result = await Utils.apiRequest('cart.php', 'POST', {
            action: 'clear'
        });

        if (result.success) {
            Utils.showNotification('Panier vidé', 'success');
            this.updateCounter(0);
            this.refreshCartDisplay();
        }
        return result;
    },

    // Checkout
    async checkout() {
        const result = await Utils.apiRequest('cart.php', 'POST', {
            action: 'checkout'
        });

        if (result.success) {
            Utils.showNotification('Achat effectué avec succès !', 'success');
            this.updateCounter(0);
            setTimeout(() => {
                window.location.href = 'pages/library.php';
            }, 1500);
        } else {
            Utils.showNotification(result.message || 'Erreur lors de l\'achat', 'danger');
        }
        return result;
    },

    // Update cart counter in header
    updateCounter(count) {
        const counters = document.querySelectorAll('.cart-count');
        counters.forEach(counter => {
            counter.textContent = count;
            counter.style.display = count > 0 ? 'block' : 'none';
        });
    },

    // Refresh cart display on cart page
    refreshCartDisplay() {
        const cartContainer = document.querySelector('.cart-items');
        if (cartContainer) {
            location.reload();
        }
    }
};

// ===== WISHLIST MANAGEMENT =====
const Wishlist = {
    // Toggle wishlist item
    async toggle(gameId, button) {
        const result = await Utils.apiRequest('games.php', 'POST', {
            action: 'toggle_wishlist',
            game_id: gameId
        });

        if (result.success) {
            if (result.added) {
                button.classList.add('active');
                button.innerHTML = '<i class="fas fa-heart"></i>';
                Utils.showNotification('Ajouté à la liste de souhaits', 'success');
            } else {
                button.classList.remove('active');
                button.innerHTML = '<i class="far fa-heart"></i>';
                Utils.showNotification('Retiré de la liste de souhaits', 'success');
            }
            this.updateCounter(result.wishlist_count);
        } else {
            Utils.showNotification(result.message || 'Erreur', 'danger');
        }
        return result;
    },

    // Update wishlist counter
    updateCounter(count) {
        const counters = document.querySelectorAll('.wishlist-count');
        counters.forEach(counter => {
            counter.textContent = count;
            counter.style.display = count > 0 ? 'block' : 'none';
        });
    }
};

// ===== AUTHENTICATION =====
const Auth = {
    // Login
    async login(email, password, remember = false) {
        const result = await Utils.apiRequest('auth.php', 'POST', {
            action: 'login',
            email,
            password,
            remember
        });

        if (result.success) {
            Utils.showNotification('Connexion réussie !', 'success');
            setTimeout(() => {
                window.location.href = result.redirect || 'index.php';
            }, 1000);
        } else {
            Utils.showNotification(result.message || 'Identifiants incorrects', 'danger');
        }
        return result;
    },

    // Register
    async register(formData) {
        const result = await Utils.apiRequest('auth.php', 'POST', {
            action: 'register',
            ...formData
        });

        if (result.success) {
            Utils.showNotification('Inscription réussie !', 'success');
            setTimeout(() => {
                window.location.href = 'pages/login.php';
            }, 1500);
        } else {
            Utils.showNotification(result.message || 'Erreur lors de l\'inscription', 'danger');
        }
        return result;
    },

    // Logout
    async logout() {
        const result = await Utils.apiRequest('auth.php', 'POST', {
            action: 'logout'
        });

        if (result.success) {
            Utils.showNotification('Déconnexion réussie', 'success');
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1000);
        }
        return result;
    },

    // Update profile
    async updateProfile(formData) {
        const result = await Utils.apiRequest('users.php', 'POST', {
            action: 'update_profile',
            ...formData
        });

        if (result.success) {
            Utils.showNotification('Profil mis à jour !', 'success');
        } else {
            Utils.showNotification(result.message || 'Erreur lors de la mise à jour', 'danger');
        }
        return result;
    },

    // Change password
    async changePassword(currentPassword, newPassword) {
        const result = await Utils.apiRequest('users.php', 'POST', {
            action: 'change_password',
            current_password: currentPassword,
            new_password: newPassword
        });

        if (result.success) {
            Utils.showNotification('Mot de passe modifié !', 'success');
        } else {
            Utils.showNotification(result.message || 'Erreur lors du changement', 'danger');
        }
        return result;
    }
};

// ===== SEARCH =====
const Search = {
    debounceTimer: null,

    // Initialize search
    init() {
        const searchInput = document.querySelector('.search-bar input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    this.search(e.target.value);
                }, 300);
            });
        }
    },

    // Perform search
    async search(query) {
        if (query.length < 2) return;

        const result = await Utils.apiRequest(`games.php?action=search&q=${encodeURIComponent(query)}`);
        if (result.success && result.games) {
            this.displayResults(result.games);
        }
    },

    // Display search results
    displayResults(games) {
        let dropdown = document.querySelector('.search-dropdown');
        if (!dropdown) {
            dropdown = document.createElement('div');
            dropdown.className = 'search-dropdown';
            dropdown.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--card-bg);
                border-radius: 8px;
                box-shadow: var(--shadow);
                max-height: 300px;
                overflow-y: auto;
                z-index: 100;
            `;
            document.querySelector('.search-bar').style.position = 'relative';
            document.querySelector('.search-bar').appendChild(dropdown);
        }

        if (games.length === 0) {
            dropdown.innerHTML = '<div style="padding: 15px; text-align: center; color: var(--text-muted);">Aucun résultat</div>';
        } else {
            dropdown.innerHTML = games.map(game => `
                <a href="pages/game-detail.php?id=${game.id}" style="display: flex; padding: 10px; border-bottom: 1px solid var(--border-color); color: var(--text-color);">
                    <img src="${Utils.escapeHtml(game.image)}" style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px; margin-right: 10px;">
                    <div>
                        <div style="font-weight: 500;">${Utils.escapeHtml(game.name)}</div>
                        <div style="font-size: 0.9rem; color: var(--success-color);">${Utils.formatPrice(game.price)}</div>
                    </div>
                </a>
            `).join('');
        }

        dropdown.style.display = 'block';
    }
};

// ===== FILTERS =====
const Filters = {
    // Initialize filters
    init() {
        const filterSelects = document.querySelectorAll('.filter-group select');
        filterSelects.forEach(select => {
            select.addEventListener('change', () => this.applyFilters());
        });
    },

    // Apply filters
    applyFilters() {
        const params = new URLSearchParams();
        
        document.querySelectorAll('.filter-group select').forEach(select => {
            if (select.value) {
                params.set(select.name, select.value);
            }
        });

        window.location.href = '?' + params.toString();
    }
};

// ===== MODAL =====
const Modal = {
    // Open modal
    open(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },

    // Close modal
    close(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    // Close all modals
    closeAll() {
        document.querySelectorAll('.modal.active').forEach(modal => {
            modal.classList.remove('active');
        });
        document.body.style.overflow = '';
    },

    // Confirm dialog
    async confirm(message) {
        return new Promise((resolve) => {
            const modal = document.createElement('div');
            modal.className = 'modal active';
            modal.innerHTML = `
                <div class="modal-content" style="max-width: 400px;">
                    <div class="modal-header">
                        <h2>Confirmation</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>${Utils.escapeHtml(message)}</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-action="cancel">Annuler</button>
                        <button class="btn btn-primary" data-action="confirm">Confirmer</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            modal.querySelector('[data-action="confirm"]').addEventListener('click', () => {
                modal.remove();
                resolve(true);
            });

            modal.querySelector('[data-action="cancel"]').addEventListener('click', () => {
                modal.remove();
                resolve(false);
            });

            modal.querySelector('.modal-close').addEventListener('click', () => {
                modal.remove();
                resolve(false);
            });
        });
    }
};

// ===== FORM VALIDATION =====
const FormValidation = {
    // Validate email
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    // Validate password strength
    isStrongPassword(password) {
        return password.length >= 8 && 
               /[A-Z]/.test(password) && 
               /[a-z]/.test(password) && 
               /[0-9]/.test(password);
    },

    // Show field error
    showFieldError(input, message) {
        this.clearFieldError(input);
        input.classList.add('is-invalid');
        const error = document.createElement('div');
        error.className = 'form-error';
        error.textContent = message;
        input.parentNode.appendChild(error);
    },

    // Clear field error
    clearFieldError(input) {
        input.classList.remove('is-invalid');
        const error = input.parentNode.querySelector('.form-error');
        if (error) error.remove();
    },

    // Validate form
    validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('[required]');

        inputs.forEach(input => {
            this.clearFieldError(input);

            if (!input.value.trim()) {
                this.showFieldError(input, 'Ce champ est requis');
                isValid = false;
            } else if (input.type === 'email' && !this.isValidEmail(input.value)) {
                this.showFieldError(input, 'Email invalide');
                isValid = false;
            } else if (input.name === 'password' && input.dataset.strength === 'true' && !this.isStrongPassword(input.value)) {
                this.showFieldError(input, 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre');
                isValid = false;
            }
        });

        // Check password confirmation
        const password = form.querySelector('[name="password"]');
        const confirm = form.querySelector('[name="confirm_password"]');
        if (password && confirm && password.value !== confirm.value) {
            this.showFieldError(confirm, 'Les mots de passe ne correspondent pas');
            isValid = false;
        }

        return isValid;
    }
};

// ===== ADMIN FUNCTIONS =====
const Admin = {
    // Delete item
    async deleteItem(type, id) {
        const confirmed = await Modal.confirm(`Êtes-vous sûr de vouloir supprimer cet élément ?`);
        if (!confirmed) return;

        const endpoint = type === 'game' ? 'games.php' : 
                        type === 'user' ? 'users.php' : 
                        'games.php';

        const result = await Utils.apiRequest(endpoint, 'POST', {
            action: 'delete',
            id
        });

        if (result.success) {
            Utils.showNotification('Élément supprimé', 'success');
            location.reload();
        } else {
            Utils.showNotification(result.message || 'Erreur lors de la suppression', 'danger');
        }
    },

    // Toggle user status
    async toggleUserStatus(userId) {
        const result = await Utils.apiRequest('users.php', 'POST', {
            action: 'toggle_status',
            user_id: userId
        });

        if (result.success) {
            Utils.showNotification('Statut mis à jour', 'success');
            location.reload();
        } else {
            Utils.showNotification(result.message || 'Erreur', 'danger');
        }
    },

    // Update order status
    async updateOrderStatus(orderId, status) {
        const result = await Utils.apiRequest('cart.php', 'POST', {
            action: 'update_order_status',
            order_id: orderId,
            status
        });

        if (result.success) {
            Utils.showNotification('Commande mise à jour', 'success');
        } else {
            Utils.showNotification(result.message || 'Erreur', 'danger');
        }
        return result;
    }
};

// ===== EVENT LISTENERS =====
document.addEventListener('DOMContentLoaded', () => {
    // Initialize components
    Search.init();
    Filters.init();

    // Add to cart buttons
    document.querySelectorAll('.add-to-cart, [data-add-cart]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const gameId = btn.dataset.gameId || btn.dataset.addCart;
            Cart.add(gameId);
        });
    });

    // Remove from cart buttons
    document.querySelectorAll('.remove-from-cart, [data-remove-cart]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const gameId = btn.dataset.gameId || btn.dataset.removeCart;
            Cart.remove(gameId);
        });
    });

    // Checkout button
    const checkoutBtn = document.querySelector('[data-checkout]');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            const confirmed = await Modal.confirm('Confirmer l\'achat ?');
            if (confirmed) {
                Cart.checkout();
            }
        });
    }

    // Wishlist buttons
    document.querySelectorAll('.toggle-wishlist, [data-wishlist]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const gameId = btn.dataset.gameId || btn.dataset.wishlist;
            Wishlist.toggle(gameId, btn);
        });
    });

    // Login form
    const loginForm = document.querySelector('#login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!FormValidation.validateForm(loginForm)) return;

            const email = loginForm.querySelector('[name="email"]').value;
            const password = loginForm.querySelector('[name="password"]').value;
            const remember = loginForm.querySelector('[name="remember"]')?.checked || false;

            await Auth.login(email, password, remember);
        });
    }

    // Register form
    const registerForm = document.querySelector('#register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!FormValidation.validateForm(registerForm)) return;

            const formData = {
                username: registerForm.querySelector('[name="username"]').value,
                email: registerForm.querySelector('[name="email"]').value,
                password: registerForm.querySelector('[name="password"]').value
            };

            await Auth.register(formData);
        });
    }

    // Profile form
    const profileForm = document.querySelector('#profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!FormValidation.validateForm(profileForm)) return;

            const formData = {
                username: profileForm.querySelector('[name="username"]').value,
                email: profileForm.querySelector('[name="email"]').value
            };

            await Auth.updateProfile(formData);
        });
    }

    // Password change form
    const passwordForm = document.querySelector('#password-form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!FormValidation.validateForm(passwordForm)) return;

            const current = passwordForm.querySelector('[name="current_password"]').value;
            const newPass = passwordForm.querySelector('[name="new_password"]').value;

            await Auth.changePassword(current, newPass);
        });
    }

    // Modal close on backdrop click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                Modal.close(modal.id);
            }
        });
    });

    // Modal close buttons
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            Modal.closeAll();
        });
    });

    // Close search dropdown on outside click
    document.addEventListener('click', (e) => {
        const dropdown = document.querySelector('.search-dropdown');
        const searchBar = document.querySelector('.search-bar');
        if (dropdown && searchBar && !searchBar.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // Admin delete buttons
    document.querySelectorAll('[data-delete]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const [type, id] = btn.dataset.delete.split(':');
            Admin.deleteItem(type, id);
        });
    });

    // Admin toggle status buttons
    document.querySelectorAll('[data-toggle-status]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            Admin.toggleUserStatus(btn.dataset.toggleStatus);
        });
    });

    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const adminSidebar = document.querySelector('.admin-sidebar');
    if (menuToggle && adminSidebar) {
        menuToggle.addEventListener('click', () => {
            adminSidebar.classList.toggle('active');
        });
    }

    // Password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.previousElementSibling;
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            btn.innerHTML = `<i class="fas fa-eye${type === 'password' ? '' : '-slash'}"></i>`;
        });
    });
});

// Expose to global scope for inline handlers
window.Cart = Cart;
window.Wishlist = Wishlist;
window.Auth = Auth;
window.Modal = Modal;
window.Admin = Admin;
window.Utils = Utils;
