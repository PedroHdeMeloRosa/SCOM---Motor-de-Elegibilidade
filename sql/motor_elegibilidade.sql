-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 10/11/2026 às 15:40
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `motor_elegibilidade`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `cadastros`
--

DROP TABLE IF EXISTS `cadastros`;
CREATE TABLE `cadastros` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `tipo_perfil` enum('ESTUDANTE','COORDENADOR','DIRETOR') NOT NULL DEFAULT 'ESTUDANTE',
  `status_conta` enum('ATIVA','BANIDA') DEFAULT 'ATIVA',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `cadastros`
--

INSERT INTO `cadastros` (`id`, `nome`, `email`, `senha_hash`, `tipo_perfil`, `status_conta`, `criado_em`) VALUES
(7, 'Diretoria SCOM', 'diretor@unilegi.com', '$2y$10$OzXOZXoakOHCk8pFal3OrOn8eGGWMrKlhyTa.DjKA7BMaVmreYZwG', 'DIRETOR', 'ATIVA', '2026-11-10 13:36:52'),
(8, 'Coordenação Acadêmica', 'coordenador@unilegi.com', '$2y$10$OzXOZXoakOHCk8pFal3OrOn8eGGWMrKlhyTa.DjKA7BMaVmreYZwG', 'COORDENADOR', 'ATIVA', '2026-11-10 13:36:52'),
(9, 'Pedro (Aluno Teste)', 'estudante@unilegi.com', '$2y$10$OzXOZXoakOHCk8pFal3OrOn8eGGWMrKlhyTa.DjKA7BMaVmreYZwG', 'ESTUDANTE', 'ATIVA', '2026-11-10 13:36:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `comentarios`
--

DROP TABLE IF EXISTS `comentarios`;
CREATE TABLE `comentarios` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_edital` int(11) NOT NULL,
  `texto_comentario` text NOT NULL,
  `data_comentario` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_comentario` enum('VISIVEL','OCULTO') DEFAULT 'VISIVEL'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `editais`
--

DROP TABLE IF EXISTS `editais`;
CREATE TABLE `editais` (
  `id` int(11) NOT NULL,
  `instituicao` varchar(100) NOT NULL,
  `nome_processo` varchar(150) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `estado` char(2) NOT NULL,
  `regiao` enum('SUDESTE','SUL','CENTRO_OESTE','NORDESTE','NORTE','INTERNACIONAL') DEFAULT 'SUDESTE',
  `latitude` decimal(10,6) NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `custo_medio_republica` decimal(10,2) DEFAULT 600.00,
  `tem_moradia_estudantil` tinyint(1) DEFAULT 1,
  `ano_referencia` year(4) NOT NULL,
  `link_documento` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `editais`
--

INSERT INTO `editais` (`id`, `instituicao`, `nome_processo`, `cidade`, `estado`, `regiao`, `latitude`, `longitude`, `custo_medio_republica`, `tem_moradia_estudantil`, `ano_referencia`, `link_documento`, `criado_em`) VALUES
(1, 'UNESP', 'Vestibular UNESP - Campus Sorocaba', 'Sorocaba', 'SP', 'SUDESTE', -23.483600, -47.426100, 650.00, 0, '2027', 'edital_detalhe.html?id=1', '2026-11-10 14:20:39'),
(2, 'UNESP', 'Provão Paulista - Campus Bauru', 'Bauru', 'SP', 'SUDESTE', -22.349600, -49.031500, 550.00, 1, '2027', 'edital_detalhe.html?id=2', '2026-11-10 14:20:39'),
(3, 'USP', 'FUVEST - Campus Butantã', 'São Paulo', 'SP', 'SUDESTE', -23.561400, -46.730800, 1200.00, 1, '2027', 'edital_detalhe.html?id=3', '2026-11-10 14:20:39'),
(4, 'UNICAMP', 'Vestibular UNICAMP - Barão Geraldo', 'Campinas', 'SP', 'SUDESTE', -22.818400, -47.064700, 850.00, 1, '2027', 'edital_detalhe.html?id=4', '2026-11-10 14:20:39'),
(5, 'UFSCar', 'SiSU UFSCar - Campus São Carlos', 'São Carlos', 'SP', 'SUDESTE', -21.983900, -47.881900, 600.00, 1, '2027', 'edital_detalhe.html?id=5', '2026-11-10 14:20:39'),
(6, 'UFPR', 'Vestibular UFPR - Politécnico', 'Curitiba', 'PR', 'SUL', -25.451100, -49.233600, 750.00, 1, '2027', 'edital_detalhe.html?id=6', '2026-11-10 14:20:39'),
(7, 'UFMG', 'SiSU UFMG - Pampulha', 'Belo Horizonte', 'MG', 'SUDESTE', -19.870500, -43.967700, 700.00, 1, '2027', 'edital_detalhe.html?id=7', '2026-11-10 14:20:39'),
(8, 'Univ. do Porto', 'Ingresso Internacional ENEM', 'Porto (Portugal)', 'PT', 'INTERNACIONAL', 41.149600, -8.610900, 2500.00, 0, '2027', 'edital_detalhe.html?id=8', '2026-11-10 14:20:39');

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_retificacoes`
--

DROP TABLE IF EXISTS `historico_retificacoes`;
CREATE TABLE `historico_retificacoes` (
  `id` int(11) NOT NULL,
  `id_regra_original` int(11) NOT NULL,
  `id_regra_nova` int(11) NOT NULL,
  `motivo_retificacao` varchar(255) NOT NULL,
  `data_retificacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `perfis_estudantes`
--

DROP TABLE IF EXISTS `perfis_estudantes`;
CREATE TABLE `perfis_estudantes` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `cep` varchar(15) DEFAULT '18013-004',
  `cidade_origem` varchar(100) DEFAULT 'Sorocaba',
  `estado_origem` char(2) DEFAULT 'SP',
  `lat_origem` decimal(10,6) DEFAULT -23.501500,
  `lng_origem` decimal(10,6) DEFAULT -47.452600,
  `renda_per_capita` decimal(10,2) NOT NULL DEFAULT 1500.00,
  `tipo_escola` enum('PUBLICA','PRIVADA_BOLSA','PRIVADA') NOT NULL DEFAULT 'PUBLICA',
  `etnia` enum('NAO_DECLARADO','PPI','BRANCO','AMARELO') DEFAULT 'NAO_DECLARADO',
  `pcd` tinyint(1) DEFAULT 0,
  `nota_media_enem` int(11) DEFAULT 650,
  `nivel_mobilidade` enum('LOCAL','ESTADUAL','INTERESTADUAL','INTERNACIONAL') DEFAULT 'ESTADUAL',
  `orcamento_moradia` decimal(10,2) DEFAULT 800.00,
  `interesse_republica` tinyint(1) DEFAULT 1,
  `precisa_auxilio_moradia` tinyint(1) DEFAULT 0,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `perfis_estudantes`
--

INSERT INTO `perfis_estudantes` (`id`, `id_usuario`, `cep`, `cidade_origem`, `estado_origem`, `lat_origem`, `lng_origem`, `renda_per_capita`, `tipo_escola`, `etnia`, `pcd`, `nota_media_enem`, `nivel_mobilidade`, `orcamento_moradia`, `interesse_republica`, `precisa_auxilio_moradia`, `atualizado_em`) VALUES
(1, 9, '18013-004', 'Sorocaba', 'SP', -23.501500, -47.452600, 1500.00, 'PUBLICA', 'NAO_DECLARADO', 0, 660, 'ESTADUAL', 800.00, 1, 0, '2026-11-10 14:20:39');

-- --------------------------------------------------------

--
-- Estrutura para tabela `regras_elegibilidade`
--

DROP TABLE IF EXISTS `regras_elegibilidade`;
CREATE TABLE `regras_elegibilidade` (
  `id` int(11) NOT NULL,
  `id_edital` int(11) NOT NULL,
  `tipo_beneficio` enum('ISENCAO_TAXA','COTA_RENDA','COTA_ESCOLA_PUBLICA','COTA_RACIAL','AMPLA_CONCORRENCIA') NOT NULL,
  `renda_maxima_per_capita` decimal(10,2) DEFAULT NULL,
  `exige_escola_publica` tinyint(1) DEFAULT 0,
  `exige_etnia` tinyint(1) DEFAULT 0,
  `nota_corte_estimada` int(11) DEFAULT 500,
  `prazo_inicio` date NOT NULL,
  `prazo_fim` date NOT NULL,
  `documentos_exigidos` text DEFAULT NULL,
  `status_regra` enum('ATIVA','INATIVA') DEFAULT 'ATIVA',
  `versao` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `regras_elegibilidade`
--

INSERT INTO `regras_elegibilidade` (`id`, `id_edital`, `tipo_beneficio`, `renda_maxima_per_capita`, `exige_escola_publica`, `exige_etnia`, `nota_corte_estimada`, `prazo_inicio`, `prazo_fim`, `documentos_exigidos`, `status_regra`, `versao`) VALUES
(1, 1, 'ISENCAO_TAXA', 2118.00, 1, 0, 580, '2026-08-01', '2026-11-25', 'Histórico Escolar Público, Comprovante de Renda Familiar, CPF e RG.', 'ATIVA', 1),
(2, 2, 'COTA_ESCOLA_PUBLICA', NULL, 1, 0, 550, '2026-09-01', '2026-11-30', 'Declaração de Matrícula na Rede Pública de Ensino.', 'ATIVA', 1),
(3, 3, 'COTA_RENDA', 2118.00, 1, 0, 720, '2026-08-15', '2026-12-05', 'Folha Resumo CadÚnico ou Contracheques dos últimos 3 meses + Histórico Escolar.', 'ATIVA', 1),
(4, 4, 'ISENCAO_TAXA', 2118.00, 1, 0, 680, '2026-05-01', '2026-11-20', 'Comprovante de Renda Bruta, Histórico de Escola Pública.', 'ATIVA', 1),
(5, 5, 'COTA_RACIAL', 2118.00, 1, 1, 640, '2026-10-01', '2027-01-15', 'Autodeclaração Étnico-Racial assinada, Comprovante de Renda e Histórico Escolar.', 'ATIVA', 1),
(6, 6, 'COTA_ESCOLA_PUBLICA', 3000.00, 1, 0, 620, '2026-09-10', '2026-12-10', 'Histórico Completo do Ensino Médio em Escola Pública.', 'ATIVA', 1),
(7, 7, 'COTA_RENDA', 1500.00, 1, 0, 690, '2026-10-15', '2027-01-20', 'Declaração de Imposto de Renda ou Isenção + Histórico Escolar.', 'ATIVA', 1),
(8, 8, 'AMPLA_CONCORRENCIA', NULL, 0, 0, 750, '2026-11-01', '2027-02-28', 'Boletim Oficial do ENEM, Passaporte Válido e Apostila de Haia no Histórico.', 'ATIVA', 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `cadastros`
--
ALTER TABLE `cadastros`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_edital` (`id_edital`);

--
-- Índices de tabela `editais`
--
ALTER TABLE `editais`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `historico_retificacoes`
--
ALTER TABLE `historico_retificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_regra_original` (`id_regra_original`),
  ADD KEY `id_regra_nova` (`id_regra_nova`);

--
-- Índices de tabela `perfis_estudantes`
--
ALTER TABLE `perfis_estudantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `regras_elegibilidade`
--
ALTER TABLE `regras_elegibilidade`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_edital` (`id_edital`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `cadastros`
--
ALTER TABLE `cadastros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `editais`
--
ALTER TABLE `editais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `historico_retificacoes`
--
ALTER TABLE `historico_retificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `perfis_estudantes`
--
ALTER TABLE `perfis_estudantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `regras_elegibilidade`
--
ALTER TABLE `regras_elegibilidade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `cadastros` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`id_edital`) REFERENCES `editais` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `historico_retificacoes`
--
ALTER TABLE `historico_retificacoes`
  ADD CONSTRAINT `historico_retificacoes_ibfk_1` FOREIGN KEY (`id_regra_original`) REFERENCES `regras_elegibilidade` (`id`),
  ADD CONSTRAINT `historico_retificacoes_ibfk_2` FOREIGN KEY (`id_regra_nova`) REFERENCES `regras_elegibilidade` (`id`);

--
-- Restrições para tabelas `perfis_estudantes`
--
ALTER TABLE `perfis_estudantes`
  ADD CONSTRAINT `perfis_estudantes_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `cadastros` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `regras_elegibilidade`
--
ALTER TABLE `regras_elegibilidade`
  ADD CONSTRAINT `regras_elegibilidade_ibfk_1` FOREIGN KEY (`id_edital`) REFERENCES `editais` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
