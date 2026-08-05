<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Cronograma — {{ $projeto->nomeProjeto }}</title>

  <link rel="stylesheet" href="/vendor/jquerygantt/platform.css">
  <link rel="stylesheet" href="/vendor/jquerygantt/libs/jquery/dateField/jquery.dateField.css">
  <link rel="stylesheet" href="/vendor/jquerygantt/gantt.css">
  <link rel="stylesheet" href="/vendor/jquerygantt/ganttPrint.css" media="print">

  {{-- Fallback de localStorage: evita "SecurityError" quando a página roda em contexto
       sandboxed (iframe/preview). O jQueryGantt usa localStorage p/ colapso/zoom. --}}
  <script>
  (function () {
    var ok = false;
    try { window.localStorage.setItem('__t', '1'); window.localStorage.removeItem('__t'); ok = true; } catch (e) {}
    if (!ok) {
      var mem = {};
      var shim = {
        getItem: function (k) { return Object.prototype.hasOwnProperty.call(mem, k) ? mem[k] : null; },
        setItem: function (k, v) { mem[k] = String(v); },
        removeItem: function (k) { delete mem[k]; },
        clear: function () { mem = {}; },
        key: function (i) { return Object.keys(mem)[i] || null; }
      };
      Object.defineProperty(shim, 'length', { get: function () { return Object.keys(mem).length; } });
      try { Object.defineProperty(window, 'localStorage', { value: shim, configurable: true, writable: true }); } catch (e) {}
    }
  })();
  </script>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"></script>

  <script src="/vendor/jquerygantt/libs/jquery/jquery.livequery.1.1.1.min.js"></script>
  <script src="/vendor/jquerygantt/libs/jquery/jquery.timers.js"></script>
  <script src="/vendor/jquerygantt/libs/utilities.js"></script>
  <script src="/vendor/jquerygantt/libs/forms.js"></script>
  <script src="/vendor/jquerygantt/libs/date.js"></script>
  <script src="/vendor/jquerygantt/libs/dialogs.js"></script>
  <script src="/vendor/jquerygantt/libs/layout.js"></script>
  <script src="/vendor/jquerygantt/libs/i18nJs.js"></script>
  <script src="/vendor/jquerygantt/libs/jquery/dateField/jquery.dateField.js"></script>
  <script src="/vendor/jquerygantt/libs/jquery/JST/jquery.JST.js"></script>
  <script src="/vendor/jquerygantt/libs/jquery/valueSlider/jquery.mb.slider.js"></script>
  <script src="/vendor/jquerygantt/libs/jquery/svg/jquery.svg.min.js"></script>
  <script src="/vendor/jquerygantt/libs/jquery/svg/jquery.svgdom.1.8.js"></script>
  <script src="/vendor/jquerygantt/ganttUtilities.js"></script>
  <script src="/vendor/jquerygantt/ganttTask.js"></script>
  <script src="/vendor/jquerygantt/ganttDrawerSVG.js"></script>
  <script src="/vendor/jquerygantt/ganttZoom.js"></script>
  <script src="/vendor/jquerygantt/ganttGridEditor.js"></script>
  <script src="/vendor/jquerygantt/ganttMaster.js"></script>

  <style>
    :root {
      --app-font: "Segoe UI", Roboto, Arial, sans-serif;
    }
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      overflow: hidden;
      background-color: #fff;
      font-family: var(--app-font);
    }
    body,
    input,
    select,
    textarea,
    button {
      font-family: var(--app-font);
    }
    #gantt-topbar {
      position: fixed;
      top: 4px;
      right: 10px;
      z-index: 9999;
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 4px 8px;
      background: transparent;
      pointer-events: none;
    }
    #gantt-topbar > * {
      pointer-events: auto;
    }
    #gantt-topbar a.btn-top {
      font-family: var(--app-font);
      font-size: 12px;
      color: #2b9af3;
      text-decoration: none;
      font-weight: bold;
      padding: 3px 10px;
      border: 1px solid #2b9af3;
      border-radius: 3px;
      background: #fff;
    }
    #gantt-topbar a.btn-top:hover {
      background: #e8f4ff;
    }
    #workSpace {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      overflow: hidden;
      font-family: var(--app-font);
    }
    #workSpace .gdfCell,
    #workSpace .gdfColHeader,
    #workSpace .ganttTaskEditor,
    #workSpace .resourceEditor,
    #workSpace .taskFieldsHelpModal,
    #workSpace input,
    #workSpace select,
    #workSpace textarea,
    #workSpace button {
      font-family: var(--app-font) !important;
    }
    #workSpace .taskLabelSVG,
    #workSpace .textPerc {
      font-family: var(--app-font);
    }
    .ganttButtonBar h1 {
      color: #000000;
      font-weight: bold;
      font-size: 28px;
      margin-left: 10px;
    }
    /* Estilos originais limpos idênticos ao Phalcon project.phtml */
    #workSpace .vBoxGantt { overflow-x: auto !important; overflow-y: auto !important; }
    #workSpace .vBoxGantt::-webkit-scrollbar:vertical { width: 0px !important; display: none !important; }
    #workSpace .vBoxGantt { -ms-overflow-style: none; scrollbar-width: none; }
    #workSpace .ganttSVGPage { overflow-x: auto !important; overflow-y: auto !important; }
    .taskEditorTitle {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 0 0 12px;
    }
    .taskEditorTitle h2 {
      margin: 0;
    }
    .taskFieldsHelpBtn {
      border: 1px solid #8abbe0;
      background: #eef8ff;
      color: #24516f;
      border-radius: 4px;
      cursor: pointer;
      font-size: 12px;
      font-weight: 700;
      padding: 5px 9px;
      line-height: 1.2;
    }
    .taskFieldsHelpBtn:hover {
      background: #dff1ff;
      border-color: #5aa5d8;
    }
    .taskFieldsHelpModal {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 250;
      background: rgba(20, 31, 43, .42);
      padding: 28px;
      box-sizing: border-box;
    }
    .taskFieldsHelpPanel {
      width: min(760px, 100%);
      max-height: calc(100vh - 56px);
      overflow: auto;
      margin: 0 auto;
      background: #fff;
      border-radius: 6px;
      box-shadow: 0 18px 45px rgba(15, 23, 42, .28);
      border: 1px solid #cfe8f8;
    }
    .taskFieldsHelpHeader {
      position: sticky;
      top: 0;
      z-index: 1;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 18px 22px;
      background: #f8fcff;
      border-bottom: 1px solid #d7edf9;
    }
    .taskFieldsHelpHeader h3 {
      margin: 0;
      color: #2d4964;
      font-size: 22px;
      font-weight: 700;
    }
    .taskFieldsHelpClose {
      border: 0;
      background: transparent;
      color: #334155;
      cursor: pointer;
      font-size: 24px;
      line-height: 1;
      padding: 2px 6px;
    }
    .taskFieldsHelpBody {
      padding: 18px 22px 22px;
    }
    .taskFieldsHelpBody dl {
      display: grid;
      grid-template-columns: 190px 1fr;
      gap: 12px 18px;
      margin: 0;
    }
    .taskFieldsHelpBody dt {
      color: #1f3f5a;
      font-weight: 700;
    }
    .taskFieldsHelpBody dd {
      color: #334155;
      line-height: 1.45;
      margin: 0;
    }
    @media (max-width: 640px) {
      .taskEditorTitle {
        align-items: flex-start;
        flex-direction: column;
      }
      .taskFieldsHelpModal {
        padding: 14px;
      }
      .taskFieldsHelpPanel {
        max-height: calc(100vh - 28px);
      }
      .taskFieldsHelpBody dl {
        grid-template-columns: 1fr;
        gap: 4px 0;
      }
      .taskFieldsHelpBody dd {
        margin-bottom: 12px;
      }
    }
  </style>
