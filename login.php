<?php
session_start();
require_once 'config.php';

// Proteção contra CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Token de segurança inválido');
    }

    $usuario = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_STRING);
    $senha = $_POST['senha'];

    // Proteção contra brute force
    if (isset($_SESSION['tentativas_login']) && $_SESSION['tentativas_login'] >= 3) {
        $tempo_espera = 300; // 5 minutos
        if (time() - $_SESSION['ultimo_login'] < $tempo_espera) {
            $erro = "Muitas tentativas. Tente novamente em " . ceil(($tempo_espera - (time() - $_SESSION['ultimo_login'])) / 60) . " minutos.";
        } else {
            $_SESSION['tentativas_login'] = 0;
        }
    }

    if (!isset($erro)) {
        try {
            $stmt = $conn->prepare("SELECT id, senha_hash FROM usuarios WHERE usuario = ? AND status = 'ativo' LIMIT 1");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($senha, $user['senha_hash'])) {
                // Login bem sucedido
                $_SESSION['admin_logado'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['ultimo_acesso'] = time();
                
                // Registrar log de acesso
                $ip = $_SERVER['REMOTE_ADDR'];
                $stmt = $conn->prepare("INSERT INTO logs_acesso (usuario_id, ip, data_acesso) VALUES (?, ?, NOW())");
                $stmt->execute([$user['id'], $ip]);

                // Limpar sessão de tentativas
                unset($_SESSION['tentativas_login']);
                unset($_SESSION['ultimo_login']);

                header('Location: index.php');
                exit();
            } else {
                // Incrementar contador de tentativas
                $_SESSION['tentativas_login'] = isset($_SESSION['tentativas_login']) ? $_SESSION['tentativas_login'] + 1 : 1;
                $_SESSION['ultimo_login'] = time();
                
                $erro = "Usuário ou senha incorretos";
            }
        } catch(PDOException $e) {
            $erro = "Erro no sistema. Tente novamente mais tarde.";
            error_log("Erro no login: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
        }
        .card {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border: none;
        }
        .card-header {
            background: #2ecc71;
            color: white;
            text-align: center;
            padding: 20px;
        }
        .btn-primary {
            background: #2ecc71;
            border-color: #2ecc71;
        }
        .btn-primary:hover {
            background: #27ae60;
            border-color: #27ae60;
        }
    </style>
</head>
<body class="bg-light">
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Marketing na Veia</h3>
                <p class="mb-0">Painel Administrativo</p>
            </div>
            <div class="card-body p-4">
                <?php if (isset($erro)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuário</label>
                        <input type="text" class="form-control" id="usuario" name="usuario" required 
                               autofocus pattern="[a-zA-Z0-9]+" title="Apenas letras e números">
                    </div>
                    
                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="senha" name="senha" required 
                               minlength="8">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 