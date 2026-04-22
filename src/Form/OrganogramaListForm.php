<?php

namespace Drupal\mikedelta_organogramas\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;

class OrganogramaListForm extends FormBase {

  public function getFormId() {
    return 'mikedelta_organograma_list_form';
  }

  private function getListaArvore($superior_id = NULL, $nivel = 0, &$lista = []) {
    $query = Database::getConnection()->select('mikedelta_organograma_membros', 'm')
      ->fields('m');
      
    if ($superior_id === NULL) {
      $query->isNull('superior_id');
    } else {
      $query->condition('superior_id', $superior_id);
    }
    
    $query->orderBy('peso', 'ASC');
    $resultados = $query->execute();

    foreach ($resultados as $linha) {
      $linha->nivel = $nivel;
      $lista[$linha->id] = $linha;
      // Chama a si mesma para buscar os subordinados deste militar
      $this->getListaArvore($linha->id, $nivel + 1, $lista);
    }

    return $lista;
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $membros = $this->getListaArvore();

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

    $form['membros'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Membro'),
        $this->t('Função/Setor'),
        $this->t('Código'),
        $this->t('Peso'),
        $this->t('Superior'),
        $this->t('Operações'),
      ],
      '#empty' => $this->t('Nenhum membro cadastrado. Vá para a aba "Adicionar Membro".'),

      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'membro-parent',
          'subgroup' => 'membro-parent',
          'source' => 'membro-id',
          'hidden' => TRUE,
          'limit' => 10,
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'membro-weight',
        ],
      ],
    ];

    foreach ($membros as $id => $membro) {
      $form['membros'][$id]['#attributes']['class'][] = 'draggable';
      $form['membros'][$id]['#weight'] = $membro->peso;

      // Coluna 1: Nome com recuo visual (Indentation) para mostrar subordinação
      $form['membros'][$id]['nome'] = [
        'indentation' => [
          '#theme' => 'indentation',
          '#size' => $membro->nivel,
        ],
        'texto' => [
          '#markup' => '<strong>' . $membro->titulo_cargo . ' ' . $membro->nome . '</strong>',
        ],
      ];

      // Coluna 2: Função
      $form['membros'][$id]['nome_funcao'] = [
        '#markup' => $membro->nome_funcao,
      ];

      // Coluna 3: CPO-ID
      $form['membros'][$id]['codigo_funcao'] = [
        '#markup' => $membro->codigo_funcao,
      ];

      // Coluna 4: Peso (Oculta visualmente pelo CSS do TableDrag)
      $form['membros'][$id]['peso'] = [
        '#type' => 'weight',
        '#title' => $this->t('Peso para @nome', ['@nome' => $membro->nome]),
        '#title_display' => 'invisible',
        '#default_value' => $membro->peso,
        '#attributes' => ['class' => ['membro-weight']],
      ];

      // Coluna 5: Superior ID (Oculta visualmente, atualizada ao arrastar)
      $form['membros'][$id]['superior_id'] = [
        '#type' => 'textfield',
        '#default_value' => $membro->superior_id ?: 0,
        '#attributes' => ['class' => ['membro-parent']],
      ];

      // Coluna oculta obrigatória para mapear quem é quem no Drag n Drop
      $form['membros'][$id]['id'] = [
        '#type' => 'hidden',
        '#value' => $id,
        '#attributes' => ['class' => ['membro-id']],
      ];

      // Coluna 6: Operações (Editar/Excluir - Criaremos as rotas finais depois)
      $form['membros'][$id]['operacoes'] = [
        '#type' => 'operations',
        '#links' => [
          'edit' => [
            'title' => $this->t('Editar'),
            'url' => Url::fromRoute('mikedelta_organogramas.admin_edit', ['id' => $id]),
          ],
          'delete' => [
            'title' => $this->t('Excluir'),
            'url' => Url::fromRoute('mikedelta_organogramas.admin_delete', ['id' => $id]),
          ],
        ],
      ];
    }

    // Só exibe o botão de salvar se houver membros
    if (!empty($membros)) {
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Salvar Nova Ordem e Hierarquia'),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $valores = $form_state->getValue('membros');
    $conexao = Database::getConnection();

    // Loop rápido e seguro para atualizar o banco de dados conforme o arrasto do usuário
    foreach ($valores as $id => $dados) {
      $superior = ($dados['superior_id'] == 0 || empty($dados['superior_id'])) ? NULL : $dados['superior_id'];
      
      $conexao->update('mikedelta_organograma_membros')
        ->fields([
          'peso' => $dados['peso'],
          'superior_id' => $superior,
        ])
        ->condition('id', $id)
        ->execute();
        Cache::invalidateTags(['mikedelta_organograma:view']);
    }

    \Drupal::messenger()->addStatus($this->t('A hierarquia e ordem do organograma foram atualizadas.'));
  }
}