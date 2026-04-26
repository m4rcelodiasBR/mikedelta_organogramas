<?php

namespace Drupal\mikedelta_organogramas\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

class OrganogramaController extends ControllerBase {

  public function exibir($slug = NULL) {
    $conexao = Database::getConnection();

    $organograma = $conexao->select('mikedelta_organogramas_lista', 'l')
      ->fields('l')
      ->condition('slug', $slug)
      ->execute()
      ->fetchObject();

    if (!$organograma) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $query = $conexao->select('mikedelta_organograma_membros', 'm')
      ->fields('m')
      ->condition('organograma_id', $organograma->id)
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

    $is_logged_in = \Drupal::currentUser()->isAuthenticated();

    $dados_organograma = [
      'is_logged_in' => $is_logged_in,
      'url_config' => $is_logged_in ? Url::fromRoute('mikedelta_organogramas.admin_list', ['organograma_id' => $organograma->id])->toString() : '',
    ];

    return [
      'conteudo_organograma' => [
        '#theme' => 'mikedelta_organograma_page',
        '#dados_membros' => $membros,
        '#dados_organograma' => $dados_organograma,
        '#titulo_organograma' => $organograma->titulo,
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
          'contexts' => ['url.path', 'user.roles:authenticated'],
          'tags' => ['mikedelta_organogramas_membros'],
        ],
      ],
    ];
  }
}