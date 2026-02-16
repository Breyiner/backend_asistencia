<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar correo electrónico</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f7fafc; padding: 20px 0;">
    
    <div style="background: white; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); margin: 0 20px; padding: 0;">
        
        <!-- Header -->
        <div style="background-color: #10b981; color: white; padding: 32px 40px; border-radius: 8px 8px 0 0; text-align: center;">
            <h1 style="font-size: 24px; font-weight: 500; margin: 0; line-height: 1.3;">Verificar correo</h1>
        </div>
        
        <!-- Content -->
        <div style="padding: 40px;">
            <h2 style="font-size: 20px; font-weight: 500; color: #2d3748; margin: 0 0 8px;">Hola {{ $user->profile->first_name ?? $user->email }}!</h2>
            <p style="font-size: 16px; color: #4a5568; margin: 0 0 32px 0;">
                Haz clic en el botón de abajo para verificar tu correo electrónico:
            </p>
            
            <!-- Botón principal -->
            <div style="text-align: center; margin: 40px 0;">
                <a href="{{ $url }}"
                   style="display: inline-block; background-color: #10b981; color: white; padding: 16px 40px; font-size: 16px; font-weight: 500; text-decoration: none; border-radius: 6px; box-shadow: 0 4px 12px rgba(16,185,129,0.3);">
                   Verificar correo
                </a>
            </div>
            
            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 32px 0;">
            <p style="font-size: 14px; color: #a0aec0; line-height: 1.5; margin: 0;">
                Si no solicitaste esta verificación, ignora este mensaje.
            </p>
        </div>
        
        <!-- Footer -->
        <div style="background-color: #f7fafc; padding: 32px 40px; border-top: 1px solid #e2e8f0; border-radius: 0 0 8px 8px; text-align: center;">
            <p style="font-size: 14px; color: #a0aec0; margin: 0 0 8px;">
                Sistema de Asistencias
            </p>
            <p style="font-size: 12px; color: #a0aec0; margin: 0;">
                © {{ date('Y') }} Todos los derechos reservados.
            </p>
        </div>
    </div>
</body>
</html>