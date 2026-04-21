<?php

namespace Drupal\mikedelta_organogramas\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;

class MembroDeleteForm extends ConfirmFormBase {

  protected $id;
  protected $membroDados;

  public function getFormId() {
    return 'mikedelta_organograma_delete_form';
  }

  public function getQuestion() {
    return $this->t('Tem certeza que deseja excluir o membro %nome do organograma?', ['%nome' => $this->membroDados->posto_espec . ' ' . $this->membroDados->nome_guerra]);
  }

  public function getCancelUrl() {
    return Url::fromRoute('mikedelta_organogramas.admin_list');
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $this->id = $id;
    $conexao = Database::getConnection();
    $this->membroDados = $conexao->select('mikedelta_organograma_membros', 'm')
      ->fields('m')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    if (!$this->membroDados) {
      \Drupal::messenger()->addError($this->t('Membro não encontrado.'));
      return $this->redirect('mikedelta_organogramas.admin_list');
    }

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // 1. Apaga a foto fisicamente do servidor se ela existir
    if (!empty($this->membroDados->foto_fid)) {
      $file = File::load($this->membroDados->foto_fid);
      if ($file) {
        \Drupal::entityTypeManager()->getStorage('file')->delete([$file]);
      }
    }

    // 2. Remove subordinações (quem era subordinado a ele agora vai para o topo)
    $conexao = Database::getConnection();
    $conexao->update('mikedelta_organograma_membros')
      ->fields(['superior_id' => NULL])
      ->condition('superior_id', $this->id)
      ->execute();

    // 3. Apaga o registro do membro
    $conexao->delete('mikedelta_organograma_membros')
      ->condition('id', $this->id)
      ->execute();

    \Drupal::messenger()->addStatus($this->t('Membro e arquivos associados excluídos com sucesso.'));
    $form_state->setRedirect('mikedelta_organogramas.admin_list');
  }
}