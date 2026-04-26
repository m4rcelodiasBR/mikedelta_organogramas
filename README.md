# MikeDelta Organogramas

Um módulo nativo para Drupal 10/11 desenhado para a criação, gestão e exibição visual de organogramas dinâmicos. Criado com foco em ambientes corporativos e de intranet restrita da Marinha do Brasil (RECIM), o módulo opera de forma **100% offline**, não dependendo de conexões com a internet externa ou CDNs para renderizar os gráficos ou exportar os documentos. Este Módulo foi desenvolvido por Marcelo Dias da Silva

## 🚀 Funcionalidades Principais

* **Renderização Dinâmica:** Geração automática de árvores hierárquicas utilizando a biblioteca *Treant.js*.
* **Operação 100% Offline:** Todos os recursos (scripts, estilos e ferramentas de exportação) estão embutidos localmente.
* **Campos Especializados:** Suporte estruturado para hierarquias complexas com campos dedicados para Título/Cargo, Nome, Função na estrutura, Retelma/Telefone e E-mail.
* **Ordenação Drag-and-Drop:** Interface administrativa intuitiva que permite organizar a hierarquia da árvore e o peso de cada membro simplesmente arrastando e soltando as linhas - indentação.
* **Personalização Visual:** * Definição de cores individuais para o fundo do cartão e tags de função. Cálculo automático de contraste para garantir que o texto (preto ou branco) seja sempre legível de acordo com a cor de fundo escolhida.
* **Produtividade:** Sistema de herança de estilo. Ao adicionar vários membros em sequência, o formulário herda automaticamente a paleta de cores do último membro cadastrado.
* **Exportação Nativa para PNG:** Ferramenta integrada de "câmera" (via `html2canvas`) que permite aos usuários baixarem o organograma atualizado em alta resolução, preservando cores, linhas e ícones vetoriais.
* **Segurança e UX:** Formulários protegidos nativamente contra CSRF e desenhados para alta usabilidade, com botões de ação flutuantes em tabelas extensas.

---

## 🛠️ Requisitos
* **Drupal:** 10/11
* **PHP:** 8.1 ou superior
* **Banco de Dados:** PostreSQL ou MySQL
* **Dependências:** Nenhuma

---

## 📦 Instalação

1. Faça o download e coloque a pasta `mikedelta_organogramas` dentro do diretório `../modules/` da sua instalação Drupal.
2. Acesse o painel de Extensões do Drupal (`/admin/modules`).
3. Localize o **MikeDelta Organogramas** e clique em Instalar.
4. Limpe os caches do sistema em `Configurações > Desenvolvimento > Desempenho > Limpar todos os caches`.

---

## 📝 Histórico de Versões (Changelog)

### [1.0.0] - Lançamento Oficial
*Lançamento inicial unificando o motor de renderização base com as ferramentas de exportação e UX avançadas.*

**Destaques da Versão:**
* **Integração Treant.js:** Motor principal de renderização de árvore estrutural ativado e estilizado.
* **Exportação de Imagem:** Adicionado o recurso de exportação visual do organograma gerado para PNG de forma autônoma e off-line.
* **Herança de Cores:** Implementada a lógica de autopreenchimento de estilos no `MembroForm.php` para acelerar o cadastro em massa, espelhando o último membro inserido.
* **Experiência do Administrador:** Duplicação estratégica dos botões de ação (Salvar/Cancelar) no topo e no rodapé das listas de ordenação, facilitando a interação em departamentos com listagem grande de membros.

## 🖥️ Downloads

- [mikedelta_organogramas-1.0.0.zip](https://github.com/m4rcelodiasBR/mikedelta_organogramas/archive/refs/tags/v1.1.0.zip)
- [mikedelta_organogramas-1.0.0.tar.gz](https://github.com/m4rcelodiasBR/mikedelta_organogramas/archive/refs/tags/v1.1.0.tar.gz)

---
*Desenvolvido sob arquitetura MVC adaptada para o ecosistema Drupal.*
