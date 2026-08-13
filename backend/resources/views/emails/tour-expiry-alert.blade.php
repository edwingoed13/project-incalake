@php
    $adminUrl = rtrim(config('services.incalake.admin_url'), '/');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Disponibilidad de tours</title>
</head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<div style="max-width:680px;margin:0 auto;background:#ffffff;border-radius:12px;padding:28px;">

    <h1 style="margin:0 0 6px;font-size:20px;">Disponibilidad de tours</h1>
    <p style="margin:0 0 24px;font-size:13px;color:#64748b;">
        Cuando un tour pasa su fecha final, su calendario se queda sin días seleccionables
        y deja de poder reservarse, aunque siga publicado.
    </p>

    @if($expiring->isNotEmpty())
        <h2 style="font-size:15px;margin:0 0 10px;">Caducan pronto ({{ $expiring->count() }})</h2>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
               style="border-collapse:collapse;font-size:13px;margin-bottom:28px;">
            <tr style="background:#f8fafc;text-align:left;">
                <th style="padding:8px;border-bottom:1px solid #e2e8f0;">Tour</th>
                <th style="padding:8px;border-bottom:1px solid #e2e8f0;">Fecha final</th>
                <th style="padding:8px;border-bottom:1px solid #e2e8f0;">Quedan</th>
            </tr>
            @foreach($expiring as $row)
                @php $tour = $row['tour']; @endphp
                <tr>
                    <td style="padding:8px;border-bottom:1px solid #f1f5f9;">
                        <a href="{{ $adminUrl }}/admin/v2/tours/{{ $tour->id }}/edit?step=8"
                           style="color:#4f46e5;text-decoration:none;font-weight:bold;">
                            {{ $tour->code }}
                        </a>
                        <span style="color:#64748b;"> · #{{ $tour->id }}</span>
                    </td>
                    <td style="padding:8px;border-bottom:1px solid #f1f5f9;">
                        {{ ($tour->availability_data['end'] ?? '') ?: '—' }}
                    </td>
                    <td style="padding:8px;border-bottom:1px solid #f1f5f9;font-weight:bold;
                        color:{{ $row['days_left'] <= 30 ? '#dc2626' : '#0f172a' }};">
                        {{ $row['days_left'] }} días
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    @if($backlog->isNotEmpty())
        <h2 style="font-size:15px;margin:0 0 6px;">Ya caducados ({{ $backlog->count() }})</h2>
        <p style="margin:0 0 10px;font-size:12px;color:#64748b;">
            Este listado se envía <strong>una sola vez</strong>. Estos tours siguen publicados
            pero su calendario ya no admite fechas.
        </p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
               style="border-collapse:collapse;font-size:13px;">
            <tr style="background:#f8fafc;text-align:left;">
                <th style="padding:8px;border-bottom:1px solid #e2e8f0;">Tour</th>
                <th style="padding:8px;border-bottom:1px solid #e2e8f0;">Caducó</th>
                <th style="padding:8px;border-bottom:1px solid #e2e8f0;">Hace</th>
            </tr>
            @foreach($backlog as $row)
                @php $tour = $row['tour']; @endphp
                <tr>
                    <td style="padding:8px;border-bottom:1px solid #f1f5f9;">
                        <a href="{{ $adminUrl }}/admin/v2/tours/{{ $tour->id }}/edit?step=8"
                           style="color:#4f46e5;text-decoration:none;font-weight:bold;">
                            {{ $tour->code }}
                        </a>
                        <span style="color:#64748b;"> · #{{ $tour->id }}</span>
                    </td>
                    <td style="padding:8px;border-bottom:1px solid #f1f5f9;">
                        {{ ($tour->availability_data['end'] ?? '') ?: '—' }}
                    </td>
                    <td style="padding:8px;border-bottom:1px solid #f1f5f9;color:#64748b;">
                        {{ $row['days_overdue'] }} días
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    <p style="margin:28px 0 0;font-size:12px;color:#94a3b8;">
        Para un tour sin fecha de fin, marca <strong>«Este tour no caduca»</strong> en el paso 8;
        dejará de aparecer en este aviso.
    </p>
</div>
</body>
</html>
