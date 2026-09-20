# Documentação Técnica — Sistema Metalúrgica Oliveira

**Disciplinas:** Banco de Dados Avançado · Desenvolvimento Web Avançado · Lógica Avançada  
**Tecnologias:** PHP · MariaDB · TypeScript · Bootstrap 5  
**Ambiente:** XAMPP (Apache + MySQL)

---

## O que é este sistema

O sistema foi desenvolvido para a **Metalúrgica Oliveira**, uma empresa fictícia de estruturas metálicas e galpões. Ele permite que clientes façam solicitações de orçamento pelo site, e que a empresa gerencie esses dados internamente através de uma área administrativa com dashboard de indicadores em tempo real.

O fluxo completo do dado é: **Banco de Dados → PHP → JSON → TypeScript → Tela do usuário**.

---

## Como rodar o projeto

1. Abra o **XAMPP Control Panel** e inicie o **Apache** e o **MySQL**
2. No navegador, acesse `http://localhost:8080/phpmyadmin`
3. Importe o arquivo `database/instalar_tudo.sql` — ele cria o banco, tabelas, dados de exemplo, triggers, views, functions e procedures automaticamente
4. Para recompilar o TypeScript, abra o terminal na pasta do projeto e execute: `npm run build`
5. Acesse o sistema em: `http://localhost:8080/projeto%20ads/`

---

## Estrutura de pastas

```
projeto ads/
├── admin/               → Área administrativa (3 CRUDs)
│   ├── clientes.php
│   ├── produtos.php
│   └── orcamentos.php
├── api/                 → Endpoints JSON consumidos pelo TypeScript
│   └── dashboard.php
├── assets/
│   ├── css/style.css    → Estilos customizados além do Bootstrap
│   ├── imagens/         → Imagens do site
│   └── js/
│       └── dashboard.ts → Código-fonte TypeScript (não compilado)
├── database/            → Scripts SQL organizados por etapa
│   ├── 01_schema_e_dados.sql
│   ├── 02_triggers.sql
│   ├── 03_functions.sql
│   ├── 04_views_e_ctes.sql
│   ├── 05_stored_procedures.sql
│   └── instalar_tudo.sql
├── dist/
│   └── dashboard.js     → JavaScript compilado a partir do TypeScript
├── includes/            → Template reutilizável
│   ├── conexao.php
│   ├── header.php
│   └── footer.php
├── index.php            → Dashboard principal
├── servicos.php         → Catálogo de produtos
├── orcamento.php        → Formulário de solicitação de orçamento
├── contato.php          → Página de contato
├── package.json         → Configuração do compilador TypeScript
└── tsconfig.json        → Configurações de compilação TypeScript
```

---

## Banco de Dados

### Modelagem — `01_schema_e_dados.sql`

O banco `metalurgica_oliveira` possui 4 tabelas interligadas:

**`clientes`** — armazena os clientes que solicitam orçamentos. O campo `email` é `UNIQUE` para evitar duplicatas. O campo `cidade` tem valor padrão `'São Paulo'`.

**`produtos`** — armazena os produtos disponíveis para orçamento (galpões, perfis, chapas). Possui `preco_base` e `quantidade_disponivel`.

**`orcamentos`** — cada orçamento pertence a um cliente (`FOREIGN KEY id_cliente`). Possui `status` que indica a etapa do pedido (Pendente, Aprovado, Em Fabricação, Entregue, Cancelado). A chave estrangeira usa `ON DELETE RESTRICT`, impedindo excluir um cliente que tenha orçamentos.

**`orcamento_itens`** — tabela de junção entre orçamentos e produtos. Um orçamento pode ter vários produtos. A chave estrangeira com `orcamentos` usa `ON DELETE CASCADE`, ou seja, ao excluir um orçamento os seus itens são removidos automaticamente. A chave com `produtos` usa `ON DELETE RESTRICT`, impedindo excluir um produto que já está em um orçamento.

---

### Triggers — `02_triggers.sql`

Triggers são regras automáticas que o banco executa **antes** de uma operação. Foram implementados dois `BEFORE UPDATE`:

