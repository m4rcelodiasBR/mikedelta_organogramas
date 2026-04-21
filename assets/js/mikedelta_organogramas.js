(function (Drupal, once, drupalSettings) {
  'use strict';

  Drupal.behaviors.mikeDeltaOrganograma = {
    attach: function (context, settings) {
      
      // O 'once' garante que o código não duplique o gráfico caso a página tenha AJAX
      once('initOrganograma', '.organograma-wrapper', context).forEach(function (element) {
        
        // Recebe os dados que vieram do PHP/Controlador
        var dadosMembros = settings.mikeDeltaData.membros;
        
        // Se não houver dados, não desenha nada
        if (!dadosMembros || Object.keys(dadosMembros).length === 0) {
          return;
        }

        var config = {
          container: "#mikedelta-tree",
          levelSeparation: 60,
          siblingSeparation: 40,
          subTeeSeparation: 40,
          connectors: {
            type: 'step',
            style: { "stroke": "#ffffff", "stroke-width": 2 }
          },
          node: { HTMLclass: 'md-cartao' }
        };

        var nodes = {};
        var tree_data = [config];

        // Cria os nós HTML
        for (var id in dadosMembros) {
          var m = dadosMembros[id];
          
          var htmlCartao = `
            <div class="md-cartao-inner" style="background: linear-gradient(135deg, ${m.cor_principal}, ${m.cor_secundaria}); border: 1px solid #475569; border-radius: 6px; width: 280px; display: flex; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
              <div style="width: 80px; background-color: #e2e8f0; display:flex; align-items:center; justify-content:center;">
                ${m.foto_url ? `<img src="${m.foto_url}" style="width: 100%; height: 100%; object-fit: cover;">` : `<div style="font-size: 10px; color:#64748b; text-align:center;">Sem<br>Foto</div>`}
              </div>
              <div style="flex: 1; padding: 10px; color: #fff;">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 4px; margin-bottom: 4px;">
                  <span style="font-weight: bold; font-size: 14px; color: #93c5fd;">${m.funcao_nome}</span>
                  ${m.cpo_id ? `<span style="background-color: #0284c7; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold;">${m.cpo_id}</span>` : ''}
                </div>
                <div style="font-size: 13px; font-weight: bold;">${m.posto_espec} ${m.nome_guerra}</div>
                <div style="font-size: 11px; color: #cbd5e1; margin-top: 4px;">RET: ${m.retelma}</div>
              </div>
            </div>
          `;

          nodes[id] = { innerHTML: htmlCartao };
        }

        // Estabelece hierarquia
        for (var id in dadosMembros) {
          var m = dadosMembros[id];
          if (m.superior_id && nodes[m.superior_id]) {
            nodes[id].parent = nodes[m.superior_id];
          }
          tree_data.push(nodes[id]);
        }

        // Renderiza
        new Treant(tree_data);
      });
    }
  };

})(Drupal, once, drupalSettings);