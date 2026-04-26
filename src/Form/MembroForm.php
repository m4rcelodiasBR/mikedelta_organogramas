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

  public function buildForm(array $form, FormStateInterface $form_state, $organograma_id = NULL, $id = NULL) {

    $this->membroId = $id;
    $membro = NULL;
    $conexao = Database::getConnection();

    if ($id) {
      $membro = $conexao->select('mikedelta_organograma_membros', 'm')
        ->fields('m')
        ->condition('id', $id)
        ->execute()
        ->fetchObject();
        $organograma_id = $membro->organograma_id;
    }

    if ($organograma_id) {
      $organograma = $conexao->select('mikedelta_organogramas_lista', 'l')
        ->fields('l')
        ->condition('id', $organograma_id)
        ->execute()
        ->fetchObject();

      if ($organograma) {
        $prefixo = $id ? $this->t('Editar Membro') : $this->t('Adicionar Membro');
        $form['#title'] = $prefixo . ': Organograma ' . $organograma->titulo;
      }
    }
    
    $query = $conexao->select('mikedelta_organograma_membros', 'm');
    $query->fields('m', ['id', 'nome', 'titulo_cargo']);
    $query->condition('organograma_id', $organograma_id);
    
    if ($id) {
      $query->condition('id', $id, '<>');
    }

    $resultados = $query->execute();
    
    $opcoes_superiores = ['0' => '- Topo -'];
    foreach ($resultados as $row) {
      $opcoes_superiores[$row->id] = $row->titulo_cargo . ' ' . $row->nome;
    }

    $ultimo_estilo = [];
    if (!$this->membroId && $organograma_id) {
      $ultimo_estilo = \Drupal::database()->select('mikedelta_organograma_membros', 'm')
        ->fields('m', ['cor_principal', 'cor_secundaria', 'codigo_funcao_bgcolor', 'codigo_funcao_color'])
        ->condition('organograma_id', $organograma_id)
        ->orderBy('id', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchAssoc();
    }

    $form['id'] = [
      '#type' => 'hidden',
      '#value' => $id,
    ];
    
    $form['organograma_id'] = [
      '#type' => 'hidden',
      '#value' => $organograma_id,
    ];



    $form['aba_dados'] = [
      '#type' => 'details',
      '#title' => $this->t('1) Dados do Membro'),
      '#open' => TRUE,
    ];

    $form['aba_dados']['foto_fid'] = [
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

    $form['aba_dados']['linha_nome'] = [
      '#type' => 'container',
      '#attributes' => ['style' => 'display: flex; gap: 20px;'],
    ];
    
    $form['aba_dados']['linha_nome']['titulo_cargo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Título/Cargo ou Posto(Quadro)/Grad(Espec)'),
      '#maxlength' => 20,
      '#description' => $this->t('Exs.: Diretor, Gerente, CF (T), 1T (AA), 1SG (PD).'),
      '#required' => FALSE,
      '#default_value' => $membro ? $membro->titulo_cargo : '',
      '#attributes' => ['style' => 'flex: 1;'],
    ];

    $form['aba_dados']['linha_nome']['nome'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nome do Membro'),
      '#maxlength' => 50,
      '#required' => FALSE,
      '#default_value' => $membro ? $membro->nome : '',
      '#attributes' => ['style' => 'flex: 3;'],
    ];

    $form['aba_dados']['linha_contato'] = [
      '#type' => 'container',
      '#attributes' => ['style' => 'display: flex; gap: 20px;'],
    ];

    $form['aba_dados']['linha_contato']['retelma'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Telefone/RETELMA'),
      '#maxlength' => 9,
      '#description' => $this->t('Formato obrigatório: 0000-0000'),
      '#required' => FALSE,
      '#default_value' => $membro ? $membro->retelma : '',
      '#attributes' => ['style' => 'flex: 1;'],
    ];

    $form['aba_dados']['linha_contato']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail (Opcional)'),
      '#maxlength' => 100,
      '#description' => $this->t('Endereço de e-mail válido.'),
      '#default_value' => $membro ? $membro->email : '',
      '#attributes' => ['style' => 'flex: 1;'],
    ];



    $form['aba_funcao'] = [
      '#type' => 'details',
      '#title' => $this->t('2) Função e Hierarquia'),
      '#open' => TRUE,
    ];

    $form['aba_funcao']['linha_funcao'] = [
      '#type' => 'container',
      '#attributes' => ['style' => 'display: flex; gap: 20px;'],
    ];

    $form['aba_funcao']['linha_funcao']['nome_funcao'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nome da Função/Setor'),
      '#maxlength' => 50,
      '#description' => $this->t('Exs.: Assessor do Comandante, Ajudante para Sistemas, Secretaria.'),
      '#required' => TRUE,
      '#default_value' => $membro ? $membro->nome_funcao : '',
      '#attributes' => ['style' => 'flex: 3;'],
    ];

    $form['aba_funcao']['linha_funcao']['codigo_funcao'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Código Função'),
      '#maxlength' => 30,
      '#description' => $this->t('Exs.: CPO-01, MD-03, SSPM-01.2, TI-11.'),
      '#required' => FALSE,
      '#default_value' => $membro ? $membro->codigo_funcao : '',
      '#attributes' => ['style' => 'flex: 1;'],
    ];

    $form['aba_funcao']['superior_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Superior Imediato'),
      '#options' => $opcoes_superiores,
      '#description' => $this->t('Selecione a quem este membro está subordinado diretamente.'),
      '#default_value' => $membro ? ($membro->superior_id ?: '0') : '0',
    ];



    $form['aba_visual'] = [
      '#type' => 'details',
      '#title' => $this->t('3) Configurações Visuais'),
      '#open' => FALSE,
    ];

    $form['aba_visual']['linha_cores_cartao'] = [
      '#type' => 'container',
      '#attributes' => ['style' => 'display: flex; gap: 20px;'],
    ];

    $form['aba_visual']['linha_cores_cartao']['cor_principal'] = [
      '#type' => 'color',
      '#title' => $this->t('Cor Primária do Cartão de Membro'),
      '#default_value' => $membro ? $membro->cor_principal : ($ultimo_estilo['cor_principal'] ?? '#0f172a'),
      '#attributes' => ['style' => 'flex: 1;'],
    ];

    $form['aba_visual']['linha_cores_cartao']['cor_secundaria'] = [
      '#type' => 'color',
      '#title' => $this->t('Cor Secundária do Cartão de Membro'),
      '#description' => $this->t('Para cor sólida, escolha a mesma da primária.'),
      '#default_value' => $membro ? $membro->cor_secundaria : ($ultimo_estilo['cor_secundaria'] ?? '#1e293b'),
      '#attributes' => ['style' => 'flex: 1;'],
    ];

    $form['aba_visual']['linha_cores_tag'] = [
      '#type' => 'container',
      '#attributes' => ['style' => 'display: flex; gap: 20px;'],
    ];

    $form['aba_visual']['linha_cores_tag']['codigo_funcao_bgcolor'] = [
      '#type' => 'color',
      '#title' => $this->t('Cor de Fundo da Tag (Código)'),
      '#default_value' => $membro ? $membro->codigo_funcao_bgcolor : ($ultimo_estilo['codigo_funcao_bgcolor'] ?? '#0284c7'),
      '#attributes' => ['style' => 'flex: 1;'],
    ];

    $form['aba_visual']['linha_cores_tag']['codigo_funcao_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Cor do Texto da Tag (Código)'),
      '#default_value' => $membro ? $membro->codigo_funcao_color : ($ultimo_estilo['codigo_funcao_color'] ?? '#ffffff'),
      '#attributes' => ['style' => 'flex: 1;'],
    ];

    $form['aba_visual']['linha_layout'] = [
      '#type' => 'container',
      '#attributes' => ['style' => 'display: flex; gap: 20px;'],
    ];

    $form['aba_visual']['linha_layout']['posicao_linha'] = [
      '#type' => 'select',
      '#title' => $this->t('Ponto de Saída da Linha'),
      '#description' => $this->t('Escolha o alinhamento da linha dos subordinados (Quando aplicável).'),
      '#options' => [
        1 => $this->t('Posição 1 (Esquerda)'),
        2 => $this->t('Posição 2 (Centro-Esquerda)'),
        3 => $this->t('Posição 3 (Centro - Padrão)'),
        4 => $this->t('Posição 4 (Centro-Direita)'),
        5 => $this->t('Posição 5 (Direita)'),
      ],
      '#default_value' => $membro ? $membro->posicao_linha : 3,
      '#attributes' => ['style' => 'flex: 1;'],
    ];

    $form['aba_visual']['linha_layout']['empilhar_filhos'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Empilhar subordinados verticalmente?'),
      '#description' => $this->t('Marque para exibir os subordinados em formato de lista.'),
      '#default_value' => $membro ? $membro->empilhar_filhos : 0,
      '#attributes' => ['style' => 'flex: 1; align-self: flex-end; padding-bottom: 20px;'],
    ];



    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['style' => 'display: flex; gap: 10px; align-items: center;'],
    ];
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
    $form['actions']['voltar'] = [
      '#type' => 'link',
      '#title' => $this->t('Voltar'),
      '#url' => Url::fromRoute('mikedelta_organogramas.admin_list', ['organograma_id' => $organograma_id]),
      '#attributes' => ['class' => ['button']],
    ];

    $form['actions_top'] = $form['actions'];
    $form['actions_top']['#weight'] = -100;

    return $form;
  }

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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $valores = $form_state->getValues();
    
    $foto_fid = 0;
    if (!empty($valores['foto_fid'][0])) {
      $foto_fid = $valores['foto_fid'][0];
      $file = File::load($foto_fid);
      if ($file) {
        $file->setPermanent();
        $file->save();
      }
    }

    $campos = [
      'foto_fid' => $foto_fid,
      'codigo_funcao' => $valores['codigo_funcao'],
      'codigo_funcao_bgcolor' => $valores['codigo_funcao_bgcolor'],
      'codigo_funcao_color' => $valores['codigo_funcao_color'],
      'nome_funcao' => $valores['nome_funcao'],
      'titulo_cargo' => $valores['titulo_cargo'],
      'nome' => $valores['nome'],
      'retelma' => $valores['retelma'],
      'email' => $valores['email'],
      'cor_principal' => $valores['cor_principal'],
      'cor_secundaria' => $valores['cor_secundaria'],
      'organograma_id' => $valores['organograma_id'],
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
        Cache::invalidateTags(['mikedelta_organogramas_membros']);
        \Drupal::messenger()->addStatus($this->t('Membro atualizado com sucesso.'));
      } else {
        Database::getConnection()->insert('mikedelta_organograma_membros')
          ->fields($campos)
          ->execute();
        Cache::invalidateTags(['mikedelta_organogramas_membros']);
        \Drupal::messenger()->addStatus($this->t('Membro cadastrado com sucesso.'));
      }

      \Drupal::logger('mikedelta_organogramas')->info('O usuário @user @acao o membro "@nome" (Função: @funcao) no Organograma ID: @org_id.', [
        '@user' => \Drupal::currentUser()->getAccountName(),
        '@acao' => $this->membroId ? 'editou' : 'cadastrou',
        '@nome' => $valores['titulo_cargo'] . ' ' . $valores['nome'],
        '@funcao' => $valores['nome_funcao'],
        '@org_id' => $valores['organograma_id'],
      ]);

      $botao_clicado = $form_state->getTriggeringElement()['#name'];
      if ($botao_clicado === 'save_and_new') {
        $form_state->setRedirect('mikedelta_organogramas.admin_add', ['organograma_id' => $valores['organograma_id']]);
      } else {
        $form_state->setRedirect('mikedelta_organogramas.admin_list', ['organograma_id' => $valores['organograma_id']]);
      }
    } 
    catch (\Exception $e) {
      \Drupal::messenger()->addError($this->t('Erro ao salvar no banco de dados. Contate o administrador.'));
    }
  }
}