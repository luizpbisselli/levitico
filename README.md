
Plano projeto sistema conciliacao fretes

Plano do Projeto

Sistema de conciliação de fretes (CT-e / NF-e) com área do motorista e área administrativa

1. Visão geral

Um app web próprio, independente da Ravex, que recebe os XMLs de NF-e e CT-e por e-mail, concilia NF-e ↔ CT-e ↔ veículo ↔ motorista e entrega duas áreas totalmente separadas: a área do Motorista (mobile first) e a área Administrativa (desktop e mobile).

O objetivo é eliminar o trabalho manual em planilhas de Excel usado hoje para reunir informações, gerar relatórios, calcular pagamento de agregados e enviar mensagens de WhatsApp aos clientes.

2. Decisões já tomadas
Captura inicial: conta de e-mail dedicada (NF-e e CT-e em anexo).
Backend: Laravel.
Frontend: PHP puro (views Blade), sem build, com Vanilla CSS e JavaScript ES6+ modular puro.
Banco de dados: MySQL relacional.
Layout: glassmorphic, 100% funcional em desktop e muito bom no mobile.
Acesso: motorista com login e área própria; administradores com área totalmente separada.
Cadastros: veículos (amarrados pela placa, com dados expansíveis) e motoristas associados a um ou mais veículos.
WhatsApp: atalho simples que abre o app com os dados da entrega já preenchidos a partir do CT-e/NF-e.
Banco transparente: criação e atualizações feitas pelo próprio sistema, sem comandos manuais.
Futuro: módulo financeiro específico e controle de estoque.
3. Arquitetura
Backend: Laravel
Laravel como API e motor de regras, com views Blade (PHP puro). Sem Vite, sem Node, sem build.
CSS e JS ficam direto em public/assets/.
Fila com driver database (sem Redis) e agendador via um único cron: * * * * * php artisan schedule:run.
Frontend
Blade renderizado no servidor, com Vanilla CSS e módulos nativos (<script type="module">).
Estrutura sugerida: assets/css/ (tokens, base, glass, componentes) e assets/js/ (api.js, ui/, pages/).
Tokens de design em variáveis CSS (--glass-bg, --blur, --radius), para trocar o visual em um lugar só.
Banco de dados
MySQL (InnoDB), relacional, com chaves estrangeiras e índice único pela chave de acesso de 44 dígitos.
4. Banco criado e atualizado sozinho

Uso das migrations do Laravel, disparadas automaticamente:

Instalador web (primeiro acesso): o sistema detecta que não há banco configurado, pede as credenciais MySQL e cria o admin inicial. Roda as migrations e grava o .env.
Atualizações: na inicialização, um middleware compara a versão do schema com as migrations pendentes e roda a migração sozinho.
Cuidado importante: usar um lock (arquivo ou GET_LOCK do MySQL) para que duas requisições simultâneas não migrem ao mesmo tempo, e nunca checar a cada requisição sem cache. Incluir backup automático (dump) antes de migrar.
Regra para o futuro: somente migrations aditivas (criar tabela/coluna). Remoções ficam para uma fase posterior, com cuidado.
5. Perfis e controle de acesso
Perfis: admin e motorista, com estrutura pronta para novos perfis (financeiro, estoque).
Duas áreas totalmente separadas: /admin e /motorista, com layouts, grupos de rotas e middlewares próprios.
O motorista só enxerga entregas ligadas aos seus veículos, garantido por Policies no backend. Esconder botão na tela não basta.
Senhas com hash, sessão com expiração, limite de tentativas de login e log de auditoria das ações do admin.
6. Modelo de dados (núcleo)
Tabela	Função
users	Login e perfil de acesso
motoristas	Dados do motorista, ligado a users
veiculos	Placa (única), tipo e campos extras (JSON ou colunas futuras)
motorista_veiculo	Relação N:N entre motorista e veículo
nfes	Cabeçalho, emitente, destinatário, valores, chave de acesso, XML original
ctes	Tomador, remetente, destinatário, valor do frete, chave de acesso, XML original
cte_nfe	Relação N:N entre CT-e e as NF-e que ele transporta
entregas	Status (a coletar, em trânsito, entregue, ocorrência), motorista e veículo
email_ingestoes	Log de cada e-mail lido: sucesso, erro, duplicado
whatsapp_modelos	Modelos de mensagem editáveis pelo admin

Os campos de pagamento de agregado, financeiro e estoque entram depois, em tabelas novas, sem mexer no núcleo.

