<?php

namespace Drupal\mikedelta_organogramas\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\Response;

class BackupController extends ControllerBase {

  public function exportar() {
    $conexao = Database::getConnection();

    $resultados = $conexao->select('mikedelta_organograma_membros', 'm')
      ->fields('m')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);

    $json = json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $response = new Response($json);
    $response->headers->set('Content-Type', 'application/json');
    $response->headers->set('Content-Disposition', 'attachment; filename="md_organograma_backup_' . date('Ymd_His') . '.json"');
    
    return $response;
  }
}