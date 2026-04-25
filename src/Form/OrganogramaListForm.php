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

  private function getListaArvore($organograma_id, $superior_id = NULL, $nivel = 0, &$lista = []) {
    $query = Database::getConnection()->select('mikedelta_organograma_membros', 'm')
      ->fields('m');

    $query->condition('organograma_id', $organograma_id);
      
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
      $this->getListaArvore($organograma_id, $linha->id, $nivel + 1, $lista);
    }

    return $lista;
  }

  public function buildForm(array $form, FormStateInterface $form_state, $organograma_id = NULL) {
    $conexao = Database::getConnection();

    $organograma = $conexao->select('mikedelta_organogramas_lista', 'l')
      ->fields('l')
      ->condition('id', $organograma_id)
      ->execute()
      ->fetchObject();

    if ($organograma) {
      $form['#title'] = $this->t('Membros: Organograma @titulo', ['@titulo' => $organograma->titulo]);
    }

    $form['organograma_id'] = [
      '#type' => 'hidden',
      '#value' => $organograma_id,
    ];

    $membros = $this->getListaArvore($organograma_id);

    $form['instrucoes_drag'] = [
      '#markup' => '<div style="margin-bottom: 20px;">
        <p>Esta seção é destinada a organizar a hierarquia do organograma. Você pode arrastar e soltar os membros para definir quem é subordinado a quem e a ordem de exibição.</p>
        <p><strong>Instruções:</strong></p>
        <ul style="margin-top: 5px;">
          <li>Utilize o ícone de cruz direcional <span style="font-size: 1.2em;">☩</span> ao lado do nome para mover os membros.</li>
          <li><strong>Mudar Ordem (Peso):</strong> Arraste para cima ou para baixo para alterar quem aparece primeiro na mesma linha.</li>
          <li><strong>Subordinação:</strong> Arraste o militar para a <strong>direita</strong>, colocando-o logo abaixo do seu chefe imediato. O recuo visual confirmará a subordinação.</li>
        </ul>
        <p><em>Nota: Não se esqueça de clicar em "Salvar Nova Ordem e Hierarquia" no final da página para aplicar as mudanças!</em></p>
      </div>',
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
            'url' => Url::fromRoute('mikedelta_organogramas.admin_edit', ['organograma_id' => $organograma_id, 'id' => $id]),
          ],
          'delete' => [
            'title' => $this->t('Excluir'),
            'url' => Url::fromRoute('mikedelta_organogramas.admin_delete', ['organograma_id' => $organograma_id, 'id' => $id]),
          ],
        ],
      ];
    }


    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['style' => 'display: flex; gap: 10px; align-items: center; margin-top: 20px;'],
    ];

    if (!empty($membros)) {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Salvar Nova Ordem e Hierarquia'),
        '#button_type' => 'primary',
      ];
    }

    $form['actions']['voltar'] = [
      '#type' => 'link',
      '#title' => $this->t('Voltar'),
      '#url' => Url::fromRoute('mikedelta_organogramas.dashboard'),
      '#attributes' => ['class' => ['button']],
    ];

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