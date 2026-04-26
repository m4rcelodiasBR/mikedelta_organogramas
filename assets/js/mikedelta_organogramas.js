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
            levelSeparation: 60,
            siblingSeparation: 28,
            subTeeSeparation: 30,
            connectors: {
              type: "step",
              style: { stroke: "#555555", "stroke-width": 1 },
            },
            node: { HTMLclass: "md-cartao" },
          };

          var nodes = {};
          var tree_data = [config];

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

            var corDestaque = corTexto === "#000000" ? "#123083" : "#88aeda";

            var htmlCartao = `
            <div class="md-cartao-inner" style="background: linear-gradient(to bottom, ${m.cor_principal}, ${m.cor_secundaria}); color: ${corTexto};">
              
              ${m.codigo_funcao ? `<span class="md-cartao-cpoid" style="background-color: ${m.codigo_funcao_bgcolor}; color: ${m.codigo_funcao_color};" title="${m.codigo_funcao}">${m.codigo_funcao}</span>` : ""}
              
              <div class="md-cartao-foto-container" style="border-left: .3rem solid ${m.codigo_funcao_bgcolor};">
                ${m.foto_url ? `<img src="${m.foto_url}" class="md-cartao-foto" alt="Foto">` : `<div class="md-cartao-sem-foto">Sem<br>Foto</div>`}
              </div>
              
              <div class="md-cartao-dados">
                <div class="md-cartao-header" style="border-bottom: 1px solid ${corBordaInterna};">
                  <span class="md-cartao-funcao" style="color: ${corDestaque};" title="${m.nome_funcao}">${m.nome_funcao}</span>
                </div>

                <div class="md-cartao-nome" title="${m.titulo_cargo} ${m.nome}">${m.titulo_cargo} ${m.nome}</div>
                
                <div class="md-cartao-email" style="color: ${corSubtexto};">
                  ${m.retelma ? `<span>RET.: ${m.retelma}</span>` : ""}
                  ${m.email ? `<a href="mailto:${m.email}" title="${m.email}"><svg class="md-icone-email" width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="${corTexto}" style="flex-shrink: 0; display: block;"><path d="M112 128C85.5 128 64 149.5 64 176C64 191.1 71.1 205.3 83.2 214.4L291.2 370.4C308.3 383.2 331.7 383.2 348.8 370.4L556.8 214.4C568.9 205.3 576 191.1 576 176C576 149.5 554.5 128 528 128L112 128zM64 260L64 448C64 483.3 92.7 512 128 512L512 512C547.3 512 576 483.3 576 448L576 260L377.6 408.8C343.5 434.4 296.5 434.4 262.4 408.8L64 260z"/></svg></a>` : ""}
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

          for (var i = 0; i < dadosMembros.length; i++) {
            var m = dadosMembros[i];
            if (m.superior_id && nodes[m.superior_id]) {
              nodes[m.id].parent = nodes[m.superior_id];
            }
            tree_data.push(nodes[m.id]);
          }

          new Treant(tree_data);
          
          var btnBaixar = document.getElementById('md-btn-baixar-imagem');
          if (btnBaixar) {
            btnBaixar.addEventListener('click', function() {
              var elementoAlvo = document.querySelector('#mikedelta-tree');
              if (!elementoAlvo) return;
              
              var textoOriginal = btnBaixar.innerHTML;
              
              btnBaixar.innerHTML = 'Gerando imagem...';
              btnBaixar.style.opacity = '0.7';
              
              html2canvas(elementoAlvo, {
                backgroundColor: null,
                scale: 2,
                useCORS: true,
                logging: false
              }).then(function(canvas) {
                var link = document.createElement('a');
                link.download = 'organograma-exportado.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
                btnBaixar.innerHTML = textoOriginal;
                btnBaixar.style.opacity = '1';
              });
            });
          }
        },
      );
    },
  };
})(Drupal, once, drupalSettings);
