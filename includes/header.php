<?php
/**
 * Header commun - Games Store
 */
require_once __DIR__ . '/functions.php';

$currentUser = getCurrentUser();
$cartCount = getCartCount();
$flash = getFlashMessage();

// Détermine la page active
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? escape($pageTitle) . ' - ' : ''; ?>Games Store</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation principale -->
    <nav class="navbar">
        <div class="nav-container">
            <!-- Logo -->
            <a href="/index.php" class="nav-logo">
                <i class="fas fa-gamepad"></i>
                <span>Games Store</span>
            </a>

            <!-- Menu principal -->
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="/index.php" class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> Accueil
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/pages/store.php" class="nav-link <?php echo $currentPage === 'store' ? 'active' : ''; ?>">
                        <i class="fas fa-store"></i> Boutique
                    </a>
                </li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item">
                    <a href="/pages/library.php" class="nav-link <?php echo $currentPage === 'library' ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i> Bibliothèque
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/pages/wishlist.php" class="nav-link <?php echo $currentPage === 'wishlist' ? 'active' : ''; ?>">
                        <i class="fas fa-heart"></i> Wishlist
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Barre de recherche -->
            <div class="nav-search">
                <form action="/pages/store.php" method="GET">
                    <input type="text" name="search" placeholder="Rechercher un jeu..." class="search-input">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <!-- Actions utilisateur -->
            <div class="nav-actions">
                <a href="/pages/cart.php" class="nav-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </a>

                <?php if ($currentUser): ?>
                <div class="nav-user dropdown">
                    <button class="dropdown-toggle">
                        <img src="/assets/images/avatars/<?php echo escape($currentUser['avatar']); ?>" alt="Avatar" class="user-avatar">
                        <span><?php echo escape($currentUser['username']); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="/pages/profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i> Mon Profil
                        </a>
                        <a href="/pages/library.php" class="dropdown-item">
                            <i class="fas fa-gamepad"></i> Mes Jeux
                        </a>
                        <?php if (isAdmin()): ?>
                        <div class="dropdown-divider"></div>
                        <a href="/admin/index.php" class="dropdown-item">
                            <i class="fas fa-cog"></i> Administration
                        </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="/api/auth.php?action=logout" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="nav-auth">
                    <a href="/pages/login.php" class="btn btn-outline">Connexion</a>
                    <a href="/pages/register.php" class="btn btn-primary">S'inscrire</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Menu mobile toggle -->
            <button class="nav-toggle" id="navToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Message Flash -->
    <?php if ($flash): ?>
    <div class="alert alert-<?php echo escape($flash['type']); ?>" id="flashMessage">
        <span><?php echo escape($flash['message']); ?></span>
        <button class="alert-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- Contenu principal -->
    <main class="main-content">
