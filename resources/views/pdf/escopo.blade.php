<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Escopo do SGI - {{ $company->nome_fantasia ?? 'Empresa' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header img {
            max-height: 80px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            margin-top: 10px;
            text-transform: uppercase;
        }
        .content {
            margin-top: 20px;
            line-height: 1.6;
        }
        .footer {
            margin-top: 50px;
            border-top: 1px solid #ccc;
            padding-top: 20px;
            font-size: 12px;
        }
        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
        }
        .signatures td {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            padding: 10px;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            margin: 0 auto 5px auto;
            width: 80%;
            display: inline-block;
        }
        .hash-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            color: #777;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <div class="header">
        @if($company->logo)
            <img src="{{ public_path('storage/' . $company->logo) }}" alt="Logo">
        @else
            <h2>{{ $company->nome_fantasia ?? 'Nome da Empresa' }}</h2>
        @endif
        <div class="title">Escopo do SGI</div>
    </div>

    <div class="content">
        {!! $escopo->conteudo !!}
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="signature-line"></div>
                <b>Elaborador</b><br>
                {{ $escopo->elaborador->name ?? 'N/A' }}<br>
                {{ $escopo->data_elaboracao ? $escopo->data_elaboracao->format('d/m/Y H:i') : '' }}
            </td>
            <td>
                <div class="signature-line"></div>
                <b>Revisor</b><br>
                {{ $escopo->revisor->name ?? 'N/A' }}<br>
                {{ $escopo->data_revisao ? $escopo->data_revisao->format('d/m/Y H:i') : '' }}
            </td>
            <td>
                <div class="signature-line"></div>
                <b>Aprovador</b><br>
                {{ $escopo->aprovador->name ?? 'N/A' }}<br>
                {{ $escopo->data_aprovacao ? $escopo->data_aprovacao->format('d/m/Y H:i') : '' }}
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>Documento gerado eletronicamente pelo Sistema de Gestão Integrada (SGI).</p>
        <p>Este documento é de uso restrito e confidencial da {{ $company->nome_fantasia ?? 'Empresa' }}.</p>
    </div>

    <div class="hash-code">
        Hash de Assinatura Digital:<br>
        {{ $escopo->hash_assinatura }}
    </div>

</body>
</html>
