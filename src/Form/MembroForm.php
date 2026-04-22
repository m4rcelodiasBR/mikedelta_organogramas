<?php

namespace Drupal\mikedelta_organogramas\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;

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
    $query->fields('m', ['id', 'nome', 'titulo_cargo']);
    if ($id) {
      $query->condition('id', $id, '<>');
    }

    $resultados = $query->execute();
    
    $opcoes_superiores = ['0' => '- Topo -'];
    foreach ($resultados as $row) {
      $opcoes_superiores[$row->id] = $row->titulo_cargo . ' ' . $row->nome;
    }

    $form['top_actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'style' => 'display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px;',
      ],
    ];

    $form['top_actions']['view_org'] = [
      '#type' => 'link',
      '#title' => 'Ver Organograma',
      '#url' => Url::fromRoute('mikedelta_organogramas.public_view'),
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];

    $form['top_actions']['help_org'] = [
      '#type' => 'link',
      '#title' => 'Ajuda do Módulo',
      '#url' => Url::fromRoute('help.page', ['name' => 'mikedelta_organogramas']),
      '#attributes' => ['class' => ['button']],
    ];

    $form['#tree'] = TRUE;

    $form['foto_fid'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Foto'),
      '#description' => $this->t('Tamanho máximo: 1MB. Formatos permitidos: png jpg jpeg.'),
      '#upload_location' => 'public://md_organograma_fotos/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
        'file_validate_size' => [1024 * 1024],
      ],
      '#default_value' => $membro && $membro->foto_fid ? [$membro->foto_fid] : [],
    ];

    $form['codigo_funcao'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Código da Função'),
      '#maxlength' => 30,
      '#description' => $this->t('Ex: CPO-01, MD-03, SSPM-01.2. Pode ser deixado em branco se a função não possuir código.'),
      '#required' => FALSE,
      '#default_value' => $membro ? $membro->codigo_funcao : '',
    ];

    $form['cores_codigo_funcao'] = [
      '#type' => 'details',
      '#title' => $this->t('Personalização do Código da Função (Tag)'),
      '#open' => FALSE,
    ];

    $form['cores_codigo_funcao']['cor_fundo'] = [
      '#type' => 'color',
      '#title' => $this->t('Cor de Fundo Função (Tag)'),
      '#default_value' => $membro ? $membro->codigo_funcao_bgcolor : '#0284c7',
    ];

    $form['cores_codigo_funcao']['cor_texto'] = [
      '#type' => 'color',
      '#title' => $this->t('Cor do Texto Função (Tag)'),
      '#default_value' => $membro ? $membro->codigo_funcao_color : '#ffffff',
    ];

    $form['nome_funcao'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nome da Função/Setor'),
      '#maxlength' => 50,
      '#description' => $this->t('Ex: Assessor do Secretário, Ajudante para Sistemas.'),
      '#required' => TRUE,
      '#default_value' => $membro ? $membro->nome_funcao : '',
    ];

    $form['titulo_cargo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Título, Cargo ou Posto/Quadro'),
      '#maxlength' => 20,
      '#description' => $this->t('Ex: CF(IM), 1T(AA), Diretor, Gerente.'),
      '#required' => FALSE,
      '#default_value' => $membro ? $membro->titulo_cargo : '',
    ];

    $form['nome'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nome do Membro'),
      '#maxlength' => 50,
      '#required' => FALSE,
      '#default_value' => $membro ? $membro->nome : '',
    ];

    $form['retelma'] = [
      '#type' => 'textfield',
      '#title' => $this->t('RETELMA'),
      '#maxlength' => 9,
      '#description' => $this->t('Formato obrigatório: 0000-0000'),
      '#required' => FALSE,
      '#default_value' => $membro ? $membro->retelma : '',
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail'),
      '#maxlength' => 100,
      '#description' => $this->t('Endereço de e-mail válido. Opcional.'),
      '#default_value' => $membro ? $membro->email : '',
    ];

    $form['cor_principal'] = [
      '#type' => 'color',
      '#title' => $this->t('Cor Primária do Cartão'),
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
      '#description' => $this->t('Selecione a quem este membro está subordinado para montar a hierarquia visual.'),
      '#default_value' => $membro ? ($membro->superior_id ?: '0') : '0',
    ];

    $form['empilhar_filhos'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Empilhar subordinados verticalmente?'),
      '#description' => $this->t('Marque se os subordinados diretos desta função deverão aparecer empilhados em formato de lista. Deixe desmarcado para espalhá-los lado a lado.'),
      '#default_value' => $membro ? $membro->empilhar_filhos : 0,
    ];

    $form['posicao_linha'] = [
      '#type' => 'select',
      '#title' => $this->t('Ponto de Saída da Linha (Subordinados)'),
      '#description' => $this->t('Escolha entre as cinco posições para a linha que conecta aos subordinados.'),
      '#options' => [
        1 => $this->t('Posição 1 (Esquerda)'),
        2 => $this->t('Posição 2 (Centro-Esquerda)'),
        3 => $this->t('Posição 3 (Centro)'),
        4 => $this->t('Posição 4 (Centro-Direita)'),
        5 => $this->t('Posição 5 (Direita)'),
      ],
      '#default_value' => $membro ? $membro->posicao_linha : 3,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Salvar'),
      '#button_type' => 'primary',
    ];
    $form['actions']['submit_and_new'] = [
      '#type' => 'submit',
      '#value' => $this->t('Salvar e Adicionar Novo'),
      '#name' => 'save_and_new',
    ];

    return $form;
  }

  // Validação rigorosa dos dados antes de salvar (Blindagem)
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $retelma = $form_state->getValue('retelma');
    $email = $form_state->getValue('email');

    if (!empty($retelma) && !preg_match('/^\d{4}-\d{4}$/', $retelma)) {
      $form_state->setErrorByName('retelma', $this->t('O RETELMA deve estar exatamente no formato 0000-0000.'));
    }

    if (!empty($email) && !\Drupal::service('email.validator')->isValid($email)) {
      $form_state->setErrorByName('email', $this->t('O e-mail informado não é válido.'));
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
      'codigo_funcao' => $valores['codigo_funcao'],
      'codigo_funcao_bgcolor' => $valores['cores_codigo_funcao']['cor_fundo'],
      'codigo_funcao_color' => $valores['cores_codigo_funcao']['cor_texto'],
      'nome_funcao' => $valores['nome_funcao'],
      'titulo_cargo' => $valores['titulo_cargo'],
      'nome' => $valores['nome'],
      'retelma' => $valores['retelma'],
      'email' => $valores['email'],
      'cor_principal' => $valores['cor_principal'],
      'cor_secundaria' => $valores['cor_secundaria'],
      'superior_id' => $valores['superior_id'] == '0' ? NULL : $valores['superior_id'],
      'posicao_linha' => $valores['posicao_linha'],
      'empilhar_filhos' => $valores['empilhar_filhos'],
    ];

    try {
      if ($this->membroId) {
        Database::getConnection()->update('mikedelta_organograma_membros')
          ->fields($campos)
          ->condition('id', $this->membroId)
          ->execute();
        Cache::invalidateTags(['mikedelta_organograma:view']);
        \Drupal::messenger()->addStatus($this->t('Membro atualizado com sucesso.'));
      } else {
        Database::getConnection()->insert('mikedelta_organograma_membros')
          ->fields($campos)
          ->execute();
        Cache::invalidateTags(['mikedelta_organograma:view']);
        \Drupal::messenger()->addStatus($this->t('Membro cadastrado com sucesso.'));
      }

      $botao_clicado = $form_state->getTriggeringElement()['#name'];
      if ($botao_clicado === 'save_and_new') {
        $form_state->setRedirect('mikedelta_organogramas.admin_add');
      } else {
        $form_state->setRedirect('mikedelta_organogramas.admin_list');
      }
    } 
    catch (\Exception $e) {
      \Drupal::messenger()->addError($this->t('Erro ao salvar no banco de dados. Contate o administrador.'));
    }
  }
}