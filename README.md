# ADELSS Sistema Web

Sistema de gestão e administração desenvolvido em Laravel, similar ao Enuves.

## Tecnologias

- **Laravel 10.x**
- **PHP 8.1+**
- **MySQL**
- **Bootstrap 5.3**
- **Font Awesome 6.4**

## Instalação

### Pré-requisitos

- PHP >= 8.1
- Composer
- MySQL 5.7+ ou MariaDB 10.3+
- Node.js e NPM (opcional, para assets)

### Passos

1. Clone o repositório ou extraia os arquivos
2. Instale as dependências do Composer:
```bash
composer install
```

3. Configure o arquivo `.env`:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure as informações do banco de dados no arquivo `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=adelss
DB_USERNAME=root
DB_PASSWORD=sua_senha
```

5. Execute as migrações:
```bash
php artisan migrate
```

6. Crie o link simbólico para storage:
```bash
php artisan storage:link
```

7. Inicie o servidor de desenvolvimento:
```bash
php artisan serve
```

O sistema estará disponível em `http://localhost:8000`

## Módulos

### Membros ✅

Módulo completo de gestão de membros com:
- Cadastro, edição e exclusão
- Upload de fotos
- Filtros avançados (status, gênero, busca)
- Visualização detalhada
- Associação com departamentos e PGIs

### Próximos Módulos

- Departamentos
- PGIs (Pequenos Grupos de Interesse)
- Ensino
- Financeiro
- Patrimônio
- Agenda
- Mídias

## Estrutura do Banco de Dados

### Tabelas Principais

- `members` - Membros da organização
- `departments` - Departamentos
- `pgis` - Pequenos Grupos de Interesse

## Desenvolvimento

Este projeto segue o padrão MVC do Laravel e as melhores práticas de desenvolvimento web.

## Licença

MIT


