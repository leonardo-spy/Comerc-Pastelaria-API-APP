# Comerc API RESTful - Pastelaria

## Sobre o Projeto

API RESTful para gerenciamento de Clientes, Produtos e Pedidos de uma pastelaria, desenvolvida em PHP 8.2 com Laravel 12. A aplicação suporta operações CRUDL completas, com validação, relacionamentos e envio de e-mail automático após criação de pedidos.  
A aplicação está dockerizada para facilitar a execução local e em ambientes de produção.

---

## Tecnologias Utilizadas

- PHP 8.2  
- Laravel 12
- MySQL  
- Docker & Docker Compose  
- PHPUnit  
- Swagger para documentação interativa da API  
- Sistema de envio de e-mails integrado (SMTP configurável)

---

## Funcionalidades

- CRUDL para Clientes, Produtos e Pedidos  
- Validações completas, com tratamento estruturado dos dados utilizando DTOs (Data Transfer Objects) via pacote Spati, e regras de negócio (e-mail único, soft deletes)  
- Relacionamento: Cliente pode ter múltiplos Pedidos, Pedido pode ter múltiplos Produtos  
- Envio automático de e-mail para cliente após pedido  
- Soft delete para registros  
- Testes unitários para principais funcionalidades  
- Documentação Swagger acessível via rota `/api/documentation`  

---

## Como Rodar a Aplicação

### Pré-requisitos

- Docker e Docker Compose instalados  

### Passos

1. Clone o repositório:  
```bash
git clone https://github.com/leonardo-spy/Comerc-Pastelaria-API-APP.git
cd Comerc-Pastelaria-API-APP
```

2. Copie e configure o arquivo `.env` com as variáveis necessárias:  
```bash
cp .env.example .env
```

3. Suba os containers (se estiver rodando dentro de sistema Linux, o compose é `docker compose` ao inves de `docker-compose`):  
```bash
docker-compose up -d --build 
```

4. Acesse o terminal do container:  
```bash
docker-compose exec app bash
```

5. Execute a instalação das blibiotecas pelo compose:  
```bash
composer install --no-interaction --prefer-dist --optimize-autoloader
```

6. Crie um Link simbólico do Storage:  
```bash
php artisan storage:link
```

7. Gere a chave da aplicação e atualize a configuração:  
```bash
php artisan key:generate
php artisan config:cache
```

8. Execute as migrations:  
```bash
php artisan migrate
```

9. Execute os seeders:  
```bash
php artisan db:seed
```

10. Garanta permissão para a documentação da API:  
```bash
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

11. Acesse a documentação interativa da API via:  
```
http://localhost:8080/api/documentation
```

---

## Documentação Interativa (Swagger UI)

A documentação interativa da API pode ser acessada via Swagger UI pelo endpoint `/api/documentation`.  
Ela permite visualizar e testar todos os endpoints com facilidade.

![CRUDL Swagger UI](https://github.com/user-attachments/assets/c62d7a5f-3c35-4c5c-8be8-6e4dcd1b8d5b)

---

## Rotas da API

### Clientes

| Método | Endpoint              | Descrição                       |
|--------|-----------------------|--------------------------------|
| GET    | /api/clients          | Lista todos os clientes          |
| POST   | /api/clients          | Cria um novo cliente             |
| GET    | /api/clients/{id}     | Detalhes de cliente específico   |
| PUT    | /api/clients/{id}     | Atualiza um cliente              |
| DELETE | /api/clients/{id}     | Soft delete de cliente           |

### Produtos

| Método | Endpoint              | Descrição                       |
|--------|-----------------------|--------------------------------|
| GET    | /api/products         | Lista todos os produtos          |
| POST   | /api/products         | Cria um novo produto             |
| GET    | /api/products/{id}    | Detalhes de produto específico   |
| PUT    | /api/products/{id}    | Atualiza um produto              |
| DELETE | /api/products/{id}    | Soft delete de produto           |

### Pedidos

| Método | Endpoint              | Descrição                       |
|--------|-----------------------|--------------------------------|
| GET    | /api/orders           | Lista todos os pedidos           |
| POST   | /api/orders           | Cria um novo pedido              |
| GET    | /api/orders/{id}      | Detalhes de pedido específico    |
| PUT    | /api/orders/{id}      | Atualiza um pedido               |
| DELETE | /api/orders/{id}      | Soft delete de pedido            |

---

## Testes Unitários

Para rodar os testes (dentro do container da aplicação):  :  
```bash
php artisan test
```
Ou se preferir rodar pelo PHPUnit:
```bash
./vendor/bin/phpunit
```

---

## Exemplo de E-mail Enviado Após Criação do Pedido

Após a criação de um pedido, a API envia automaticamente um e-mail para o cliente com os detalhes do pedido.

Segue um exemplo do conteúdo do e-mail recebido:
![compra](https://github.com/user-attachments/assets/2d9bb256-1cd5-49bb-907c-a61c3c425d2e)
>OBS: Esse email foi criado para teste dessa aplicação, após a análise, ele será desativado