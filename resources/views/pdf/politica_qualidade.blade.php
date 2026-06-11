<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Política da Qualidade - {{ $company->nome_fantasia ?? 'Empresa' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.5;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #1f2937;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 12px;
            color: #6b7280;
        }
        .content {
            margin-bottom: 40px;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .signature-table th, .signature-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }
        .signature-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            width: 33.33%;
        }
        .hash-box {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9fafb;
            border: 1px dashed #cbd5e1;
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            color: #64748b;
            word-break: break-all;
            text-align: center;
        }
        .logo-placeholder {
            width: 100px;
            height: 100px;
            background-color: #eee;
            display: inline-block;
            margin-bottom: 10px;
            line-height: 100px;
            color: #999;
            font-weight: bold;
        }
        img.logo {
            max-width: 150px;
            max-height: 80px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        @if($company && $company->logo)
            <img src="{{ public_path('storage/' . $company->logo) }}" class="logo" alt="Logo">
        @else
            <div class="logo-placeholder">LOGO</div>
        @endif
        
        <h1>Política da Qualidade</h1>
        <p>{{ $company->razao_social ?? 'Razão Social não informada' }} - CNPJ: {{ $company->cnpj ?? 'N/A' }}</p>
    </div>

    <div class="content">
        {!! $politica->conteudo !!}
    </div>

    <table class="signature-table">
        <thead>
            <tr>
                <th>Elaboração</th>
                <th>Revisão</th>
                <th>Aprovação</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>Nome:</strong> {{ $politica->elaborador ? $politica->elaborador->name : 'N/A' }}<br>
                    <strong>Data:</strong> {{ $politica->data_elaboracao ? \Carbon\Carbon::parse($politica->data_elaboracao)->format('d/m/Y H:i') : 'N/A' }}
                </td>
                <td>
                    <strong>Nome:</strong> {{ $politica->revisor ? $politica->revisor->name : 'N/A' }}<br>
                    <strong>Data:</strong> {{ $politica->data_revisao ? \Carbon\Carbon::parse($politica->data_revisao)->format('d/m/Y H:i') : 'N/A' }}
                </td>
                <td>
                    <strong>Nome:</strong> {{ $politica->aprovador ? $politica->aprovador->name : 'N/A' }}<br>
                    <strong>Data:</strong> {{ $politica->data_aprovacao ? \Carbon\Carbon::parse($politica->data_aprovacao)->format('d/m/Y H:i') : 'N/A' }}
                </td>
            </tr>
        </tbody>
    </table>

    @if($politica->hash_assinatura)
    <div class="hash-box">
        <strong>Hash de Autenticidade (SHA-256):</strong><br>
        {{ $politica->hash_assinatura }}
    </div>
    @endif

</body>
</html>
