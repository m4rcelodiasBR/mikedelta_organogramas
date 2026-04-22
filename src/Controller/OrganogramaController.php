<?php

namespace Drupal\mikedelta_organogramas\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

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
      $foto_url = '';
      if (!empty($row->foto_fid)) {
        $file = File::load($row->foto_fid);
        if ($file) {
          $foto_url = $file->createFileUrl(FALSE);
        }
      }

      $membros[] = [
        'id' => $row->id,
        'superior_id' => $row->superior_id,
        'codigo_funcao' => $row->codigo_funcao,
        'codigo_funcao_bgcolor' => $row->codigo_funcao_bgcolor,
        'codigo_funcao_color' => $row->codigo_funcao_color,
        'nome_funcao' => $row->nome_funcao,
        'titulo_cargo' => $row->titulo_cargo,
        'nome' => $row->nome,
        'retelma' => $row->retelma,
        'email' => $row->email,
        'cor_principal' => $row->cor_principal,
        'cor_secundaria' => $row->cor_secundaria,
        'foto_url' => $foto_url,
        'posicao_linha' => $row->posicao_linha,
        'empilhar_filhos' => $row->empilhar_filhos,
      ];
    }

    $dados_organograma = [
      'is_logged_in' => \Drupal::currentUser()->isAuthenticated(),
      'url_config' => Url::fromRoute('mikedelta_organogramas.admin_list')->toString(),
    ];

    return [
      'titulo_pagina' => [
        '#markup' => '<div class="container"><h1 class="page-title mb-4">' . $this->t('Organograma') . '</h1></div>',
      ],
      'conteudo_organograma' => [
        '#theme' => 'mikedelta_organograma_page',
        '#dados_membros' => $membros,
        '#dados_organograma' => $dados_organograma,
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
        '#cache' => [
          'tags' => ['mikedelta_organograma:view'],
        ],
      ],
    ];
  }
}