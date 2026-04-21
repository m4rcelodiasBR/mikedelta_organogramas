<?php

namespace Drupal\mikedelta_organogramas\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;

class MembroForm extends FormBase {

  protected $membroId;

  public function getFormId() {
    return 'mikedelta_organograma_membro_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    // Busca no banco de dados para popular o campo "Superior Imediato"
    $this->membroId = $id;
    $membro = NULL;
    $conexao = Database::getConnection();

    if ($id) {
      $membro = $conexao->select('mikedelta_organograma_membros', 'm')
        ->fields('m')
        ->condition('id', $id)
        ->execute()
        ->fetchObject();
    }
    
    $query = $conexao->select('mikedelta_organograma_membros', 'm');
    $query->fields('m', ['id', 'nome_guerra', 'posto_espec']);
    if ($id) {
      $query->condition('id', $id, '<>');
    }

    $resultados = $query->execute();
    
    $opcoes_superiores = ['0' => '- Topo do Organograma (Nenhum) -'];
    foreach ($resultados as $row) {
      $opcoes_superiores[$row->id] = $row->posto_espec . ' ' . $row->nome_guerra;
    }

    // Heurística de Nielsen: Prevenção de Erros e Design Minimalista
    $form['#tree'] = TRUE;

    $form['foto_fid'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Foto do Militar'),
      '#description' => $this->t('Formatos permitidos: png jpg jpeg. A imagem será redimensionada automaticamente no organograma.'),
      '#upload_location' => 'public://md-organograma_fotos/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
      ],
      '#default_value' => $membro && $membro->foto_fid ? [$membro->foto_fid] : [],
    ];

    $form['cpo_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Código da Função'),
      '#maxlength' => 10,
      '#description' => $this->t('Ex: CPO-01. Pode ser deixado em branco se a função não possuir código.'),
      '#required' => FALSE,
      '#default_value' => $membro ? $membro->cpo_id : '',
    ];

    $form['funcao_nome'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nome da Função'),
      '#maxlength' => 40,
      '#description' => $this->t('Ex: Assessor do Secretário, Ajudante para Sistemas.'),
      '#required' => TRUE,
      '#default_value' => $membro ? $membro->funcao_nome : '',
    ];

    $form['posto_espec'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Posto/Quadro'),
      '#maxlength' => 10,
      '#description' => $this->t('Ex: CF(IM), 1T(AA).'),
      '#required' => TRUE,
      '#default_value' => $membro ? $membro->posto_espec : '',
    ];

    $form['nome_guerra'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nome de Guerra'),
      '#maxlength' => 40,
      '#required' => TRUE,
      '#default_value' => $membro ? $membro->nome_guerra : '',
    ];

    $form['retelma'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Telefone/RETELMA'),
      '#maxlength' => 9,
      '#description' => $this->t('Formato obrigatório: 0000-0000'),
      '#required' => TRUE,
      '#default_value' => $membro ? $membro->retelma : '',
    ];

    // Utilizando o Colorpicker nativo do HTML5
    $form['cor_principal'] = [
      '#type' => 'color',
      '#title' => $this->t('Cor Principal do Cartão'),
      '#default_value' => $membro ? $membro->cor_principal : '#0f172a',
    ];

    $form['cor_secundaria'] = [
      '#type' => 'color',
      '#title' => $this->t('Cor Secundária do Cartão'),
      '#description' => $this->t('Para uma cor sólida, escolha a mesma cor selecionada acima.'),
      '#default_value' => $membro ? $membro->cor_secundaria : '#1e293b',
    ];

    $form['superior_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Superior Imediato'),
      '#options' => $opcoes_superiores,
      '#description' => $this->t('Selecione a quem este militar está subordinado para montar a hierarquia visual.'),
      '#default_value' => $membro ? ($membro->superior_id ?: '0') : '0',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Salvar Membro'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  // Validação rigorosa dos dados antes de salvar (Blindagem)
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $retelma = $form_state->getValue('retelma');
    
    // Expressão Regular (Regex) para forçar o padrão 0000-0000
    if (!preg_match('/^\d{4}-\d{4}$/', $retelma)) {
      $form_state->setErrorByName('retelma', $this->t('O RETELMA deve estar exatamente no formato 0000-0000.'));
    }
  }

  // Inserção no banco de dados usando abstração segura
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $valores = $form_state->getValues();
    
    // Tratamento do ID do arquivo (Foto)
    $foto_fid = 0;
    if (!empty($valores['foto_fid'][0])) {
      $foto_fid = $valores['foto_fid'][0];
      // Torna o arquivo permanente no sistema do Drupal
      $file = File::load($foto_fid);
      if ($file) {
        $file->setPermanent();
        $file->save();
      }
    }

    $campos = [
      'foto_fid' => $foto_fid,
      'cpo_id' => $valores['cpo_id'],
      'funcao_nome' => $valores['funcao_nome'],
      'posto_espec' => $valores['posto_espec'],
      'nome_guerra' => $valores['nome_guerra'],
      'retelma' => $valores['retelma'],
      'cor_principal' => $valores['cor_principal'],
      'cor_secundaria' => $valores['cor_secundaria'],
      'superior_id' => $valores['superior_id'] == '0' ? NULL : $valores['superior_id'],
    ];

   try {
      if ($this->membroId) {
        Database::getConnection()->update('mikedelta_organograma_membros')
          ->fields($campos)
          ->condition('id', $this->membroId)
          ->execute();
        \Drupal::messenger()->addStatus($this->t('Membro atualizado com sucesso.'));
      } else {
        Database::getConnection()->insert('mikedelta_organograma_membros')
          ->fields($campos)
          ->execute();
        \Drupal::messenger()->addStatus($this->t('Membro cadastrado com sucesso.'));
      }
      $form_state->setRedirect('mikedelta_organogramas.admin_list');
    } 
    catch (\Exception $e) {
      \Drupal::messenger()->addError($this->t('Erro ao salvar no banco de dados. Contate o administrador.'));
    }
  }
}