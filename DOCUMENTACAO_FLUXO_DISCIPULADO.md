# Tutorial Completo — Fluxo do Módulo de Discipulado

Este documento descreve o fluxo completo do módulo de Discipulado do ADELSS, passando por cada submenu e suas funcionalidades.

---

## Visão Geral da Estrutura

O módulo de Discipulado organiza-se em torno de **Ciclos**, dentro dos quais membros são vinculados e acompanhados por **discipuladores**. O fluxo lógico é:

```
CICLO → MEMBROS (vinculação) → ENCONTROS + PROPÓSITOS + FEEDBACKS + INDICADORES
```

- **Ciclo**: Período/turma de discipulado (ex: "Ciclo 2025", "Discipulado Iniciantes").
- **Membro**: Vinculação de um membro da igreja a um ciclo, com discipulador designado.
- **Encontros**: Registro de cada reunião presencial ou online com o discípulo.
- **Propósitos**: Metas e objetivos definidos com o discípulo (jejum, oração, leitura).
- **Feedbacks**: Anotações e observações sobre o acompanhamento.
- **Indicadores**: Métricas configuráveis para avaliar o progresso (espiritual, material).

---

## Menu Lateral — Submenus

| Submenu | Ícone | Descrição |
|---------|-------|-----------|
| **Ciclos** | ciclo | Criar e gerenciar ciclos de discipulado |
| **Membros** | usuário | Vincular membros aos ciclos e ver detalhes |
| **Encontros** | calendário | Registrar e visualizar encontros |
| **Indicadores** | gráfico | Configurar indicadores de acompanhamento |
| **Propósitos** | alvo | Definir e acompanhar propósitos/metas |
| **Feedbacks** | mensagem | Registrar feedbacks sobre os discípulos |
| **Dashboard** | painel | Visão geral para discipulador e liderança |

---

## 1. Ciclos

**Rota:** `/discipleship/cycles`  
**Objetivo:** Organizar o discipulado em períodos ou turmas.

### 1.1 Listar Ciclos
- Exibe ciclos **ativos** ou **encerrados** (filtro por status).
- Cada ciclo mostra: nome, descrição, datas, quantidade de membros.
- Ações: **Ver**, **Editar**, **Excluir**.

### 1.2 Criar Ciclo
- **Nome** (obrigatório): Ex.: "Ciclo 2025", "Discipulado Iniciantes".
- **Descrição** (opcional).
- **Data de Início** (obrigatório).
- **Data de Fim** (opcional).
- **Status**: Ativo ou Encerrado.

### 1.3 Ver Ciclo
- Informações do ciclo.
- Tabela de **membros vinculados** com discipulador, status e data de início.
- Botão **Adicionar Membro** para vincular novos membros ao ciclo.

### 1.4 Editar Ciclo
- Mesmos campos do cadastro. Permite alterar status para Encerrado quando o ciclo terminar.

---

## 2. Membros

**Rota:** `/discipleship/members`  
**Objetivo:** Vincular membros da igreja aos ciclos de discipulado e acompanhar cada um.

### 2.1 Listar Membros
- Filtros: **Ciclo** e **Status** (ativo/concluído).
- Lista membros com ciclo, discipulador, status e data de início.
- Ações: **Ver detalhes**, **Editar**, **Excluir**.

### 2.2 Criar (Vincular Membro)
- **Ciclo** (obrigatório).
- **Membro** (obrigatório): escolher da lista de membros cadastrados.
- **Discipulador** (opcional): usuário que fará o acompanhamento.
- **Status**: Ativo, Concluído ou Pausado.
- **Data de Início** (obrigatório).
- **Data de Fim** (opcional).

### 2.3 Ver Detalhes do Membro em Discipulado
Tela principal de acompanhamento individual, com:

- **Informações do vínculo:** Membro, ciclo, discipulador, status, datas.
- **Histórico de Encontros:** Tabela com data, tipo (presencial/online), propósitos vinculados, assuntos. Botão **Ver** em cada encontro.
- **Gráfico Comparativo:** (quando há 2+ encontros) Evolução da área espiritual: Oração (min/dia), Jejum (h/semana), Leitura (cap/dia).
- **Propósitos:** Lista de propósitos do membro com status e botão **Ver**.

Ações disponíveis: **Novo Encontro**, **Criar propósito**, **Editar vínculo**.

### 2.4 Editar Vínculo
- Permite alterar ciclo, membro, discipulador, status e datas.

---

## 3. Encontros

**Rota:** `/discipleship/meetings`  
**Objetivo:** Registrar cada reunião (presencial ou online) com o discípulo.

### 3.1 Listar Encontros
- Filtro por **membro em discipulado** (discipleship_member_id).
- Tabela com data, tipo, assuntos e ações.

### 3.2 Registrar Encontro
- **Membro em Discipulado** (obrigatório).
- **Data** (obrigatório).
- **Tipo:** Presencial ou Online.
- **Assuntos Tratados** (texto livre).
- **Propósitos a vincular:** Checkboxes dos propósitos em andamento do membro — vincula os propósitos discutidos neste encontro.
- **Questionário Área Espiritual:**
  - **Oração:** Tempo por dia (0–60 min ou +1h), como são as orações, observações.
  - **Jejum:** Horas/semana, tipo (Total/Parcial/Nenhum), com propósito (Sim/Não), observações.
  - **Leitura Bíblica:** Capítulos/dia, se estuda os capítulos, observações.
- **Próximo Passo** (texto livre).
- **Observações Privadas** (não compartilhadas com o discípulo).

Também há botão **Cadastrar Propósito** para criar novo propósito antes de salvar o encontro.

