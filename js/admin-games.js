/**
 * Admin Games Management
 */
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('gameModal');
    const form = document.getElementById('gameForm');
    
    if (!modal || !form) return;

    // Ouvrir modal pour ajouter
    const addBtn = document.getElementById('addGameBtn');
    if (addBtn) {
        addBtn.addEventListener('click', () => {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus"></i> Ajouter un jeu';
            form.reset();
            document.getElementById('gameId').value = '';
            document.getElementById('gameFormMessage').style.display = 'none';
            modal.classList.add('active');
        });
    }

    // Ouvrir modal pour modifier
    document.querySelectorAll('.edit-game').forEach(btn => {
        btn.addEventListener('click', function() {
            const game = JSON.parse(this.dataset.game);
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Modifier le jeu';
            
            document.getElementById('gameId').value = game.id;
            document.getElementById('title').value = game.title || '';
            document.getElementById('description').value = game.description || '';
            document.getElementById('price').value = game.price || '';
            document.getElementById('discount_price').value = game.discount_price || '';
            document.getElementById('category_id').value = game.category_id || '';
            document.getElementById('developer').value = game.developer || '';
            document.getElementById('publisher').value = game.publisher || '';
            document.getElementById('release_date').value = game.release_date || '';
            document.getElementById('image').value = game.image || '';
            document.getElementById('banner_image').value = game.banner_image || '';
            document.getElementById('video_url').value = game.video_url || '';
            document.getElementById('is_featured').checked = game.is_featured == 1;
            
            document.getElementById('gameFormMessage').style.display = 'none';
            modal.classList.add('active');
        });
    });

    // Fermer modal
    document.querySelectorAll('.modal-close, .modal-cancel').forEach(btn => {
        btn.addEventListener('click', () => modal.classList.remove('active'));
    });

    // Fermer modal sur clic en dehors
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });

    // Soumettre formulaire
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const gameId = document.getElementById('gameId').value;
        const isEdit = gameId !== '';
        
        const data = {};
        formData.forEach((value, key) => {
            if (key === 'is_featured') {
                data[key] = document.getElementById('is_featured').checked ? 1 : 0;
            } else {
                data[key] = value;
            }
        });
        
        // Ensure is_featured is set even if unchecked
        if (!data.hasOwnProperty('is_featured')) {
            data['is_featured'] = document.getElementById('is_featured').checked ? 1 : 0;
        }
        
        try {
            const response = await fetch('/api/games.php' + (isEdit ? '?id=' + gameId : ''), {
                method: isEdit ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                location.reload();
            } else {
                const msgDiv = document.getElementById('gameFormMessage');
                msgDiv.className = 'alert alert-danger';
                msgDiv.textContent = result.error || 'Erreur lors de l\'enregistrement';
                msgDiv.style.display = 'block';
            }
        } catch (error) {
            console.error(error);
            const msgDiv = document.getElementById('gameFormMessage');
            msgDiv.className = 'alert alert-danger';
            msgDiv.textContent = 'Erreur de connexion au serveur';
            msgDiv.style.display = 'block';
        }
    });

    // Supprimer un jeu
    document.querySelectorAll('.delete-game').forEach(btn => {
        btn.addEventListener('click', async function() {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce jeu?')) return;
            
            const gameId = this.dataset.gameId;
            
            try {
                const response = await fetch('/api/games.php?id=' + gameId, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.closest('tr').remove();
                    Utils.showNotification('Jeu supprimé', 'success');
                } else {
                    Utils.showNotification(result.error || 'Erreur', 'danger');
                }
            } catch (error) {
                console.error(error);
                Utils.showNotification('Erreur de connexion', 'danger');
            }
        });
    });

    // Toggle featured
    document.querySelectorAll('.toggle-featured').forEach(btn => {
        btn.addEventListener('click', async function() {
            const gameId = this.dataset.gameId;
            const isFeatured = this.classList.contains('btn-warning');
            
            try {
                const response = await fetch('/api/games.php?id=' + gameId, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ is_featured: isFeatured ? 0 : 1 })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.classList.toggle('btn-warning');
                    this.classList.toggle('btn-outline');
                }
            } catch (error) {
                console.error(error);
            }
        });
    });
});
