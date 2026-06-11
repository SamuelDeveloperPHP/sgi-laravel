<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Planilha de Controle de Calibrações</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            color: #4A708B;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #888;
            padding: 5px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: bold;
        }
        /* Footer Table specific styles */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            background-color: #e0f0ff;
        }
        .footer-table td {
            border: 1px solid #888;
            text-align: left;
            padding: 4px;
        }
    </style>
</head>
<body>

    <div class="header">Planilha de controle de Calibrações - {{ strtoupper($empresa->nome_fantasia) }}</div>

    <table>
        <thead>
            <tr>
                <th>Equipamento</th>
                <th>Local</th>
                <th>Identificação</th>
                <th>Certificado (nº)</th>
                <th>Frequência de<br>Certificação</th>
                <th>Data da<br>Última Calibração</th>
                <th>Data da<br>Próxima Calibração</th>
                <th>Observações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($calibracoes as $item)
                <tr>
                    <td>{{ $item->equipamento }}</td>
                    <td>{{ $item->local }}</td>
                    <td>{{ $item->identificacao }}</td>
                    <td>{{ $item->certificado_numero }}</td>
                    <td>{{ $item->frequencia_meses ? $item->frequencia_meses . ' meses' : '' }}</td>
                    <td>{{ $item->data_ultima_calibracao ? $item->data_ultima_calibracao->format('d/m/Y') : '' }}</td>
                    <td>{{ $item->data_proxima_calibracao ? $item->data_proxima_calibracao->format('d/m/Y') : '' }}</td>
                    <td>{{ $item->observacoes }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Nenhum equipamento cadastrado.</td>
                </tr>
            @endforelse
            
            {{-- Linhas em branco para preencher conforme modelo se houver poucos itens --}}
            @for($i = 0; $i < (15 - $calibracoes->count()); $i++)
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            @endfor
        </tbody>
    </table>

    <table class="footer-table">
        <tr>
            <td style="width: 50%;">Data: &nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Revisão:</td>
            <td rowspan="4" style="width: 50%; vertical-align: top; border-left: none;"></td>
        </tr>
        <tr>
            <td>Legenda:</td>
        </tr>
        <tr>
            <td>Nota:</td>
        </tr>
        <tr>
            <td>Anexo:</td>
        </tr>
    </table>

</body>
</html>
