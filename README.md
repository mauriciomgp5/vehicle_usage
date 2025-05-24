# 🚗 Sistema de Registro de Uso de Veículos via WhatsApp  
### Laravel 12 + Evolution API

Este projeto permite o registro simples e eficiente do uso de veículos da empresa via WhatsApp, utilizando menus estáticos e integração com o Evolution API. Ideal para empresas que querem rastrear quem está com qual veículo, quando pegou e quando devolveu — tudo direto pelo zap.

---

## 🔧 Como funciona

### 📱 Menu WhatsApp

O colaborador interage com o bot via mensagens simples:

Menu:
1 - Utilizar Veículo
2 - Devolver Veículo


### ✅ Fluxo de Utilização

#### **1 - Utilizar Veículo**
- Usuário envia **"1"**
- Sistema pede a **placa**
- Usuário informa a placa
- Sistema pede **KM inicial**
- Após resposta, sistema registra o uso (**check-in**)

#### **2 - Devolver Veículo**
- Usuário envia **"2"**
- Sistema identifica o uso aberto vinculado ao número
- Pede **KM final**
- Após resposta, registra a devolução (**check-out**)

Tudo com mensagens diretas e **sem IA**. Simples, prático e funcional.

---

## 🚀 Tecnologias

- **Laravel 12** – API backend robusta
- **Docker** – Ambiente isolado e pronto para produção
- **Evolution API** – Canal oficial do WhatsApp
- **MySQL** – Armazenamento dos registros

---

## 📦 Próximos passos (roadmap)

- [ ] Validação automática de placa
- [ ] Anexar foto do painel do veículo
- [ ] Check-out com geolocalização (futuro)
- [ ] Painel administrativo com FilamentPHP

---

> Para dúvidas ou melhorias, entre em contato com o mantenedor do projeto 🚀
