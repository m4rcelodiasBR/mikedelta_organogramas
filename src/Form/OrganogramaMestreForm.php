<?php

namespace Drupal\mikedelta_organogramas\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

class OrganogramaMestreForm extends FormBase {

  public function getFormId() {
    return 'mikedelta_organograma_mestre_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $conexao = Database::getConnection();
    $organograma = NULL;

    if ($id) {
      $organograma = $conexao->select('mikedelta_organogramas_lista', 'l')
        ->fields('l')
        ->condition('id', $id)
        ->execute()
        ->fetchObject();
    }

    $form['id'] = [
      '#type' => 'hidden',
      '#value' => $id,
    ];

    $form['titulo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Título do Organograma'),
      '#description' => $this->t('Ex: Divisão de TI. Este nome será exibido como subtítulo na página pública.'),
      '#default_value' => $organograma ? $organograma->titulo : '',
      '#required' => TRUE,
    ];

    $form['descricao'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Descrição'),
      '#description' => $this->t('Descrição do Organograma para controle interno.'),
      '#default_value' => $organograma ? $organograma->descricao : '',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $id ? $this->t('Salvar Alterações') : $this->t('Criar Organograma'),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Voltar'),
      '#url' => Url::fromRoute('mikedelta_organogramas.dashboard'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $conexao = Database::getConnection();
    $valores = $form_state->getValues();
    $id = $valores['id'];
    $titulo = trim($valores['titulo']);
    $transliteration = \Drupal::service('transliteration');
    $slug_base = $transliteration->transliterate($titulo, 'pt-br', '-');
    $slug_base = strtolower($slug_base);
    $slug_base = preg_replace('/[^a-z0-9\-]+/', '-', $slug_base);
    $slug_base = trim(preg_replace('/-+/', '-', $slug_base), '-');
    $slug_final = $slug_base;
    $contador = 1;

    while (TRUE) {
      $query = $conexao->select('mikedelta_organogramas_lista', 'l')->condition('slug', $slug_final);
      if ($id) {
        $query->condition('id', $id, '<>');
      }
      $existe = $query->countQuery()->execute()->fetchField();
      
      if (!$existe) {
        break;
      }
      $slug_final = $slug_base . '-' . $contador;
      $contador++;
    }

    $dados_salvar = [
      'titulo' => $titulo,
      'slug' => $slug_final,
      'descricao' => $valores['descricao'],
    ];

    if ($id) {
      $conexao->update('mikedelta_organogramas_lista')
        ->fields($dados_salvar)
        ->condition('id', $id)
        ->execute();
      \Drupal::messenger()->addStatus($this->t('Organograma atualizado com sucesso.'));
    } else {
      $conexao->insert('mikedelta_organogramas_lista')
        ->fields($dados_salvar)
        ->execute();
      \Drupal::messenger()->addStatus($this->t('Novo organograma criado com sucesso! Rota gerada: /md-organograma/@slug', ['@slug' => $slug_final]));
    }

    $form_state->setRedirect('mikedelta_organogramas.dashboard');
  }
}