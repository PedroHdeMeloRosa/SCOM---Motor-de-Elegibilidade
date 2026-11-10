document.addEventListener('DOMContentLoaded', () => {
    const formElegibilidade = document.getElementById('form-elegibilidade');
    const btnProcessar = document.getElementById('btn-processar');
    const resultadosDiv = document.getElementById('resultados');

    formElegibilidade.addEventListener('submit', async function(e) {
        e.preventDefault(); 
        
        btnProcessar.textContent = "Processando regras...";
        btnProcessar.disabled = true;
        resultadosDiv.innerHTML = "";

        const formData = new FormData(this);
        const perfilAluno = Object.fromEntries(formData.entries());

        try {
            // Caminho ajustado para partir da raiz do projeto
            const response = await fetch('api/controllers/rules_engine.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json' 
                },
                body: JSON.stringify(perfilAluno)
            });

            if (!response.ok) {
                throw new Error('Falha na comunicação com o servidor.');
            }
            
            const oportunidades = await response.json();
            
            if (oportunidades.length === 0) {
                resultadosDiv.innerHTML = "<p>Nenhuma oportunidade encontrada para este perfil no momento.</p>";
            } else {
                oportunidades.forEach(op => {
                    resultadosDiv.innerHTML += `
                        <div class="card-oportunidade">
                            <h3>${op.instituicao} - ${op.nome_processo}</h3>
                            <strong>Benefício:</strong> ${op.tipo_beneficio} <br>
                            <strong>Prazo Final:</strong> ${op.prazo_fim} <br>
                            <strong>Documentos Necessários:</strong> ${op.documentos_exigidos}
                        </div>
                    `;
                });
            }
        } catch (error) {
            console.error("Erro na Engine:", error);
            resultadosDiv.innerHTML = `<p style="color: var(--alerta-retificacao);">Erro ao processar elegibilidade. Verifique sua conexão ou tente novamente mais tarde.</p>`;
        } finally {
            btnProcessar.textContent = "Analisar Oportunidades";
            btnProcessar.disabled = false;
        }
    });
});