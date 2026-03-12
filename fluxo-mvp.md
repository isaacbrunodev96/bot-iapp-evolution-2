# Fluxo MVP WhatsApp (Texto e Voz)

```mermaid
flowchart LR
    Entrada[Lead entra via WhatsApp]
    Bot[Bot WhatsApp (Node.js)]
    Backend[Backend Laravel]
    Fila[Fila de Atendimento]
    Distribuicao[Distribuição para Usuário]
    Atendimento[Atendimento (Bot/Humano)]
    AI[AI (Llama/Ollama/Gemini)]
    Voz[Voz (ElevenLabs)]
    Status[Atualização de Status]
    Log[Registro de Logs]
    CRM[CRM Kanban]
    Infra[Infra: Docker/Nginx/Postgres/Redis]
    Entrada --> Bot
    Bot --> Backend
    Backend --> Fila
    Fila --> Distribuicao
    Distribuicao --> Atendimento
    Atendimento -->|Bot| AI
    Atendimento -->|Bot| Voz
    Atendimento -->|Humano| CRM
    AI --> Atendimento
    Voz --> Atendimento
    Atendimento --> Status
    Status --> CRM
    Atendimento --> Log
    Log --> CRM
    Atendimento --> Finalizacao[Finalização do Lead]
    Finalizacao --> Log
    Finalizacao --> Status
    CRM --> Log
    CRM --> Status
    CRM --> Finalizacao
    Backend --> Log
    Backend --> Status
    Backend --> Finalizacao
    Backend --> Infra
    Bot --> Log
    Bot --> Status
    Bot --> Finalizacao
```
