<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 8px; vertical-align: top; }
        .header-table td { border: 1px solid #000; }
        .logo-cell { width: 25%; text-align: center; font-weight: bold; color: #8b0000; }
        .title-cell { width: 50%; text-align: center; font-size: 18px; font-weight: bold; }
        .info-cell { width: 25%; }
        .field-title { font-weight: bold; }
        .content-box { border: 1px solid #000; padding: 10px; min-height: 200px; margin-bottom: 20px; }
        .signatures-table { margin-top: 30px; font-size: 10px; }
        .signatures-table th { background-color: #f2f2f2; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="logo-cell" rowspan="3">
                <div style="font-size: 24px; color: #8b0000;">&#x2628;</div>
                <br>
                {{ strtoupper($ata->empresa->nome_fantasia) }}
            </td>
            <td class="title-cell" rowspan="3">ATA DE REUNIÃO</td>
            <td class="info-cell"><strong>Data:</strong> {{ $ata->data->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="info-cell"><strong>Início:</strong> {{ date('H:i', strtotime($ata->hora_inicio)) }}</td>
        </tr>
        <tr>
            <td class="info-cell"><strong>Término:</strong> {{ date('H:i', strtotime($ata->hora_termino)) }}</td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="field-title" style="width: 100%;">LOCAL: <span style="font-weight: normal;">{{ $ata->local }}</span></td>
        </tr>
        <tr>
            <td class="field-title">PARTICIPANTES: 
                <span style="font-weight: normal;">
                    {{ implode(', ', $ata->participantes->pluck('user.name')->toArray()) }}
                </span>
            </td>
        </tr>
        <tr>
            <td class="field-title">ASSUNTO: <span style="font-weight: normal;">{{ $ata->assunto }}</span></td>
        </tr>
        <tr>
            <td class="field-title">PAUTAS:<br>
                <div style="font-weight: normal; margin-top: 10px;">{!! $ata->pautas !!}</div>
            </td>
        </tr>
    </table>

    <div style="font-weight: bold; margin-bottom: 5px;">REGISTRO:</div>
    <div class="content-box">
        {!! $ata->registro !!}
    </div>

    <div style="margin-top: 30px;">
        <strong>RESPONSÁVEL PELO REGISTRO DA ATA:</strong> {{ $ata->responsavel->name }}
    </div>

    @if($ata->status === 'concluida')
        <div style="margin-top: 50px;">
            <strong>ASSINATURAS ELETRÔNICAS:</strong>
            <table class="signatures-table">
                <tr>
                    <th>Nome</th>
                    <th>Data/Hora Assinatura</th>
                    <th>Hash de Autenticação</th>
                </tr>
                @foreach($ata->participantes as $part)
                    <tr>
                        <td>{{ $part->user->name }}</td>
                        <td>{{ $part->data_assinatura ? $part->data_assinatura->format('d/m/Y H:i:s') : 'Pendente' }}</td>
                        <td style="word-break: break-all;">{{ $part->hash_assinatura ?? '-' }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

</body>
</html>