7. Captura por e-mail
Caixa dedicada, lida via IMAP (ou API do Gmail/Microsoft) por um job a cada 1 a 2 minutos.
Baixa anexos .xml e também .zip com XMLs dentro.
Identifica o tipo pela raiz do XML (nfeProc, cteProc e eventos de cancelamento).
Faz o parse com SimpleXML e salva os dados e o XML bruto.
Idempotência pela chave de acesso: reprocessar o mesmo e-mail não duplica nada.
Concilia o CT-e com as NF-e pelas chaves em infDoc e marca o e-mail como processado (movendo para uma pasta).
Falhas vão para uma tela de pendências no admin; nunca ficam silenciosas.

Ponto a validar com XMLs reais: a vinculação do CT-e ao veículo/placa depende de a placa vir no XML (às vezes está só no MDF-e ou nem vem). Se não vier, o admin atribui o veículo à entrega manualmente, ou o sistema sugere com base no histórico. Pedir à transportadora alguns XMLs reais para confirmar.

8. Área do motorista (mobile first)
Lista das entregas dos seus veículos, com cartões grandes e fáceis de tocar.
Detalhe da entrega com cliente, endereço, NF-e e volumes.
Botão de WhatsApp: abre https://wa.me/<número>?text=<mensagem> com a mensagem montada a partir do modelo e preenchida com os dados da entrega (cliente, NF-e, placa, status). Não precisa de API nem tem custo. O número do cliente vem do XML quando existir, ou de um cadastro de contatos.
Botões rápidos de status: "Saí para entrega", "Entregue" e "Ocorrência".
9. Área administrativa
Dashboard com entregas do dia, pendências de e-mail e documentos sem veículo.
CRUDs de veículos, motoristas (com vínculo aos veículos), clientes/contatos e modelos de mensagem.
Lista de CT-e e NF-e com filtros e busca por chave, número ou placa, além de visualização do XML.
Relatórios exportáveis em CSV (base para o financeiro depois).
10. Layout glassmorphic
backdrop-filter: blur() com fundos translúcidos e bordas finas, sobre um fundo com gradiente.
Atenção: o blur pesa em celulares mais simples. Limitar o número de camadas com blur, usar @supports (backdrop-filter: blur(1px)) com fallback sólido, e respeitar prefers-reduced-motion e o contraste. Para o motorista, no sol, a legibilidade pesa mais que o efeito.
Mobile first no CSS, com min-width nos breakpoints, áreas de toque de pelo menos 44px, tema claro e escuro por variáveis.
11. Fases de entrega
Fase	Entregas
1. Base	Projeto Laravel, instalador, migrations automáticas, login, perfis, layouts e tokens glass
2. Cadastros	Veículos, motoristas e vínculos
3. Ingestão	Leitura de e-mail, parse de NF-e/CT-e, conciliação, tela de pendências
4. Entregas	Geração das entregas a partir dos documentos, área do motorista e botão de WhatsApp
5. Relatórios	Listas, filtros e exportações
6. Financeiro	Regras de pagamento do agregado (depende das regras que a transportadora definir)
7. Estoque	Controle de estoque
12. Sugestões de evolução
PWA: manifest e service worker simples (sem build) para o motorista instalar o app na tela inicial, com cache das telas.
Comprovante de entrega: foto do canhoto pelo celular, anexada à entrega.
SEFAZ Distribuição DF-e: segunda fonte além do e-mail, para não depender de ninguém enviar o XML.
Integração Ravex: se a API estiver disponível, cruzar os dados de viagem (posição e jornada) com as entregas.
WhatsApp API oficial: envios automáticos ao cliente por gatilho de status, com templates aprovados.
Backups automáticos do banco e dos XMLs, mais painel de saúde (último e-mail lido, fila, erros).
Guarda do XML original: armazenar sempre, por obrigação fiscal.
13. Pontos a levantar com a transportadora
Existe contrato com a Ravex que inclua integração/API, ou é cobrada à parte?
Como o CT-e é emitido hoje (sistema próprio, SEFAZ direto, contador)?
Quais regras de pagamento do agregado existem (por km, por peso, percentual do frete, adiantamentos)?
Quais mensagens de WhatsApp o cliente recebe e em quais gatilhos (saiu para carregar, em trânsito, entregue)?
Acesso a planilhas atuais e a alguns XMLs reais de NF-e e CT-e para validar o escopo.
