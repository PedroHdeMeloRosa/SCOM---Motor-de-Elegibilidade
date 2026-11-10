-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 10/11/2026 às 14:44
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
  `ano_referencia` year(4) NOT NULL,
  `link_documento` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Estrutura para tabela `regras_elegibilidade`
--

DROP TABLE IF EXISTS `regras_elegibilidade`;
CREATE TABLE `regras_elegibilidade` (
  `id` int(11) NOT NULL,
  `id_edital` int(11) NOT NULL,
  `tipo_beneficio` enum('ISENCAO_TAXA','COTA_RENDA','COTA_ESCOLA_PUBLICA','COTA_RACIAL') NOT NULL,
  `renda_maxima_per_capita` decimal(10,2) DEFAULT NULL,
  `exige_escola_publica` tinyint(1) DEFAULT 0,
  `exige_etnia` tinyint(1) DEFAULT 0,
  `prazo_inicio` date NOT NULL,
  `prazo_fim` date NOT NULL,
  `documentos_exigidos` text DEFAULT NULL,
  `status_regra` enum('ATIVA','INATIVA') DEFAULT 'ATIVA',
  `versao` int(11) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `historico_retificacoes`
--
ALTER TABLE `historico_retificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `regras_elegibilidade`
--
ALTER TABLE `regras_elegibilidade`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Restrições para tabelas `regras_elegibilidade`
--
ALTER TABLE `regras_elegibilidade`
  ADD CONSTRAINT `regras_elegibilidade_ibfk_1` FOREIGN KEY (`id_edital`) REFERENCES `editais` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
