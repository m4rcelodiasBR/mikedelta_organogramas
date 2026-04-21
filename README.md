# MikeDelta Organogramas

Módulo customizado para Drupal 10/11 desenvolvido para a criação de organogramas responsivos em ambientes corporativos e militares. Projetado para funcionar com 100% de seus recursos em ambiente local (Offline/Intranet), sem requisições externas.

## Funcionalidades
* Renderização visual avançada via `Treant.js` (Offline).
* Gerenciamento de árvore hierárquica por Drag n' Drop (TableDrag nativo do Drupal).
* Customização individual de cores (Gradientes) por setor ou militar via interface gráfica.
* Formulário blindado via Form API (Proteção CSRF/XSS) com validações rígidas de expressões regulares (RETELMA).
* Exclusão segura de ativos: Limpeza física automática de fotos do disco ao remover militares.
* Abordagem moderna seguindo o padrão Drupal 10/11 (Sem jQuery forçado, utilizando `core/once` e `drupalSettings`).

## Instalação
1. Copie a pasta `mikedelta_organogramas` para `web/modules/custom/`.
2. Certifique-se de que as dependências JavaScript (`treant.js` e `raphael.js`) estão presentes no diretório `assets/js/`.
3. Acesse o painel do Drupal em **Extensões** (`/admin/modules`) e ative o "MikeDelta Organogramas".
4. Limpe o cache do Drupal (`drush cr`).

## Uso
* **Cadastro/Gerenciamento:** Acesse `Administração > Estrutura > Organograma` (`/admin/structure/md-organograma`).
* **Visualização:** O gráfico gerado está disponível publicamente na rota `/md-organograma`.

## Arquitetura de Banco de Dados
A tabela `mikedelta_organograma_membros` utiliza a estrutura de *Adjacency List* para permitir níveis infinitos de subordinação visual, implementando indexação composta para otimização de consultas da árvore gerada.