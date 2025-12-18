<?php
/**
 * Gestion des catégories - Admin Games Store
 */

$pageTitle = "Gestion des catégories";
require_once __DIR__ . '/../includes/header.php';

requireAdmin();

// Récupérer les catégories avec le nombre de jeux
$categories = fetchAll("
    SELECT c.*, COUNT(g.id) as games_count
    FROM categories c
    LEFT JOIN games g ON c.id = g.category_id
    GROUP BY c.id
    ORDER BY c.name
");
?>

<section class="admin-section">
    <div class="admin-container">
        <!-- Sidebar Admin -->
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <i class="fas fa-gamepad"></i>
                <span>Admin Panel</span>
            </div>
            <nav class="admin-nav">
                <a href="/admin/index.php" class="admin-nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="/admin/games.php" class="admin-nav-link">
                    <i class="fas fa-gamepad"></i> Jeux
                </a>
                <a href="/admin/users.php" class="admin-nav-link">
                    <i class="fas fa-users"></i> Utilisateurs
                </a>
                <a href="/admin/categories.php" class="admin-nav-link active">
                    <i class="fas fa-tags"></i> Catégories
                </a>
                <a href="/admin/orders.php" class="admin-nav-link">
                    <i class="fas fa-shopping-bag"></i> Commandes
                </a>
                <div class="admin-nav-divider"></div>
                <a href="/index.php" class="admin-nav-link">
                    <i class="fas fa-home"></i> Retour au site
                </a>
            </nav>
        </aside>
        
        <!-- Contenu principal -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Gestion des catégories</h1>
                <button class="btn btn-primary" id="addCategoryBtn">
                    <i class="fas fa-plus"></i> Ajouter une catégorie
                </button>
            </div>
            
            <!-- Liste des catégories -->
            <div class="categories-grid admin-categories">
                <?php foreach ($categories as $cat): ?>
                <div class="admin-category-card" data-category-id="<?php echo $cat['id']; ?>">
                    <div class="category-icon">
                        <i class="fas <?php echo escape($cat['icon'] ?? 'fa-gamepad'); ?>"></i>
                    </div>
                    <div class="category-info">
                        <h3><?php echo escape($cat['name']); ?></h3>
                        <p><?php echo escape($cat['description'] ?? 'Aucune description'); ?></p>
                        <span class="category-count"><?php echo $cat['games_count']; ?> jeu(x)</span>
                    </div>
                    <div class="category-actions">
                        <button class="btn btn-sm btn-primary edit-category" 
                                data-category='<?php echo htmlspecialchars(json_encode($cat), ENT_QUOTES); ?>'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-category" 
                                data-category-id="<?php echo $cat['id']; ?>"
                                <?php echo $cat['games_count'] > 0 ? 'disabled title="Catégorie utilisée"' : ''; ?>>
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</section>

<!-- Modal Catégorie -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="categoryModalTitle"><i class="fas fa-tag"></i> Ajouter une catégorie</h2>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="categoryForm">
                <input type="hidden" name="id" id="categoryId">
                
                <div class="form-group">
                    <label for="categoryName">Nom *</label>
                    <input type="text" id="categoryName" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="categoryDescription">Description</label>
                    <textarea id="categoryDescription" name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="categoryIcon">Icône (Font Awesome)</label>
                    <input type="text" id="categoryIcon" name="icon" class="form-control" placeholder="fa-gamepad">
                    <small class="form-hint">Voir: <a href="https://fontawesome.com/icons" target="_blank">Font Awesome Icons</a></small>
                </div>
                
                <div id="categoryFormMessage" class="alert" style="display: none;"></div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline modal-cancel">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const modal = document.getElementById('categoryModal');
const form = document.getElementById('categoryForm');

// Ouvrir modal pour ajouter
document.getElementById('addCategoryBtn').addEventListener('click', () => {
    document.getElementById('categoryModalTitle').innerHTML = '<i class="fas fa-plus"></i> Ajouter une catégorie';
    form.reset();
    document.getElementById('categoryId').value = '';
    modal.classList.add('active');
});

// Ouvrir modal pour éditer
document.querySelectorAll('.edit-category').forEach(btn => {
    btn.addEventListener('click', function() {
        const category = JSON.parse(this.dataset.category);
        document.getElementById('categoryModalTitle').innerHTML = '<i class="fas fa-edit"></i> Modifier la catégorie';
        
        document.getElementById('categoryId').value = category.id;
        document.getElementById('categoryName').value = category.name || '';
        document.getElementById('categoryDescription').value = category.description || '';
        document.getElementById('categoryIcon').value = category.icon || '';
        
        modal.classList.add('active');
    });
});

// Fermer modal
document.querySelectorAll('.modal-close, .modal-cancel').forEach(btn => {
    btn.addEventListener('click', () => modal.classList.remove('active'));
});

// Soumettre formulaire
form.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const categoryId = document.getElementById('categoryId').value;
    const isEdit = categoryId !== '';
    
    const data = {};
    formData.forEach((value, key) => data[key] = value);
    
    try {
        const response = await fetch('/api/games.php?action=category' + (isEdit ? '&id=' + categoryId : ''), {
            method: isEdit ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            const msgDiv = document.getElementById('categoryFormMessage');
            msgDiv.className = 'alert alert-danger';
            msgDiv.textContent = result.error || 'Erreur';
            msgDiv.style.display = 'block';
        }
    } catch (error) {
        console.error(error);
    }
});

// Supprimer
document.querySelectorAll('.delete-category').forEach(btn => {
    btn.addEventListener('click', async function() {
        if (this.disabled) return;
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette catégorie?')) return;
        
        const categoryId = this.dataset.categoryId;
        
        try {
            const response = await fetch('/api/games.php?action=category&id=' + categoryId, {
                method: 'DELETE'
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.closest('.admin-category-card').remove();
            }
        } catch (error) {
            console.error(error);
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
