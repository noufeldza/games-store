<?php
/**
 * API Jeux - Games Store
 * Gère les opérations CRUD sur les jeux
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

// Lire les données JSON
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'single') {
            getGame();
        } elseif ($action === 'featured') {
            getFeaturedGames();
        } elseif ($action === 'categories') {
            getCategories();
        } elseif ($action === 'search') {
            searchGames();
        } else {
            getGames();
        }
        break;
    case 'POST':
        if ($action === 'toggle_wishlist') {
            toggleWishlist($input);
        } else {
            requireAdmin();
            createGame();
        }
        break;
    case 'PUT':
        requireAdmin();
        updateGame();
        break;
    case 'DELETE':
        requireAdmin();
        deleteGame();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}

/**
 * Toggle wishlist item
 */
function toggleWishlist($input) {
    global $pdo;
    
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Connexion requise']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $gameId = intval($input['game_id'] ?? 0);
    
    if ($gameId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de jeu invalide']);
        return;
    }
    
    // Vérifie si déjà dans la wishlist
    $stmt = $pdo->prepare("SELECT id FROM wishlists WHERE user_id = ? AND game_id = ?");
    $stmt->execute([$userId, $gameId]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        // Retirer de la wishlist
        $stmt = $pdo->prepare("DELETE FROM wishlists WHERE user_id = ? AND game_id = ?");
        $stmt->execute([$userId, $gameId]);
        $added = false;
    } else {
        // Ajouter à la wishlist
        $stmt = $pdo->prepare("INSERT INTO wishlists (user_id, game_id) VALUES (?, ?)");
        $stmt->execute([$userId, $gameId]);
        $added = true;
    }
    
    // Compter le total wishlist
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlists WHERE user_id = ?");
    $stmt->execute([$userId]);
    $count = $stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'added' => $added,
        'message' => $added ? 'Ajouté à la wishlist' : 'Retiré de la wishlist',
        'wishlist_count' => $count
    ]);
}

/**
 * Search games
 */
function searchGames() {
    global $pdo;
    
    $query = $_GET['q'] ?? '';
    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'games' => []]);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT id, title as name, image, COALESCE(discount_price, price) as price 
        FROM games 
        WHERE title LIKE ? OR developer LIKE ?
        LIMIT 10
    ");
    $searchTerm = "%$query%";
    $stmt->execute([$searchTerm, $searchTerm]);
    $games = $stmt->fetchAll();
    
    // Ajouter le chemin complet de l'image
    foreach ($games as &$game) {
        $game['image'] = getGameImage($game['image']);
    }
    
    echo json_encode(['success' => true, 'games' => $games]);
}

/**
 * Récupère la liste des jeux avec filtres
 */
function getGames() {
    global $pdo;
    
    // Paramètres de filtrage
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $minPrice = $_GET['min_price'] ?? '';
    $maxPrice = $_GET['max_price'] ?? '';
    $sortBy = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'DESC';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(1, intval($_GET['limit'] ?? 12)));
    $offset = ($page - 1) * $limit;
    $discount = $_GET['discount'] ?? '';
    $featured = $_GET['featured'] ?? '';
    
    // Construction de la requête
    $where = [];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(g.title LIKE ? OR g.description LIKE ? OR g.developer LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($category)) {
        $where[] = "g.category_id = ?";
        $params[] = $category;
    }
    
    if (!empty($minPrice)) {
        $where[] = "COALESCE(g.discount_price, g.price) >= ?";
        $params[] = $minPrice;
    }
    
    if (!empty($maxPrice)) {
        $where[] = "COALESCE(g.discount_price, g.price) <= ?";
        $params[] = $maxPrice;
    }
    
    if ($discount === '1') {
        $where[] = "g.discount_price IS NOT NULL";
    }
    
    if ($featured === '1') {
        $where[] = "g.is_featured = 1";
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Tri sécurisé
    $allowedSorts = ['title', 'price', 'release_date', 'created_at', 'rating'];
    $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';
    $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
    
    // Requête principale
    $sql = "SELECT g.*, c.name as category_name 
            FROM games g 
            LEFT JOIN categories c ON g.category_id = c.id 
            $whereClause 
            ORDER BY g.$sortBy $order 
            LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $games = $stmt->fetchAll();
    
    // Compte total pour pagination
    $countSql = "SELECT COUNT(*) FROM games g $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'games' => $games,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Récupère un jeu spécifique
 */
function getGame() {
    global $pdo;
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID invalide']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT g.*, c.name as category_name 
        FROM games g 
        LEFT JOIN categories c ON g.category_id = c.id 
        WHERE g.id = ?
    ");
    $stmt->execute([$id]);
    $game = $stmt->fetch();
    
    if (!$game) {
        http_response_code(404);
        echo json_encode(['error' => 'Jeu non trouvé']);
        return;
    }
    
    // Récupération des images supplémentaires
    $imgStmt = $pdo->prepare("SELECT * FROM game_images WHERE game_id = ?");
    $imgStmt->execute([$id]);
    $game['images'] = $imgStmt->fetchAll();
    
    // Récupération des avis
    $reviewStmt = $pdo->prepare("
        SELECT r.*, u.username 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.game_id = ? 
        ORDER BY r.created_at DESC 
        LIMIT 10
    ");
    $reviewStmt->execute([$id]);
    $game['reviews'] = $reviewStmt->fetchAll();
    
    echo json_encode(['success' => true, 'game' => $game]);
}

/**
 * Récupère les jeux en vedette
 */
function getFeaturedGames() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT g.*, c.name as category_name 
        FROM games g 
        LEFT JOIN categories c ON g.category_id = c.id 
        WHERE g.is_featured = 1 
        ORDER BY g.created_at DESC 
        LIMIT 5
    ");
    
    echo json_encode(['success' => true, 'games' => $stmt->fetchAll()]);
}

/**
 * Récupère les catégories
 */
function getCategories() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT c.*, COUNT(g.id) as game_count 
        FROM categories c 
        LEFT JOIN games g ON c.id = g.category_id 
        GROUP BY c.id 
        ORDER BY c.name
    ");
    
    echo json_encode(['success' => true, 'categories' => $stmt->fetchAll()]);
}

