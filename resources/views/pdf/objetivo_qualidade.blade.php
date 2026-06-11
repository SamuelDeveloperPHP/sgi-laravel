<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Objetivo da Qualidade - {{ $company->nome_fantasia ?? 'Empresa' }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header img { max-height: 80px; }
        .title { font-size: 20px; font-weight: bold; margin-top: 10px; text-transform: uppercase; }
        .objective-info { margin-bottom: 20px; }
        .objective-info p { margin: 5px 0; }
        .content { margin-top: 20px; line-height: 1.6; }
        .footer { margin-top: 50px; border-top: 1px solid #ccc; padding-top: 20px; font-size: 12px; }
        .signatures { width: 100%; border-collapse: collapse; margin-top: 40px; }
        .signatures td { width: 33.33%; text-align: center; vertical-align: bottom; padding: 10px; }
        .signature-line { border-bottom: 1px solid #333; margin: 0 auto 5px auto; width: 80%; display: inline-block; }
        .hash-code { font-family: 'Courier New', Courier, monospace; font-size: 10px; color: #777; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>

    <div class="header">
        @if($company->logo)
            <img src="{{ public_path('storage/' . $company->logo) }}" alt="Logo">
        @else
            <h2>{{ $company->nome_fantasia ?? 'Nome da Empresa' }}</h2>
        @endif
        <div class="title">Objetivo da Qualidade</div>
    </div>

    <div class="objective-info">
        <p><strong>Título:</strong> {{ $objetivo->titulo }}</p>
        <p><strong>Prazo:</strong> {{ $objetivo->prazo->format('d/m/Y') }}</p>
        <p><strong>Responsáveis:</strong> 
            @foreach($objetivo->responsaveis as $resp)
                {{ $resp->name }}@if(!$loop->last), @endif
            @endforeach
        </p>
    </div>

    <div class="content">
        <h3>Descrição</h3>
        {!! $objetivo->descricao !!}
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="signature-line"></div>
                <b>Elaborador</b><br>
                {{ $objetivo->elaborador->name ?? 'N/A' }}<br>
                {{ $objetivo->data_elaboracao ? $objetivo->data_elaboracao->format('d/m/Y H:i') : '' }}
            </td>
            <td>
                <div class="signature-line"></div>
                <b>Revisor</b><br>
                {{ $objetivo->revisor->name ?? 'N/A' }}<br>
                {{ $objetivo->data_revisao ? $objetivo->data_revisao->format('d/m/Y H:i') : '' }}
            </td>
            <td>
                <div class="signature-line"></div>
                <b>Aprovador</b><br>
                {{ $objetivo->aprovador->name ?? 'N/A' }}<br>
                {{ $objetivo->data_aprovacao ? $objetivo->data_aprovacao->format('d/m/Y H:i') : '' }}
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>Documento gerado eletronicamente pelo Sistema de Gestão Integrada (SGI).</p>
        <p>Este documento é de uso restrito e confidencial da {{ $company->nome_fantasia ?? 'Empresa' }}.</p>
    </div>

    <div class="hash-code">
        Hash de Assinatura Digital:<br>
        {{ $objetivo->hash_assinatura }}
    </div>

</body>
</html>
