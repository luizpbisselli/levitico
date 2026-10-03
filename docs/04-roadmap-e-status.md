# 04 - Roadmap e Matriz de Fases

## Matriz Comparativa do Projeto

Esta matriz lista o status de cada uma das 7 fases previstas no plano do projeto, além de evoluções técnicas mapeadas.

---

### Status por Fase

| Fase | Título | Status | Detalhes da Implementação |
| :---: | :--- | :---: | :--- |
| **1** | **Base e Infraestrutura** | Concluído | Laravel estruturado, instalador automático (`AutoInstall`), painel web para configuração de MySQL e IMAP sem SSH, RBAC (`admin` e `motorista`), rate limit no login e auditoria. |
| **2** | **Cadastros** | Concluído | CRUDs completos de Veículos (com JSON expansível), Motoristas (com vínculo N:N a veículos), Clientes/Contatos, Usuários e Modelos de Mensagem do WhatsApp. |
| **3** | **Ingestão e Conciliação** | Concluído | Conexão IMAP com extração de `.zip`, parser de XMLs fiscais e cancelamentos com guarda obrigatória, amarração N:N em `cte_nfe`, identificação/sugestão de placa e tela de pendências. |
| **4** | **Entregas e Motorista** | Concluído | Criação automática de entregas com endereço completo, interface mobile first com botões táteis de status ("Saí para entrega", "Entregue", "Ocorrência") e link de WhatsApp direto. |
| **5** | **Relatórios e Consultas** | Concluído | Filtros de CT-e/NF-e por chave/placa/período, visualizador do XML bruto, exportação nativa em CSV formatado para Excel (BOM UTF-8) e agendador via CLI/Webhook. |
| **PWA** | **PWA Universal (Admin + Motorista)** | Concluído | `manifest.json`, Service Worker (`sw.js`), ícones vetoriais, atalhos, tema dark/slate, tela de fallback offline e botão de instalação nativo no navegador. |
| **Canhoto**| **Canhoto Digital / Comprovante** | Concluído | Captura de foto via câmera/arquivo na entrega pelo motorista, preview instantâneo, storage protegido e visualização no painel administrativo e de relatórios. |
| **6** | **Módulo Financeiro** | A Construir | Tabelas e fórmulas de cálculo de frete para agregados (por km, peso, cubagem ou % de frete), controle de adiantamentos, descontos operacionais e extrato/recibo de pagamento. |
| **7** | **Controle de Estoque** | A Construir | Cadastro de armazéns/posições, controle de conferência física na descarga e gestão de transbordo (cross-docking). |

---

## 🚀 O que Podemos Fazer a Seguir para Avançar a Construção

### Opção 1: Fase 6 - Módulo Financeiro de Agregados
- **Escopo**:
  1. Criar migration para tabela `tabelas_frete` ou regras de cálculo (ex.: R$/km, R$/peso ou % sobre o CT-e).
  2. Criar tabela `pagamentos_agregados` (viagem, frete bruto, adiantamento, pedágio, descontos, valor líquido).
  3. Criar tela de Fechamento Financeiro no painel admin com cálculo automático a partir das entregas concluídas.
  4. Gerar extrato e recibo de pagamento em PDF/CSV para envio ao motorista.

### Opção 2: Fase 7 - Controle de Estoque & Cross-Docking
- **Escopo**:
  1. Modelagem de armazéns, docas e posições de estocagem.
  2. Tela de conferência de volumes no recebimento das NF-es.
  3. Relatório de mercadorias em trânsito vs. armazenadas no galpão.

### Opção 3: SEFAZ Distribuição DF-e Automática
- **Escopo**:
  1. Consulta direta e automática à SEFAZ através de Certificado Digital A1.
  2. Recepção automática de XMLs sem depender de terceiros enviarem por e-mail.
