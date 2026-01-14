# Instruções de Instalação - ADELSS Sistema Web

## Passo a Passo para Instalação

### 1. Instalar Dependências do Composer

```bash
composer install
```

### 2. Configurar o Arquivo .env

Copie o arquivo `.env.example` para `.env`:

```bash
cp .env.example .env
```

Ou no Windows PowerShell:
```powershell
Copy-Item .env.example .env
```

Depois, gere a chave da aplicação:
```bash
php artisan key:generate
```

### 3. Configurar o Banco de Dados

Edite o arquivo `.env` e configure as informações do banco de dados:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=adelss
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

**Importante:** Certifique-se de que o banco de dados `adelss` já existe. Caso contrário, crie-o manualmente:

```sql
CREATE DATABASE adelss CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Executar as Migrações

Execute as migrações para criar as tabelas no banco de dados:

```bash
php artisan migrate
```

**Ordem das Migrações (executada automaticamente):**
1. `departments` - Cria a tabela de departamentos
2. `pgis` - Cria a tabela de PGIs
3. `members` - Cria a tabela de membros
4. Adiciona foreign keys entre as tabelas

### 5. Criar Link Simbólico para Storage

Para que as fotos dos membros sejam acessíveis publicamente:

```bash
php artisan storage:link
```

### 6. Configurar Permissões (Linux/Mac)

Se estiver em Linux ou Mac, configure as permissões:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 7. Iniciar o Servidor

Inicie o servidor de desenvolvimento:

```bash
php artisan serve
```

O sistema estará disponível em: `http://localhost:8000`

## Estrutura do Módulo de Membros

O módulo de Membros está completo e inclui:

### Funcionalidades Implementadas:

- ✅ **Listagem de Membros** (`/members`)
  - Tabela com paginação
  - Filtros: busca, status, gênero
  - Ordenação por nome, data de cadastro ou status
  - Visualização de foto, nome, email, telefone, status, departamento e PGI

- ✅ **Cadastro de Membros** (`/members/create`)
  - Formulário completo com validação
  - Campos: nome, email, telefone, gênero, data de nascimento, status, CPF, RG, endereço completo, foto, observações
  - Upload de foto
  - Validação de dados no servidor

- ✅ **Edição de Membros** (`/members/{id}/edit`)
  - Formulário pré-preenchido com dados existentes
  - Atualização de foto (opcional)
  - Todas as validações do cadastro

- ✅ **Visualização de Membros** (`/members/{id}`)
  - Página detalhada com todas as informações do membro
  - Exibição de foto, dados pessoais, documentos, endereço, associações
  - Cálculo automático de idade

- ✅ **Exclusão de Membros** (`/members/{id}`)
  - Soft delete (exclusão lógica)
  - Remoção automática da foto ao excluir

### Modelo de Dados

O modelo `Member` inclui:
- Relacionamentos com `Department` e `Pgi`
- Accessor para cálculo de idade
- Scopes para filtros (active, byGender, search)
- Soft deletes

## Próximos Passos

Após a instalação, você pode:

1. Acessar o dashboard: `http://localhost:8000`
2. Navegar para o módulo de Membros: `http://localhost:8000/members`
3. Começar a cadastrar membros

## Problemas Comuns

### Erro ao executar migrações

Se receber erro sobre foreign keys, certifique-se de que:
- As migrações estão sendo executadas na ordem correta
- O banco de dados existe e está acessível
- As credenciais no `.env` estão corretas

### Fotos não aparecem

Verifique se:
- O link simbólico foi criado: `php artisan storage:link`
- A pasta `storage/app/public` tem permissão de escrita
- A URL em `config/filesystems.php` está correta

### Erro 404 nas rotas

Execute:
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

## Suporte

Para mais informações, consulte a documentação do Laravel: https://laravel.com/docs


