<?php

namespace Drupal\mikedelta_organogramas\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;

class OrganogramaController extends ControllerBase {

  public function exibir() {
    $conexao = Database::getConnection();
    // Busca todos os membros ordenados pelo peso (a ordem que você arrastou no painel)
    $query = $conexao->select('mikedelta_organograma_membros', 'm')
      ->fields('m')
      ->orderBy('peso', 'ASC');
    $resultados = $query->execute()->fetchAll();

    $membros = [];
    foreach ($resultados as $row) {
      // Pega a URL da foto se existir
      $foto_url = '';
      if (!empty($row->foto_fid)) {
        $file = File::load($row->foto_fid);
        if ($file) {
          $foto_url = $file->createFileUrl(FALSE);
        }
      }

      $membros[$row->id] = [
        'id' => $row->id,
        'superior_id' => $row->superior_id,
        'cpo_id' => $row->cpo_id,
        'funcao_nome' => $row->funcao_nome,
        'posto_espec' => $row->posto_espec,
        'nome_guerra' => $row->nome_guerra,
        'retelma' => $row->retelma,
        'cor_principal' => $row->cor_principal,
        'cor_secundaria' => $row->cor_secundaria,
        'foto_url' => $foto_url,
      ];
    }

    return [
      '#theme' => 'mikedelta_organograma_page',
      '#dados_membros' => $membros,
      '#attached' => [
        'library' => [
          'mikedelta_organogramas/treant',
        ],
        'drupalSettings' => [
          'mikeDeltaData' => [
            'membros' => $membros,
          ],
        ],
      ],
    ];
  }
}