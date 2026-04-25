<?php

namespace Drupal\mikedelta_organogramas\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;

class OrganogramaMestreDeleteForm extends ConfirmFormBase {

  protected $id;
  protected $organograma;

  public function getFormId() {
    return 'mikedelta_organograma_mestre_delete_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $this->id = $id;
    $this->organograma = Database::getConnection()->select('mikedelta_organogramas_lista', 'l')
      ->fields('l')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    return parent::buildForm($form, $form_state);
  }

  public function getQuestion() {
    return $this->t('Tem certeza de que deseja excluir o organograma %titulo?', ['%titulo' => $this->organograma->titulo]);
  }

  public function getDescription() {
    return $this->t('AVISO DE DESTRUIÇÃO: Esta ação não pode ser desfeita. Todos os membros vinculados a este organograma serão apagados do banco de dados e suas fotos serão permanentemente removidas do servidor.');
  }

  public function getCancelUrl() {
    return new Url('mikedelta_organogramas.dashboard');
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $conexao = Database::getConnection();

    $membros = $conexao->select('mikedelta_organograma_membros', 'm')
      ->fields('m', ['foto_fid'])
      ->condition('organograma_id', $this->id)
      ->execute();

    foreach ($membros as $membro) {
      if (!empty($membro->foto_fid)) {
        $file = File::load($membro->foto_fid);
        if ($file) {
          $file->delete();
        }
      }
    }

    $conexao->delete('mikedelta_organograma_membros')
      ->condition('organograma_id', $this->id)
      ->execute();

    $conexao->delete('mikedelta_organogramas_lista')
      ->condition('id', $this->id)
      ->execute();

    \Drupal::messenger()->addStatus($this->t('Organograma e todos os seus vínculos foram destruídos com sucesso.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}