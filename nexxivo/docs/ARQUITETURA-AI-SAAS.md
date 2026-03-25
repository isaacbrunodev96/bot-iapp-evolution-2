# Arquitetura: IA no SaaS (Nexxivo)

## O que é o AIService?

O **AIService** é um **motor genérico** de IA. Ele:

- Recebe: **prompt** (texto), mensagem do usuário, provedor (Ollama/Gemini), modelo opcional, histórico.
- **Não** guarda persona (Laura, João, etc.).
- **Não** guarda o roteiro de nenhum cliente.

Ou seja: quem define *quem* responde e *o que* dizer é o **prompt do fluxo**, que fica no banco (por fluxo).

## Onde está o prompt da “Laura”?

No **Fluxo** que você criou (ex.: “claudio teste”):

- Ao editar o fluxo, na ação **“Resposta com IA”**, o campo **“Prompt da IA”** é o texto que define a Laura (estados, tom, regras, etc.).
- Esse texto é salvo em `flows.actions` (JSON) no banco.
- Quando uma mensagem chega, o job busca os fluxos ativos, acha o que casa (ex.: “Qualquer Mensagem”), e passa **esse** prompt para o AIService.

Ou seja: **cada fluxo tem seu próprio prompt**. A “Laura” está só no fluxo que você configurou.

## Multi-tenant (outro usuário no SaaS)

- Cada usuário tem suas **instâncias** (WhatsApp) e seus **fluxos**.
- Na listagem/edição, os fluxos já são filtrados pelas instâncias do usuário logado (`FlowManagementController`).
- Outro usuário cria **outro fluxo** com **outro prompt** (outro nome, outro tom, outro idioma). O AIService só recebe o prompt que vier do fluxo dele.
- Conclusão: **não** “todo mundo responde por Laura”. Cada um responde pelo personagem/regras do **próprio** fluxo.

## O que é global no AIService (para todos os fluxos)

- Regra de **idioma** (“responda em português”) e lembrete no final do prompt.
- **Sanitização** da resposta: remoção de `**smiles**`, `{img002}`, linhas tipo “ESTADO 1:”, “PARE AQUI”, etc.
- **Substituições** de frases em inglês por português (ex.: “Great choice!” → “Ótima escolha!”).

Isso foi feito para corrigir respostas em inglês e “sem nexo”. Se no futuro você tiver inquilinos que queiram resposta em inglês, dá para tornar a regra de idioma e as substituições **opcionais por fluxo** (ex.: campo “Idioma da resposta” na ação “Resposta com IA”).

## Pipeline: automação / fluxo → duas camadas no system

1. **`ProcessIncomingMessageJob::composeAiPromptFromFlowAndAction`** monta um único texto com comportamento (descrição), marcador **`<<<TAREFA_AUTOMACAO>>>`** (`AI_TASK_DELIMITER`) e tarefa (prompt da ação).

2. **`AIService`** separa comportamento vs tarefa e coloca no system em blocos distintos (NOTA_INTERNA + instrução da rodada). Formato legado `\n---\n\n[Tarefa]\n` ainda é aceite.

3. Sanitização e `stripResponseIfEchoesFlowDescription` mantêm-se.

## Por que a IA “repetia a descrição do fluxo”?

1. **O que vai para o modelo**  
   O job junta descrição + prompt da ação; o serviço agora **explicita duas camadas** no system para reduzir o modelo a “recitar” o guião inteiro.

2. **Defesas já existentes**  
   - Regras fixas no system (“não copie Persona/Objetivos…”).  
   - Bloco interno delimitado (`<<<NOTA_INTERNA…>>>`) + exemplo certo/errado.  
   - Lembrete na última mensagem do utilizador (Ollama + texto usado no Gemini).  
   - `stripResponseIfEchoesFlowDescription` no job e heurísticas em `sanitizeResponseForChat` / `stripLeakedPromptFromResponse`.  
   - Limite de caracteres do contexto (`AI_MAX_FLOW_CONTEXT_CHARS` em `config/services.php`).

3. **Como escrever para soar humano**  
   - **Descrição do fluxo**: quem você é, tom, o que pode/não pode prometer — em frases curtas. **Não** coloque aqui a fala literal que o cliente deve receber.  
   - **Prompt da ação**: a *tarefa* (“responda à dúvida sobre preço”, “confirme o horário”).  
   - Evite colar o **mesmo texto** nos dois campos (o job já evita duplicar se forem idênticos).  
   - Prefira “seja breve e natural” a roteiros com “ESTADO 1 / ESTADO 2” e falas fixas.

4. **Ollama: temperatura**  
   `OLLAMA_CHAT_TEMPERATURE` (padrão `0.32`) e `OLLAMA_CHAT_TOP_P` (`0.68`) em `.env` afinam naturalidade vs. consistência. Valores muito altos podem aumentar eco do guião em modelos fracos.

## Conversa menos robótica (boas práticas)

1. **Usar histórico**  
   Na ação “Resposta com IA”, marque **“Usar contexto da conversa”** (ou equivalente). Assim o modelo vê as mensagens anteriores e evita repetir a mesma abertura.

2. **Prompt mais “instrução” e menos “script rígido”**  
   Em vez de só listar “ESTADO 1 / ESTADO 2” e falas exatas, inclua instruções do tipo: “Responda de forma natural e curta, como uma pessoa real. Não repita instruções internas (ESTADO, PARE AQUI) na resposta. Não use placeholders como {img002} na mensagem.”

3. **Evitar vazamento de metadados**  
   Já tratamos na sanitização: remover “Pergunte:”, “Enviar uma imagem aqui:”, “Awaiting your response!”, etc. Assim o cliente só vê o texto final.

4. **Modelo**  
   Modelos maiores (ou com mais instrução em PT-BR) tendem a seguir melhor “só português” e “tom natural”. Vale testar outro modelo no Ollama/Gemini se a conversa ainda ficar robótica.

## Resumo

| Onde | O que |
|------|--------|
| **AIService** | Motor genérico: recebe prompt e devolve resposta. Sem persona. |
| **Fluxo (banco)** | Guarda o prompt (Laura, João, etc.) por fluxo. Cada usuário configura o seu. |
| **Job** | Pega o fluxo que deu match, lê o `prompt` da ação “Resposta com IA” e chama o AIService com esse prompt. |
| **SaaS** | Outro usuário = outros fluxos = outros prompts = outro “personagem”, não a Laura. |
