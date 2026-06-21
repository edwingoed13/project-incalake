<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;background:#f5f7f8;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
  <div style="max-width:560px;margin:0 auto;padding:24px;">
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
      <div style="background:#0077cc;color:#fff;padding:18px 24px;">
        <h1 style="margin:0;font-size:18px;">Nueva consulta de disponibilidad</h1>
      </div>
      <div style="padding:24px;">
        <p style="margin:0 0 16px;font-size:14px;color:#475569;">
          Un cliente solicitó verificar disponibilidad{{ $inquiry->tour_title ? ' para:' : '.' }}
        </p>
        @if($inquiry->tour_title)
          <p style="margin:0 0 16px;font-size:16px;font-weight:bold;">{{ $inquiry->tour_title }}</p>
        @endif

        <table style="width:100%;border-collapse:collapse;font-size:14px;">
          <tr><td style="padding:6px 0;color:#64748b;width:140px;">Nombre</td><td style="padding:6px 0;font-weight:bold;">{{ $inquiry->name }}</td></tr>
          <tr><td style="padding:6px 0;color:#64748b;">Email</td><td style="padding:6px 0;"><a href="mailto:{{ $inquiry->email }}" style="color:#0077cc;">{{ $inquiry->email }}</a></td></tr>
          @if($inquiry->phone)
          <tr><td style="padding:6px 0;color:#64748b;">WhatsApp / Tel.</td><td style="padding:6px 0;">{{ $inquiry->phone }}</td></tr>
          @endif
          @if($inquiry->preferred_date)
          <tr><td style="padding:6px 0;color:#64748b;">Fecha deseada</td><td style="padding:6px 0;">{{ $inquiry->preferred_date->format('d/m/Y') }}</td></tr>
          @endif
          <tr><td style="padding:6px 0;color:#64748b;">Personas</td><td style="padding:6px 0;">{{ $inquiry->adults }} adulto(s)@if($inquiry->children) · {{ $inquiry->children }} niño(s)@endif</td></tr>
          @if($inquiry->language)
          <tr><td style="padding:6px 0;color:#64748b;">Idioma</td><td style="padding:6px 0;text-transform:uppercase;">{{ $inquiry->language }}</td></tr>
          @endif
        </table>

        @if($inquiry->message)
          <div style="margin-top:16px;padding:12px 14px;background:#f1f5f9;border-radius:10px;font-size:14px;">
            <p style="margin:0 0 4px;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.05em;">Mensaje</p>
            <p style="margin:0;white-space:pre-line;">{{ $inquiry->message }}</p>
          </div>
        @endif

        <p style="margin:20px 0 0;font-size:12px;color:#94a3b8;">
          Responde directamente a este correo para contactar al cliente.
        </p>
      </div>
    </div>
  </div>
</body>
</html>
