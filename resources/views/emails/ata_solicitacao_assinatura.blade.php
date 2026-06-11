<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Solicitação de Assinatura</h2>
    <p>Olá, <strong>{{ $user->name }}</strong>.</p>
    <p>Uma nova <strong>Ata de Reunião</strong> foi publicada e exige a sua assinatura.</p>
    
    <ul>
        <li><strong>Assunto:</strong> {{ $ata->assunto }}</li>
        <li><strong>Data:</strong> {{ $ata->data->format('d/m/Y') }}</li>
        <li><strong>Local:</strong> {{ $ata->local }}</li>
    </ul>

    <p>Para ler a ata e assiná-la digitalmente, por favor acesse o sistema através do link abaixo:</p>
    
    <p>
        <a href="{{ route('atas-reuniao.show', $ata->id) }}" style="display: inline-block; padding: 10px 20px; background-color: #4F46E5; color: white; text-decoration: none; border-radius: 5px;">
            Acessar Ata de Reunião
        </a>
    </p>

    <p>Atenciosamente,<br>Equipe SGI</p>
</body>
</html>