**`trg_produtos_validar_valores_positivos_bu`** — Dispara toda vez que alguém tenta atualizar um produto. Verifica se `preco_base` é negativo ou se `quantidade_disponivel` é negativa. Se for, o banco bloqueia a operação e retorna uma mensagem de erro com `SIGNAL SQLSTATE '45000'`. Isso garante integridade mesmo se alguém tentar alterar o banco diretamente sem passar pela interface.

**`trg_orcamento_itens_validar_quantidade_bu`** — Dispara ao atualizar um item de orçamento. Impede que `quantidade_solicitada` seja zero ou negativa.

---

### Função — `03_functions.sql`

**`fn_calcular_total_orcamento(p_id_orcamento INT)`** — função reutilizável que recebe o ID de um orçamento e retorna o valor total dele. Internamente faz um `SUM(quantidade_solicitada * preco_base)` cruzando as tabelas `orcamento_itens` e `produtos`. O `COALESCE` garante que retorna `0.00` caso o orçamento não tenha itens, evitando `NULL`.

Esta função é chamada diretamente no SQL da página de administração de orçamentos para calcular o valor de cada linha da tabela sem repetir a lógica de cálculo.

---

### Views e CTE — `04_views_e_ctes.sql`

**`vw_orcamentos_detalhados`** — View que centraliza informações de 4 tabelas (`orcamentos`, `clientes`, `orcamento_itens`, `produtos`) em uma única consulta. Retorna todos os dados de um orçamento com nome do cliente, produto, preço unitário, quantidade e subtotal calculado. Elimina a necessidade de fazer JOINs repetidos em várias partes do código.

**`vw_analise_faturamento_produtos`** — View analítica que usa uma **CTE** (Common Table Expression) internamente. A CTE `dados_vendas_brutos` agrega os dados brutos de vendas por produto, calculando volume total demandado e faturamento potencial. A query externa sobre essa CTE adiciona a classificação de estoque (`SEM ESTOQUE`, `ESTOQUE CRÍTICO`, `ESTOQUE NORMAL`) usando `CASE WHEN`. Esta view é consumida pela API PHP para alimentar o dashboard.

---

### Stored Procedure — `05_stored_procedures.sql`

**`sp_dashboard_indicadores`** — Procedure que centraliza a busca de orçamentos com suporte a filtros e paginação. Aceita 5 parâmetros: `p_status` (filtra por status), `p_data_inicio` e `p_data_fim` (filtro de período), `p_limite` e `p_pagina` (paginação). Quando os parâmetros são `NULL`, retorna todos os registros sem filtro. Internamente usa a função `fn_calcular_total_orcamento()` para calcular o valor de cada orçamento. A API PHP chama essa procedure com `CALL sp_dashboard_indicadores(NULL, NULL, NULL, 100, 1)`.

---

## Backend — PHP

### `includes/conexao.php`

Gerencia a conexão com o banco usando **PDO** (PHP Data Objects). Tenta conectar em múltiplos hosts (`localhost`, `127.0.0.1`, e um IP de rede) para funcionar em diferentes ambientes. Configura `ERRMODE_EXCEPTION` para que erros do banco virem exceções PHP capturáveis com `try/catch`. Se nenhuma conexão funcionar, exibe mensagem de erro e para a execução.

### `includes/header.php`

Template de cabeçalho reutilizado em todas as páginas. Detecta automaticamente se a página está na raiz do projeto ou dentro da pasta `/admin/` e ajusta todos os caminhos de CSS e links da navbar de forma dinâmica, usando `$pasta = basename(dirname($_SERVER['PHP_SELF']))`. Contém a navbar do Bootstrap com destaque na página ativa (`active`) detectado pelo `basename($_SERVER['PHP_SELF'])`.

### `includes/footer.php`

Template de rodapé. Inclui o JavaScript do Bootstrap (`bootstrap.bundle.min.js`) necessário para componentes interativos como o collapse na tela de orçamentos e os botões de fechar alertas.

### `api/dashboard.php`

