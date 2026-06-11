<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    
    @if($empresa->logo)
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="{{ url('storage/' . $empresa->logo) }}" alt="{{ $empresa->nome_fantasia }}" style="max-height: 80px;">
        </div>
    @else
        <h2 style="text-align: center; color: #4A708B;">{{ $empresa->nome_fantasia }}</h2>
    @endif

    <p>Olá, <strong>{{ $fornecedor->contato_nome ?: $fornecedor->razao_social }}</strong>.</p>
    
    <p>Informamos que houve uma pendência em sua documentação enviada para a empresa <strong>{{ $empresa->nome_fantasia }}</strong>.</p>

    <div style="background-color: #ffeaea; border-left: 4px solid #cc0000; padding: 15px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #cc0000;">Documento Reprovado: {{ $documento->nome_documento }}</h3>
        <p><strong>Motivo da Reprovação:</strong></p>
        <p style="white-space: pre-wrap;">{{ $documento->motivo_reprovacao }}</p>
    </div>

    <p>Por favor, providencie o ajuste ou o envio de um novo documento o mais breve possível para não impactar sua homologação como fornecedor.</p>

    <br>
    <p>Atenciosamente,</p>
    <p>
        <strong>{{ $userLogado->name }}</strong><br>
        Departamento de Qualidade / Suprimentos<br>
        {{ $empresa->nome_fantasia }}
    </p>
</body>
</html>
