(function (Drupal, once, drupalSettings) {
  "use strict";

  function getContrastYIQ(hexcolor) {
    hexcolor = hexcolor.replace("#", "");
    var r = parseInt(hexcolor.substr(0, 2), 16);
    var g = parseInt(hexcolor.substr(2, 2), 16);
    var b = parseInt(hexcolor.substr(4, 2), 16);
    var yiq = (r * 299 + g * 587 + b * 114) / 1000;
    return yiq >= 128 ? "#000000" : "#ffffff";
  }

  Drupal.behaviors.mikeDeltaOrganograma = {
    attach: function (context, settings) {
      once("initOrganograma", ".organograma-wrapper", context).forEach(
        function (element) {
          var dadosMembros = settings.mikeDeltaData.membros;

          if (!dadosMembros || Object.keys(dadosMembros).length === 0) {
            return;
          }

          var config = {
            container: "#mikedelta-tree",
            levelSeparation: 40,
            siblingSeparation: 28,
            subTeeSeparation: 15,
            connectors: {
              type: "step",
              style: { stroke: "#000000", "stroke-width": 2 },
            },
            node: { HTMLclass: "md-cartao" },
          };

          var nodes = {};
          var tree_data = [config];

          // Cria os nós HTML
          for (var i = 0; i < dadosMembros.length; i++) {
            var m = dadosMembros[i];

            var corTexto = getContrastYIQ(m.cor_principal);
            var corSubtexto =
              corTexto === "#000000"
                ? "rgba(0,0,0,0.7)"
                : "rgba(255,255,255,0.7)";
            var corBordaInterna =
              corTexto === "#000000"
                ? "rgba(0,0,0,0.1)"
                : "rgba(255,255,255,0.2)";

            var corDestaque = corTexto === "#000000" ? "#1d4ed8" : "#93c5fd";

            var htmlCartao = `
            <div class="md-cartao-inner" style="background: linear-gradient(135deg, ${m.cor_principal}, ${m.cor_secundaria}); color: ${corTexto};">
              
              ${m.codigo_funcao ? `<span class="md-cartao-cpoid" style="background-color: ${m.codigo_funcao_bgcolor}; color: ${m.codigo_funcao_color};" title="${m.codigo_funcao}">${m.codigo_funcao}</span>` : ""}
              
              <div class="md-cartao-foto-container" style="box-shadow: inset -5px 0 0 ${m.codigo_funcao_bgcolor};">
                ${m.foto_url ? `<img src="${m.foto_url}" class="md-cartao-foto" alt="Foto">` : `<div class="md-cartao-sem-foto">Sem<br>Foto</div>`}
              </div>
              
              <div class="md-cartao-dados">
                <div class="md-cartao-header" style="border-bottom: 1px solid ${corBordaInterna};">
                  <span class="md-cartao-funcao" style="color: ${corDestaque};" title="${m.nome_funcao}">${m.nome_funcao}</span>
                </div>
                
                <div class="md-cartao-nome" title="${m.titulo_cargo} ${m.nome}">${m.titulo_cargo} ${m.nome}</div>
                
                <div class="md-cartao-retelma" style="color: ${corSubtexto};">
                  ${m.retelma ? "RET: " + m.retelma : ""}
                  ${m.email ? `<a href="mailto:${m.email}" title="${m.email}" style="color: inherit; text-decoration: none; margin-left: 8px;"><i class="fa-solid fa-envelope"></i></a>` : ""}
                </div>
              </div>
            </div>
          `;

            var empilhar = parseInt(m.empilhar_filhos) === 1;

            nodes[m.id] = {
              innerHTML: htmlCartao,
              stackChildren: empilhar,
              meta: { posicao: parseInt(m.posicao_linha) || 3 },
            };
          }
          // Estabelece hierarquia
          for (var i = 0; i < dadosMembros.length; i++) {
            var m = dadosMembros[i];
            if (m.superior_id && nodes[m.superior_id]) {
              nodes[m.id].parent = nodes[m.superior_id];
            }
            tree_data.push(nodes[m.id]);
          }

          // Renderiza
          new Treant(tree_data);
        },
      );
    },
  };
})(Drupal, once, drupalSettings);
