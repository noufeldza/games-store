<?php
/**
 * API Utilisateurs - Games Store
 * Gère les opérations utilisateur
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'profile') {
            getProfile();
        } elseif ($action === 'library') {
            getLibrary();
        } elseif ($action === 'wishlist') {
            getWishlist();
        } elseif ($action === 'all' && isAdmin()) {
            getAllUsers();
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Action non valide']);
        }
        break;
    case 'POST':
        if ($action === 'wishlist') {
            toggleWishlist();
        } elseif ($action === 'review') {
            addReview();
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Action non valide']);
        }
        break;
    case 'PUT':
        updateProfile();
        break;
    case 'DELETE':
        if (isAdmin()) {
            deleteUser();
        } else {
            http_response_code(403);
            echo json_encode(['error' => 'Accès refusé']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
}

/**
 * Récupère le profil utilisateur
 */
function getProfile() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Connexion requise']);
        return;
    }
    
    global $pdo;
    $userId = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("
        SELECT id, username, email, avatar, role, created_at 
        FROM users WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    // Statistiques
    $statsStmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM purchases WHERE user_id = ?) as games_owned,
            (SELECT COUNT(*) FROM wishlists WHERE user_id = ?) as wishlist_count,
            (SELECT COUNT(*) FROM reviews WHERE user_id = ?) as reviews_count
    ");
    $statsStmt->execute([$userId, $userId, $userId]);
    $stats = $statsStmt->fetch();
    
    echo json_encode([
        'success' => true,
        'user' => $user,
        'stats' => $stats
    ]);
}

/**
 * Récupère la bibliothèque de jeux
 */
function getLibrary() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Connexion requise']);
        return;
    }
    
    global $pdo;
    $userId = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("
        SELECT g.*, p.purchase_date, p.purchase_price, c.name as category_name
        FROM purchases p
        JOIN games g ON p.game_id = g.id
        LEFT JOIN categories c ON g.category_id = c.id
        WHERE p.user_id = ?
        ORDER BY p.purchase_date DESC
    ");
    $stmt->execute([$userId]);
    
    echo json_encode([
        'success' => true,
        'games' => $stmt->fetchAll()
    ]);
}

/**
 * Récupère la wishlist
 */
function getWishlist() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Connexion requise']);
        return;
    }
    
    global $pdo;
    $userId = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("
        SELECT g.*, w.added_at, c.name as category_name,
               COALESCE(g.discount_price, g.price) as final_price
        FROM wishlists w
        JOIN games g ON w.game_id = g.id
        LEFT JOIN categories c ON g.category_id = c.id
        WHERE w.user_id = ?
        ORDER BY w.added_at DESC
    ");
    $stmt->execute([$userId]);
    
    echo json_encode([
        'success' => true,
        'games' => $stmt->fetchAll()
    ]);
}

/**
 * Ajoute/Supprime de la wishlist
 */
function toggleWishlist() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Connexion requise']);
        return;
    }
    
    global $pdo;
    $userId = $_SESSION['user_id'];
    $gameId = intval($_POST['game_id'] ?? 0);
    
    if ($gameId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de jeu invalide']);
        return;
    }
    
    // Vérifie si déjà dans la wishlist
    $checkStmt = $pdo->prepare("SELECT id FROM wishlists WHERE user_id = ? AND game_id = ?");
    $checkStmt->execute([$userId, $gameId]);
    
    if ($checkStmt->fetch()) {
        // Supprime de la wishlist
        $pdo->prepare("DELETE FROM wishlists WHERE user_id = ? AND game_id = ?")
            ->execute([$userId, $gameId]);
        echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Retiré de la wishlist']);
    } else {
        // Ajoute à la wishlist
        $pdo->prepare("INSERT INTO wishlists (user_id, game_id) VALUES (?, ?)")
            ->execute([$userId, $gameId]);
        echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Ajouté à la wishlist']);
    }
}

/**
 * Ajoute un avis
 */
function addReview() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Connexion requise']);
        return;
    }
    
    global $pdo;
    $userId = $_SESSION['user_id'];
    $gameId = intval($_POST['game_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    // Validation
    if ($gameId <= 0 || $rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['error' => 'Données invalides']);
        return;
    }
    
    // Vérifie si l'utilisateur possède le jeu
    if (!isOwned($gameId)) {
        http_response_code(403);
        echo json_encode(['error' => 'Vous devez posséder le jeu pour laisser un avis']);
        return;
    }
    
    // Insert ou update
    $stmt = $pdo->prepare("
        INSERT INTO reviews (user_id, game_id, rating, comment) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE rating = ?, comment = ?
    ");
    $stmt->execute([$userId, $gameId, $rating, $comment, $rating, $comment]);
    
    // Met à jour la note moyenne du jeu
    updateGameRating($gameId);
    
    echo json_encode(['success' => true, 'message' => 'Avis enregistré']);
}

/**
 * Met à jour la note moyenne d'un jeu
 */
function updateGameRating($gameId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE games 
        SET rating = (SELECT AVG(rating) FROM reviews WHERE game_id = ?)
        WHERE id = ?
    ");
    $stmt->execute([$gameId, $gameId]);
}

/**
 * Met à jour le profil
 */
function updateProfile() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Connexion requise']);
        return;
    }
    
    global $pdo;
    $userId = $_SESSION['user_id'];
    
    parse_str(file_get_contents('php://input'), $data);
    
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $currentPassword = $data['current_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';
    
    // Récupère l'utilisateur actuel
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    $updates = [];
    $params = [];
    
    // Mise à jour du username
    if (!empty($username)) {
        // Vérifie l'unicité
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $checkStmt->execute([$username, $userId]);
        if ($checkStmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Ce nom d\'utilisateur est déjà pris']);
            return;
        }
        $updates[] = "username = ?";
        $params[] = $username;
    }
    
    // Mise à jour de l'email
    if (!empty($email)) {
        if (!isValidEmail($email)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email invalide']);
            return;
        }
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkStmt->execute([$email, $userId]);
        if ($checkStmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Cet email est déjà utilisé']);
            return;
        }
        $updates[] = "email = ?";
        $params[] = $email;
    }
    
    // Mise à jour du mot de passe
    if (!empty($newPassword)) {
        if (!password_verify($currentPassword, $user['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Mot de passe actuel incorrect']);
            return;
        }
        if (strlen($newPassword) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'Le nouveau mot de passe doit contenir au moins 8 caractères']);
            return;
        }
        $updates[] = "password = ?";
        $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'Aucune modification']);
        return;
    }
    
    $params[] = $userId;
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    $pdo->prepare($sql)->execute($params);
    
    // Met à jour la session si le username a changé
    if (!empty($username)) {
        $_SESSION['username'] = $username;
    }
    
    echo json_encode(['success' => true, 'message' => 'Profil mis à jour']);
}

/**
 * Récupère tous les utilisateurs (Admin)
 */
function getAllUsers() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT id, username, email, role, created_at,
               (SELECT COUNT(*) FROM purchases WHERE user_id = users.id) as games_count
        FROM users 
        ORDER BY created_at DESC
    ");
    
    echo json_encode(['success' => true, 'users' => $stmt->fetchAll()]);
}

/**
 * Supprime un utilisateur (Admin)
 */
function deleteUser() {
    global $pdo;
    
    $userId = intval($_GET['id'] ?? 0);
    
    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID invalide']);
        return;
    }
    
    // Empêche la suppression de l'admin principal
    if ($userId === 1) {
        http_response_code(403);
        echo json_encode(['error' => 'Impossible de supprimer l\'administrateur principal']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    
    echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé']);
}
?>
