# 🚗 Sistema de Gestão de Veículos

Sistema completo de gestão de veículos desenvolvido com **Laravel 11** e **Filament v3**, oferecendo controle total sobre frota, usuários e uso de veículos com interface moderna e funcionalidades avançadas.

![Laravel](https://img.shields.io/badge/Laravel-11.x-red?style=flat&logo=laravel)
![Filament](https://img.shields.io/badge/Filament-3.3-orange?style=flat&logo=php)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue?style=flat&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange?style=flat&logo=mysql)
![Redis](https://img.shields.io/badge/Redis-7.0-red?style=flat&logo=redis)
![Docker](https://img.shields.io/badge/Docker-Enabled-blue?style=flat&logo=docker)

## 📋 Índice

- [Características](#-características)
- [Funcionalidades](#-funcionalidades)
- [Tecnologias](#-tecnologias)
- [Instalação](#-instalação)
- [Configuração](#-configuração)
- [Uso](#-uso)
- [Screenshots](#-screenshots)
- [Estrutura](#-estrutura-do-projeto)
- [API](#-api-endpoints)
- [Contribuição](#-contribuição)
- [Licença](#-licença)

## ✨ Características

### 🎯 **Interface Moderna e Profissional**
- **100% em Português Brasileiro**
- Interface construída com **Filament v3**
- Design responsivo e intuitivo
- Tema moderno com cores semânticas

### 🔐 **Segurança Avançada**
- Autenticação baseada em **FilamentUser contract**
- Controle de acesso por usuário ativo/inativo
- Sistema de permissões (supervisor/usuário comum)
- Hash seguro de senhas com validação

### 📊 **Relatórios e Analytics**
- Estatísticas em tempo real
- Filtros avançados com 12+ opções
- Exportação de dados em CSV
- Cálculos automáticos de distância e duração

### ⚡ **Performance Otimizada**
- Eager loading nos relacionamentos
- Cache Redis integrado
- Polling em tempo real
- Paginação inteligente

## 🚀 Funcionalidades

### 🚗 **Gestão de Veículos**
- ✅ CRUD completo de veículos
- ✅ Controle de status (Ativo, Inativo, Manutenção)
- ✅ Rastreamento de quilometragem
- ✅ Controle de licenciamento com alertas
- ✅ Filtros por marca, modelo, ano e status
- ✅ Validações automatizadas

### 📋 **Controle de Uso de Veículos**
- ✅ Registro de saída e entrada
- ✅ Controle de quilometragem inicial/final
- ✅ Cálculo automático de distância percorrida
- ✅ Controle de tempo de uso com alertas
- ✅ Finalização rápida diretamente na tabela
- ✅ Status visual (Em uso, Finalizado, Atrasado)

#### 🔍 **Filtros Avançados do Uso de Veículos**
1. **Múltipla seleção** de veículos e usuários
2. **Status do veículo** (ativo, inativo, manutenção)
3. **Status do uso** (em uso, finalizado, atrasado >24h)
4. **Período de saída** com data de/até
5. **Distância percorrida** com valores min/max
6. **Períodos rápidos** (hoje, ontem, semana, mês, ano)
7. **Busca na finalidade** com texto livre
8. **Uso prolongado** (identificação de usos >8h)
9. **Registros sem KM** para auditoria
10. **Uso em fim de semana** para controle especial

### 👥 **Gestão de Usuários**
- ✅ CRUD completo com validações
- ✅ Upload de avatar com fallback automático
- ✅ Controle de departamento e cargo
- ✅ Sistema supervisor/usuário comum
- ✅ Ativação/desativação de contas
- ✅ Redefinição de senhas
- ✅ Contagem de usos por usuário

### 📊 **Relatórios e Exportações**
- ✅ Exportação CSV com dados completos
- ✅ Estatísticas integradas
- ✅ Ações em lote para usuários
- ✅ Filtros persistentes na sessão
- ✅ Ordenação personalizável

## 🛠 Tecnologias

### **Backend**
- **Laravel 11.x** - Framework PHP robusto
- **Filament v3.3** - Admin panel moderno
- **MySQL 8.0** - Banco de dados principal
- **Redis 7.0** - Cache e sessões

### **Frontend**
- **Livewire v3** - Componentes reativos
- **Alpine.js** - JavaScript reativo
- **Tailwind CSS** - Framework CSS utility-first
- **Heroicons** - Ícones consistentes

### **Infraestrutura**
- **Docker & Docker Compose** - Containerização
- **PHP 8.2+** - Linguagem moderna
- **Nginx/Apache** - Servidor web

## 📦 Instalação

### **Pré-requisitos**
- Docker e Docker Compose
- Git

### **1. Clone o repositório**
```bash
git clone https://github.com/seu-usuario/vehicle-management.git
cd vehicle-management
```

### **2. Configure o ambiente**
```bash
# Copie o arquivo de ambiente
cp .env.example .env

# Configure as variáveis do banco de dados
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=vehicle_usage
DB_USERNAME=root
DB_PASSWORD=root_password

# Configure o Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### **3. Suba os containers**
```bash
# Construa e inicie os containers
docker compose up -d

# Acesse o container da aplicação
docker compose exec app bash
```

### **4. Configure a aplicação**
```bash
# Instale as dependências
composer install

# Gere a chave da aplicação
php artisan key:generate

# Execute as migrations
php artisan migrate

# Execute os seeders (opcional)
php artisan db:seed --class=VehicleSeeder

# Crie um usuário admin
php artisan make:filament-user
```

## ⚙️ Configuração

### **Usuário Administrador**
Para acesso em produção, o model User implementa o contrato `FilamentUser`:

```php
public function canAccessPanel(Panel $panel): bool
{
    // Para produção, use validação mais restritiva:
    // return str_ends_with($this->email, '@suaempresa.com') && $this->hasVerifiedEmail();
    
    // Para desenvolvimento:
    return $this->is_active ?? true;
}
```

### **Configurações de Produção**
1. **Otimização do Filament:**
   ```bash
   php artisan filament:optimize
   ```

2. **Cache da aplicação:**
   ```bash
   php artisan optimize
   ```

3. **Configuração de storage:**
   - Configure o disco `FILAMENT_FILESYSTEM_DISK=s3` para produção
   - Use storage privado para uploads de avatares

## 🎮 Uso

### **Acesso ao Sistema**
- **URL:** `http://localhost:8001/admin`
- **Login:** Use as credenciais criadas com `make:filament-user`

### **Navegação Principal**
1. **🚗 Veículos** - Gestão completa da frota
2. **📋 Uso de Veículos** - Controle de utilização
3. **👥 Usuários** - Gestão de pessoas

### **Fluxo de Trabalho Típico**

#### **1. Cadastrar Veículos**
- Acesse "Veículos" → "Novo Veículo"
- Preencha: placa, marca, modelo, ano, quilometragem
- Configure status e data de licenciamento

#### **2. Registrar Uso**
- Acesse "Uso de Veículos" → "Novo Uso"
- Selecione veículo e usuário
- Defina finalidade e quilometragem inicial
- Sistema registra automaticamente data/hora de saída

#### **3. Finalizar Uso**
- Na lista de usos, clique em "Finalizar Uso"
- Informe quilometragem final e observações
- Sistema calcula automaticamente a distância percorrida

#### **4. Relatórios e Filtros**
- Use os filtros avançados para análises específicas
- Exporte dados em CSV para relatórios externos
- Visualize estatísticas em tempo real

## 📸 Screenshots

### Dashboard Principal
Interface moderna com navegação intuitiva e estatísticas em tempo real.

### Gestão de Veículos
- Listagem com filtros por status, marca e modelo
- Formulário organizado em seções
- Controle de licenciamento com alertas visuais

### Controle de Uso
- 12 filtros avançados para análises específicas
- Status visual: em uso (amarelo), finalizado (verde), atrasado (vermelho)
- Ações rápidas: finalizar uso, exportar dados

### Gestão de Usuários
- Upload de avatar com fallback automático
- Controle de departamentos e cargos
- Ações: ativar/desativar, redefinir senha

## 📁 Estrutura do Projeto

```
vehicle-management/
├── app/
│   ├── Filament/
│   │   ├── Resources/
│   │   │   ├── VehicleResource.php         # 🚗 Gestão de veículos
│   │   │   ├── VehicleUsageResource.php    # 📋 Controle de uso
│   │   │   └── UserResource.php            # 👥 Gestão de usuários
│   │   └── Providers/
│   │       └── Filament/
│   │           └── AdminPanelProvider.php  # Configuração do painel
│   ├── Models/
│   │   ├── Vehicle.php                     # Model de veículos
│   │   ├── VehicleUsage.php               # Model de uso
│   │   └── User.php                       # Model de usuários (Filament-ready)
│   └── Casts/
│       └── MoneyCast.php                  # Cast para valores monetários
├── database/
│   ├── migrations/                        # Migrações do banco
│   └── seeders/                          # Dados de exemplo
├── docker-compose.yml                    # Configuração Docker
├── Dockerfile                           # Imagem da aplicação
└── README.md                           # Esta documentação
```

## 🔧 API Endpoints

O sistema é principalmente baseado em interface web, mas os seguintes endpoints estão disponíveis:

### **Autenticação**
- `GET /admin/login` - Página de login
- `POST /admin/login` - Processar login
- `POST /admin/logout` - Logout

### **Recursos Principais**
- `GET /admin/vehicles` - Lista de veículos
- `GET /admin/vehicle-usages` - Lista de usos
- `GET /admin/users` - Lista de usuários

### **Exportações**
- `POST /admin/vehicle-usages/export` - Exportar usos em CSV
- `POST /admin/users/export` - Exportar usuários em CSV

## 🤝 Contribuição

### **Como Contribuir**
1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

### **Padrões de Código**
- Siga o **PSR-12** para PHP
- Use **camelCase** para métodos e **snake_case** para banco de dados
- Mantenha os comentários em **português brasileiro**
- Escreva testes para novas funcionalidades

### **Estrutura de Commit**
```
tipo(escopo): descrição

feat(vehicles): adiciona filtro por marca
fix(users): corrige validação de email
docs(readme): atualiza documentação
```

## 📋 Roadmap

### **Próximas Funcionalidades**
- [ ] **Dashboard avançado** com gráficos de uso
- [ ] **Relatórios PDF** automatizados
- [ ] **Notificações** por email/SMS
- [ ] **API REST** completa
- [ ] **App mobile** para motoristas
- [ ] **Integração GPS** para rastreamento
- [ ] **Manutenção preventiva** automatizada
- [ ] **Multas e infrações** associadas

### **Melhorias Técnicas**
- [ ] **Testes automatizados** (PHPUnit/Pest)
- [ ] **CI/CD pipeline** com GitHub Actions
- [ ] **Monitoramento** com Sentry
- [ ] **Backup automatizado** do banco
- [ ] **Logs estruturados** com ELK Stack

## 🐛 Problemas Conhecidos

- Upload de arquivos grandes pode falhar (limite PHP)
- Filtros complexos podem ser lentos com muitos dados
- Cache Redis precisa ser limpo após updates importantes

## 📞 Suporte

### **Documentação**
- [Laravel 11](https://laravel.com/docs/11.x)
- [Filament v3](https://filamentphp.com/docs/3.x)
- [Docker](https://docs.docker.com/)

### **Contato**
- **Issues:** Use o sistema de issues do GitHub
- **Discussões:** Para dúvidas gerais, use as Discussions
- **Email:** Para questões sensíveis, contate diretamente

## 📝 Licença

Este projeto está licenciado sob a **Licença MIT** - veja o arquivo [LICENSE](LICENSE) para detalhes.

---

## 🙏 Agradecimentos

- **Laravel Team** - Framework excepcional
- **Filament Team** - Admin panel incrível
- **Comunidade PHP** - Suporte e inspiração
- **Docker** - Containerização simplificada

---

<div align="center">

**Desenvolvido com ❤️ usando Laravel & Filament**

⭐ **Gostou do projeto? Dê uma estrela!** ⭐

</div>