Endpoint que retorna um JSON com os indicadores da dashboard. O processo é:
1. Consulta a view `vw_analise_faturamento_produtos` para obter dados de produtos ordenados por faturamento
2. Chama a Stored Procedure `sp_dashboard_indicadores` via `CALL` para obter os orçamentos
3. Usa `array_reduce` para somar o faturamento total
4. Usa `array_filter` para separar os produtos com estoque crítico
5. Usa `array_map` para transformar e limpar os dados antes de enviar
6. Retorna tudo como JSON com `json_encode`
7. Em caso de erro, retorna HTTP 500 com JSON de fallback para a interface não quebrar

### `index.php` — Dashboard

Página principal do sistema. Contém o HTML da dashboard com cards de indicadores e tabela de produtos. Não faz nenhuma consulta ao banco diretamente — todos os dados chegam via TypeScript que consome a API. O único include é o `header.php`.

### `servicos.php` — Catálogo

Exibe o catálogo de produtos cadastrados. Inclui filtro por preço máximo via formulário GET. Calcula automaticamente o preço com desconto PIX (10% de desconto) usando a função `calcularDescontoPix`. Valida se o catálogo está vazio ou com dados inconsistentes usando `validarCatalogo`. Exibe cards com imagem, preço original riscado e preço com desconto destacado.

### `orcamento.php` — Solicitação

Permite que visitantes solicitem orçamentos. Usa transação de banco (`beginTransaction`, `commit`, `rollBack`) para garantir que, se qualquer passo falhar, nada é salvo pela metade. O fluxo é: verifica se o cliente já existe pelo e-mail → se não, cadastra → cria o orçamento → cria o item do orçamento → faz commit. Exibe resumo com o valor estimado ao final.

---

## Área Administrativa — `admin/`

Os três CRUDs seguem o mesmo padrão de funcionamento:

1. **Verificação de ação na URL** (`$_GET['excluir']` ou `$_GET['editar']`) — detecta se o usuário clicou em excluir ou editar
2. **Processamento do formulário** (`$_SERVER['REQUEST_METHOD'] === 'POST'`) — decide entre INSERT e UPDATE com base em um campo oculto `id`
3. **Consulta de listagem** — busca todos os registros para exibir na tabela
4. **Renderização** — exibe alertas de sucesso/erro, formulário (em modo criação ou edição) e tabela

**Tratamento de erros de exclusão:** quando o banco retorna `PDOException` com código `23000` (violação de chave estrangeira), a interface captura esse erro e exibe uma mensagem em português explicando o motivo, como *"Não é possível excluir este cliente porque ele possui orçamentos vinculados"*, em vez de mostrar o erro técnico do banco.

### `admin/clientes.php`
CRUD completo de clientes. Campos: nome, telefone, e-mail, cidade. O e-mail é único no banco — se houver duplicata, a mensagem informa que o e-mail já está cadastrado.

### `admin/produtos.php`
CRUD completo de produtos. O badge de estoque muda de cor automaticamente: verde (normal), amarelo (crítico, ≤5 unidades), vermelho (sem estoque). Ao editar um produto, o trigger `BEFORE UPDATE` do banco ainda valida os valores — se o PHP tentar salvar um preço negativo, o banco bloqueia e o PHP captura o erro e exibe mensagem amigável.

### `admin/orcamentos.php`
Permite alterar o status de qualquer orçamento e excluir. O valor total de cada orçamento é calculado pela função SQL `fn_calcular_total_orcamento()` diretamente na query. Ao excluir um orçamento, os itens são removidos automaticamente pelo `CASCADE` do banco. A edição de status acontece inline com um formulário que expande na própria linha da tabela (componente `collapse` do Bootstrap).

---

## Frontend — TypeScript

### `assets/js/dashboard.ts` → compilado para `dist/dashboard.js`

O TypeScript é compilado para JavaScript com o comando `npm run build`. O arquivo compilado é `dist/dashboard.js`, que é o arquivo carregado pelo browser.

**Interfaces de tipagem (sem `any`):**

```typescript
type ProdutoDashboard = {
    nome_produto: string;
    categoria: string;
    faturamento_bruto_potencial: number;
    estoque_atual: number;
    status_estoque: string;
    volume_total_demandado: number;
};

type DashboardResponse = {
    total_orcamentos: number;
    valor_total: number;
    estoque_critico: number;
    produto_mais_vendido: string;
    produtos: ProdutoDashboard[];
};
```

