<?php
/**
 * API Authentification - Games Store
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/functions.php';

// ================= CONFIGURATION SESSION =================
class SessionManager {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            session_name('GAMES_STORE_SESSION');
            session_start();
        }
    }
    
    public static function destroy() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }
}

SessionManager::start();

// ================= GESTION DES DONNÉES =================
class RequestHandler {
    public static function getInput() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            return json_decode(file_get_contents('php://input'), true) ?? [];
        }
        
        return array_merge($_GET, $_POST);
    }
    
    public static function getAction() {
        $input = self::getInput();
        return $input['action'] ?? 'check';
    }
}

// ================= GESTION DES COOKIES =================
class CookieManager {
    public static function setRememberToken($token) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        
        return setcookie('remember_token', $token, [
            'expires' => time() + (86400 * 30),
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    public static function deleteRememberToken() {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    public static function hasConsent() {
        return ($_COOKIE['cookie_consent'] ?? null) === 'accepted';
    }
}

// ================= VALIDATION =================
class Validator {
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validateUsername($username) {
        return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
    }
    
    public static function validatePassword($password) {
        return strlen($password) >= 8;
    }
    
    public static function sanitize($input) {
        return trim(strip_tags($input));
    }
}

// ================= GESTION AUTH =================
class AuthHandler {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function login($input) {
        // Nettoyage et validation
        $email = Validator::sanitize($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $remember = isset($input['remember']);
        
        if (empty($email) || empty($password)) {
            $this->jsonError('Email et mot de passe requis', 400);
            return;
        }
        
        // Recherche utilisateur
        $stmt = $this->pdo->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            $this->jsonError('Identifiants incorrects', 401);
            return;
        }
        
        // Création session
        $this->createSession($user);
        
        // Cookie "Se souvenir de moi" (avec consentement)
        if ($remember && CookieManager::hasConsent()) {
            $token = $this->generateRememberToken($user['id']);
            CookieManager::setRememberToken($token);
        }
        
        // Réponse
        $this->jsonSuccess([
            'message' => 'Connexion réussie',
            'redirect' => $user['role'] === 'admin' ? '/admin/' : '/index.php',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ]);
    }
    
    public function register($input) {
        // Nettoyage
        $username = Validator::sanitize($input['username'] ?? '');
        $email = Validator::sanitize($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $confirmPassword = $input['confirm_password'] ?? $password;
        
        // Validation
        $errors = [];
        
        if (!Validator::validateUsername($username)) {
            $errors[] = 'Nom d\'utilisateur invalide (3-50 caractères, lettres/chiffres/underscores)';
        }
        
        if (!Validator::validateEmail($email)) {
            $errors[] = 'Email invalide';
        }
        
        if (!Validator::validatePassword($password)) {
            $errors[] = 'Le mot de passe doit faire au moins 8 caractères';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Les mots de passe ne correspondent pas';
        }
        
        if (!empty($errors)) {
            $this->jsonError(implode(', ', $errors), 400);
            return;
        }
        
        // Vérification unicité
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        if ($stmt->fetch()) {
            $this->jsonError('Cet email ou nom d\'utilisateur existe déjà', 400);
            return;
        }
        
        // Création utilisateur
        $userId = $this->createUser($username, $email, $password);
        
        // Connexion automatique
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = 'user';
        $_SESSION['username'] = $username;
        
        $this->jsonSuccess([
            'message' => 'Inscription réussie',
            'user' => [
                'id' => $userId,
                'username' => $username,
                'role' => 'user'
            ]
        ]);
    }
    
    public function logout() {
        // Destruction session
        SessionManager::destroy();
        
        // Suppression cookie
        CookieManager::deleteRememberToken();
        
        // Redirection pour requêtes non-AJAX
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Location: /index.php');
            exit();
        }
        
        $this->jsonSuccess(['message' => 'Déconnexion réussie']);
    }
    
    public function checkAuth() {
        if (isLoggedIn()) {
            $user = getCurrentUser();
            $this->jsonSuccess([
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
    
    // ================= MÉTHODES PRIVÉES =================
    
    private function createSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['last_activity'] = time();
    }
    
    private function generateRememberToken($userId) {
        $token = bin2hex(random_bytes(32));
        
        // Stockage en BDD (à implémenter selon votre structure)
        $stmt = $this->pdo->prepare(
            "INSERT INTO user_tokens (user_id, token, expires_at) 
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))"
        );
        $stmt->execute([$userId, hash('sha256', $token)]);
        
        return $token;
    }
    
    private function createUser($username, $email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (username, email, password, created_at) 
             VALUES (?, ?, ?, NOW())"
        );
        $stmt->execute([$username, $email, $hashedPassword]);
        
        return $this->pdo->lastInsertId();
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
    global $pdo;
    
    // Récupération de l'action
    $action = RequestHandler::getAction();
    $handler = new AuthHandler($pdo);
    
    // Router
    switch ($action) {
        case 'login':
            $handler->login(RequestHandler::getInput());
            break;
            
        case 'register':
            $handler->register(RequestHandler::getInput());
            break;
            
        case 'logout':
            $handler->logout();
            break;
            
        case 'check':
            $handler->checkAuth();
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Action non valide'
            ]);
    }
    
} catch (Exception $e) {
    // Log l'erreur en production
    error_log('Auth error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur'
    ]);
}
?>