<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credenciales de acceso</title>
</head>

<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f7fafc; padding: 20px 0;">

    <div style="background: white; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0, 0,0, 0.1), 0 4px 6px -2px rgba(0, 0,0, 0.05); margin: 0 20px; padding: 0;">

        <!-- Header -->
        <div style="background-color: #10b981; color: white; padding: 32px 40px; border-radius: 8px 8px 0 0; text-align: center;">
            <h1 style="font-size: 24px; font-weight: 500; margin: 0; line-height: 1.3;">Cuenta creada</h1>
        </div>

        <!-- Content -->
        <div style="padding: 40px;">
            <h2 style="font-size: 20px; font-weight: 500; color: #2d3748; margin: 0 0 8px;">Hola {{ $first_name }} {{ $last_name }},</h2>
            <p style="font-size: 16px; color: #4a5568; margin: 0 0 32px 0;">
                Se creó tu cuenta en el sistema de asistencias el <strong>{{ $created_at->format('d/m/Y \a \l\a\s H:i') }}</strong>.
            </p>

            <!-- Credenciales destacadas -->
            <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #10b981; border-radius: 12px; padding: 32px; margin: 0 0 40px 0; box-shadow: 0 4px 12px rgba(16,185,129,0.15);">
                <div style="display: flex; align-items: center; margin-bottom: 24px;">
                    <h3 style="font-size: 18px; font-weight: 600; color: #065f46; margin: 0; line-height: 1;">Tus credenciales de acceso</h3>
                </div>

                <table style="width: 100%; font-size: 16px;">
                    <tr style="height: 24px;">
                        <td style="padding: 12px 0 12px 12px; color: #047857; font-weight: 500; width: 120px; vertical-align: top;">Correo:</td>
                        <td style="padding: 12px 16px 12px 16px; color: #1f2937; font-weight: 600; font-size: 16px; word-break: break-all;">{{ $email }}</td>
                    </tr>
                    <tr style="background: rgba(16,185,129,0.1); border-radius: 8px;">
                        <td style="padding: 16px 0 16px 12px; color: #047857; font-weight: 500; vertical-align: top;">Contraseña:</td>
                        <td style="padding: 16px 16px 16px 16px; color: #dc2626; font-weight: 700; font-family: 'Courier New', monospace; font-size: 18px; letter-spacing: -0.5px;">{{ $password }}</td>
                    </tr>
                </table>

                <p style="font-size: 14px; color: #047857; margin: 16px 0 0 0; font-weight: 500;">
                    💡 Cambia tu contraseña al iniciar sesión por primera vez
                </p>
            </div>

            <!-- Instrucciones claras -->
            <div style="background-color: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px; padding: 24px; margin: 0 0 40px 0;">
                <h4 style="font-size: 16px; font-weight: 600; color: #92400e; margin: 0 0 12px;">⚠️ Antes de iniciar sesión:</h4>
                <ol style="font-size: 15px; color: #92400e; line-height: 1.6; margin: 0; padding-left: 20px;">
                    <li>Revisa tu bandeja de <strong>entrada</strong> (incluyendo spam)</li>
                    <li>Verifica tu correo haciendo clic en el enlace</li>
                    <li>Si no llega el email, usa el botón de abajo</li>
                </ol>
            </div>

            <!-- Botones -->
            <div style="text-align: center; margin: 40px 0;">
                <div style="margin-bottom: 16px;">
                    <a
                        href="https://breyiner.github.io/frontend_asistencia/reenviar-verificacion?email={{ urlencode($email) }}"
                        style="display: inline-block; background-color: #f59e0b; color: white; padding: 14px 36px; font-size: 16px; font-weight: 500; text-decoration: none; border-radius: 6px; box-shadow: 0 4px 6px -1px rgba(0, 0,0, 0.1); margin-right: 12px;">
                        Reenviar verificación
                    </a>
                </div>

                <a
                    href="https://breyiner.github.io/frontend_asistencia/login"
                    style="display: inline-block; background-color: #10b981; color: white; padding: 14px 36px; font-size: 16px; font-weight: 500; text-decoration: none; border-radius: 6px; box-shadow: 0 4px 6px -1px rgba(0, 0,0, 0.1);">
                    Iniciar sesión
                </a>
            </div>


            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 32px 0;">
            <p style="font-size: 14px; color: #a0aec0; line-height: 1.5; margin: 0;">
                Si tienes dudas contacta al administrador del sistema.
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