/**
 * Crée un nouveau jeu (Admin)
 */
function createGame() {
    global $pdo, $input;
    
    // Récupérer les données JSON ou POST
    $data = !empty($input) ? $input : $_POST;
    
    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $price = floatval($data['price'] ?? 0);
    $discountPrice = !empty($data['discount_price']) ? floatval($data['discount_price']) : null;
    $categoryId = !empty($data['category_id']) ? intval($data['category_id']) : null;
    $releaseDate = !empty($data['release_date']) ? $data['release_date'] : null;
    $developer = trim($data['developer'] ?? '');
    $publisher = trim($data['publisher'] ?? '');
    $imageName = trim($data['image'] ?? 'default-game.jpg');
    $bannerImage = !empty($data['banner_image']) ? trim($data['banner_image']) : null;
    $videoUrl = trim($data['video_url'] ?? '');
    $isFeatured = !empty($data['is_featured']) ? 1 : 0;
    
    // Validation
    if (empty($title) || $price <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Titre et prix requis']);
        return;
    }
    
    // Gestion de l'image uploadée (si fichier)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageName = uploadImage($_FILES['image']);
        if (!$imageName) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Erreur lors du téléchargement de l\'image']);
            return;
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO games (title, description, price, discount_price, category_id, release_date, developer, publisher, image, banner_image, video_url, is_featured) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $title, $description, $price, $discountPrice, 
        $categoryId, $releaseDate, $developer, $publisher, $imageName, $bannerImage, $videoUrl, $isFeatured
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Jeu créé avec succès',
        'id' => $pdo->lastInsertId()
    ]);
}

/**
 * Met à jour un jeu (Admin)
 */
function updateGame() {
    global $pdo;
    
    // Récupération des données JSON ou PUT
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        parse_str(file_get_contents('php://input'), $data);
    }
    
    $id = intval($data['id'] ?? $_GET['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID invalide']);
        return;
    }
    
    $fields = [];
    $params = [];
    
    $allowedFields = ['title', 'description', 'price', 'discount_price', 'category_id', 
                      'release_date', 'developer', 'publisher', 'image', 'banner_image', 'video_url', 'is_featured'];
    
    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $fields[] = "$field = ?";
            $value = $data[$field];
            // Convertir les valeurs vides en NULL pour les champs numériques
            if ($value === '' && in_array($field, ['discount_price', 'category_id'])) {
                $value = null;
            }
            $params[] = $value;
        }
    }
    
    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Aucun champ à mettre à jour']);
        return;
    }
    
    $params[] = $id;
    
    $sql = "UPDATE games SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode(['success' => true, 'message' => 'Jeu mis à jour avec succès']);
}

/**
 * Supprime un jeu (Admin)
 */
function deleteGame() {
    global $pdo;
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID invalide']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM games WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Jeu non trouvé']);
        return;
    }
    
    echo json_encode(['success' => true, 'message' => 'Jeu supprimé avec succès']);
}

/**
 * Upload d'image
 */
function uploadImage($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5 MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = uniqid('game_') . '.' . $extension;
    $destination = __DIR__ . '/../assets/images/games/' . $newName;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $newName;
    }
    
    return false;
}
?>
