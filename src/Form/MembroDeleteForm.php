<?php

namespace Drupal\mikedelta_organogramas\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Drupal\Core\Cache\Cache;

class MembroDeleteForm extends ConfirmFormBase {

  protected $id;
  protected $organogramaId;
  protected $membroDados;

  public function getFormId() {
    return 'mikedelta_organograma_delete_form';
  }

  public function getQuestion() {
    return $this->t('Tem certeza que deseja excluir o membro %nome do organograma?', [
      '%nome' => $this->membroDados->titulo_cargo . ' ' . $this->membroDados->nome
    ]);
  }

  public function getCancelUrl() {
    return Url::fromRoute('mikedelta_organogramas.admin_list', ['organograma_id' => $this->organogramaId]);
  }

  public function buildForm(array $form, FormStateInterface $form_state, $organograma_id = NULL, $id = NULL) {
    $this->id = $id;
    $this->organogramaId = $organograma_id;
    
    $conexao = Database::getConnection();
    $this->membroDados = $conexao->select('mikedelta_organograma_membros', 'm')
      ->fields('m')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    if (!$this->membroDados) {
      \Drupal::messenger()->addError($this->t('Membro não encontrado.'));
      return $this->redirect('mikedelta_organogramas.dashboard');
    }

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!empty($this->membroDados->foto_fid)) {
      $file = File::load($this->membroDados->foto_fid);
      if ($file) {
        $file->delete();
      }
    }

    $conexao = Database::getConnection();

    $conexao->update('mikedelta_organograma_membros')
      ->fields(['superior_id' => NULL])
      ->condition('superior_id', $this->id)
      ->execute();

    $conexao->delete('mikedelta_organograma_membros')
      ->condition('id', $this->id)
      ->execute();

    Cache::invalidateTags(['mikedelta_organogramas_membros']);
    \Drupal::messenger()->addStatus($this->t('Membro e ficheiros associados excluídos com sucesso.'));
    \Drupal::logger('mikedelta_organogramas')->info('O usuário @user EXCLUIU o membro "@nome" do Organograma ID: @org_id.', [
      '@user' => \Drupal::currentUser()->getAccountName(),
      '@nome' => $this->membroDados->titulo_cargo . ' ' . $this->membroDados->nome,
      '@org_id' => $this->organogramaId,
    ]);
    $form_state->setRedirect('mikedelta_organogramas.admin_list', ['organograma_id' => $this->organogramaId]);
  }
}