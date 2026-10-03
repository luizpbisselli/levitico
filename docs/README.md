# 📚 Índice da Documentação do Sistema

Documentação técnica e arquitetural do **Sistema de Conciliação de Fretes (CT-e / NF-e)**.

---

## 🗂️ Estrutura da Documentação

1. [**01 - Arquitetura e Padrões de Projeto**](file:///c:/levitico/docs/01-arquitetura-e-padroes.md)
   - Visão geral da arquitetura sem build (PHP/Blade/Vanilla).
   - Diagrama de componentes e fluxo de dados.
   - Padrões de projeto aplicados (*Service Layer*, *Policy*, *Chain of Responsibility*, *Idempotency*, *Adapter*).

2. [**02 - Banco de Dados e Modelagem**](file:///c:/levitico/docs/02-banco-de-dados-e-modelagem.md)
   - Diagrama Entidade-Relacionamento (DER).
   - Dicionário de dados de todas as tabelas.
   - Política de migrações aditivas e auto-instalador.

3. [**03 - Fluxos de Ingestão e Conciliação**](file:///c:/levitico/docs/03-fluxos-e-conciliacao.md)
   - Ciclo de vida da leitura de e-mails IMAP e arquivos `.zip`.
   - Parsing fiscal com SimpleXML (`nfeProc`, `cteProc`, eventos).
   - Algoritmo de amarração via `infDoc` e sugestão de veículo por histórico.
   - Integração de WhatsApp com tags dinâmicas.

4. [**04 - Roadmap e Matriz de Fases**](file:///c:/levitico/docs/04-roadmap-e-status.md)
   - Matriz detalhada das 7 fases do projeto.
   - O que já está construído e 100% funcional (Fases 1 a 5).
   - O que falta construir (Fases 6, 7 e evoluções de produto).
