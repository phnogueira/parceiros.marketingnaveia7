<?php
// Prevenir acesso direto ao arquivo
if (!defined('ACESSO_PERMITIDO')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

// Configurações de ambiente
define('AMBIENTE', 'producao'); // producao ou desenvolvimento

// Configurações de erro
if (AMBIENTE === 'desenvolvimento') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configurações do site
define('SITE_URL', 'https://parceiros.marketingnaveia7.com');
define('SITE_TITULO', 'Marketing na Veia - Parceiros');
define('ADMIN_EMAIL', 'admin@marketingnaveia7.com');

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'u947394968_marketing');
define('DB_USER', 'u947394968_parceiros');
define('DB_PASS', 'sua_senha_segura'); // Alterar para senha segura

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
session_name('MARKETINGSESSID');

// Configurações de timezone
date_default_timezone_set('America/Sao_Paulo');
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');

// Configurações de upload
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Conexão com o banco de dados usando PDO
try {
    $conn = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
} catch(PDOException $e) {
    if (AMBIENTE === 'desenvolvimento') {
        die("Erro na conexão: " . $e->getMessage());
    } else {
        error_log("Erro na conexão: " . $e->getMessage());
        die("Erro interno do servidor. Por favor, tente novamente mais tarde.");
    }
}

// Funções de utilidade
function limpar_string($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function gerar_slug($str) {
    $str = mb_strtolower($str);
    $str = preg_replace('/(à|á|ã|â|ä)/', 'a', $str);
    $str = preg_replace('/(è|é|ê|ë)/', 'e', $str);
    $str = preg_replace('/(ì|í|î|ï)/', 'i', $str);
    $str = preg_replace('/(ò|ó|õ|ô|ö)/', 'o', $str);
    $str = preg_replace('/(ù|ú|û|ü)/', 'u', $str);
    $str = preg_replace('/[^a-z0-9-]/', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return trim($str, '-');
}

function formatar_preco($valor) {
    return number_format($valor, 2, ',', '.');
}

function validar_upload($arquivo) {
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    
    if ($arquivo['size'] > MAX_UPLOAD_SIZE) {
        return "Arquivo muito grande. Tamanho máximo: " . (MAX_UPLOAD_SIZE / 1024 / 1024) . "MB";
    }
    
    if (!in_array($extensao, ALLOWED_EXTENSIONS)) {
        return "Extensão não permitida. Use: " . implode(', ', ALLOWED_EXTENSIONS);
    }
    
    return true;
}

// Função para log de atividades
function registrar_log($acao, $descricao) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            INSERT INTO logs_acesso (usuario_id, ip, user_agent, pagina, descricao) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'],
            $_SERVER['REQUEST_URI'],
            $descricao
        ]);
    } catch(PDOException $e) {
        error_log("Erro ao registrar log: " . $e->getMessage());
    }
}

// Verificar se usuário está logado
function verificar_login() {
    if (!isset($_SESSION['admin_logado']) || !isset($_SESSION['ultimo_acesso'])) {
        header('Location: login.php');
        exit();
    }

    if (time() - $_SESSION['ultimo_acesso'] > 1800) { // 30 minutos
        session_destroy();
        header('Location: login.php?expired=1');
        exit();
    }

    $_SESSION['ultimo_acesso'] = time();
}
?> 