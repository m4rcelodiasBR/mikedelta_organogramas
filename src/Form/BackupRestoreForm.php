<?php

namespace Drupal\mikedelta_organogramas\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Core\Cache\Cache;

class BackupRestoreForm extends FormBase {

  public function getFormId() {
    return 'mikedelta_organograma_backup_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Seção de Backup (Download)
    $form['backup_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Fazer Backup (Exportar Dados)'),
      '#open' => TRUE,
    ];
    
    $form['backup_section']['download'] = [
      '#type' => 'link',
      '#title' => $this->t('Baixar Arquivo de Backup (.json)'),
      '#url' => Url::fromRoute('mikedelta_organogramas.export'),
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];

    $form['restore_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Restaurar Backup (Importar Dados)'),
      '#open' => TRUE,
      '#attributes' => ['style' => 'margin-top: 20px;'],
    ];

    $form['restore_section']['aviso'] = [
      '#markup' => '<div style="color: #dc2626; font-weight: bold; margin-bottom: 15px;">⚠️ Atenção: A restauração apagará TODO o organograma atual e o substituirá pelos dados do arquivo.</div>',
    ];

    $form['restore_section']['arquivo_json'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Arquivo JSON'),
      '#upload_validators' => [
        'file_validate_extensions' => ['json'],
      ],
      '#upload_location' => 'temporary://',
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Iniciar Restauração'),
      '#button_type' => 'danger',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file_id = $form_state->getValue(['arquivo_json', 0]);

    if (!empty($file_id)) {
      $file = File::load($file_id);
      if ($file) {
        $json_data = file_get_contents($file->getFileUri());
        $membros = json_decode($json_data, TRUE);

        if (is_array($membros)) {
          $batch = [
            'title' => $this->t('Restaurando Organograma...'),
            'operations' => [
              [[$this, 'limparTabela'], []], // Operação 1: Truncate
            ],
            'finished' => [$this, 'restauracaoFinalizada'],
            'init_message' => $this->t('Iniciando processo de restauração.'),
            'progress_message' => $this->t('Processando membros... (@current de @total).'),
            'error_message' => $this->t('Ocorreu um erro durante a restauração.'),
          ];

          // Adiciona uma operação de inserção para cada membro (ou em grupos)
          foreach ($membros as $membro) {
            $batch['operations'][] = [[$this, 'processarMembro'], [$membro]];
          }
          batch_set($batch);
        } else {
          \Drupal::messenger()->addError($this->t('O arquivo não contém um formato JSON válido.'));
        }
      }
    }
  }

  public static function limparTabela(&$context) {
    $conexao = Database::getConnection();
    $conexao->truncate('mikedelta_organograma_membros')->execute();
    $context['message'] = t('Limpando dados antigos...');
  }

  public static function processarMembro($membro, &$context) {
    $conexao = Database::getConnection();
    $conexao->insert('mikedelta_organograma_membros')
      ->fields($membro)
      ->execute();
    
    $context['message'] = t('Importando: @nome', ['@nome' => $membro['nome']]);
  }

  public static function restauracaoFinalizada($success, $results, $operations) {
    if ($success) {
      $conexao = Database::getConnection();
      if ($conexao->driver() == 'pgsql') {
        $conexao->query("SELECT setval('mikedelta_organograma_membros_id_seq', (SELECT MAX(id) FROM mikedelta_organograma_membros))");
      }
      
      Cache::invalidateTags(['mikedelta_organograma:view']);
      \Drupal::messenger()->addStatus(t('Organograma restaurado com sucesso usando o ProgressBar nativo!'));
    } else {
      \Drupal::messenger()->addError(t('Houve um erro no processamento do lote.'));
    }
  }
}