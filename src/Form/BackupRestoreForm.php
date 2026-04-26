<?php

namespace Drupal\mikedelta_organogramas\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;

class BackupRestoreForm extends FormBase {

  public function getFormId() {
    return 'mikedelta_organograma_backup_restore_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Backup e Restauração (Global)');

    $form['export'] = [
      '#type' => 'details',
      '#title' => $this->t('Exportar Dados'),
      '#open' => TRUE,
    ];

    $form['export']['help'] = [
      '#markup' => '<p>' . $this->t('Clique no botão abaixo para gerar um arquivo JSON contendo todos os organogramas e os seus respectivos membros cadastrados neste sistema.') . '</p>',
    ];

    $form['export']['submit'] = [
      '#type' => 'link',
      '#title' => $this->t('Gerar Backup Global (.json)'),
      '#url' => Url::fromRoute('mikedelta_organogramas.backup_global'),
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];

    $form['import'] = [
      '#type' => 'details',
      '#title' => $this->t('Importar Dados'),
      '#open' => TRUE,
    ];

    $form['import']['backup_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Selecionar arquivo de backup (.json)'),
      '#description' => $this->t('Selecione o arquivo gerado pelo sistema MikeDelta. Nota: Este processo não apaga os dados atuais, ele adiciona os novos organogramas.'),
    ];

    $form['import']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Iniciar Restauração'),
      '#button_type' => 'primary',
    ];

    $form['actions']['voltar'] = [
      '#type' => 'link',
      '#title' => $this->t('Voltar ao Dashboard'),
      '#url' => Url::fromRoute('mikedelta_organogramas.dashboard'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $all_files = \Drupal::request()->files->get('files', []);
    if (empty($all_files['backup_file'])) {
      $form_state->setErrorByName('backup_file', $this->t('Por favor, selecione um arquivo válido.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $all_files = \Drupal::request()->files->get('files', []);
    $file = $all_files['backup_file'];

    if ($file) {
      $data = file_get_contents($file->getRealPath());
      $backup = json_decode($data, TRUE);

      if (!$backup || !isset($backup['versao_backup'])) {
        \Drupal::messenger()->addError($this->t('Arquivo JSON inválido ou formato incompatível.'));
        return;
      }

      $batch = [
        'title' => $this->t('Restaurando Organogramas...'),
        'operations' => [],
        'init_message' => $this->t('Iniciando a leitura do arquivo de backup...'),
        'progress_message' => $this->t('Processando organograma @current de @total.'),
        'error_message' => $this->t('Ocorreu um erro durante a restauração. Verifique os logs.'),
        'finished' => '\Drupal\mikedelta_organogramas\Form\BackupRestoreForm::batchFinished',
      ];

      foreach ($backup['organogramas'] as $item) {
        $batch['operations'][] = [
          '\Drupal\mikedelta_organogramas\Form\BackupRestoreForm::processarOrganogramaBatch',
          [$item]
        ];
      }

      batch_set($batch);
    }
  }

  public static function processarOrganogramaBatch($item, &$context) {
    if (empty($context['results'])) {
      $context['results']['orgs'] = 0;
      $context['results']['membros'] = 0;
    }

    $conexao = Database::getConnection();
    $meta = $item['metadata'];

    $slug_final = $meta['slug'];
    $contador = 1;
    while ($conexao->select('mikedelta_organogramas_lista', 'l')->condition('slug', $slug_final)->countQuery()->execute()->fetchField()) {
      $slug_final = $meta['slug'] . '-' . $contador;
      $contador++;
    }

    $org_id_novo = $conexao->insert('mikedelta_organogramas_lista')
      ->fields([
        'titulo' => $meta['titulo'],
        'slug' => $slug_final,
        'descricao' => $meta['descricao'],
      ])
      ->execute();

    $mapa_ids = []; 

    foreach ($item['membros'] as $m_antigo) {
      $id_antigo = $m_antigo['id'];
      
      $membro_fields = $m_antigo;
      unset($membro_fields['id']);
      $membro_fields['organograma_id'] = $org_id_novo;
      $membro_fields['superior_id'] = NULL;
      $membro_fields['foto_fid'] = 0;

      $id_novo = $conexao->insert('mikedelta_organograma_membros')
        ->fields($membro_fields)
        ->execute();
      
      $mapa_ids[$id_antigo] = $id_novo;
    }

    foreach ($item['membros'] as $m_antigo) {
      if (!empty($m_antigo['superior_id']) && isset($mapa_ids[$m_antigo['superior_id']])) {
        $conexao->update('mikedelta_organograma_membros')
          ->fields(['superior_id' => $mapa_ids[$m_antigo['superior_id']]])
          ->condition('id', $mapa_ids[$m_antigo['id']])
          ->execute();
      }
    }

    $context['results']['orgs']++;
    $context['results']['membros'] += count($item['membros']);
    $context['message'] = 'Organograma "' . $meta['titulo'] . '" restaurado com sucesso!';
  }

  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      Cache::invalidateTags(['mikedelta_organogramas_membros']);
      \Drupal::cache('render')->deleteAll();
      \Drupal::logger('mikedelta_organogramas')->info('O usuário @user realizou uma Restauração de Backup JSON (@orgs organogramas, @membros membros importados).', [
        '@user' => \Drupal::currentUser()->getAccountName(),
        '@orgs' => $results['orgs'] ?? 0,
        '@membros' => $results['membros'] ?? 0,
      ]);
      \Drupal::messenger()->addStatus(t('Restauração concluída com sucesso! @orgs organograma(s) e @membros membro(s) importados.', [
        '@orgs' => $results['orgs'] ?? 0,
        '@membros' => $results['membros'] ?? 0,
      ]));
    } else {
      \Drupal::messenger()->addError(t('O processo de restauração encontrou um erro e não pôde ser finalizado.'));
    }
  }
}