Essas interfaces mapeiam 100% do JSON retornado pela API PHP. Sem nenhum uso de `any`.

**Separação de responsabilidades e modularidade:**

O código do TypeScript foi estruturado separando claramente a camada de rede/dados da camada de renderização de interface (DOM):
- **`buscarDadosDashboard()`**: função assíncrona dedicada exclusivamente a realizar a requisição HTTP (`fetch`) para a API PHP com cabeçalhos apropriados e tratamento de resposta.
- **`renderizarIndicadores()`**: função focada em processar cálculos e atualizar os cards de métricas/KPIs no DOM.
- **`renderizarTabelaProdutos()`**: função focada em iterar os produtos e renderizar dinamicamente as linhas da tabela HTML.
- **`renderizarErro()`**: função de fallback para atualizar a interface em caso de falha de conexão ou erro de execução.
- **`inicializarDashboard()`**: orquestrador que coordena a chamada da busca e dispara as renderizações com `try/catch`.

**Recursos de manipulação funcional:**
- `.reduce()` para somar o faturamento total de todos os produtos
- `.filter()` para separar apenas os produtos com `status_estoque === 'ESTOQUE CRÍTICO'`
- `.sort()` para ordenar por faturamento e identificar o produto destaque
- `.map()` para gerar as linhas HTML da tabela formatadas com `Intl.NumberFormat` em moeda R$
- Validações defensivas com `if (element)` antes de manipular o DOM
- Em caso de erro ou banco vazio, exibe "Nenhum dado registrado" em todos os campos, sem quebrar


---

## Fluxo completo de um dado

```
1. Usuário preenche formulário em orcamento.php
        ↓
2. PHP valida, abre transação, insere cliente + orçamento + itens no banco
        ↓
3. Banco executa triggers BEFORE UPDATE ao editar dados
        ↓
4. API dashboard.php consulta a View e chama a Stored Procedure
        ↓
5. PHP retorna JSON com os indicadores calculados
        ↓
6. TypeScript (dist/dashboard.js) faz fetch() assíncrono da API
        ↓
7. TypeScript processa com .reduce() .filter() .map() .sort()
        ↓
8. TypeScript atualiza o DOM com os valores formatados em R$
```

---

## Critérios técnicos atendidos

| Critério | Implementação |
|---|---|
| CTE analítica | `vw_analise_faturamento_produtos` em `04_views_e_ctes.sql` |
| View centralizada | `vw_orcamentos_detalhados` com 4 tabelas |
| Stored Procedure com CALL na API | `sp_dashboard_indicadores` em `05_stored_procedures.sql` + `api/dashboard.php` |
| Trigger BEFORE UPDATE | `trg_produtos_validar_valores_positivos_bu` + `trg_orcamento_itens_validar_quantidade_bu` |
| Função reutilizável | `fn_calcular_total_orcamento` em `03_functions.sql` |
| 3 CRUDs completos | `admin/clientes.php`, `admin/produtos.php`, `admin/orcamentos.php` |
| Regras de exclusão com mensagem clara | Captura de `PDOException` com `getCode() === '23000'` nos 3 CRUDs |
| Bootstrap 3+ componentes | Navbar, Cards, Table, Alert, Badge, Collapse, Form |
| Template header/footer | `includes/header.php` + `includes/footer.php` |
| Tipagem TypeScript sem `any` | Interfaces `ProdutoDashboard` e `DashboardResponse` |
| `.reduce()` | Cálculo de faturamento total no TypeScript |
| `.filter()` | Separação de estoque crítico no TypeScript |
| `.map()` | Geração de linhas da tabela formatadas em R$ |
| `.sort()` | Identificação do produto destaque por maior faturamento |
| Edge cases / banco vazio | "Nenhum dado registrado" em todos os pontos de falha |
| fetch + async/await + try/catch | Função `carregarDashboard` no TypeScript |
| Manipulação segura do DOM | `if (element)` antes de todo `document.getElementById` |
| Compilação TypeScript | `npm run build` → `dist/dashboard.js` sem erros |
