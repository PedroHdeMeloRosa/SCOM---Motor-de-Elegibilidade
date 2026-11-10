<?php
// api/controllers/painel_aluno.php
session_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");

// Conexão PDO
$host = '127.0.0.1';
$db   = 'motor_elegibilidade';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Se não houver sessão ativa (em testes diretos), pega o estudante padrão do banco
    $id_usuario = $_SESSION['usuario_id'] ?? null;
    if (!$id_usuario) {
        $stmtUser = $pdo->query("SELECT id, nome FROM cadastros WHERE tipo_perfil = 'ESTUDANTE' LIMIT 1");
        $userFallback = $stmtUser->fetch();
        $id_usuario = $userFallback['id'];
        $nome_usuario = $userFallback['nome'];
    } else {
        $nome_usuario = $_SESSION['usuario_nome'] ?? 'Estudante';
    }

    // =====================================================================
    // ROTA POST: ATUALIZAR FICHA SOCIOECONÔMICA E MOBILIDADE
    // =====================================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $dados = json_decode(file_get_contents("php://input"), true);

        // Coordenadas aproximadas das principais cidades de origem para o mapa
        $mapaCidades = [
            'Sorocaba'       => ['lat' => -23.5015, 'lng' => -47.4526, 'uf' => 'SP'],
            'São Paulo'      => ['lat' => -23.5505, 'lng' => -46.6333, 'uf' => 'SP'],
            'Campinas'       => ['lat' => -22.9099, 'lng' => -47.0626, 'uf' => 'SP'],
            'Bauru'          => ['lat' => -22.3145, 'lng' => -49.0587, 'uf' => 'SP'],
            'Curitiba'       => ['lat' => -25.4284, 'lng' => -49.2733, 'uf' => 'PR'],
            'Belo Horizonte' => ['lat' => -19.9167, 'lng' => -43.9345, 'uf' => 'MG'],
            'Rio de Janeiro' => ['lat' => -22.9068, 'lng' => -43.1729, 'uf' => 'RJ']
        ];

        $cidadeEscolhida = $dados['cidade_origem'] ?? 'Sorocaba';
        $coords = $mapaCidades[$cidadeEscolhida] ?? $mapaCidades['Sorocaba'];

        $sql = "INSERT INTO perfis_estudantes 
                (id_usuario, cep, cidade_origem, estado_origem, lat_origem, lng_origem, renda_per_capita, tipo_escola, etnia, pcd, nota_media_enem, nivel_mobilidade, orcamento_moradia, interesse_republica, precisa_auxilio_moradia)
                VALUES (:id_u, :cep, :cid, :uf, :lat, :lng, :renda, :escola, :etnia, :pcd, :nota, :mob, :orc, :rep, :aux)
                ON DUPLICATE KEY UPDATE
                cep = VALUES(cep), cidade_origem = VALUES(cidade_origem), estado_origem = VALUES(estado_origem),
                lat_origem = VALUES(lat_origem), lng_origem = VALUES(lng_origem), renda_per_capita = VALUES(renda_per_capita),
                tipo_escola = VALUES(tipo_escola), etnia = VALUES(etnia), pcd = VALUES(pcd), nota_media_enem = VALUES(nota_media_enem),
                nivel_mobilidade = VALUES(nivel_mobilidade), orcamento_moradia = VALUES(orcamento_moradia),
                interesse_republica = VALUES(interesse_republica), precisa_auxilio_moradia = VALUES(precisa_auxilio_moradia)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_u'   => $id_usuario,
            ':cep'    => htmlspecialchars($dados['cep'] ?? '18013-004'),
            ':cid'    => $cidadeEscolhida,
            ':uf'     => $coords['uf'],
            ':lat'    => $coords['lat'],
            ':lng'    => $coords['lng'],
            ':renda'  => floatval($dados['renda_per_capita'] ?? 1500),
            ':escola' => $dados['tipo_escola'] ?? 'PUBLICA',
            ':etnia'  => $dados['etnia'] ?? 'NAO_DECLARADO',
            ':pcd'    => !empty($dados['pcd']) ? 1 : 0,
            ':nota'   => intval($dados['nota_media_enem'] ?? 650),
            ':mob'    => $dados['nivel_mobilidade'] ?? 'ESTADUAL',
            ':orc'    => floatval($dados['orcamento_moradia'] ?? 800),
            ':rep'    => !empty($dados['interesse_republica']) ? 1 : 0,
            ':aux'    => !empty($dados['precisa_auxilio_moradia']) ? 1 : 0
        ]);

        echo json_encode(["sucesso" => true, "mensagem" => "Ficha socioeconômica atualizada com sucesso!"]);
        exit;
    }

    // =====================================================================
    // ROTA GET: CARREGAR PERFIL E PROCESSAR MOTOR DE REGRAS (RULE ENGINE)
    // =====================================================================
    $stmtPerfil = $pdo->prepare("SELECT * FROM perfis_estudantes WHERE id_usuario = ?");
    $stmtPerfil->execute([$id_usuario]);
    $perfil = $stmtPerfil->fetch();

    if (!$perfil) {
        $perfil = [
            'cep' => '18013-004', 'cidade_origem' => 'Sorocaba', 'estado_origem' => 'SP',
            'lat_origem' => -23.5015, 'lng_origem' => -47.4526, 'renda_per_capita' => 1500.00,
            'tipo_escola' => 'PUBLICA', 'etnia' => 'NAO_DECLARADO', 'pcd' => 0, 'nota_media_enem' => 660,
            'nivel_mobilidade' => 'ESTADUAL', 'orcamento_moradia' => 800.00, 'interesse_republica' => 1, 'precisa_auxilio_moradia' => 0
        ];
    }

    // Busca todas as regras ativas com dados dos editais
    $sqlRegras = "SELECT r.*, e.instituicao, e.nome_processo, e.cidade, e.estado, e.regiao, 
                         e.latitude, e.longitude, e.custo_medio_republica, e.tem_moradia_estudantil, e.link_documento
                  FROM regras_elegibilidade r
                  INNER JOIN editais e ON r.id_edital = e.id
                  WHERE r.status_regra = 'ATIVA'
                  ORDER BY r.prazo_fim ASC";
    $regras = $pdo->query($sqlRegras)->fetchAll();

    $oportunidadesProcessadas = [];
    $contagem = ['ALTA' => 0, 'MEDIA' => 0, 'BAIXA' => 0];
    $universidadesUnicas = [];

    foreach ($regras as $regra) {
        $universidadesUnicas[$regra['instituicao']] = true;
        $pontosFalhos = [];
        $alertasAtencao = [];

        // 1. Critério de Escola Pública
        if ($regra['exige_escola_publica'] && $perfil['tipo_escola'] !== 'PUBLICA') {
            $pontosFalhos[] = "Exige ensino médio integral em escola pública.";
        }

        // 2. Critério de Renda
        if ($regra['renda_maxima_per_capita'] !== null) {
            if ($perfil['renda_per_capita'] > $regra['renda_maxima_per_capita']) {
                $pontosFalhos[] = "Renda acima do limite (Máx: R$ " . number_format($regra['renda_maxima_per_capita'], 2, ',', '.') . ").";
            } elseif ($perfil['renda_per_capita'] >= ($regra['renda_maxima_per_capita'] * 0.9)) {
                $alertasAtencao[] = "Renda muito próxima ao teto limite.";
            }
        }

        // 3. Critério Étnico (PPI)
        if ($regra['exige_etnia'] && $perfil['etnia'] !== 'PPI') {
            $pontosFalhos[] = "Vaga exclusiva para autodeclarados Pretos, Pardos ou Indígenas (PPI).";
        }

        // 4. Critério Geográfico / Mobilidade
        $escopoVaga = 'LOCAL';
        if ($regra['regiao'] === 'INTERNACIONAL') {
            $escopoVaga = 'INTERNACIONAL';
        } elseif ($regra['estado'] !== $perfil['estado_origem']) {
            $escopoVaga = 'INTERESTADUAL';
        } elseif ($regra['cidade'] !== $perfil['cidade_origem']) {
            $escopoVaga = 'ESTADUAL';
        }

        $niveisOrdem = ['LOCAL' => 1, 'ESTADUAL' => 2, 'INTERESTADUAL' => 3, 'INTERNACIONAL' => 4];
        if ($niveisOrdem[$escopoVaga] > $niveisOrdem[$perfil['nivel_mobilidade']]) {
            $alertasAtencao[] = "Fora do seu raio de mudança preferido ($escopoVaga).";
        }

        // 5. Critério Financeiro de Moradia / República e Nota
        if ($escopoVaga !== 'LOCAL' && $regra['custo_medio_republica'] > $perfil['orcamento_moradia']) {
            if (!$regra['tem_moradia_estudantil']) {
                $pontosFalhos[] = "Custo de vida local excede seu orçamento e não há moradia gratuita.";
            } else {
                $alertasAtencao[] = "Custo de república (R$ {$regra['custo_medio_republica']}) acima do orçamento (exige Moradia Estudantil).";
            }
        }

        if ($perfil['nota_media_enem'] < $regra['nota_corte_estimada']) {
            $alertasAtencao[] = "Nota média abaixo da nota de corte estimada ({$regra['nota_corte_estimada']} pts).";
        }

        // Classificação Final (Verde / Amarela / Vermelha)
        if (count($pontosFalhos) > 0) {
            $probabilidade = 'BAIXA';
            $motivo = implode(" ", $pontosFalhos);
        } elseif (count($alertasAtencao) > 0) {
            $probabilidade = 'MEDIA';
            $motivo = implode(" ", $alertasAtencao);
        } else {
            $probabilidade = 'ALTA';
            $motivo = "Perfil 100% compatível com os requisitos acadêmicos, financeiros e de moradia!";
        }

        $contagem[$probabilidade]++;

        $oportunidadesProcessadas[] = [
            'id_regra'              => $regra['id'],
            'id_edital'             => $regra['id_edital'],
            'instituicao'           => $regra['instituicao'],
            'nome_processo'         => $regra['nome_processo'],
            'cidade'                => $regra['cidade'],
            'estado'                => $regra['estado'],
            'escopo_mobilidade'     => $escopoVaga,
            'latitude'              => floatval($regra['latitude']),
            'longitude'             => floatval($regra['longitude']),
            'custo_medio_republica' => floatval($regra['custo_medio_republica']),
            'tem_moradia'           => (bool)$regra['tem_moradia_estudantil'],
            'tipo_beneficio'        => $regra['tipo_beneficio'],
            'prazo_fim'             => $regra['prazo_fim'],
            'prazo_formatado'       => date('d/m/Y', strtotime($regra['prazo_fim'])),
            'documentos_exigidos'   => $regra['documentos_exigidos'],
            'link_documento'        => $regra['link_documento'],
            'probabilidade'         => $probabilidade,
            'diagnostico'           => $motivo
        ];
    }

    echo json_encode([
        'sucesso' => true,
        'usuario' => $nome_usuario,
        'perfil'  => $perfil,
        'kpis'    => [
            'total_universidades' => count($universidadesUnicas),
            'total_vagas'         => count($oportunidadesProcessadas),
            'verdes'              => $contagem['ALTA'],
            'amarelas'            => $contagem['MEDIA'],
            'vermelhas'           => $contagem['BAIXA']
        ],
        'oportunidades' => $oportunidadesProcessadas
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["sucesso" => false, "mensagem" => "Erro no servidor: " . $e->getMessage()]);
}
?>