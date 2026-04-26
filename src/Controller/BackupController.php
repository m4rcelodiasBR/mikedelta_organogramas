<?php

namespace Drupal\mikedelta_organogramas\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class BackupController extends ControllerBase {

  public function exportarTodosJson() {
    $conexao = Database::getConnection();
    
    $organogramas = $conexao->select('mikedelta_organogramas_lista', 'l')
      ->fields('l')
      ->execute()
      ->fetchAll();

    $dados_completos = $this->montarEstruturaJson($organogramas, $conexao);

    \Drupal::logger('mikedelta_organogramas')->info('O usuário @user gerou um Backup Global (@qtd organogramas exportados).', [
      '@user' => \Drupal::currentUser()->getAccountName(),
      '@qtd' => count($organogramas),
    ]);

    return $this->baixarArquivo($dados_completos, 'md_organogramas_backup_global');
  }

  public function exportarUnicoJson($id) {
    $conexao = Database::getConnection();
    
    $organograma = $conexao->select('mikedelta_organogramas_lista', 'l')
      ->fields('l')
      ->condition('id', $id)
      ->execute()
      ->fetchAll(); 

    $dados_completos = $this->montarEstruturaJson($organograma, $conexao);
    $nome_arquivo = 'md_organogramas_backup_' . $organograma[0]->slug;

    \Drupal::logger('mikedelta_organogramas')->info('O usuário @user gerou Backup do organograma "@slug".', [
      '@user' => \Drupal::currentUser()->getAccountName(),
      '@slug' => $organograma[0]->slug,
    ]);

    return $this->baixarArquivo($dados_completos, $nome_arquivo);
  }

  private function montarEstruturaJson($organogramas, $conexao) {
    $dados_completos = [
      'titulo_backup' => 'Backup MikeDelta Organogramas',
      'versao_backup' => '1.0',
      'data_geracao' => date('Y-m-d H:i:s'),
      'organogramas' => []
    ];

    foreach ($organogramas as $org) {
      $membros = $conexao->select('mikedelta_organograma_membros', 'm')
        ->fields('m')
        ->condition('organograma_id', $org->id)
        ->execute()
        ->fetchAllAssoc('id');

      $dados_completos['organogramas'][] = [
        'metadata' => [
          'titulo' => $org->titulo,
          'slug' => $org->slug,
          'descricao' => $org->descricao,
        ],
        'membros' => $membros
      ];
    }

    return $dados_completos;
  }

  private function baixarArquivo($dados, $nome_base) {
    $response = new JsonResponse($dados);
    $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $nome_base . '_' . date('Ymd_His') . '.json'
    );
    
    $response->headers->set('Content-Disposition', $disposition);
    return $response;
  }
}