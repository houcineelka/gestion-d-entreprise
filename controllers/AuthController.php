<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../models/User.php';

/**
 * AuthController - Gestion de l'authentification
 */
class AuthController {

    /**
     * Connexion d'un utilisateur
     * @return array ['success' => bool, 'message' => string, 'user' => array|null]
     */
    public function login($email, $password) {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email et mot de passe requis'];
        }

        $userModel = new User();
        $user = $userModel->authenticate($email, $password);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_role'] = $user['categorie_nom'];

            return [
                'success' => true,
                'message' => 'Connexion réussie',
                'user' => $user
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Email ou mot de passe incorrect'
            ];
        }
    }

    /**
     * Déconnexion
     */
    public function logout() {
        session_destroy();
        header('Location: ../pages/connexion.php');
        exit();
    }

    /**
     * Vérifier si l'utilisateur est connecté
     */
    public function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../pages/connexion.php');
            exit();
        }
        return true;
    }
}

// Gestion des routes directes
if (isset($_GET['action'])) {
    $controller = new AuthController();

    switch ($_GET['action']) {
        case 'login':
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $result = $controller->login($email, $password);
            
            if ($result['success']) {
                header('Location: ../pages/dashboard.php');
            } else {
                header('Location: ../pages/connexion.php?error=' . urlencode($result['message']));
            }
            exit();
            break;
            
        case 'logout':
            $controller->logout();
            break;
            
        default:
            header('Location: ../pages/connexion.php');
            break;
    }
}