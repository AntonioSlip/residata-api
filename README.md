# ResiData API

API REST desenvolvida em PHP para fornecer dados ao aplicativo mobile **ResiData**, permitindo a integração entre o sistema de monitoramento de residências em saúde e os clientes consumidores da plataforma.

---

## Objetivo

Centralizar o acesso aos dados do sistema por meio de serviços HTTP, desacoplando a camada de apresentação (Flutter) da camada de persistência de dados (MySQL).

---

## Tecnologias Utilizadas

- PHP 8
- MySQL
- PDO (PHP Data Objects)
- Composer
- DomPDF
- Apache (XAMPP)
- JSON
- REST API

---

## Arquitetura

A solução segue uma arquitetura baseada em APIs REST:

```text
Flutter Mobile
       │
       ▼
   ResiData API
       │
       ▼
     MySQL
```

A API é responsável por:

- Autenticação de usuários
- Consulta de indicadores
- Geração de dashboards
- Emissão de relatórios PDF
- Consulta de residentes
- Consulta de residências
- Consulta de preceptores
- Consulta de perfil do usuário

---

## Estrutura do Projeto

```text
residata-api/
│
├── api/
│   ├── login.php
│   ├── dashboard.php
│   ├── dashboard_pdf.php
│   ├── residentes.php
│   ├── residencias.php
│   ├── preceptores.php
│   └── perfil.php
│
├── includes/
│   └── db_config.php
│
├── vendor/
│
├── composer.json
├── composer.lock
└── README.md
```

---

## Principais Endpoints

### Login

```http
POST /api/login.php
```

Responsável pela autenticação dos usuários.

#### Exemplo de Requisição

```json
{
  "email": "usuario@email.com",
  "senha": "123456"
}
```

#### Exemplo de Resposta

```json
{
  "status": "sucesso",
  "usuario": {
    "id": 1,
    "nome": "Administrador",
    "email": "admin@email.com",
    "cargo": "Administrador Geral",
    "codemp": 1,
    "codfil": 1
  }
}
```

---

### Dashboard

```http
GET /api/dashboard.php
```

#### Parâmetros

```text
codemp
codfil
mes
idresidenc
```

#### Exemplo

```http
GET /api/dashboard.php?codemp=1&codfil=1&mes=2026-06&idresidenc=todas
```

Retorna indicadores institucionais, métricas consolidadas e URL para geração do PDF.

---

### Dashboard PDF

```http
GET /api/dashboard_pdf.php
```

#### Exemplo

```http
GET /api/dashboard_pdf.php?codemp=1&codfil=1&mes=2026-06&idresidenc=todas
```

Gera um relatório institucional em PDF utilizando a biblioteca DomPDF.

---

### Residências

```http
GET /api/residencias.php
```

Retorna a lista de programas de residência cadastrados.

---

### Residentes

```http
GET /api/residentes.php
```

Retorna os residentes vinculados à instituição.

---

### Preceptores

```http
GET /api/preceptores.php
```

Retorna os preceptores cadastrados.

---

### Perfil

```http
GET /api/perfil.php
```

Retorna os dados do usuário autenticado.

---

## Instalação

### 1. Clonar o projeto

```bash
git clone https://github.com/seu-usuario/residata-api.git
```

### 2. Entrar na pasta

```bash
cd residata-api
```

### 3. Instalar dependências

```bash
composer install
```

### 4. Configurar banco de dados

Editar o arquivo:

```text
includes/db_config.php
```

Informando:

```php
$host = "localhost";
$dbname = "residata_db";
$user = "root";
$password = "";
```

### 5. Executar no XAMPP

Copiar o projeto para:

```text
C:\xampp\htdocs\residata-api
```

A API ficará disponível em:

```text
http://localhost/residata-api/api
```

---

## Bibliotecas Externas

### DomPDF

Biblioteca utilizada para geração de relatórios PDF.

Documentação:

https://github.com/dompdf/dompdf

Instalação:

```bash
composer install
```

---

## Características do Backend

- Arquitetura baseada em APIs REST
- Comunicação via JSON
- Integração com banco MySQL
- Consultas utilizando PDO
- Geração dinâmica de relatórios PDF
- Controle de CORS para consumo externo
- Separação entre cliente mobile e camada de dados
- Reutilização das regras de negócio do sistema institucional
- Estrutura preparada para futura integração com aplicações web e mobile

---

## Diferencial Arquitetural

Inicialmente o sistema possuía integração direta entre a aplicação e o banco de dados.

Para melhorar a arquitetura da solução, foi criada uma camada intermediária de serviços (API REST), permitindo:

- Menor acoplamento entre frontend e banco de dados;
- Reutilização dos mesmos endpoints por diferentes clientes;
- Maior organização do código;
- Facilidade de manutenção;
- Escalabilidade para futuras integrações.

---

## Autores

Projeto desenvolvido para o sistema **ResiData**, plataforma de monitoramento institucional de programas de residência em saúde da Secretaria Estadual de Saúde de Pernambuco (SES-PE).

Desenvolvido como projeto acadêmico na área de Análise e Desenvolvimento de sistemas.
