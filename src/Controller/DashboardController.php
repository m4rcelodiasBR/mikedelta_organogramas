<?php

namespace Drupal\mikedelta_organogramas\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Link;

class DashboardController extends ControllerBase {

  public function listar() {
    $conexao = Database::getConnection();
    $resultados = $conexao->select('mikedelta_organogramas_lista', 'l')
      ->fields('l')
      ->orderBy('titulo', 'ASC')
      ->execute()
      ->fetchAll();

    $linhas = [];
    foreach ($resultados as $row) {
      $qtd_membros = $conexao->select('mikedelta_organograma_membros', 'm')
        ->condition('organograma_id', $row->id)
        ->countQuery()
        ->execute()
        ->fetchField();

      $url_publica = Url::fromRoute('mikedelta_organogramas.public_view', ['slug' => $row->slug])->toString();

      $opcoes_btn = ['attributes' => ['class' => ['button', 'button--small'], 'style' => 'margin-right: 5px;']];
      $opcoes_btn_excluir = ['attributes' => ['class' => ['button', 'button--small', 'button--danger'], 'style' => 'margin-right: 5px;']];
      
      $btn_ver = Link::fromTextAndUrl('Visualizar', Url::fromRoute('mikedelta_organogramas.public_view', ['slug' => $row->slug], $opcoes_btn))->toString();
      $btn_gerenciar = Link::fromTextAndUrl('Membros', Url::fromRoute('mikedelta_organogramas.admin_list', ['organograma_id' => $row->id], $opcoes_btn))->toString();
      $btn_editar = Link::fromTextAndUrl('Editar', Url::fromRoute('mikedelta_organogramas.mestre_edit', ['id' => $row->id], $opcoes_btn))->toString();
      $btn_backup = Link::fromTextAndUrl('Backup', Url::fromRoute('mikedelta_organogramas.backup_single', ['id' => $row->id], $opcoes_btn))->toString();
      $btn_excluir = Link::fromTextAndUrl('Excluir', Url::fromRoute('mikedelta_organogramas.mestre_delete', ['id' => $row->id], $opcoes_btn_excluir))->toString();

      $linhas[] = [
        $row->titulo,
        ['data' => ['#markup' => $url_publica]],
        $qtd_membros,
        ['data' => ['#markup' => $btn_ver . $btn_gerenciar . $btn_editar . $btn_backup . $btn_excluir]],
      ];
    }

    $build['header'] = [
      '#type' => 'container',
      '#attributes' => [
        'style' => 'display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;',
      ],
    ];

    $build['header']['acoes_esquerda'] = [
      '#type' => 'container',
      'add' => [
        '#type' => 'link',
        '#title' => 'Criar Novo Organograma',
        '#url' => Url::fromRoute('mikedelta_organogramas.mestre_add'),
        '#attributes' => ['class' => ['button', 'button--primary', 'button--action'], 'style' => 'margin-right: 10px;'],
      ],
      'backup' => [
        '#type' => 'link',
        '#title' => 'Backup/Restore',
        '#url' => Url::fromRoute('mikedelta_organogramas.backup_restore'),
        '#attributes' => ['class' => ['button',]],
      ],
    ];

    $build['header']['acoes_direita'] = [
      '#type' => 'container',
      'ajuda' => [
        '#type' => 'link',
        '#title' => 'Ajuda do Módulo',
        '#url' => Url::fromRoute('help.page', ['name' => 'mikedelta_organogramas']),
        '#attributes' => ['class' => ['button']],
      ],
    ];

    $build['instrucoes'] = [
      '#markup' => '<div>
        <p>Este é o painel central de todos os organogramas existentes. Você pode criar um Organograma clicando no botão "Criar Novo Organograma" ou importar clicando em "Backup/Restore", onde você poderá importar um ou mais organogramas. Ações possíveis nesta lista são:</p>
        <ul style="margin-top: 5px;">
          <li><strong>Visualizar:</strong> Abre a versão pública interativa do organograma.</li>
          <li><strong>Membros:</strong> Gerencie os membros do organograma, adicionando, editando e removendo membros. Bem como definir as hierarquias.</li>
          <li><strong>Editar:</strong> Permite alterar o título e a descrição do organograma.</li>
          <li><strong>Backup:</strong> Faz o download do backup em arquivo <code>.json</code> do organograma específico.</li>
          <li><strong>Excluir:</strong> Remove permanentemente o organograma e seus membros. Use com cautela! Uma vez excluído, não há como recuperar.</li>
        </ul>
      </div>',
    ];

    $build['tabela'] = [
      '#type' => 'table',
      '#header' => ['Título', 'URL Pública', 'Qtd Membros', 'Ações'],
      '#rows' => $linhas,
      '#empty' => $this->t('Nenhum organograma criado ainda. Clique em "Criar Novo Organograma".'),
    ];

    return $build;
  }
}