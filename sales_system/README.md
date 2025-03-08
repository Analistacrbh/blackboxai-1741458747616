# Sistema de Vendas (Sales System)

Sistema completo de gestão de vendas com PDV, controle financeiro e relatórios.

## Requisitos do Sistema

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Extensões PHP:
  - PDO
  - PDO_MySQL
  - mbstring
  - json
  - gd
  - zip
- Composer (Gerenciador de dependências)

## Instalação

1. Clone o repositório:
```bash
git clone https://github.com/seu-usuario/sales-system.git
cd sales-system
```

2. Instale as dependências via Composer:
```bash
composer install
```

3. Configure o banco de dados:
   - Crie um banco de dados MySQL
   - Copie o arquivo `config/database.example.php` para `config/database.php`
   - Edite `config/database.php` com suas credenciais do banco de dados

4. Importe a estrutura do banco de dados:
```bash
mysql -u seu_usuario -p seu_banco < db/schema.sql
```

5. Configure as permissões dos diretórios:
```bash
chmod -R 777 logs/
chmod -R 777 backups/
chmod -R 777 uploads/
```

## Estrutura do Sistema

- `/config` - Arquivos de configuração
- `/db` - Scripts do banco de dados
- `/includes` - Classes e funções do sistema
- `/modules` - Módulos do sistema
  - `/sales` - Módulo de vendas e PDV
  - `/financial` - Módulo financeiro
  - `/users` - Gerenciamento de usuários
  - `/settings` - Configurações do sistema
- `/logs` - Logs do sistema
- `/backups` - Backups do banco de dados
- `/uploads` - Arquivos enviados
- `/vendor` - Dependências (gerenciado pelo Composer)

## Módulos Principais

### PDV (Ponto de Venda)
- Interface intuitiva para vendas
- Busca rápida de produtos
- Múltiplas formas de pagamento
- Impressão de recibos

### Financeiro
- Controle de contas a receber
- Relatórios financeiros
- Gráficos de desempenho
- Controle de parcelas

### Relatórios
- Relatórios de vendas
- Relatórios financeiros
- Exportação em PDF e Excel
- Gráficos e análises

### Usuários
- Níveis de acesso:
  - Administrador
  - Superusuário
  - Usuário comum
- Controle de permissões
- Log de atividades

## Níveis de Acesso

### Administrador
- Acesso total ao sistema
- Gerenciamento de usuários
- Configurações do sistema
- Backups do banco de dados

### Superusuário
- Acesso aos módulos operacionais
- Relatórios e análises
- Gerenciamento de vendas

### Usuário
- Acesso ao PDV
- Visualização de relatórios básicos

## Segurança

- Autenticação segura
- Proteção contra SQL Injection
- Validação de dados
- Logs de atividades
- Backup automático

## Manutenção

### Backups
- Backups automáticos diários
- Armazenamento compactado
- Restauração simplificada

### Logs
- Log de atividades dos usuários
- Log de erros do sistema
- Rotação automática de logs

## Suporte

Para suporte técnico ou dúvidas:
- Email: suporte@exemplo.com
- Telefone: (00) 0000-0000

## Licença

Este sistema é proprietário e seu uso é restrito aos termos da licença.

## Versão

Versão atual: 1.0.0

## Changelog

### 1.0.0 - (Data do lançamento)
- Lançamento inicial do sistema
- Implementação do PDV
- Módulo financeiro
- Sistema de relatórios
- Gerenciamento de usuários
