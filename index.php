<?php
require_once 'config.php';
session_start();

// Verificar login e tempo de sessão
if (!isset($_SESSION['admin_logado']) || !isset($_SESSION['ultimo_acesso'])) {
    header('Location: login.php');
    exit();
}

// Verificar se a sessão expirou (30 minutos)
if (time() - $_SESSION['ultimo_acesso'] > 1800) {
    session_destroy();
    header('Location: login.php?expired=1');
    exit();
}

// Atualizar tempo de último acesso
$_SESSION['ultimo_acesso'] = time();

// Buscar produtos com paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

try {
    // Contar total de produtos
    $stmt = $conn->query("SELECT COUNT(*) FROM produtos");
    $total_produtos = $stmt->fetchColumn();
    $total_paginas = ceil($total_produtos / $por_pagina);

    // Buscar produtos da página atual
    $stmt = $conn->prepare("
        SELECT p.*, c.nome as categoria_nome 
        FROM produtos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        ORDER BY p.data_criacao DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$por_pagina, $offset]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Erro ao buscar produtos: " . $e->getMessage());
    $erro = "Erro ao carregar produtos.";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Marketing na Veia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        .table img {
            max-width: 50px;
            height: auto;
        }
        .actions-column {
            width: 150px;
        }
        .dashboard-stats {
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="../images/Marketing na veia 7 copiar.png" alt="Logo">
                Marketing na Veia - Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Produtos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categorias.php">Categorias</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="vendas.php">Vendas</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <!-- Dashboard Stats -->
        <div class="row dashboard-stats">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <h3><?php echo $total_produtos; ?></h3>
                    <p class="text-muted mb-0">Total de Produtos</p>
                </div>
            </div>
            <!-- Adicione mais cards de estatísticas conforme necessário -->
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Gerenciar Produtos</h5>
                <a href="adicionar_produto.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Novo Produto
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imagem</th>
                                <th>Nome</th>
                                <th>Categoria</th>
                                <th>Preço</th>
                                <th>Status</th>
                                <th class="actions-column">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtos as $produto): ?>
                            <tr>
                                <td><?php echo $produto['id']; ?></td>
                                <td>
                                    <?php if ($produto['imagem_url']): ?>
                                        <img src="<?php echo htmlspecialchars($produto['imagem_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($produto['nome']); ?>">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                                <td><?php echo htmlspecialchars($produto['categoria_nome']); ?></td>
                                <td>R$ <?php echo number_format($produto['preco_normal'], 2, ',', '.'); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $produto['status'] == 'ativo' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo ucfirst($produto['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="editar_produto.php?id=<?php echo $produto['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="visualizar_produto.php?id=<?php echo $produto['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="confirmarExclusao(<?php echo $produto['id']; ?>)" 
                                                title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <?php if ($total_paginas > 1): ?>
                <nav aria-label="Navegação de páginas">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarExclusao(id) {
            if (confirm('Tem certeza que deseja excluir este produto?')) {
                window.location.href = 'excluir_produto.php?id=' + id;
            }
        }
    </script>
</body>
</html> 