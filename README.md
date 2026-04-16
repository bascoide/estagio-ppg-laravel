# PPG • Gestão de Protocolos de Estágio

Aplicação web desenvolvida em Laravel para apoiar a gestão de protocolos, documentos de estágio e validações administrativas no contexto académico do ISCAP/PPG.

O sistema permite que alunos se registem, preencham formulários dinâmicos, gerem documentos em PDF ou DOCX, submetam versões finais assinadas e acompanhem o processo de validação. A área administrativa inclui gestão de cursos, utilizadores, documentos, relatórios e registos de atividade.

---

## Principais funcionalidades

### Área do utilizador
- Registo e autenticação
- Verificação de conta por email
- Seleção de documentos por curso e tipo de curso
- Preenchimento dinâmico de formulários
- Geração e download de documentos em PDF e DOCX
- Upload do documento final assinado em formato PDF

### Área administrativa
- Gestão de administradores, utilizadores e cursos
- Upload, ativação e desativação de modelos de documentos
- Aceitação, rejeição e validação de protocolos
- Gestão de emails presidenciais para validação
- Visualização de documentos pendentes e validados
- Geração de relatórios por professor
- Exportação de informação para Excel
- Consulta de logs de atividade

---

## Tecnologias utilizadas

- PHP 8.2+
- Laravel 12
- Blade Templates
- MySQL / MariaDB ou SQLite
- PHPMailer
- Dompdf
- PhpWord
- PhpSpreadsheet
- Tailwind/CSS estático para interface

---

## Requisitos

Antes de iniciar, garante que tens instalado:

- PHP 8.2 ou superior
- Composer
- Servidor web local, como XAMPP
- MySQL/MariaDB ou SQLite
- Extensões PHP normalmente usadas pelo Laravel, como OpenSSL, Mbstring, PDO e Fileinfo

> Nota: o projeto pode funcionar com a folha de estilos já gerada, pelo que o Node.js não é obrigatório para a execução básica.

---

## Instalação

### 1. Clonar o projeto

```bash
git clone <url-do-repositorio>
cd estagio-ppg-laravel
```

### 2. Instalar dependências PHP

```bash
composer install
```

### 3. Criar o ficheiro de ambiente

```bash
copy .env.example .env
```

Se estiveres a usar PowerShell:

```powershell
Copy-Item .env.example .env
```

### 4. Configurar a aplicação

Gerar a chave da aplicação:

```bash
php artisan key:generate
```

Configurar a base de dados no ficheiro .env.

#### Exemplo com MySQL / XAMPP

```env
APP_NAME=PPG
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=estagio_ppg
DB_USERNAME=root
DB_PASSWORD=
```

> O ficheiro .env.example vem com SQLite por omissão. Se estiveres em XAMPP, atualiza estas variáveis antes das migrações.

### 5. Executar migrações

```bash
php artisan migrate
```

### 6. Criar um administrador local para testes

```bash
php artisan db:seed --class=AdminSeeder
```

> Recomendado apenas em ambiente de desenvolvimento.

### 7. Iniciar o servidor

```bash
php artisan serve
```

Depois abre no navegador:

```text
http://127.0.0.1:8000
```

---

## Configuração de email

O sistema envia notificações de verificação, aprovação, rejeição e validação. Para isso, configura um servidor SMTP no ficheiro .env.

Exemplo:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.exemplo.com
MAIL_PORT=587
MAIL_USERNAME=utilizador
MAIL_PASSWORD=palavra-passe
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=no-reply@exemplo.com
MAIL_FROM_NAME="PPG"
```

Se não configurares SMTP, algumas funcionalidades de email podem não funcionar corretamente.

---

## Fluxo principal do sistema

1. O utilizador cria conta e confirma o email.
2. Seleciona o tipo de documento aplicável ao seu curso.
3. Preenche os campos dinâmicos do formulário.
4. O sistema gera o protocolo e permite exportação em PDF ou DOCX.
5. A administração revê o documento e decide se aceita, rejeita ou valida.
6. Após aprovação, o utilizador submete a versão final assinada.
7. O sistema mantém histórico e logs das ações relevantes.

---

## Estrutura resumida do projeto

```text
app/
 ├─ Http/Controllers/   # Lógica da aplicação
 ├─ Models/             # Modelos Eloquent
 └─ Services/           # Serviços auxiliares, como envio de email

routes/
 └─ web.php             # Rotas principais da aplicação

resources/views/        # Interfaces Blade

database/
 ├─ migrations/         # Estrutura da base de dados
 └─ seeders/            # Seeders para ambiente local
```

---

## Comandos úteis

```bash
composer dev
composer test
php artisan migrate:fresh
php artisan config:clear
php artisan route:list
```

---

## Observações

- O projeto foi estruturado para uso académico e administrativo.
- Algumas funcionalidades dependem de envio de email e de ficheiros PDF válidos.
- Para ambiente Windows, a utilização com XAMPP é adequada e compatível com MySQL/MariaDB.

---

## Melhorias futuras sugeridas

- Adicionar testes automáticos para fluxos críticos
- Melhorar paginação e filtros na área administrativa
- Integrar armazenamento externo para documentos
- Reforçar auditoria e permissões por perfil

---

## Licença

Projeto para contexto académico/institucional. Ajusta a licença conforme as regras da organização ou da equipa responsável.
