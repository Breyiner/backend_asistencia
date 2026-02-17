<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.5;">
    <div style="max-width: 600px; margin: 0 auto; padding: 40px 20px;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 12px; text-align: center; margin-bottom: 30px;">
            <h1 style="color: white; margin: 0; font-size: 28px; font-weight: 700;">🔐 Restablecer Contraseña</h1>
        </div>
        
        <div style="background: white; padding: 40px; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
            <p style="font-size: 18px; color: #1f2937; margin: 0 0 24px; line-height: 1.6;">
                Hola,
            </p>
            
            <p style="color: #6b7280; margin: 0 0 32px; line-height: 1.6;">
                Recibiste este correo porque se solicitó un restablecimiento de contraseña para tu cuenta.
            </p>
            
            <div style="text-align: center; margin: 40px 0;">
                <a href="{{ $resetUrl }}" 
                   style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 16px 40px; font-size: 16px; font-weight: 600; text-decoration: none; border-radius: 12px; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4); transition: all 0.2s;">
                    Restablecer Contraseña Ahora
                </a>
            </div>
            
            <div style="background: #f8fafc; padding: 24px; border-radius: 12px; border-left: 4px solid #667eea;">
                <p style="color: #6b7280; margin: 0 0 8px; font-size: 14px;">
                    <strong>⏰ Este enlace expira en 60 minutos.</strong>
                </p>
                <p style="color: #9ca3af; margin: 0; font-size: 14px;">
                    Si no solicitaste este cambio, puedes ignorar este mensaje.
                </p>
            </div>
            
            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 40px 0;">
            
            <p style="color: #6b7280; font-size: 14px; text-align: center; margin: 0;">
                Saludos,<br>
                <strong>{{ config('app.name') }}</strong>
            </p>
        </div>
    </div>
</body>
</html>
