# MikeDelta Organogramas

Módulo Drupal desenvolvido para gerenciar e renderizar organogramas hierárquicos interativos, suportando múltiplas divisões e departamentos. Desenhado para atender padrões de intranets governamentais e militares.

## 🚀 Funcionalidades

* **Múltiplos Organogramas:** Crie infinitas estruturas departamentais independentes, cada uma com sua URL dedicada (`/md-organograma/slug-da-divisao`).
* **Dashboard Centralizado:** Painel de administração unificado para gerenciamento rápido.
* **Visualização Responsiva (Treant.js):** Renderização baseada em SVG e CSS, garantindo funcionamento 100% offline.
* **Gestão de Hierarquia (Drag n' Drop):** Definição de laços de subordinação (Superior Imediato) de forma visual arrastando as linhas na interface administrativa.
* **Customização Visual Avançada:**
    * Formatação individual de cores (fundo, rodapé e *tags* de código de função).
    * Controle de empilhamento de subordinados (layout horizontal ou vertical).
    * Ajuste do ponto de ancoragem das linhas conectoras.
* **Exportação/Importação Segura:**
    * Backups granulares (por divisão) ou globais em formato JSON.
    * Lógica de remapeamento automático de chaves estrangeiras (IDs) durante a importação para evitar colisões no banco de dados.
    * Processamento Batch (barra de progresso) para arquivos grandes.
* **Privacidade e Limpeza:** A exclusão de um membro remove rigorosamente do disco os arquivos de foto associados e trata a orfandade de subordinados remanescentes.

## 🛠️ Tecnologias Utilizadas

* **Backend:** PHP 8+, Drupal Form API, Database Abstraction Layer, Batch API.
* **Frontend:** JavaScript Nativo, Treant.js, Raphael.js, Drupal Behaviors, Flexbox CSS.
* **Cache:** Invalidação granular via Drupal Cache Tags (suporte a APCu).

## ⚙️ Instalação e Configuração

1.  Mova a pasta `mikedelta_organogramas` para o diretório `web/modules/custom/` do seu Drupal.
2.  Acesse o servidor via terminal e ative o módulo:
    ```bash
    drush en mikedelta_organogramas -y
    ```
3.  Vá para `Estrutura > Organogramas (MikeDelta)` ou acesse a rota `/admin/structure/md-organogramas-dashboard` para começar a criar as divisões.

## ⚠️ Requisitos Técnicos e Limitações

* **Fotos de Perfil:** Permitido apenas arquivos `jpg`, `jpeg` e `png` até 1MB.
* **Backup:** Os arquivos JSON não carregam os metadados binários das imagens dos usuários. Ao restaurar um backup, os campos `foto_fid` são redefinidos para `0`, sendo necessário o reenvio das mídias.
* **Campos formatados:** O campo de telefone/RETELMA exige validação Regex estrita no formato `0000-0000`.

---
*Desenvolvido sob arquitetura MVC adaptada para o ecosistema Drupal.*