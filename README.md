# MikeDelta Organogramas

Módulo Drupal desenvolvido para gerenciar e renderizar organogramas hierárquicos interativos, suportando múltiplas divisões e departamentos. Desenhado para atender padrões a normas em vigor na Marinha do Brasil.

## 🚀 Funcionalidades

* **Múltiplos Organogramas:** Crie infinitas estruturas departamentais independentes, cada uma com sua URL dedicada.
* **Dashboard Centralizado:** Painel de administração unificado para gerenciamento rápido.
* **Visualização Responsiva:** Renderização baseada em SVG e CSS, garantindo funcionamento 100% offline.
* **Gestão de Hierarquia (Drag n' Drop):** Definição de laços de subordinação (Superior Imediato) de forma visual arrastando as linhas na interface administrativa.
* **Customização Visual Avançada:**
    * Formatação individual de cores.
    * Controle de empilhamento de subordinados (layout horizontal ou vertical).
    * Ajuste do ponto de ancoragem das ramificações.
* **Exportação/Importação Segura:**
    * Backups granulares individuais ou globais em formato JSON.
    * Lógica de remapeamento automático de chaves estrangeiras (IDs) durante a importação para evitar colisões no banco de dados.
    * Processamento Batch (barra de progresso) para arquivos grandes.
* **Privacidade e Limpeza:** A exclusão de um membro remove rigorosamente do disco os arquivos de foto associados e trata a orfandade de subordinados remanescentes.

## 🛠️ Tecnologias Utilizadas

* **Backend:** PHP 8+, Drupal Form API, Database Abstraction Layer, Batch API.
* **Frontend:** JavaScript Nativo, Treant.js, Raphael.js, Drupal Behaviors, Flexbox CSS.
* **Cache:** Invalidação granular via Drupal Cache Tags (suporte a APCu).

## ⚙️ Instalação e Configuração

1.  Mova a pasta `mikedelta_organogramas` para o diretório `web/modules/` do seu Drupal.
2.  Vá para `Estrutura > Organogramas (MikeDelta)` ou acesse a rota `/admin/structure/md-organogramas-dashboard` para começar a criar seus organogramas.

## ⚠️ Requisitos Técnicos e Limitações

* **Fotos de Perfil:** Permitido apenas arquivos `jpg`, `jpeg` e `png` até 1MB.
* **Backup:** Os arquivos JSON não carregam os metadados binários das imagens dos usuários. Ao restaurar um backup, as fotos não são restauradas, sendo necessário o reenvio das mídias.
* **Campos formatados:** O campo de Telefone/RETELMA exige validação Regex estrita no formato `0000-0000`.

## 🖥️ Downloads

- [mikedelta_organogramas-1.0.0.zip](https://github.com/m4rcelodiasBR/mikedelta_organogramas/archive/refs/tags/v1.1.0.zip)
- [mikedelta_organogramas-1.0.0.tar.gz](https://github.com/m4rcelodiasBR/mikedelta_organogramas/archive/refs/tags/v1.1.0.tar.gz)

---
*Desenvolvido sob arquitetura MVC adaptada para o ecosistema Drupal.*
*Desenvolvido por Marcelo Dias da Silva*