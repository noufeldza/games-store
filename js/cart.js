/**
 * Cart page JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // Checkout button
    const checkoutBtn = document.getElementById('checkoutBtn');
    const checkoutModal = document.getElementById('checkoutModal');
    
    if (checkoutBtn && checkoutModal) {
        checkoutBtn.addEventListener('click', () => {
            checkoutModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }
    
    // Close modal
    const modalClose = checkoutModal?.querySelector('.modal-close');
    if (modalClose) {
        modalClose.addEventListener('click', () => {
            checkoutModal.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // Close on backdrop click
    if (checkoutModal) {
        checkoutModal.addEventListener('click', (e) => {
            if (e.target === checkoutModal) {
                checkoutModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
    
    // Checkout form submit
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = checkoutForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('/api/cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ action: 'checkout' })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success message
                    checkoutModal.querySelector('.modal-body').innerHTML = `
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-check-circle" style="font-size: 4rem; color: var(--success-color); margin-bottom: 20px;"></i>
                            <h2>Paiement réussi !</h2>
                            <p style="color: var(--text-muted); margin: 15px 0;">Vos jeux ont été ajoutés à votre bibliothèque.</p>
                            <a href="/pages/library.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-gamepad"></i> Voir ma bibliothèque
                            </a>
                        </div>
                    `;
                    
                    // Update cart count
                    document.querySelectorAll('.cart-count').forEach(el => {
                        el.textContent = '0';
                        el.style.display = 'none';
                    });
                } else {
                    Utils.showNotification(result.message || 'Erreur lors du paiement', 'danger');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Checkout error:', error);
                Utils.showNotification('Erreur de connexion au serveur', 'danger');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    }
    
    // Clear cart button
    const clearCartBtn = document.getElementById('clearCartBtn');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', async () => {
            if (!confirm('Êtes-vous sûr de vouloir vider votre panier ?')) {
                return;
            }
            
            try {
                const response = await fetch('/api/cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ action: 'clear' })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    Utils.showNotification(result.message || 'Erreur', 'danger');
                }
            } catch (error) {
                Utils.showNotification('Erreur de connexion', 'danger');
            }
        });
    }
    
    // Remove item buttons
    document.querySelectorAll('.remove-from-cart').forEach(btn => {
        btn.addEventListener('click', async () => {
            const gameId = btn.dataset.gameId;
            
            try {
                const response = await fetch('/api/cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ action: 'remove', game_id: gameId })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    Utils.showNotification(result.message || 'Erreur', 'danger');
                }
            } catch (error) {
                Utils.showNotification('Erreur de connexion', 'danger');
            }
        });
    });
    
    // Payment method toggle
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const cardDetails = document.getElementById('cardDetails');
    
    paymentMethods.forEach(method => {
        method.addEventListener('change', () => {
            if (cardDetails) {
                cardDetails.style.display = method.value === 'card' ? 'block' : 'none';
            }
        });
    });
});
