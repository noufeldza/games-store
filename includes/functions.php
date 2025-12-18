<?php
/**
 * Fonctions utilitaires pour Games Store
 */

session_start();

require_once __DIR__ . '/../config/database.php';

/**
 * Vérifie si l'utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur est admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirige vers une page
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Protège une page (nécessite connexion)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/pages/login.php');
    }
}

/**
 * Protège une page admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        redirect('/index.php');
    }
}

/**
 * Échappe les données HTML
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Génère un token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le token CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Obtient l'utilisateur actuel
 */
function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT id, username, email, avatar, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Obtient le nombre d'items dans le panier
 */
function getCartCount() {
    global $pdo;
    if (!isLoggedIn()) {
        return 0;
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    return $result['count'];
}

/**
 * Formate un prix
 */
function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' €';
}

/**
 * Calcule le pourcentage de réduction
 */
function calculateDiscount($originalPrice, $discountPrice) {
    if ($discountPrice && $discountPrice < $originalPrice) {
        return round((($originalPrice - $discountPrice) / $originalPrice) * 100);
    }
    return 0;
}

/**
 * Vérifie si un jeu est dans le panier
 */
function isInCart($gameId) {
    global $pdo;
    if (!isLoggedIn()) {
        return false;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND game_id = ?");
    $stmt->execute([$_SESSION['user_id'], $gameId]);
    return $stmt->fetch() !== false;
}

/**
 * Vérifie si un jeu est possédé
 */
function isOwned($gameId) {
    global $pdo;
    if (!isLoggedIn()) {
        return false;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM purchases WHERE user_id = ? AND game_id = ?");
    $stmt->execute([$_SESSION['user_id'], $gameId]);
    return $stmt->fetch() !== false;
}

/**
 * Vérifie si un jeu est dans la wishlist
 */
function isInWishlist($gameId) {
    global $pdo;
    if (!isLoggedIn()) {
        return false;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM wishlists WHERE user_id = ? AND game_id = ?");
    $stmt->execute([$_SESSION['user_id'], $gameId]);
    return $stmt->fetch() !== false;
}

/**
 * Affiche un message flash
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Récupère et supprime le message flash
 */
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Exécute une requête SELECT et retourne tous les résultats
 */
function fetchAll($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Exécute une requête SELECT et retourne une seule ligne
 */
function fetchOne($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Exécute une requête INSERT/UPDATE/DELETE
 */
function execute($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Retourne le dernier ID inséré
 */
function lastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}

/**
 * Valide une adresse email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Génère un slug à partir d'une chaîne
 */
function slugify($string) {
    $string = preg_replace('/[^\w\s-]/', '', $string);
    $string = preg_replace('/[\s_]/', '-', $string);
    return strtolower(trim($string, '-'));
}

/**
 * Retourne l'URL de l'image du jeu ou un placeholder
 */
function getGameImage($image) {
    $imagePath = '/assets/images/' . $image;
    $fullPath = __DIR__ . '/..' . $imagePath;
    
    if ($image && file_exists($fullPath)) {
        return $imagePath;
    }
    return '/assets/images/placeholder.svg';
}

/**
 * Retourne l'URL de la bannière du jeu (pour carrousel et fond de page)
 * Cherche d'abord dans /banners/, sinon utilise l'image normale
 */
function getBannerImage($image, $bannerImage = null) {
    // Si une bannière spécifique est définie
    if ($bannerImage) {
        $bannerPath = '/assets/images/banners/' . $bannerImage;
        $fullPath = __DIR__ . '/..' . $bannerPath;
        if (file_exists($fullPath)) {
            return $bannerPath;
        }
    }
    
    // Sinon cherche une bannière avec le même nom que l'image
    if ($image) {
        $bannerPath = '/assets/images/banners/' . $image;
        $fullPath = __DIR__ . '/..' . $bannerPath;
        if (file_exists($fullPath)) {
            return $bannerPath;
        }
    }
    
    // Fallback sur l'image normale
    return getGameImage($image);
}

/**
 * Retourne l'URL de l'avatar ou un avatar par défaut
 */
function getAvatar($avatar) {
    if ($avatar && $avatar !== 'default-avatar.png') {
        return '/assets/images/avatars/' . $avatar;
    }
    return '/assets/images/placeholder.svg';
}
?>
