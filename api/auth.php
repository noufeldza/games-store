<?php
/**
 * API Authentification - Games Store
 * Gère la connexion, déconnexion et inscription
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

// Lire les données JSON
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin($input);
        break;
    case 'register':
        handleRegister($input);
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        checkAuth();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action non valide']);
}

/**
 * Connexion utilisateur
 */
function handleLogin($input) {
    global $pdo;
    
    // Récupération des données
    $email = $input['email'] ?? $_POST['email'] ?? '';
    $password = $input['password'] ?? $_POST['password'] ?? '';
    $remember = $input['remember'] ?? isset($_POST['remember']);
    
    // Validation
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email et mot de passe requis']);
        return;
    }
    
    // Recherche de l'utilisateur
    $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Vérification du mot de passe
    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect']);
        return;
    }
    
    // Création de la session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['username'] = $user['username'];
    
    // Cookie "Se souvenir de moi"
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
        // Note: Dans un vrai projet, stocker ce token en BDD
    }
    
    // Redirection selon le rôle
    $redirect = $user['role'] === 'admin' ? '/admin/' : '/index.php';
    
    echo json_encode([
        'success' => true,
        'message' => 'Connexion réussie',
        'redirect' => $redirect,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ]
    ]);
}

/**
 * Inscription utilisateur
 */
function handleRegister($input) {
    global $pdo;
    
    // Récupération des données
    $username = trim($input['username'] ?? $_POST['username'] ?? '');
    $email = trim($input['email'] ?? $_POST['email'] ?? '');
    $password = $input['password'] ?? $_POST['password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? $_POST['confirm_password'] ?? $password;
    
    // Validation
    $errors = [];
    
    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'Le nom d\'utilisateur doit contenir entre 3 et 50 caractères';
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres et underscores';
    }
    
    if (!isValidEmail($email)) {
        $errors[] = 'Adresse email invalide';
    }
    
    if (strlen($password) < 8) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caractères';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Les mots de passe ne correspondent pas';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        return;
    }
    
    // Vérification de l'unicité
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cet email ou nom d\'utilisateur est déjà utilisé']);
        return;
    }
    
    // Hashage du mot de passe
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertion en BDD
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hashedPassword]);
    
    $userId = $pdo->lastInsertId();
    
    // Connexion automatique
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = 'user';
    $_SESSION['username'] = $username;
    
    echo json_encode([
        'success' => true,
        'message' => 'Inscription réussie',
        'user' => [
            'id' => $userId,
            'username' => $username,
            'role' => 'user'
        ]
    ]);
}

/**
 * Déconnexion
 */
function handleLogout() {
    // Destruction de la session
    $_SESSION = [];
    session_destroy();
    
    // Suppression du cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Redirection si appel direct (non AJAX)
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Location: /index.php');
        exit();
    }
    
    echo json_encode(['success' => true, 'message' => 'Déconnexion réussie']);
}

/**
 * Vérifie l'état de l'authentification
 */
function checkAuth() {
    if (isLoggedIn()) {
        $user = getCurrentUser();
        echo json_encode([
            'authenticated' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ]);
    } else {
        echo json_encode(['authenticated' => false]);
    }
}
?>
