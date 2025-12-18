<?php
/**
 * API Panier - Games Store
 * Gère les opérations du panier
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

// Lire les données JSON
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? '';

// Toutes les opérations nécessitent une connexion
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Connexion requise']);
    exit();
}

switch ($method) {
    case 'GET':
        getCart();
        break;
    case 'POST':
        if ($action === 'add') {
            addToCart($input);
        } elseif ($action === 'remove') {
            removeFromCart($input);
        } elseif ($action === 'checkout') {
            checkout();
        } elseif ($action === 'clear') {
            clearCart();
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action non valide']);
        }
        break;
    case 'DELETE':
        if ($action === 'clear') {
            clearCart();
        } else {
            removeFromCart($input);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}

/**
 * Récupère le contenu du panier
 */
function getCart() {
    global $pdo;
    $userId = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("
        SELECT c.id as cart_id, c.added_at, g.*, 
               COALESCE(g.discount_price, g.price) as final_price,
               cat.name as category_name
        FROM cart c 
        JOIN games g ON c.game_id = g.id 
        LEFT JOIN categories cat ON g.category_id = cat.id
        WHERE c.user_id = ?
        ORDER BY c.added_at DESC
    ");
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();
    
    // Calcul du total
    $total = 0;
    $originalTotal = 0;
    foreach ($items as $item) {
        $total += $item['final_price'];
        $originalTotal += $item['price'];
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'count' => count($items),
        'total' => round($total, 2),
        'original_total' => round($originalTotal, 2),
        'savings' => round($originalTotal - $total, 2)
    ]);
}

/**
 * Ajoute un jeu au panier
 */
function addToCart($input) {
    global $pdo;
    $userId = $_SESSION['user_id'];
    $gameId = intval($input['game_id'] ?? 0);
    
    if ($gameId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de jeu invalide']);
        return;
    }
    
    // Vérifie que le jeu existe
    $stmt = $pdo->prepare("SELECT id, title FROM games WHERE id = ?");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch();
    
    if (!$game) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Jeu non trouvé']);
        return;
    }
    
    // Vérifie si déjà possédé
    if (isOwned($gameId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Vous possédez déjà ce jeu']);
        return;
    }
    
    // Vérifie si déjà dans le panier
    if (isInCart($gameId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ce jeu est déjà dans votre panier']);
        return;
    }
    
    // Ajout au panier
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, game_id) VALUES (?, ?)");
    $stmt->execute([$userId, $gameId]);
    
    echo json_encode([
        'success' => true,
        'message' => $game['title'] . ' ajouté au panier',
        'cart_count' => getCartCount()
    ]);
}

/**
 * Supprime un jeu du panier
 */
function removeFromCart($input) {
    global $pdo;
    $userId = $_SESSION['user_id'];
    $gameId = intval($input['game_id'] ?? $_GET['game_id'] ?? 0);
    
    if ($gameId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de jeu invalide']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND game_id = ?");
    $stmt->execute([$userId, $gameId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Jeu non trouvé dans le panier']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Jeu retiré du panier',
        'cart_count' => getCartCount()
    ]);
}

/**
 * Vide le panier
 */
function clearCart() {
    global $pdo;
    $userId = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Panier vidé',
        'cart_count' => 0
    ]);
}

/**
 * Procède au paiement (simulation)
 */
function checkout() {
    global $pdo;
    $userId = $_SESSION['user_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Récupère les jeux du panier
        $stmt = $pdo->prepare("
            SELECT c.game_id, COALESCE(g.discount_price, g.price) as price
            FROM cart c 
            JOIN games g ON c.game_id = g.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        $cartItems = $stmt->fetchAll();
        
        if (empty($cartItems)) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Votre panier est vide']);
            return;
        }
        
        // Ajoute les jeux aux achats
        $insertStmt = $pdo->prepare("
            INSERT INTO purchases (user_id, game_id, purchase_price) VALUES (?, ?, ?)
        ");
        
        $total = 0;
        foreach ($cartItems as $item) {
            $insertStmt->execute([$userId, $item['game_id'], $item['price']]);
            $total += $item['price'];
        }
        
        // Vide le panier
        $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Achat effectué avec succès !',
            'total' => round($total, 2),
            'games_count' => count($cartItems)
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors du paiement']);
    }
}
?>