### 3.3 Ver Encontro
- Informações do encontro (data, tipo, membro, ciclo).
- **Propósitos Vinculados:** Card com os propósitos discutidos e links para **Ver** cada um.
- **Assuntos Tratados.**
- **Questionário Área Espiritual** (oração, jejum, leitura).
- **Próximo Passo.**
- **Observações Privadas.**

### 3.4 Editar Encontro
- Mesmos campos do cadastro. Permite alterar propósitos vinculados e demais informações.

---

## 4. Indicadores

**Rota:** `/discipleship/indicators`  
**Objetivo:** Configurar indicadores usados para avaliar o progresso dos discípulos.

### 4.1 Listar Indicadores
- Lista indicadores cadastrados com nome, tipo (espiritual/material), status ativo e ordem.

### 4.2 Criar Indicador
- **Nome** (obrigatório).
- **Tipo:** Espiritual ou Material.
- **Ativo** (checkbox).
- **Ordem** (número para ordenação).

### 4.3 Editar Indicador
- Mesmos campos. Não há tela de visualização isolada.

### 4.4 Registrar Valor do Indicador
- É possível registrar um **valor** (0 a 5) para um indicador em relação a um membro em discipulado, com data e observação. A interface para isso pode estar na listagem de indicadores ou em outro ponto de entrada (ex.: na página do membro).

---

## 5. Propósitos

**Rota:** `/discipleship/goals`  
**Objetivo:** Definir e acompanhar propósitos/metas com o discípulo (jejum, oração, leitura bíblica etc.).

### 5.1 Listar Propósitos
- Filtros diversos (membro, ciclo, status).
- Lista propósitos com descrição, tipo, prazo, status.

### 5.2 Criar Propósito
- **Membro em Discipulado** (obrigatório).
- **Tipo:** Espiritual ou Material.
- **Descrição** (obrigatório).
- **Prazo** (opcional).
- **Status:** Em andamento, Pausado ou Concluído.
- **Observação** (texto rico).
- **Quantidade de dias** (para jejum).
- **Restrições**, **Tipo de jejum**, **Horas de jejum**, **Alimentos retirados** (jejum parcial).
- **Períodos de oração por dia**, **Minutos por período**.
- **Livro da Bíblia**, **Capítulos por dia** (leitura).

### 5.3 Ver Propósito
- Detalhes completos do propósito.
- Informações de jejum, oração e leitura quando aplicável.
- Botão para **Gerar PDF** do propósito.

### 5.4 Editar Propósito
- Mesmos campos. Permite alterar status (ex.: concluir o propósito).

### 5.5 Gerar PDF
- Gera PDF formatado do propósito para impressão ou compartilhamento.

---

## 6. Feedbacks

**Rota:** `/discipleship/feedbacks`  
**Objetivo:** Registrar observações e avaliações sobre o acompanhamento do discípulo.

### 6.1 Listar Feedbacks
- Filtro por **membro em discipulado**.
- Lista feedbacks com data, autor, visibilidade e trecho do conteúdo.

### 6.2 Criar Feedback
- **Membro em Discipulado** (obrigatório).
- **Visibilidade:** Discipulador, Pastor ou Admin — define quem pode ver.
- **Conteúdo** (obrigatório, texto livre).

### 6.3 Editar Feedback
- Permite alterar membro, visibilidade e conteúdo. Não há tela de visualização isolada (apenas listagem e edição).

---

## 7. Dashboard

**Rotas:**
- `/discipleship/dashboard/discipulador` — visão do discipulador
- `/discipleship/dashboard/lideranca` — visão da liderança

### 7.1 Dashboard do Discipulador
- **Discípulos:** Lista de membros em discipulado do usuário logado.
- **Alertas:** Sem encontro há X dias, propósitos vencidos etc.
- **Últimos Encontros:** Lista resumida dos encontros mais recentes.

### 7.2 Dashboard da Liderança
- **Estatísticas:** Total em discipulado, ciclos ativos.
- **Membros sem acompanhamento.**
- **Indicadores críticos.**
- **Evolução por ciclo.**

---

## Fluxo Recomendado de Uso

1. **Configuração inicial**
   - Criar **Indicadores** (ex.: oração, leitura, compromisso).
   - Criar um **Ciclo** de discipulado (ex.: "Ciclo 2025").

2. **Início do ciclo**
   - Em **Membros**, vincular cada discípulo ao ciclo e definir o discipulador.
   - Em **Propósitos**, criar os propósitos iniciais para cada membro.

3. **Acompanhamento contínuo**
   - Em **Encontros**, registrar cada reunião com o discípulo.
   - Vincular **propósitos** aos encontros quando forem discutidos.
   - Preencher o **questionário de área espiritual** em cada encontro.

4. **Avaliação e registro**
   - Registrar **valores dos indicadores** quando houver avaliações.
   - Registrar **feedbacks** quando necessário (observações, impressões).

5. **Visão geral**
   - Usar o **Dashboard** para acompanhar alertas e estatísticas.
   - Na página do **Membro**, ver histórico de encontros e gráfico comparativo.

---

## Permissões

O acesso ao módulo e submenus depende de permissões:

- `discipleship.view` / `discipleship.manage` — módulo e Dashboard
- `discipleship.cycles.view` / `discipleship.cycles.manage` — Ciclos
- `discipleship.members.view` / `discipleship.members.manage` — Membros
- `discipleship.meetings.view` / `discipleship.meetings.manage` — Encontros
- `discipleship.indicators.view` / `discipleship.indicators.manage` — Indicadores
- `discipleship.goals.view` / `discipleship.goals.manage` — Propósitos
- `discipleship.feedbacks.view` / `discipleship.feedbacks.manage` — Feedbacks
