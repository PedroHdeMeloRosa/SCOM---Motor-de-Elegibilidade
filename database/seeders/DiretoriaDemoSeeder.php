<?php

namespace Database\Seeders;

use App\Models\Edital;
use App\Models\LogAuditoria;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DiretoriaDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DiretoriaDemoSeeder bloqueado em ambiente de produção.');
            return;
        }

        $senhaPadrao = Hash::make('scom2026');

        // 1. Garante um diretor para assinar os logs administrativos
        $diretor = User::firstOrCreate(
            ['email' => 'diretor@unilegi.com'],
            [
                'nome' => 'Diretoria SCOM',
                'senha_hash' => $senhaPadrao,
                'tipo_perfil' => 'DIRETOR',
                'status_conta' => 'ATIVA',
                'criado_em' => Carbon::today()->subDays(13),
            ]
        );

        // 2. Cria 3 Coordenadores com perfis de produtividade distintos
        $coordenadores = [
            'lucas' => User::firstOrCreate(
                ['email' => 'lucas.rascunhos@unilegi.com'],
                [
                    'nome' => 'Lucas Almeida (Rascunhos)',
                    'senha_hash' => $senhaPadrao,
                    'tipo_perfil' => 'COORDENADOR',
                    'status_conta' => 'ATIVA',
                    'criado_em' => Carbon::today()->subDays(12),
                ]
            ),
            'mariana' => User::firstOrCreate(
                ['email' => 'mariana.revisora@unilegi.com'],
                [
                    'nome' => 'Mariana Costa (Revisora)',
                    'senha_hash' => $senhaPadrao,
                    'tipo_perfil' => 'COORDENADOR',
                    'status_conta' => 'ATIVA',
                    'criado_em' => Carbon::today()->subDays(11),
                ]
            ),
            'rafael' => User::firstOrCreate(
                ['email' => 'rafael.gestao@unilegi.com'],
                [
                    'nome' => 'Rafael Souza (Gestão)',
                    'senha_hash' => $senhaPadrao,
                    'tipo_perfil' => 'COORDENADOR',
                    'status_conta' => 'ATIVA',
                    'criado_em' => Carbon::today()->subDays(10),
                ]
            ),
        ];

        // 3. Cria 10 Editais distribuídos nos últimos 14 dias
        $editaisDemo = [
            ['inst' => 'UNESP Sorocaba', 'proc' => 'Vestibular 2027 - Cota Social', 'cidade' => 'Sorocaba', 'uf' => 'SP', 'status' => 'PUBLICADO', 'dias' => 12, 'renda' => 2118, 'escola' => true, 'etnia' => false],
            ['inst' => 'USP', 'proc' => 'FUVEST 2027 - Isenção de Taxa', 'cidade' => 'São Paulo', 'uf' => 'SP', 'status' => 'PUBLICADO', 'dias' => 10, 'renda' => 1800, 'escola' => true, 'etnia' => false],
            ['inst' => 'UNICAMP', 'proc' => 'Vestibular 2027 - Cotas PPI', 'cidade' => 'Campinas', 'uf' => 'SP', 'status' => 'PUBLICADO', 'dias' => 8, 'renda' => 2500, 'escola' => true, 'etnia' => true],
            ['inst' => 'UFSCar', 'proc' => 'SiSU 2027 - Auxílio Moradia', 'cidade' => 'Sorocaba', 'uf' => 'SP', 'status' => 'PUBLICADO', 'dias' => 6, 'renda' => 2118, 'escola' => false, 'etnia' => false],
            ['inst' => 'IFSP', 'proc' => 'Processo Seletivo 2027/1', 'cidade' => 'Sorocaba', 'uf' => 'SP', 'status' => 'PUBLICADO', 'dias' => 4, 'renda' => 2200, 'escola' => true, 'etnia' => false],
            ['inst' => 'UNIFESP', 'proc' => 'Vestibular Misto 2027', 'cidade' => 'São Paulo', 'uf' => 'SP', 'status' => 'RASCUNHO', 'dias' => 3, 'renda' => 2118, 'escola' => true, 'etnia' => false],
            ['inst' => 'UFMG', 'proc' => 'SiSU 2027 - Ampla e Renda', 'cidade' => 'Belo Horizonte', 'uf' => 'MG', 'status' => 'RASCUNHO', 'dias' => 2, 'renda' => 2118, 'escola' => true, 'etnia' => false],
            ['inst' => 'UFPR', 'proc' => 'Vestibular 2027 - Inclusão', 'cidade' => 'Curitiba', 'uf' => 'PR', 'status' => 'PUBLICADO', 'dias' => 1, 'renda' => 2118, 'escola' => true, 'etnia' => false],
            ['inst' => 'UNB', 'proc' => 'PAS 2026 - Encerrado', 'cidade' => 'Brasília', 'uf' => 'DF', 'status' => 'ARQUIVADO', 'dias' => 9, 'renda' => 2000, 'escola' => true, 'etnia' => false],
            ['inst' => 'UFRJ', 'proc' => 'Acesso Enem 2026 - Arquivado', 'cidade' => 'Rio de Janeiro', 'uf' => 'RJ', 'status' => 'ARQUIVADO', 'dias' => 5, 'renda' => 2000, 'escola' => true, 'etnia' => false],
        ];

        $editaisCriados = [];
        foreach ($editaisDemo as $item) {
            $edital = Edital::firstOrCreate(
                ['instituicao' => $item['inst'], 'nome_processo' => $item['proc']],
                [
                    'cidade' => $item['cidade'],
                    'estado' => $item['uf'],
                    'regiao' => 'SUDESTE',
                    'latitude' => -23.5015,
                    'longitude' => -47.4526,
                    'custo_medio_republica' => 750,
                    'tem_moradia_estudantil' => true,
                    'ano_referencia' => 2027,
                    'link_documento' => 'https://example.test/edital-demo.pdf',
                    'status_publicacao' => $item['status'],
                    'criado_em' => Carbon::today()->subDays($item['dias'])->addHours(10),
                ]
            );

            if ($edital->regras()->count() === 0) {
                $edital->regras()->create([
                    'tipo_beneficio' => $item['etnia'] ? 'COTA_RACIAL' : 'COTA_RENDA',
                    'renda_maxima_per_capita' => $item['renda'],
                    'exige_escola_publica' => $item['escola'],
                    'exige_etnia' => $item['etnia'],
                    'exige_pcd' => false,
                    'nota_corte_estimada' => 620,
                    'prazo_inicio' => Carbon::today()->subDays(20)->format('Y-m-d'),
                    'prazo_fim' => Carbon::today()->addDays(60)->format('Y-m-d'),
                    'documentos_exigidos' => 'RG, CPF, comprovante de renda e histórico escolar.',
                    'status_regra' => 'ATIVA',
                    'versao' => 1,
                ]);
            }

            $editaisCriados[] = $edital;
        }

        // 4. Cria 24 Estudantes espalhados pelos últimos 14 dias (completos, incompletos, sem ficha e banidos)
        $nomes = [
            'Ana Beatriz Silva', 'Bruno Henrique Oliveira', 'Camila Ferreira', 'Diego Santos',
            'Eduarda Lima', 'Felipe Rocha', 'Gabriela Martins', 'Henrique Costa',
            'Isabela Mendes', 'João Pedro Alves', 'Karina Nogueira', 'Leonardo Dias',
            'Mariana Teixeira', 'Nathan Ribeiro', 'Olívia Carvalho', 'Paulo Henrique',
            'Rafaela Gomes', 'Samuel Batista', 'Thaís Moreira', 'Vinícius Duarte',
            'Yasmin Cardoso', 'Matheus Araújo', 'Letícia Correia', 'Caio Fernandes',
        ];

        $diasDistribuicao = [13, 12, 12, 11, 10, 10, 10, 9, 8, 7, 7, 6, 5, 5, 4, 4, 4, 3, 2, 2, 1, 1, 0, 0];

        foreach ($nomes as $idx => $nome) {
            $email = 'demo.aluno' . ($idx + 1) . '@unilegi.com';
            $banido = in_array($idx, [3, 11, 19], true);
            $dataCadastro = Carbon::today()->subDays($diasDistribuicao[$idx])->addHours(9 + ($idx % 8));

            $aluno = User::firstOrCreate(
                ['email' => $email],
                [
                    'nome' => $nome,
                    'senha_hash' => $senhaPadrao,
                    'tipo_perfil' => 'ESTUDANTE',
                    'status_conta' => $banido ? 'BANIDA' : 'ATIVA',
                    'criado_em' => $dataCadastro,
                ]
            );

            // 0..11: Ficha completa e elegível | 12..15: Ficha completa mas renda alta | 16..18: Ficha incompleta | 19..23: Sem ficha
            if ($idx <= 15 && !$aluno->perfil) {
                $rendaAlta = $idx >= 12;
                $aluno->perfil()->create([
                    'renda_per_capita' => $rendaAlta ? 4800 : (850 + ($idx * 90)),
                    'tipo_escola' => $rendaAlta ? 'PRIVADA' : 'PUBLICA',
                    'raca_cor' => ['PRETA', 'PARDA', 'BRANCA', 'INDIGENA'][$idx % 4],
                    'nota_media_enem' => 580 + ($idx * 12),
                    'cep' => '18013-004',
                    'cidade_origem' => $idx % 2 === 0 ? 'Sorocaba' : 'Campinas',
                    'estado_origem' => 'SP',
                    'lat_origem' => -23.5015,
                    'lng_origem' => -47.4526,
                    'nivel_mobilidade' => ['LOCAL', 'ESTADUAL', 'INTERESTADUAL', 'INTERNACIONAL'][$idx % 4],
                    'orcamento_moradia' => 700 + ($idx * 30),
                    'interesse_republica' => $idx % 2 === 0,
                    'precisa_auxilio_moradia' => !$rendaAlta,
                    'pcd' => $idx === 5,
                ]);
            } elseif ($idx >= 16 && $idx <= 18 && !$aluno->perfil) {
                // Ficha incompleta (sem raça/cor, sem nota ENEM e sem orçamento)
                $aluno->perfil()->create([
                    'renda_per_capita' => 1400,
                    'tipo_escola' => 'PUBLICA',
                    'raca_cor' => null,
                    'nota_media_enem' => null,
                    'cidade_origem' => 'Sorocaba',
                    'estado_origem' => 'SP',
                    'nivel_mobilidade' => 'ESTADUAL',
                    'orcamento_moradia' => null,
                ]);
            }
        }

        // 5. Popula Logs de Auditoria (evita duplicar se rodar o seeder 2 vezes)
        if (LogAuditoria::where('detalhes', 'like', '%[DEMO]%')->exists()) {
            $this->command?->info('DiretoriaDemoSeeder: dados de demonstração já existem.');
            return;
        }

        $editalId = $editaisCriados[0]->id ?? 1;

        $acoesDemo = [
            // Lucas: cria muitos rascunhos (9 criados, 1 editado, 0 publicados)
            ...array_fill(0, 9, ['ator' => $coordenadores['lucas']->id, 'acao' => 'EDITAL_CRIADO', 'det' => '[DEMO] Criou rascunho rápido de edital.']),
            ['ator' => $coordenadores['lucas']->id, 'acao' => 'EDITAL_EDITADO', 'det' => '[DEMO] Ajustou título do rascunho.'],

            // Mariana: revisora (2 criados, 6 editados, 5 publicados, 4 retificados)
            ...array_fill(0, 2, ['ator' => $coordenadores['mariana']->id, 'acao' => 'EDITAL_CRIADO', 'det' => '[DEMO] Cadastrou edital completo.']),
            ...array_fill(0, 6, ['ator' => $coordenadores['mariana']->id, 'acao' => 'EDITAL_EDITADO', 'det' => '[DEMO] Revisou critérios e links oficiais do edital.']),
            ...array_fill(0, 5, ['ator' => $coordenadores['mariana']->id, 'acao' => 'EDITAL_PUBLICADO', 'det' => '[DEMO] Validou regras e publicou edital para os alunos.']),
            ...array_fill(0, 4, ['ator' => $coordenadores['mariana']->id, 'acao' => 'REGRA_RETIFICADA', 'det' => '[DEMO] Retificou teto de renda conforme adendo oficial.']),

            // Rafael: gestão de ciclo (1 criado, 2 editados, 3 publicados, 1 retificado, 4 arquivados)
            ['ator' => $coordenadores['rafael']->id, 'acao' => 'EDITAL_CRIADO', 'det' => '[DEMO] Inseriu edital de transferência.'],
            ...array_fill(0, 2, ['ator' => $coordenadores['rafael']->id, 'acao' => 'EDITAL_EDITADO', 'det' => '[DEMO] Atualizou datas de inscrição.']),
            ...array_fill(0, 3, ['ator' => $coordenadores['rafael']->id, 'acao' => 'EDITAL_PUBLICADO', 'det' => '[DEMO] Publicou edital após revisão final.']),
            ['ator' => $coordenadores['rafael']->id, 'acao' => 'REGRA_RETIFICADA', 'det' => '[DEMO] Prorrogou prazo final de inscrição.'],
            ...array_fill(0, 4, ['ator' => $coordenadores['rafael']->id, 'acao' => 'EDITAL_ARQUIVADO', 'det' => '[DEMO] Arquivou edital encerrado preservando histórico.']),

            // Ações da Diretoria
            ['ator' => $diretor->id, 'acao' => 'CONTA_ATUALIZADA', 'det' => '[DEMO] Suspendeu conta demo.aluno4@unilegi.com por inconsistência cadastral.'],
            ['ator' => $diretor->id, 'acao' => 'CONTA_ATUALIZADA', 'det' => '[DEMO] Promoveu Mariana Costa para perfil COORDENADOR.'],
        ];

        foreach ($acoesDemo as $i => $log) {
            LogAuditoria::create([
                'ator_id' => $log['ator'],
                'acao' => $log['acao'],
                'entidade_tipo' => $log['acao'] === 'CONTA_ATUALIZADA' ? 'USUARIO' : 'EDITAL',
                'entidade_id' => $editalId,
                'detalhes' => $log['det'],
                'criado_em' => Carbon::now()->subHours(count($acoesDemo) - $i),
            ]);
        }

        $this->command?->info('DiretoriaDemoSeeder executado com sucesso!');
    }
}