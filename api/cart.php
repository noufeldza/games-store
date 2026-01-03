<?php
/**
 * API Panier - Games Store
 * Gère les opérations du panier pour utilisateurs connectés ET invités
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

// ================= CONFIGURATION =================
class CartConfig {
    const GUEST_CART_NAME = 'guest_cart';
    const GUEST_CART_EXPIRE = 604800; // 7 jours
    const MAX_GUEST_ITEMS = 20;
}

// ================= GESTION DES COOKIES =================
class GuestCartManager {
    public static function getCart() {
        if (!isset($_COOKIE[CartConfig::GUEST_CART_NAME])) {
            return [];
        }
        
        $cart = json_decode($_COOKIE[CartConfig::GUEST_CART_NAME], true);
        return is_array($cart) ? $cart : [];
    }
    
    public static function saveCart($cart) {
        if (count($cart) > CartConfig::MAX_GUEST_ITEMS) {
            $cart = array_slice($cart, 0, CartConfig::MAX_GUEST_ITEMS);
        }
        // Respecter le consentement : n'enregistrer le cookie invité que si l'utilisateur a accepté
        $consent = $_COOKIE['cookie_consent'] ?? null;
        if ($consent !== 'accepted') {
            // Ne pas écrire le cookie si pas de consentement
            return false;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        return setcookie(
            CartConfig::GUEST_CART_NAME,
            json_encode($cart),
            [
                'expires' => time() + CartConfig::GUEST_CART_EXPIRE,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }
    
    public static function clear() {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie(
            CartConfig::GUEST_CART_NAME,
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }
    
    public static function migrateToUser($userId) {
        $guestCart = self::getCart();
        if (empty($guestCart)) {
            return 0;
        }
        
        global $pdo;
        $migrated = 0;
        
        foreach ($guestCart as $gameId) {
            $gameId = intval($gameId);
            if ($gameId > 0 && !self::isInUserCart($userId, $gameId)) {
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, game_id) VALUES (?, ?)");
                $stmt->execute([$userId, $gameId]);
                $migrated++;
            }
        }
        
        self::clear();
        return $migrated;
    }
    
    private static function isInUserCart($userId, $gameId) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND game_id = ?");
        $stmt->execute([$userId, $gameId]);
        return (bool)$stmt->fetch();
    }
}

// ================= GESTION DU PANIER =================
class CartHandler {
    private $userId;
    private $isGuest;
    
    public function __construct() {
        $this->isGuest = !isLoggedIn();
        $this->userId = $this->isGuest ? null : $_SESSION['user_id'];
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $input = $this->getInput();
        $action = $input['action'] ?? '';
        
        switch ($method) {
            case 'GET':
                return $this->getCart();
            case 'POST':
                return $this->handlePostAction($action, $input);
            case 'DELETE':
                return $this->handleDeleteAction($action, $input);
            default:
                return $this->jsonError('Méthode non autorisée', 405);
        }
    }
    
    // ================= ACTIONS PRINCIPALES =================
    
    public function getCart() {
        if ($this->isGuest) {
            return $this->getGuestCart();
        } else {
            return $this->getUserCart();
        }
    }
    
    public function addItem($gameId) {
        $gameId = intval($gameId);
        
        if ($gameId <= 0) {
            return $this->jsonError('ID de jeu invalide', 400);
        }
        
        if (!$this->validateGame($gameId)) {
            return $this->jsonError('Jeu non trouvé', 404);
        }
        
        if ($this->isGuest) {
            return $this->addToGuestCart($gameId);
        } else {
            return $this->addToUserCart($gameId);
        }
    }
    
    public function removeItem($gameId) {
        $gameId = intval($gameId);
        
        if ($gameId <= 0) {
            return $this->jsonError('ID de jeu invalide', 400);
        }
        
        if ($this->isGuest) {
            return $this->removeFromGuestCart($gameId);
        } else {
            return $this->removeFromUserCart($gameId);
        }
    }
    
    public function clearCart() {
        if ($this->isGuest) {
            GuestCartManager::clear();
            return $this->jsonSuccess(['message' => 'Panier invité vidé', 'cart_count' => 0]);
        } else {
            return $this->clearUserCart();
        }
    }
    
    public function checkout() {
        if ($this->isGuest) {
            return $this->jsonError('Connexion requise pour finaliser l\'achat', 401);
        }
        return $this->processCheckout();
    }
    
    public function migrateGuestCart() {
        if ($this->isGuest || !$this->userId) {
            return $this->jsonError('Opération non autorisée', 401);
        }
        
        $migrated = GuestCartManager::migrateToUser($this->userId);
        return $this->jsonSuccess([
            'message' => "{$migrated} article(s) migré(s) depuis votre panier invité",
            'migrated_count' => $migrated
        ]);
    }
    
    // ================= MÉTHODES UTILISATEUR =================
    
    private function getUserCart() {
        global $pdo;
        
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
        $stmt->execute([$this->userId]);
        $items = $stmt->fetchAll();
        
        return $this->formatCartResponse($items);
    }
    
    private function addToUserCart($gameId) {
        global $pdo;
        
        // Vérifications
        if ($this->isOwned($gameId)) {
            return $this->jsonError('Vous possédez déjà ce jeu', 400);
        }
        
        if ($this->isInUserCart($gameId)) {
            return $this->jsonError('Ce jeu est déjà dans votre panier', 400);
        }
        
        // Ajout
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, game_id) VALUES (?, ?)");
        $stmt->execute([$this->userId, $gameId]);
        
        $gameTitle = $this->getGameTitle($gameId);
        
        return $this->jsonSuccess([
            'message' => $gameTitle . ' ajouté au panier',
            'cart_count' => $this->getCartCount(),
            'user_type' => 'registered'
        ]);
    }
    
    private function removeFromUserCart($gameId) {
        global $pdo;
        
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND game_id = ?");
        $stmt->execute([$this->userId, $gameId]);
        
        if ($stmt->rowCount() === 0) {
            return $this->jsonError('Jeu non trouvé dans le panier', 404);
        }
        
        return $this->jsonSuccess([
            'message' => 'Jeu retiré du panier',
            'cart_count' => $this->getCartCount()
        ]);
    }
    
    private function clearUserCart() {
        global $pdo;
        
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$this->userId]);
        
        return $this->jsonSuccess([
            'message' => 'Panier vidé',
            'cart_count' => 0
        ]);
    }
    
    // ================= MÉTHODES INVITÉ =================
    
    private function getGuestCart() {
        $cartIds = GuestCartManager::getCart();
        
        if (empty($cartIds)) {
            return $this->formatCartResponse([]);
        }
        
        global $pdo;
        $placeholders = str_repeat('?,', count($cartIds) - 1) . '?';
        
        $stmt = $pdo->prepare("
            SELECT g.*, 
                   COALESCE(g.discount_price, g.price) as final_price,
                   cat.name as category_name
            FROM games g 
            LEFT JOIN categories cat ON g.category_id = cat.id
            WHERE g.id IN ({$placeholders})
        ");
        $stmt->execute($cartIds);
        $items = $stmt->fetchAll();
        
        return $this->formatCartResponse($items, true);
    }
    
    private function addToGuestCart($gameId) {
        $cart = GuestCartManager::getCart();
        
        // Vérifie si déjà dans le panier
        if (in_array($gameId, $cart)) {
            return $this->jsonError('Ce jeu est déjà dans votre panier', 400);
        }
        
        // Limite d'articles
        if (count($cart) >= CartConfig::MAX_GUEST_ITEMS) {
            return $this->jsonError('Panier invité plein (max ' . CartConfig::MAX_GUEST_ITEMS . ' articles)', 400);
        }
        
        $cart[] = $gameId;
        GuestCartManager::saveCart($cart);
        
        $gameTitle = $this->getGameTitle($gameId);
        
        return $this->jsonSuccess([
            'message' => $gameTitle . ' ajouté au panier invité',
            'cart_count' => count($cart),
            'user_type' => 'guest',
            'needs_login' => true,
            'needs_login_message' => 'Connectez-vous pour finaliser votre achat'
        ]);
    }
    
    private function removeFromGuestCart($gameId) {
        $cart = GuestCartManager::getCart();
        
        $index = array_search($gameId, $cart);
        if ($index === false) {
            return $this->jsonError('Jeu non trouvé dans le panier', 404);
        }
        
        unset($cart[$index]);
        $cart = array_values($cart); // Réindexe
        GuestCartManager::saveCart($cart);
        
        return $this->jsonSuccess([
            'message' => 'Jeu retiré du panier',
            'cart_count' => count($cart)
        ]);
    }
    
    // ================= MÉTHODES PARTAGÉES =================
    
    private function processCheckout() {
        global $pdo;
        
        try {
            $pdo->beginTransaction();
            
            // Récupère le panier
            $stmt = $pdo->prepare("
                SELECT c.game_id, COALESCE(g.discount_price, g.price) as price
                FROM cart c 
                JOIN games g ON c.game_id = g.id 
                WHERE c.user_id = ?
            ");
            $stmt->execute([$this->userId]);
            $cartItems = $stmt->fetchAll();
            
            if (empty($cartItems)) {
                $pdo->rollBack();
                return $this->jsonError('Votre panier est vide', 400);
            }
            
            // Filtrer les jeux déjà possédés
            $filteredItems = [];
            foreach ($cartItems as $item) {
                if (!$this->isOwned($item['game_id'])) {
                    $filteredItems[] = $item;
                }
            }
            
            if (empty($filteredItems)) {
                $pdo->rollBack();
                return $this->jsonError('Vous possédez déjà tous les jeux du panier', 400);
            }
            
            // Ajouter aux achats
            $insertStmt = $pdo->prepare("
                INSERT INTO purchases (user_id, game_id, purchase_price) VALUES (?, ?, ?)
            ");
            
            $total = 0;
            foreach ($filteredItems as $item) {
                $insertStmt->execute([$this->userId, $item['game_id'], $item['price']]);
                $total += $item['price'];
            }
            
            // Vider le panier
            $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$this->userId]);
            
            $pdo->commit();
            
            return $this->jsonSuccess([
                'success' => true,
                'message' => 'Achat effectué avec succès !',
                'total' => round($total, 2),
                'games_count' => count($filteredItems),
                'redirect' => '/library.php'
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            return $this->jsonError('Erreur lors du paiement', 500);
        }
    }
    
    // ================= MÉTHODES UTILITAIRES =================
    
    private function getInput() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            return json_decode(file_get_contents('php://input'), true) ?? [];
        }
        
        return array_merge($_GET, $_POST);
    }
    
    private function handlePostAction($action, $input) {
        switch ($action) {
            case 'add':
                $gameId = $input['game_id'] ?? 0;
                return $this->addItem($gameId);
            case 'remove':
                $gameId = $input['game_id'] ?? 0;
                return $this->removeItem($gameId);
            case 'checkout':
                return $this->checkout();
            case 'clear':
                return $this->clearCart();
            case 'migrate':
                return $this->migrateGuestCart();
            default:
                return $this->jsonError('Action non valide', 400);
        }
    }
    
    private function handleDeleteAction($action, $input) {
        switch ($action) {
            case 'clear':
                return $this->clearCart();
            default:
                $gameId = $input['game_id'] ?? $_GET['game_id'] ?? 0;
                return $this->removeItem($gameId);
        }
    }
    
    private function formatCartResponse($items, $isGuest = false) {
        $total = 0;
        $originalTotal = 0;
        
        foreach ($items as $item) {
            $total += $item['final_price'];
            $originalTotal += $item['price'];
        }
        
        return $this->jsonSuccess([
            'items' => $items,
            'count' => count($items),
            'total' => round($total, 2),
            'original_total' => round($originalTotal, 2),
            'savings' => round($originalTotal - $total, 2),
            'user_type' => $isGuest ? 'guest' : 'registered',
            'max_items' => $isGuest ? CartConfig::MAX_GUEST_ITEMS : null,
            'needs_login' => $isGuest
        ]);
    }
    
    private function validateGame($gameId) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT id, title FROM games WHERE id = ?");
        $stmt->execute([$gameId]);
        return (bool)$stmt->fetch();
    }
    
    private function getGameTitle($gameId) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT title FROM games WHERE id = ?");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch();
        return $game['title'] ?? 'Jeu inconnu';
    }
    
    private function isOwned($gameId) {
        if ($this->isGuest || !$this->userId) {
            return false;
        }
        
        global $pdo;
        $stmt = $pdo->prepare("SELECT id FROM purchases WHERE user_id = ? AND game_id = ?");
        $stmt->execute([$this->userId, $gameId]);
        return (bool)$stmt->fetch();
    }
    
    private function isInUserCart($gameId) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND game_id = ?");
        $stmt->execute([$this->userId, $gameId]);
        return (bool)$stmt->fetch();
    }
    
    private function getCartCount() {
        if ($this->isGuest) {
            return count(GuestCartManager::getCart());
        }
        
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
        $stmt->execute([$this->userId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }
    
    private function jsonSuccess($data) {
        echo json_encode(array_merge(['success' => true], $data));
    }
    
    private function jsonError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
    }
}

// ================= POINT D'ENTRÉE =================
try {
    $cartHandler = new CartHandler();
    $cartHandler->handleRequest();
    
} catch (Exception $e) {
    error_log('Cart API error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur'
    ]);
}
?>
