# Documentação Completa da Estrutura do Sistema ADELSS

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Tecnologias Utilizadas](#tecnologias-utilizadas)
3. [Estrutura de Diretórios](#estrutura-de-diretórios)
4. [Módulos do Sistema](#módulos-do-sistema)
5. [Modelos (Models)](#modelos-models)
6. [Controladores (Controllers)](#controladores-controllers)
7. [Rotas (Routes)](#rotas-routes)
8. [Views (Interface)](#views-interface)
9. [Banco de Dados](#banco-de-dados)
10. [Relacionamentos](#relacionamentos)
11. [Funcionalidades Principais](#funcionalidades-principais)

---

## Visão Geral

O **ADELSS Sistema Web** é uma aplicação de gestão e administração desenvolvida em Laravel 10.x, similar ao sistema Enuves. O sistema foi projetado para gerenciar membros, departamentos, PGIs (Pequenos Grupos de Interesse), ensino, finanças e agenda de uma organização.

---

## Tecnologias Utilizadas

- **Backend**: Laravel 10.x
- **PHP**: 8.1+
- **Banco de Dados**: MySQL/MariaDB
- **Frontend**: Bootstrap 5.3, jQuery
- **Editor de Texto**: Quill.js 2.0.2
- **Gráficos**: Chart.js
- **Calendário**: FullCalendar
- **Ícones**: Font Awesome 6.4, Boxicons

---

## Estrutura de Diretórios

```
adelss/
├── app/
│   ├── Console/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Agenda/
│   │   │   ├── Ensino/
│   │   │   └── Financial/
│   │   └── Middleware/
│   ├── Models/
│   └── Providers/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
│   ├── css/
│   ├── img/
│   ├── js/
│   ├── storage/ (link simbólico)
│   └── vendor/
├── resources/
│   ├── lang/
│   └── views/
│       ├── agenda/
│       ├── departments/
│       ├── ensino/
│       ├── financial/
│       ├── layouts/
│       ├── member-roles/
│       ├── members/
│       └── pgis/
├── routes/
│   ├── api.php
│   ├── console.php
│   └── web.php
├── storage/
│   └── app/
│       └── public/
│           ├── class-files/
│           ├── financial/
│           ├── members/
│           └── pgis/
└── vendor/
```

---

## Módulos do Sistema

### 1. **Membros** ✅
Gerenciamento completo de membros da organização.

### 2. **Departamentos** ✅
Organização hierárquica de departamentos.

### 3. **Cargos (Member Roles)** ✅
Definição de cargos/funções dos membros.

### 4. **PGIs (Pequenos Grupos de Interesse)** ✅
Gestão de grupos menores com reuniões e membros.

### 5. **Ensino** ✅
- Estudos
- Escolas
- Turmas
- Disciplinas
- Aulas
- Frequência

### 6. **Financeiro** ✅
- Transações (Receitas e Despesas)
- Categorias
- Contas
- Contatos
- Centros de Custos
- Relatórios

### 7. **Agenda** ✅
- Calendário de eventos
- Categorias de eventos
- Gestão de eventos

---

## Modelos (Models)

### Membros e Organização

#### `Member`
- **Tabela**: `members`
- **Campos principais**:
  - `name`, `email`, `phone`, `gender`, `marital_status`
  - `birth_date`, `photo_url`, `status`
  - `cpf`, `rg`, `address`, `city`, `state`, `zip_code`
  - `membership_date`, `notes`
  - `department_id`, `pgi_id`, `role_id`
- **Relacionamentos**:
  - `belongsTo(Department::class)` - Departamento principal
  - `belongsToMany(Department::class)` - Múltiplos departamentos
  - `belongsTo(Pgi::class)` - PGI
  - `belongsTo(MemberRole::class)` - Cargo
  - `belongsToMany(Turma::class)` - Turmas como aluno
  - `hasMany(FinancialTransaction::class)` - Transações financeiras

#### `Department`
- **Tabela**: `departments`
- **Relacionamentos**:
  - `hasMany(Member::class)` - Membros do departamento
  - `belongsToMany(Member::class)` - Membros (muitos-para-muitos)
  - `hasMany(DepartmentMember::class)` - Membros com cargos

#### `MemberRole`
- **Tabela**: `member_roles`
- **Campos**: `name`, `description`

#### `Pgi`
- **Tabela**: `pgis`
- **Campos principais**:
  - `name`, `description`, `leader_id`
  - `logo_url`, `banner_url`
- **Relacionamentos**:
  - `belongsToMany(Member::class)` - Membros do PGI
  - `hasMany(Meeting::class)` - Reuniões

#### `DepartmentMember`
- **Tabela**: `department_members`
- **Campos**: `member_id`, `department_id`, `department_role_id`

#### `DepartmentRole`
- **Tabela**: `department_roles`
- **Campos**: `name`, `description`

### Ensino

#### `Study`
- **Tabela**: `studies`
- **Campos**: `name`, `content`, `featured_image`, `attachment`, `attachment_name`, `send_notification`

#### `School`
- **Tabela**: `schools`
- **Campos**: `name`, `address`, `phone`, `email`, `description`

#### `Turma`
- **Tabela**: `classes`
- **Campos**: `name`, `school_id`, `schedule`, `status`, `description`
- **Relacionamentos**:
  - `belongsTo(School::class)` - Escola
  - `belongsToMany(Member::class)` - Alunos
  - `hasMany(Discipline::class)` - Disciplinas
  - `hasMany(Lesson::class)` - Aulas
  - `hasMany(ClassFile::class)` - Arquivos

#### `Discipline`
- **Tabela**: `disciplines`
- **Campos**: `name`, `class_id`, `description`
- **Relacionamentos**:
  - `belongsTo(Turma::class)` - Turma
  - `belongsToMany(Member::class)` - Professores

#### `Lesson`
- **Tabela**: `lessons`
- **Campos**: `title`, `class_id`, `discipline_id`, `date`, `content`
- **Relacionamentos**:
  - `belongsTo(Turma::class)` - Turma
  - `belongsTo(Discipline::class)` - Disciplina
  - `hasMany(LessonAttendance::class)` - Frequência

#### `LessonAttendance`
- **Tabela**: `lesson_attendances`
- **Campos**: `lesson_id`, `member_id`, `status` (presente, ausente, justificado)

#### `ClassFile`
- **Tabela**: `class_files`
- **Campos**: `class_id`, `discipline_id`, `title`, `type`, `file_path`, `content`, `external_url`, `description`

### Financeiro

#### `FinancialTransaction`
- **Tabela**: `financial_transactions`
- **Campos principais**:
  - `type` (receita/despesa)
  - `transaction_date`, `description`, `amount`
  - `is_paid`, `due_date`, `status`
  - `member_id`, `received_from_other`, `contact_id`
  - `category_id`, `account_id`, `cost_center_id`
  - `payment_type`, `document_number`, `notes`, `competence_date`
- **Relacionamentos**:
  - `belongsTo(Member::class)` - Membro (receitas)
  - `belongsTo(FinancialContact::class)` - Contato (despesas)
  - `belongsTo(FinancialCategory::class)` - Categoria
  - `belongsTo(FinancialAccount::class)` - Conta
  - `belongsTo(FinancialCostCenter::class)` - Centro de custo
  - `hasMany(FinancialTransactionAttachment::class)` - Anexos

#### `FinancialCategory`
- **Tabela**: `financial_categories`
- **Campos**: `name`, `type` (receita/despesa), `description`, `color`

#### `FinancialAccount`
- **Tabela**: `financial_accounts`
- **Campos**: `name`, `type`, `balance`, `description`

#### `FinancialContact`
- **Tabela**: `financial_contacts`
- **Campos**: `name`, `type`, `email`, `phone`, `address`, `category_id`
- **Relacionamentos**:
  - `belongsTo(FinancialContactCategory::class)` - Categoria

#### `FinancialContactCategory`
- **Tabela**: `financial_contact_categories`
- **Campos**: `name`, `description`

#### `FinancialCostCenter`
- **Tabela**: `financial_cost_centers`
- **Campos**: `name`, `description`

#### `FinancialTransactionAttachment`
- **Tabela**: `financial_transaction_attachments`
- **Campos**: `transaction_id`, `file_name`, `file_path`

### Agenda

#### `Event`
- **Tabela**: `events`
- **Campos principais**:
  - `title`, `description`, `start_date`, `start_time`
  - `end_date`, `end_time`, `all_day`
  - `recurrence`, `visibility`, `location`
  - `category_id`
- **Relacionamentos**:
  - `belongsTo(EventCategory::class)` - Categoria

#### `EventCategory`
- **Tabela**: `event_categories`
- **Campos**: `name`, `color`, `description`

### Reuniões

#### `Meeting`
- **Tabela**: `meetings`
- **Campos**: `pgi_id`, `date`, `time`, `location`, `notes`
- **Relacionamentos**:
  - `belongsTo(Pgi::class)` - PGI
  - `hasMany(MeetingAttendance::class)` - Presenças

#### `MeetingAttendance`
- **Tabela**: `meeting_attendances`
- **Campos**: `meeting_id`, `member_id`, `status` (presente, ausente, justificado)

---

## Controladores (Controllers)

### Membros e Organização

- **`MemberController`**: CRUD completo de membros
  - `index()`, `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()`

- **`DepartmentController`**: Gestão de departamentos
  - `index()`, `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()`

- **`MemberRoleController`**: Gestão de cargos
  - `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`

- **`PgiController`**: Gestão de PGIs
  - `index()`, `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()`
  - `attachMembers()`, `detachMember()`
  - `updateLogo()`, `updateBanner()`

- **`MeetingController`**: Gestão de reuniões de PGIs
  - `create()`, `store()`, `edit()`, `update()`, `destroy()`

### Ensino

- **`EstudosController`**: Gestão de estudos
  - `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`

- **`EscolasController`**: Gestão de escolas
  - `index()`, `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()`

- **`TurmasController`**: Gestão de turmas
  - `index()`, `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()`
  - `storeStudents()`, `removeStudent()`
  - `storeDiscipline()`, `updateDiscipline()`, `destroyDiscipline()`
  - `storeLesson()`, `showLesson()`, `updateLesson()`, `destroyLesson()`
  - `storeFile()`, `destroyFile()`
  - `frequencyMonthly()`

### Financeiro

- **`SummaryController`**: Dashboard financeiro
  - `index()`

- **`TransactionController`**: Gestão de transações
  - `index()`, `show()`, `edit()`, `update()`, `destroy()`
  - `storeReceita()`, `storeDespesa()`
  - `receipt()`, `duplicate()`
  - `updateDescription()`
  - `export()`, `import()`

- **`CategoryController`**: Gestão de categorias
  - CRUD completo

- **`AccountController`**: Gestão de contas
  - CRUD completo

- **`ContactController`**: Gestão de contatos
  - CRUD completo
  - `storeCategory()`

- **`CostCenterController`**: Gestão de centros de custo
  - CRUD completo

- **`ReportController`**: Relatórios financeiros
  - `index()`
  - `cashFlowExtract()`, `cashFlowRevenuesExpenses()`
  - `revenuesDailyExtract()`, `revenuesAnnualSummary()`
  - `expensesDailyExtract()`, `expensesAnnualSummary()`
  - `revenuesExpensesByCategory()`

### Agenda

- **`CalendarioController`**: Visualização do calendário
  - `index()`

- **`EventController`**: API de eventos (FullCalendar)
  - `index()`, `store()`, `show()`, `update()`, `destroy()`

- **`EventosController`**: Listagem de eventos
  - `index()`

- **`EventCategoryController`**: Gestão de categorias
  - `store()`

---

## Rotas (Routes)

### Rotas Principais

```php
// Dashboard
GET  / → dashboard

// Membros
Resource: /members

// Departamentos
Resource: /departments

// Cargos
Resource: /member-roles

// PGIs
Resource: /pgis
POST /pgis/{pgi}/members/attach
DELETE /pgis/{pgi}/members/{member}/detach
POST /pgis/{pgi}/logo
POST /pgis/{pgi}/banner
POST /pgis/{pgi}/meetings
PUT /pgis/{pgi}/meetings/{meeting}
DELETE /pgis/{pgi}/meetings/{meeting}

// Financeiro
GET  /financial/summary
GET  /financial/transactions
POST /financial/transactions/receita
POST /financial/transactions/despesa
Resource: /financial/transactions
GET  /financial/transactions/{transaction}/receipt
POST /financial/transactions/{transaction}/duplicate
PUT  /financial/transactions/{transaction}/description
GET  /financial/reports
GET  /financial/reports/cash-flow/extract
GET  /financial/reports/revenues/daily-extract
GET  /financial/reports/expenses/daily-extract
Resource: /financial/categories
Resource: /financial/accounts
Resource: /financial/contacts
Resource: /financial/cost-centers

// Ensino
Resource: /ensino/estudos
Resource: /ensino/escolas
Resource: /ensino/turmas
POST /ensino/turmas/{turma}/students
DELETE /ensino/turmas/{turma}/students/{member}
POST /ensino/turmas/{turma}/disciplines
PUT /ensino/turmas/{turma}/disciplines/{discipline}
DELETE /ensino/turmas/{turma}/disciplines/{discipline}
POST /ensino/turmas/{turma}/lessons
GET /ensino/turmas/{turma}/lessons/{lesson}
PUT /ensino/turmas/{turma}/lessons/{lesson}
DELETE /ensino/turmas/{turma}/lessons/{lesson}
POST /ensino/turmas/{turma}/files
DELETE /ensino/turmas/{turma}/files/{file}
GET /ensino/turmas/{turma}/reports/frequency-monthly

// Agenda
GET  /agenda/calendario
GET  /agenda/eventos
GET  /agenda/events (API)
POST /agenda/events
PUT /agenda/events/{event}
DELETE /agenda/events/{event}
POST /agenda/categories
```

---

## Views (Interface)

### Layouts

- **`layouts/porto.blade.php`**: Layout principal do sistema
  - Sidebar com navegação
  - Header com busca e perfil
  - Área de conteúdo

### Membros

- `members/index.blade.php` - Listagem com filtros
- `members/create.blade.php` - Formulário de criação
- `members/edit.blade.php` - Formulário de edição
- `members/show.blade.php` - Visualização detalhada

### Departamentos

- `departments/index.blade.php`
- `departments/create.blade.php`
- `departments/edit.blade.php`
- `departments/show.blade.php`

### Cargos

- `member-roles/index.blade.php`
- `member-roles/create.blade.php`
- `member-roles/edit.blade.php`

### PGIs

- `pgis/index.blade.php`
- `pgis/create.blade.php`
- `pgis/edit.blade.php`
- `pgis/show.blade.php`

### Ensino

- `ensino/estudos/index.blade.php`
- `ensino/estudos/create.blade.php`
- `ensino/estudos/edit.blade.php`
- `ensino/escolas/index.blade.php`
- `ensino/escolas/create.blade.php`
- `ensino/turmas/index.blade.php`
- `ensino/turmas/create.blade.php`
- `ensino/turmas/show.blade.php`

### Financeiro

- `financial/summary.blade.php` - Dashboard
- `financial/transactions/index.blade.php` - Listagem de transações
- `financial/transactions/receipt.blade.php` - Recibo
- `financial/categories/index.blade.php`
- `financial/accounts/index.blade.php`
- `financial/contacts/index.blade.php`
- `financial/cost-centers/index.blade.php`
- `financial/reports/index.blade.php` - Índice de relatórios
- `financial/reports/cash-flow-extract.blade.php`
- `financial/reports/cash-flow-revenues-expenses.blade.php`
- `financial/reports/revenues-daily-extract.blade.php`
- `financial/reports/revenues-annual-summary.blade.php`
- `financial/reports/expenses-daily-extract.blade.php`
- `financial/reports/expenses-annual-summary.blade.php`
- `financial/reports/revenues-expenses-by-category.blade.php`

### Agenda

- `agenda/calendario/index.blade.php` - Calendário FullCalendar
- `agenda/eventos/index.blade.php` - Listagem de eventos

---

## Banco de Dados

### Tabelas Principais

#### Membros e Organização
- `members` - Membros da organização
- `departments` - Departamentos
- `department_members` - Relação muitos-para-muitos membros-departamentos
- `department_roles` - Cargos dentro de departamentos
- `member_roles` - Cargos gerais dos membros
- `pgis` - Pequenos Grupos de Interesse
- `meetings` - Reuniões de PGIs
- `meeting_attendances` - Presenças em reuniões

#### Ensino
- `studies` - Estudos
- `schools` - Escolas
- `classes` - Turmas
- `class_students` - Alunos das turmas
- `disciplines` - Disciplinas
- `discipline_teachers` - Professores das disciplinas
- `lessons` - Aulas
- `lesson_attendances` - Frequência nas aulas
- `class_files` - Arquivos das turmas

#### Financeiro
- `financial_categories` - Categorias financeiras
- `financial_accounts` - Contas bancárias
- `financial_contacts` - Contatos (fornecedores, etc.)
- `financial_contact_categories` - Categorias de contatos
- `financial_cost_centers` - Centros de custo
- `financial_transactions` - Transações (receitas/despesas)
- `financial_transaction_attachments` - Anexos das transações

#### Agenda
- `event_categories` - Categorias de eventos
- `events` - Eventos do calendário

---

## Relacionamentos

### Diagrama de Relacionamentos Principais

```
Member
├── belongsTo → Department (departamento principal)
├── belongsTo → Pgi
├── belongsTo → MemberRole
├── belongsToMany → Department (múltiplos departamentos)
├── belongsToMany → Turma (como aluno)
└── hasMany → FinancialTransaction

Department
├── hasMany → Member (membros principais)
├── belongsToMany → Member (via department_members)
└── hasMany → DepartmentMember

Pgi
├── belongsToMany → Member
└── hasMany → Meeting

FinancialTransaction
├── belongsTo → Member (receitas)
├── belongsTo → FinancialContact (despesas)
├── belongsTo → FinancialCategory
├── belongsTo → FinancialAccount
├── belongsTo → FinancialCostCenter
└── hasMany → FinancialTransactionAttachment

Turma
├── belongsTo → School
├── belongsToMany → Member (alunos)
├── hasMany → Discipline
├── hasMany → Lesson
└── hasMany → ClassFile

Lesson
├── belongsTo → Turma
├── belongsTo → Discipline
└── hasMany → LessonAttendance

Event
└── belongsTo → EventCategory
```

---

## Funcionalidades Principais

### 1. Gestão de Membros
- ✅ Cadastro completo com dados pessoais
- ✅ Upload de fotos
- ✅ Filtros por status, gênero, busca
- ✅ Associação com departamentos e PGIs
- ✅ Histórico de transações financeiras
- ✅ Visualização detalhada do perfil

### 2. Gestão de Departamentos
- ✅ CRUD completo
- ✅ Membros podem pertencer a múltiplos departamentos
- ✅ Cargos específicos por departamento

### 3. Gestão de PGIs
- ✅ CRUD completo
- ✅ Upload de logo e banner
- ✅ Gestão de membros do grupo
- ✅ Reuniões com controle de presença

### 4. Módulo de Ensino
- ✅ Estudos com editor rico (Quill.js)
- ✅ Gestão de escolas
- ✅ Turmas com alunos
- ✅ Disciplinas e professores
- ✅ Aulas com conteúdo
- ✅ Controle de frequência
- ✅ Arquivos por turma
- ✅ Relatórios de frequência mensal

### 5. Módulo Financeiro
- ✅ Dashboard com gráficos (Chart.js)
- ✅ Transações (Receitas e Despesas)
- ✅ Categorias, Contas, Contatos, Centros de Custo
- ✅ Status: Pago, A Receber, A Pagar
- ✅ Anexos de documentos
- ✅ Recibos
- ✅ Duplicação de transações
- ✅ Exportação/Importação
- ✅ Relatórios:
  - Fluxo de caixa
  - Extrato diário de receitas/despesas
  - Resumo anual
  - Por categoria

### 6. Módulo de Agenda
- ✅ Calendário interativo (FullCalendar)
- ✅ Eventos com recorrência
- ✅ Categorias de eventos
- ✅ Visibilidade (público/privado)
- ✅ Localização

### 7. Recursos Gerais
- ✅ Soft Deletes (exclusão lógica)
- ✅ Upload de arquivos com link simbólico
- ✅ Validação de formulários
- ✅ Mensagens de sucesso/erro
- ✅ Filtros e buscas
- ✅ Paginação
- ✅ Interface responsiva

---

## Configurações Importantes

### Storage
O sistema utiliza o link simbólico do Laravel para armazenar arquivos:
```bash
php artisan storage:link
```

Arquivos são armazenados em:
- `storage/app/public/members/photos/` - Fotos de membros
- `storage/app/public/pgis/logos/` - Logos de PGIs
- `storage/app/public/pgis/banners/` - Banners de PGIs
- `storage/app/public/studies/images/` - Imagens de estudos
- `storage/app/public/studies/attachments/` - Anexos de estudos
- `storage/app/public/financial/transactions/attachments/` - Anexos financeiros
- `storage/app/public/class-files/` - Arquivos de turmas

### Cache
Para limpar caches:
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## Observações Técnicas

1. **Soft Deletes**: A maioria dos modelos utiliza `SoftDeletes` para exclusão lógica
2. **Validação**: Validação robusta em todos os formulários
3. **Relacionamentos**: Sistema bem estruturado com relacionamentos Eloquent
4. **Segurança**: CSRF protection ativado
5. **Uploads**: Validação de tipos e tamanhos de arquivo
6. **Interface**: Bootstrap 5.3 com tema Porto

---

## Próximas Melhorias Sugeridas

- [ ] Sistema de autenticação e autorização
- [ ] Notificações
- [ ] Dashboard com estatísticas gerais
- [ ] Exportação de relatórios em PDF
- [ ] API REST
- [ ] Testes automatizados
- [ ] Módulo de Patrimônio
- [ ] Módulo de Mídias

---

**Última atualização**: Janeiro 2026
**Versão do Sistema**: 1.0.0
**Laravel**: 10.x
**PHP**: 8.1+