</head>
<body>

  <div id="gantt-topbar">
    <a class="btn-top" href="{{ route('projetos.show', $projeto->id) }}">Ver Kanban</a>
    <a class="btn-top" href="{{ route('projetos.index') }}">Projetos</a>
  </div>

  <div id="workSpace"></div>

@verbatim
  <div id="gantEditorTemplates" style="display:none;">
    <div class="__template__" type="GANTBUTTONS">
      <!--
      <div class="ganttButtonBar noprint">
        <div class="buttons">
          <button onclick="$('#workSpace').trigger('undo.gantt');return false;" class="button textual icon requireCanWrite" title="desfazer"><span class="teamworkIcon">&#39;</span></button>
          <button onclick="$('#workSpace').trigger('redo.gantt');return false;" class="button textual icon requireCanWrite" title="refazer"><span class="teamworkIcon">&middot;</span></button>
          <span class="ganttButtonSeparator requireCanWrite requireCanAdd"></span>
          <button onclick="$('#workSpace').trigger('addAboveCurrentTask.gantt');return false;" class="button textual icon requireCanWrite requireCanAdd" title="inserir acima"><span class="teamworkIcon">l</span></button>
          <button onclick="$('#workSpace').trigger('addBelowCurrentTask.gantt');return false;" class="button textual icon requireCanWrite requireCanAdd" title="inserir abaixo"><span class="teamworkIcon">X</span></button>
          <span class="ganttButtonSeparator requireCanWrite requireCanInOutdent"></span>
          <button onclick="$('#workSpace').trigger('outdentCurrentTask.gantt');return false;" class="button textual icon requireCanWrite requireCanInOutdent" title="diminuir nível"><span class="teamworkIcon">.</span></button>
          <button onclick="$('#workSpace').trigger('indentCurrentTask.gantt');return false;" class="button textual icon requireCanWrite requireCanInOutdent" title="aumentar nível"><span class="teamworkIcon">:</span></button>
          <span class="ganttButtonSeparator requireCanWrite requireCanMoveUpDown"></span>
          <button onclick="$('#workSpace').trigger('moveUpCurrentTask.gantt');return false;" class="button textual icon requireCanWrite requireCanMoveUpDown" title="mover para cima"><span class="teamworkIcon">k</span></button>
          <button onclick="$('#workSpace').trigger('moveDownCurrentTask.gantt');return false;" class="button textual icon requireCanWrite requireCanMoveUpDown" title="mover para baixo"><span class="teamworkIcon">j</span></button>
          <span class="ganttButtonSeparator requireCanWrite requireCanDelete"></span>
          <button onclick="$('#workSpace').trigger('deleteFocused.gantt');return false;" class="button textual icon delete requireCanWrite" title="excluir"><span class="teamworkIcon">&cent;</span></button>
          <span class="ganttButtonSeparator"></span>
          <button onclick="$('#workSpace').trigger('expandAll.gantt');return false;" class="button textual icon " title="expandir tudo"><span class="teamworkIcon">6</span></button>
          <button onclick="$('#workSpace').trigger('collapseAll.gantt'); return false;" class="button textual icon " title="recolher tudo"><span class="teamworkIcon">5</span></button>
          <span class="ganttButtonSeparator"></span>
          <button onclick="$('#workSpace').trigger('zoomMinus.gantt'); return false;" class="button textual icon " title="menos zoom"><span class="teamworkIcon">)</span></button>
          <button onclick="$('#workSpace').trigger('zoomPlus.gantt');return false;" class="button textual icon " title="mais zoom"><span class="teamworkIcon">(</span></button>
          <span class="ganttButtonSeparator"></span>
          <button onclick="$('#workSpace').trigger('print.gantt');return false;" class="button textual icon " title="imprimir"><span class="teamworkIcon">p</span></button>
          <span class="ganttButtonSeparator"></span>
          <button onclick="ge.gantt.showCriticalPath=!ge.gantt.showCriticalPath; ge.redraw();return false;" class="button textual icon requireCanSeeCriticalPath" title="caminho crítico"><span class="teamworkIcon">&pound;</span></button>
          <span class="ganttButtonSeparator requireCanSeeCriticalPath"></span>
          <button onclick="ge.splitter.resize(.1);return false;" class="button textual icon" title="grade menor"><span class="teamworkIcon">F</span></button>
          <button onclick="ge.splitter.resize(50);return false;" class="button textual icon" title="dividir ao meio"><span class="teamworkIcon">O</span></button>
          <button onclick="ge.splitter.resize(100);return false;" class="button textual icon" title="só a grade"><span class="teamworkIcon">R</span></button>
          <span class="ganttButtonSeparator"></span>
          <button onclick="$('#workSpace').trigger('fullScreen.gantt');return false;" class="button textual icon" title="tela cheia" id="fullscrbtn"><span class="teamworkIcon">@</span></button>
          <button onclick="ge.element.toggleClass('colorByStatus' );return false;" class="button textual icon" title="colorir por situação"><span class="teamworkIcon">&sect;</span></button>
          <button onclick="editResources();" class="button textual requireWrite" title="editar responsáveis"><span class="teamworkIcon">M</span></button>
          &nbsp; &nbsp; &nbsp; &nbsp;
        </div>
        <div>
          <button onclick="saveGanttOnServer();" class="button first big requireWrite" title="Salvar">Salvar</button>
          <input type="file" name="load-file" id="load-file">
          <label for="load-file">Carregar</label>
          <button onclick='newProject();' class='button requireWrite newproject'><em>limpar projeto</em></button>
        </div>
      </div>
      -->
    </div>

    <div class="__template__" type="TASKSEDITHEAD"><!--
      <table class="gdfTable" cellspacing="0" cellpadding="0">
        <thead>
        <tr style="height:40px">
          <th class="gdfColHeader" style="width:35px; border-right: none"></th>
          <th class="gdfColHeader" style="width:25px;"></th>
          <th class="gdfColHeader gdfResizable" style="width:100px;">código</th>
          <th class="gdfColHeader gdfResizable" style="width:300px;">tarefa</th>
          <th class="gdfColHeader"  align="center" style="width:17px;" title="A data de início é um marco."><span class="teamworkIcon" style="font-size: 8px;">^</span></th>
          <th class="gdfColHeader gdfResizable" style="width:80px;">início</th>
          <th class="gdfColHeader"  align="center" style="width:17px;" title="A data de fim é um marco."><span class="teamworkIcon" style="font-size: 8px;">^</span></th>
          <th class="gdfColHeader gdfResizable" style="width:80px;">fim</th>
          <th class="gdfColHeader gdfResizable" style="width:50px;">dur.</th>
          <th class="gdfColHeader gdfResizable" style="width:20px;">%</th>
          <th class="gdfColHeader gdfResizable requireCanSeeDep" style="width:50px;">depend.</th>
          <th class="gdfColHeader gdfResizable" style="width:1000px; text-align: left; padding-left: 10px;">responsáveis</th>
        </tr>
        </thead>
      </table>
      --></div>

    <div class="__template__" type="TASKROW"><!--
      <tr id="tid_(#=obj.id#)" taskId="(#=obj.id#)" class="taskEditRow (#=obj.isParent()?'isParent':''#) (#=obj.collapsed?'collapsed':''#)" level="(#=level#)">
        <th class="gdfCell edit" align="right" style="cursor:pointer;"><span class="taskRowIndex">(#=obj.getRow()+1#)</span> <span class="teamworkIcon" style="font-size:12px;" >e</span></th>
        <td class="gdfCell noClip" align="center"><div class="taskStatus cvcColorSquare" status="(#=obj.status#)"></div></td>
        <td class="gdfCell"><input type="text" name="code" value="(#=obj.code?obj.code:''#)" placeholder="código"></td>
        <td class="gdfCell indentCell" style="padding-left:(#=obj.level*10+18#)px;">
          <div class="exp-controller" align="center"></div>
          <input type="text" name="name" value="(#=obj.name#)" placeholder="tarefa">
        </td>
        <td class="gdfCell" align="center"><input type="checkbox" name="startIsMilestone"></td>
        <td class="gdfCell"><input type="text" name="start"  value="" class="date"></td>
        <td class="gdfCell" align="center"><input type="checkbox" name="endIsMilestone"></td>
        <td class="gdfCell"><input type="text" name="end" value="" class="date"></td>
        <td class="gdfCell"><input type="text" name="duration" autocomplete="off" value="(#=obj.duration#)"></td>
        <td class="gdfCell"><input type="text" name="progress" class="validated" entrytype="PERCENTILE" autocomplete="off" value="(#=obj.progress?obj.progress:''#)" (#=obj.progressByWorklog?"readOnly":""#)></td>
        <td class="gdfCell requireCanSeeDep"><input type="text" name="depends" autocomplete="off" value="(#=obj.depends#)" (#=obj.hasExternalDep?"readonly":""#)></td>
        <td class="gdfCell taskAssigs">(#=obj.getAssigsString()#)</td>
      </tr>
      --></div>

    <div class="__template__" type="TASKEMPTYROW"><!--
      <tr class="taskEditRow emptyRow" >
        <th class="gdfCell" align="right"></th>
        <td class="gdfCell noClip" align="center"></td>
        <td class="gdfCell"></td>
        <td class="gdfCell"></td>
        <td class="gdfCell"></td>
        <td class="gdfCell"></td>
        <td class="gdfCell"></td>
        <td class="gdfCell"></td>
        <td class="gdfCell"></td>
        <td class="gdfCell"></td>
        <td class="gdfCell requireCanSeeDep"></td>
        <td class="gdfCell"></td>
      </tr>
      --></div>

    <div class="__template__" type="TASKBAR"><!--
      <div class="taskBox taskBoxDiv" taskId="(#=obj.id#)" >
        <div class="layout (#=obj.hasExternalDep?'extDep':''#)">
          <div class="taskStatus" status="(#=obj.status#)"></div>
          <div class="taskProgress" style="width:(#=obj.progress>100?100:obj.progress#)%; background-color:(#=obj.progress>100?'red':'rgb(153,255,51);'#);"></div>
          <div class="milestone (#=obj.startIsMilestone?'active':''#)" ></div>
          <div class="taskLabel"></div>
          <div class="milestone end (#=obj.endIsMilestone?'active':''#)" ></div>
        </div>
      </div>
      --></div>

    <div class="__template__" type="CHANGE_STATUS"><!--
        <div class="taskStatusBox">
        <div class="taskStatus cvcColorSquare" status="STATUS_ACTIVE" title="Ativo"></div>
        <div class="taskStatus cvcColorSquare" status="STATUS_DONE" title="Concluído"></div>
        <div class="taskStatus cvcColorSquare" status="STATUS_FAILED" title="Falhou"></div>
        <div class="taskStatus cvcColorSquare" status="STATUS_SUSPENDED" title="Suspenso"></div>
        <div class="taskStatus cvcColorSquare" status="STATUS_WAITING" title="Aguardando" style="display: none;"></div>
        <div class="taskStatus cvcColorSquare" status="STATUS_UNDEFINED" title="Indefinido"></div>
        </div>
      --></div>

    <div class="__template__" type="TASK_EDITOR"><!--
      <div class="ganttTaskEditor">
        <div class="taskData taskEditorTitle">
          <h2>Editor de tarefa</h2>
          <button type="button" class="taskFieldsHelpBtn" onclick="$(this).closest('.ganttTaskEditor').find('.taskFieldsHelpModal').fadeIn(120);return false;">Entenda os campos</button>
        </div>
        <div class="taskFieldsHelpModal" onclick="if(event.target===this){$(this).fadeOut(120);}">
          <div class="taskFieldsHelpPanel" role="dialog" aria-modal="true" aria-label="Entenda os campos do editor de tarefa">
            <div class="taskFieldsHelpHeader">
              <h3>Entenda os campos</h3>
              <button type="button" class="taskFieldsHelpClose" title="Fechar" onclick="$(this).closest('.taskFieldsHelpModal').fadeOut(120);return false;">&times;</button>
            </div>
            <div class="taskFieldsHelpBody">
              <dl>
                <dt>Código</dt>
                <dd>Identificador curto da tarefa, tipo <strong>EST-001</strong>, <strong>A1</strong>, <strong>B2</strong>. Serve para referência rápida, organização e busca. Não é a dependência em si.</dd>

                <dt>Tarefa</dt>
                <dd>Nome principal da atividade. É o texto que aparece na linha da grade e ao lado da barra no Gantt.</dd>

                <dt>Início</dt>
                <dd>Data em que a tarefa começa.</dd>

                <dt>É marco ao lado de início</dt>
                <dd>Marca a data de início como um ponto importante ou travado. Exemplo: embarque, reunião inicial, assinatura. Não significa necessariamente tarefa sem duração.</dd>

                <dt>Fim</dt>
                <dd>Data em que a tarefa termina.</dd>

                <dt>É marco ao lado de fim</dt>
                <dd>Marca a data final como um marco ou entrega importante. Exemplo: prazo final, go-live, entrega ao cliente.</dd>

                <dt>Dias</dt>
                <dd>Duração da tarefa, em dias úteis no modelo do jQueryGantt. Normalmente é calculado entre início e fim, mas também pode recalcular as datas quando alterado.</dd>

                <dt>Situação</dt>
                <dd>Status da tarefa. Exemplos: ativo, aguardando, suspenso, concluído, falhou, indefinido. Ajuda a controlar andamento e cor/estado visual.</dd>

                <dt>Progresso</dt>
                <dd>Percentual concluído da tarefa, de <strong>0</strong> a <strong>100</strong>. No exemplo, <strong>100</strong> significa concluída.</dd>

                <dt>Descrição</dt>
                <dd>Campo livre para detalhes, observações, critérios, escopo ou instruções da tarefa.</dd>

                <dt>Responsáveis - nome</dt>
                <dd>Pessoa atribuída à tarefa.</dd>

                <dt>Responsáveis - papel</dt>
                <dd>Função dessa pessoa na tarefa. Exemplo: responsável, executor, aprovador, apoio, etc. Hoje aparece Responsável.</dd>

                <dt>Responsáveis - esforço</dt>
                <dd>Quantidade de trabalho planejado para aquela pessoa, normalmente em horas/minutos. Isso é diferente de duração: uma tarefa pode durar 7 dias, mas exigir só 4 horas de esforço.</dd>

                <dt>+</dt>
                <dd>Adiciona mais um responsável à tarefa.</dd>

                <dt>Lixeira</dt>
                <dd>Remove aquele responsável da tarefa.</dd>

                <dt>Salvar</dt>
                <dd>Grava as alterações feitas no editor.</dd>

                <dt>X</dt>
                <dd>Fecha o editor sem usar o botão salvar. Dependendo do comportamento atual, alterações não salvas podem ser descartadas.</dd>
              </dl>
            </div>
          </div>
        </div>
        <table  cellspacing="1" cellpadding="5" width="100%" class="taskData table" border="0">
          <tr>
            <td width="200" style="height: 80px"  valign="top">
              <label for="code">código</label><br>
              <input type="text" name="code" id="code" value="" size=15 class="formElements" autocomplete='off' maxlength=255 style='width:100%' oldvalue="1">
            </td>
            <td colspan="3" valign="top"><label for="name" class="required">tarefa</label><br><input type="text" name="name" id="name"class="formElements" autocomplete='off' maxlength=255 style='width:100%' value="" required="true" oldvalue="1"></td>
          </tr>
          <tr class="dateRow">
            <td nowrap="">
              <div style="position:relative">
                <label for="start">início</label>&nbsp;&nbsp;&nbsp;&nbsp;
                <input type="checkbox" id="startIsMilestone" name="startIsMilestone" value="yes"> &nbsp;<label for="startIsMilestone">é marco</label>&nbsp;
                <br><input type="text" name="start" id="start" size="8" class="formElements dateField validated date" autocomplete="off" maxlength="255" value="" oldvalue="1" entrytype="DATE">
                <span title="calendário" id="starts_inputDate" class="teamworkIcon openCalendar" onclick="$(this).dateField({inputField:$(this).prevAll(':input:first'),isSearchField:false});">m</span>          </div>
            </td>
            <td nowrap="">
              <label for="end">fim</label>&nbsp;&nbsp;&nbsp;&nbsp;
              <input type="checkbox" id="endIsMilestone" name="endIsMilestone" value="yes"> &nbsp;<label for="endIsMilestone">é marco</label>&nbsp;
              <br><input type="text" name="end" id="end" size="8" class="formElements dateField validated date" autocomplete="off" maxlength="255" value="" oldvalue="1" entrytype="DATE">
              <span title="calendário" id="ends_inputDate" class="teamworkIcon openCalendar" onclick="$(this).dateField({inputField:$(this).prevAll(':input:first'),isSearchField:false});">m</span>
            </td>
            <td nowrap="" >
              <label for="duration" class=" ">dias</label><br>
              <input type="text" name="duration" id="duration" size="4" class="formElements validated durationdays" title="Duração em dias úteis." autocomplete="off" maxlength="255" value="" oldvalue="1" entrytype="DURATIONDAYS">&nbsp;
            </td>
          </tr>
          <tr>
            <td  colspan="2">
              <label for="status" class=" ">situação</label><br>
              <select id="status" name="status" class="taskStatus" status="(#=obj.status#)"  onchange="$(this).attr('STATUS',$(this).val());">
                <option value="STATUS_ACTIVE" class="taskStatus" status="STATUS_ACTIVE" >ativo</option>
                <option value="STATUS_WAITING" class="taskStatus" status="STATUS_WAITING" >aguardando</option>
                <option value="STATUS_SUSPENDED" class="taskStatus" status="STATUS_SUSPENDED" >suspenso</option>
                <option value="STATUS_DONE" class="taskStatus" status="STATUS_DONE" >concluído</option>
                <option value="STATUS_FAILED" class="taskStatus" status="STATUS_FAILED" >falhou</option>
                <option value="STATUS_UNDEFINED" class="taskStatus" status="STATUS_UNDEFINED" >indefinido</option>
              </select>
            </td>
            <td valign="top" nowrap>
              <label>progresso</label><br>
              <input type="text" name="progress" id="progress" size="7" class="formElements validated percentile" autocomplete="off" maxlength="255" value="" oldvalue="1" entrytype="PERCENTILE">
            </td>
          </tr>
          <tr>
            <td colspan="4">
              <label for="description">descrição</label><br>
              <textarea rows="3" cols="30" id="description" name="description" class="formElements" style="width:100%"></textarea>
            </td>
          </tr>
        </table>

        <h2>Responsáveis</h2>
        <table  cellspacing="1" cellpadding="0" width="100%" id="assigsTable">
          <tr>
            <th style="width:100px;">nome</th>
            <th style="width:70px;">papel</th>
            <th style="width:30px;">esforço</th>
            <th style="width:30px;" id="addAssig"><span class="teamworkIcon" style="cursor: pointer">+</span></th>
          </tr>
        </table>

        <div style="text-align: right; padding-top: 20px">
          <span id="saveButton" class="button first" onClick="$(this).trigger('saveFullEditor.gantt');">Salvar</span>
        </div>
      </div>
      --></div>

    <div class="__template__" type="ASSIGNMENT_ROW"><!--
      <tr taskId="(#=obj.task.id#)" assId="(#=obj.assig.id#)" class="assigEditRow" >
        <td ><select name="resourceId"  class="formElements" (#=obj.assig.id.indexOf("tmp_")==0?"":"disabled"#) ></select></td>
        <td ><select type="select" name="roleId"  class="formElements"></select></td>
        <td ><input type="text" name="effort" value="(#=getMillisInHoursMinutes(obj.assig.effort)#)" size="5" class="formElements"></td>
        <td align="center"><span class="teamworkIcon delAssig del" style="cursor: pointer">d</span></td>
      </tr>
      --></div>

    <div class="__template__" type="RESOURCE_EDITOR"><!--
      <div class="resourceEditor" style="padding: 5px;">
        <h2>Equipe do projeto</h2>
        <table  cellspacing="1" cellpadding="0" width="100%" id="resourcesTable">
          <tr>
            <th style="width:100px;">nome</th>
            <th style="width:30px;" id="addResource"><span class="teamworkIcon" style="cursor: pointer">+</span></th>
          </tr>
        </table>
        <div style="text-align: right; padding-top: 20px"><button id="resSaveButton" class="button big">Salvar</button></div>
      </div>
      --></div>

    <div class="__template__" type="RESOURCE_ROW"><!--
      <tr resId="(#=obj.id#)" class="resRow" >
        <td ><input type="text" name="name" value="(#=obj.name#)" style="width:100%;" class="formElements"></td>
        <td align="center"><span class="teamworkIcon delRes del" style="cursor: pointer">d</span></td>
      </tr>
      --></div>
  </div>
@endverbatim

  <script>
    // Decorators dos templates (assignments / resources)
    $.JST.loadDecorator("RESOURCE_ROW", function (resTr, res) {
      resTr.find(".delRes").click(function () { $(this).closest("tr").remove(); });
    });

    $.JST.loadDecorator("ASSIGNMENT_ROW", function (assigTr, taskAssig) {
      var resEl = assigTr.find("[name=resourceId]");
      resEl.append($("<option>"));
      for (var i = 0; i < taskAssig.task.master.resources.length; i++) {
        var res = taskAssig.task.master.resources[i];
        var opt = $("<option>").val(res.id).html(res.name);
        if (taskAssig.assig.resourceId == res.id) opt.attr("selected", "true");
        resEl.append(opt);
      }
      var roleEl = assigTr.find("[name=roleId]");
      for (var j = 0; j < taskAssig.task.master.roles.length; j++) {
        var role = taskAssig.task.master.roles[j];
        var optr = $("<option>").val(role.id).html(role.name);
        if (taskAssig.assig.roleId == role.id) optr.attr("selected", "true");
        roleEl.append(optr);
      }
      if (taskAssig.task.master.permissions.canWrite && taskAssig.task.canWrite) {
        assigTr.find(".delAssig").click(function () {
          $(this).closest("[assId]").fadeOut(200, function () { $(this).remove(); });
        });
      }
    });

    // i18n pt-BR (datas + mensagens)
    Date.monthNames = ["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"];
    Date.monthAbbreviations = ["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"];
    Date.dayNames = ["Domingo","Segunda","Terça","Quarta","Quinta","Sexta","Sábado"];
    Date.dayAbbreviations = ["Dom","Seg","Ter","Qua","Qui","Sex","Sáb"];
    Date.preferAmericanFormat = false;
    Date.defaultFormat = "dd/MM/yyyy";
    Date.today = "Hoje";

    function loadI18n() {
      GanttMaster.messages = {
        "CANNOT_WRITE": "Sem permissão para alterar a tarefa:",
        "CHANGE_OUT_OF_SCOPE": "Atualização não permitida: faltam direitos no projeto pai.",
        "START_IS_MILESTONE": "A data de início é um marco.",
        "END_IS_MILESTONE": "A data de fim é um marco.",
        "TASK_HAS_CONSTRAINTS": "A tarefa tem restrições.",
        "GANTT_ERROR_DEPENDS_ON_OPEN_TASK": "Erro: dependência de uma tarefa aberta.",
        "GANTT_ERROR_DESCENDANT_OF_CLOSED_TASK": "Erro: descendente de uma tarefa fechada.",
        "TASK_HAS_EXTERNAL_DEPS": "Esta tarefa tem dependências externas.",
        "GANNT_ERROR_LOADING_DATA_TASK_REMOVED": "Erro ao carregar os dados. Uma tarefa foi removida.",
        "CIRCULAR_REFERENCE": "Referência circular.",
        "CANNOT_DEPENDS_ON_ANCESTORS": "Não pode depender de tarefas ancestrais.",
        "INVALID_DATE_FORMAT": "Os dados informados são inválidos para o formato do campo.",
        "GANTT_ERROR_LOADING_DATA_TASK_REMOVED": "Ocorreu um erro ao carregar os dados. Uma tarefa foi descartada.",
        "CANNOT_CLOSE_TASK_IF_OPEN_ISSUE": "Não é possível concluir uma tarefa com pendências abertas.",
        "TASK_MOVE_INCONSISTENT_LEVEL": "Não é possível trocar tarefas de níveis diferentes.",
        "CANNOT_MOVE_TASK": "Não é possível mover a tarefa.",
        "PLEASE_SAVE_PROJECT": "Salve o projeto antes.",
        "GANTT_SEMESTER": "Semestre",
        "GANTT_SEMESTER_SHORT": "sem.",
        "GANTT_QUARTER": "Trimestre",
        "GANTT_QUARTER_SHORT": "trim.",
        "GANTT_WEEK": "Semana",
        "GANTT_WEEK_SHORT": "sem."
      };
    }

    // Editor de responsáveis (equipe) — abre um popup
    function editResources() {
      var resourceEditor = $.JST.createFromTemplate({}, "RESOURCE_EDITOR");
      var resTbl = resourceEditor.find("#resourcesTable");
      for (var i = 0; i < ge.resources.length; i++) {
        resTbl.append($.JST.createFromTemplate(ge.resources[i], "RESOURCE_ROW"));
      }
      resourceEditor.find("#addResource").click(function () {
        resTbl.append($.JST.createFromTemplate({ id: "new", name: "responsável" }, "RESOURCE_ROW"));
      });
      resourceEditor.find("#resSaveButton").click(function () {
        var newRes = [];
        for (var i = 0; i < ge.resources.length; i++) {
          var res = ge.resources[i];
          var row = resourceEditor.find("[resId=" + res.id + "]");
          if (row.length > 0) {
            var name = row.find("input[name]").val();
            if (name && name != "") res.name = name;
            newRes.push(res);
          } else {
            for (var j = 0; j < ge.tasks.length; j++) {
              var task = ge.tasks[j];
              var newAss = [];
              for (var k = 0; k < task.assigs.length; k++) {
                if (task.assigs[k].resourceId != res.id) newAss.push(task.assigs[k]);
              }
              task.assigs = newAss;
            }
          }
        }
        var cnt = 0;
        resourceEditor.find("[resId=new]").each(function () {
          cnt++;
          var name = $(this).find("input[name]").val();
          if (name && name != "") newRes.push(new Resource("tmp_" + new Date().getTime() + "_" + cnt, name));
        });
        ge.resources = newRes;
        closeBlackPopup();
        ge.redraw();
      });
      createModalPopup(400, 500).append(resourceEditor);
    }

    function upload(uploadedFile) {
      var fileread = new FileReader();
      fileread.onload = function (e) {
        var intern = JSON.parse(e.target.result);
        ge.loadProject(intern);
        ge.checkpoint();
      };
      fileread.readAsText(uploadedFile);
    }

    function newProject() { ge.reset(); }

    $(document).on("change", "#load-file", function () {
      var uploadedFile = $("#load-file").prop("files")[0];
      if (uploadedFile) upload(uploadedFile);
    });
  </script>

  <script>
    var ge;
    var GANTT_PROJECT = {!! json_encode($project, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
    var GANTT_SAVE_URL = "{{ route('projetos.gantt.save', $projeto->id) }}";
    var GANTT_CSRF = "{{ csrf_token() }}";

    var ganttReady = false;
    var autoSaveTimer = null;
    var saving = false;
    var realignTimer = null;

    $(function () {
      ge = new GanttMaster();
      ge.set100OnClose = true;
      ge.shrinkParent = true;
      ge.resourceUrl = "/vendor/jquerygantt/res/";
      ge.init($("#workSpace"));

      // TEMPO REAL: a cada alteração o jQueryGantt dispara "saveRequired.gantt" -> auto-save.
      // (Substitui o manageSaveRequired interno, que tinha bug de this/undoStack.)
      ge.element.off("saveRequired.gantt").on("saveRequired.gantt", function (ev, showSave) {
        if (ganttReady && showSave) scheduleAutoSave();
      });

      // BUG FIX: ao ADICIONAR/REMOVER/MOVER/INDENTAR/RECOLHER uma linha, o jQueryGantt
      // reposiciona a GRADE mas desenha as BARRAS com as posições ANTIGAS (as barras
      // "descem" e desalinham — bug de timing). Redesenhar só o GRÁFICO após essas ações
      // re-alinha, sem recriar a grade (preserva o foco/edição). Ver realignGantt().
      $("#workSpace").on(
        "addAboveCurrentTask.gantt addBelowCurrentTask.gantt deleteFocused.gantt " +
        "indentCurrentTask.gantt outdentCurrentTask.gantt moveUpCurrentTask.gantt " +
        "moveDownCurrentTask.gantt collapseAll.gantt expandAll.gantt",
        realignGantt
      );
      ge.element.on("click", ".exp-controller", realignGantt); // recolher/expandir por linha

      if (typeof loadI18n === "function") loadI18n();
      delete ge.gantt.zoom;
      ge.loadProject(GANTT_PROJECT);
      ge.checkpoint();

      // ALINHAMENTO grade x gráfico: alinha ge.rowHeight à altura REAL da linha da grade
      // (rede de segurança idempotente; se já baterem, não faz nada). Sem isso, se a linha
      // da grade for != 30px, as barras descolam acumulando drift para baixo.
      setTimeout(syncGanttRowHeight, 50);
      setTimeout(syncGanttRowHeight, 400); // 2ª passada de segurança

      // Habilita o auto-save só após a PRIMEIRA interação do usuário no Gantt — assim o
      // reagendamento que o jQueryGantt faz ao CARREGAR não é salvo (evita deslocar datas).
      $('#workSpace').one('mousedown keydown', function () { ganttReady = true; });
    });

    // Redesenha SÓ o gráfico (as barras) para re-alinhar com a grade após mudanças que
    // alteram as linhas (add/remove/mover/indentar/recolher). Diferido + debounced;
    // ge.gantt.redraw() NÃO recria a grade, então o foco/edição na grade é preservado.
    function realignGantt() {
      clearTimeout(realignTimer);
      realignTimer = setTimeout(function () {
        if (window.ge && ge.gantt) {
          syncGanttScrollState();
          ge.gantt.redraw();
        }
      }, 40);
    }

    function syncGanttScrollState() {
      if (!window.ge || !ge.splitter) return;
      var scrollY = ge.splitter.secondBox.scrollTop() || 0;
      ge.splitter.firstBox.scrollTop(scrollY);
      ge.firstScreenLine = Math.floor(scrollY / ge.rowHeight);
    }

    // Mede a distância real entre as linhas da GRADE e ajusta ge.rowHeight para o gráfico
    // desenhar exatamente na mesma altura. Idempotente: só redesenha se o valor mudou.
    function syncGanttRowHeight() {
      if (!ge || !ge.element) return;
      var rows = ge.element.find('.taskEditRow').not('.emptyRow');
      var n = rows.length - 1;
      if (n < 1) return;
      var top0 = rows.eq(0).get(0).getBoundingClientRect().top;
      var topN = rows.eq(n).get(0).getBoundingClientRect().top;
      var pitch = Math.round((topN - top0) / n);
      if (pitch > 10 && pitch < 200 && pitch !== ge.rowHeight) {
        ge.rowHeight = pitch;
        if (ge.gantt) ge.gantt.taskVertOffset = (ge.rowHeight - ge.gantt.taskHeight) / 2;
        if (ge.element) ge.numOfVisibleRows = Math.ceil(ge.element.height() / ge.rowHeight);
        syncGanttScrollState();
        ge.redraw();
      }
    }

    function setSaveStatus(state) {
      var el = document.getElementById("saveStatus");
      if (!el) return;
      var map = {
        pending: ["● alteracoes pendentes", "#9ca3af"],
        saving:  ["Salvando…", "#9ca3af"],
        saved:   ["Salvo ✓", "#059669"],
        error:   ["Erro ao salvar — edite de novo p/ tentar", "#dc2626"]
      };
      var s = map[state] || ["", "#9ca3af"];
      el.textContent = s[0];
      el.style.color = s[1];
    }

    function scheduleAutoSave() {
      if (!ganttReady || saving) return;
      clearTimeout(autoSaveTimer);
      setSaveStatus("pending");
      autoSaveTimer = setTimeout(doAutoSave, 800); // debounce: agrupa edições rápidas (drag)
    }

    function doAutoSave() {
      if (saving || !ganttReady) return;
      saving = true;
      setSaveStatus("saving");

      var prj = ge.saveProject();
      $.ajax(GANTT_SAVE_URL, {
        headers: { "X-CSRF-TOKEN": GANTT_CSRF },
        data: JSON.stringify(prj),
        contentType: "application/json",
        type: "POST",
        dataType: "json",
        success: function (resp) {
          saving = false;
          if (resp && resp.ok) {
            setSaveStatus("saved");
            // Ressincroniza IDs de tarefas novas (tmp_ -> real) NO LUGAR, sem recarregar.
            if (resp.idMap) {
              for (var tmpId in resp.idMap) {
                if (resp.idMap.hasOwnProperty(tmpId)) patchTaskId(tmpId, "" + resp.idMap[tmpId]);
              }
            }
          } else {
            setSaveStatus("error");
          }
        },
        error: function () { saving = false; setSaveStatus("error"); }
      });
    }

    // Troca o id de uma tarefa recém-criada (tmp_) pelo id real — na memória e no DOM —
    // para o próximo save reconhecê-la como existente (sem duplicar) e SEM re-render.
    function patchTaskId(tmpId, realId) {
      if (!tmpId || tmpId === realId) return;
      var t = ge.getTask(tmpId);
      if (t) t.id = realId;
      ge.element.find('[taskid="' + tmpId + '"]').attr('taskid', realId);
      var row = document.getElementById('tid_' + tmpId);
      if (row) row.id = 'tid_' + realId;
    }

    // Botão "Salvar" da toolbar: força o salvamento imediato (o normal é automático).
    function saveGanttOnServer() {
      clearTimeout(autoSaveTimer);
      doAutoSave();
    }
  </script>

</body>
</html>
