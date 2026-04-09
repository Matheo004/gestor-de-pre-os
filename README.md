# 📈 Gestor de Economia & Comparador de Preços

Sistema web completo para monitoramento de gastos e análise histórica de preços, desenvolvido para facilitar a gestão financeira pessoal e economia doméstica.

🔗 **[Acesse o sistema ao vivo aqui](https://gestaoeconomia.infinityfree.me)**

---

## 🚀 Funcionalidades Principais
* **Busca Inteligente:** Preenchimento automático de valores e locais baseado no histórico de compras (via AJAX/PHP).
* **Gráficos Dinâmicos:** Visualização de tendências de gastos por dia através de gráficos de linha interativos (Chart.js).
* **Comparativo de Preços:** Identificação automática do menor preço registrado para cada insumo.
* **Exportação Profissional:** Geração de relatórios formatados para Excel e PDF.
* **Segurança de Dados:** Implementação de *Prepared Statements* para proteção contra ataques de SQL Injection.

## 🛠️ Tecnologias Utilizadas
* **Backend:** PHP 8.x
* **Banco de Dados:** MySQL
* **Frontend:** HTML5, CSS3 (Modern UI), JavaScript (ES6)
* **Bibliotecas:** - [Chart.js](https://www.chartjs.org/) (Gráficos)
  - [FontAwesome](https://fontawesome.com/) (Ícones)

## 📋 Como funciona
1. **Cadastro:** Ao digitar um produto já cadastrado, o sistema sugere o último valor e local de compra.
2. **Análise:** O dashboard principal exibe o acumulado de gastos diários.
3. **Comparação:** Selecione um item na lista para ver o histórico dos últimos 3 preços e onde foi encontrado o melhor valor.

## 🛡️ Segurança e Boas Práticas
Este projeto segue padrões profissionais de desenvolvimento:
- Uso de **Prepared Statements** em todas as queries SQL para garantir a integridade dos dados.
- Interface **Responsiva** adaptada para uso em dispositivos móveis e desktop.
- Arquitetura que separa a lógica de busca dinâmica (`busca_automacao.php`) da interface principal.

---
Desenvolvido por **Matheo Serrone Ribeiro Barbosa** como parte do portfólio de Gestão de TI.
