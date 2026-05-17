<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirmacion de Reserva</title>
</head>
<body style="margin:0; padding:0; background:#f0f2f5; font-family:'Segoe UI',Arial,sans-serif; color:#1a1a2e; -webkit-font-smoothing:antialiased;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f2f5; padding:24px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.06);">

@if($isAdminCopy)
          {{-- ===================== ADMIN ===================== --}}
          <tr>
            <td style="background:#1e293b; color:#fff; text-align:center; padding:14px 20px;">
              <p style="margin:0; font-size:13px; font-weight:700; letter-spacing:0.5px;">NUEVA RESERVA CONFIRMADA &middot; {{ $bookings->count() }} TOURS</p>
            </td>
          </tr>
          <tr>
            <td style="padding:28px 30px 24px;">

              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                <tr>
                  <td>
                    <p style="margin:0 0 4px; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:2px; font-weight:700;">Codigos</p>
                    <p style="margin:0; font-size:15px; font-weight:800; color:#1e3a5f; font-family:'Courier New',monospace;">{{ $bookings->pluck('booking_code')->implode(' · ') }}</p>
                  </td>
                  <td style="text-align:right; vertical-align:bottom;">
                    <span style="display:inline-block; background:#22c55e; color:#fff; padding:5px 16px; border-radius:20px; font-size:11px; font-weight:700;">PAGADO</span>
                  </td>
                </tr>
              </table>

              {{-- Client --}}
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                <tr><td style="padding:10px 14px; background:#475569; border-radius:8px 8px 0 0;">
                  <p style="margin:0; font-size:11px; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:1px;">Cliente</p>
                </td></tr>
                <tr><td style="border:1px solid #e2e8f0; border-top:none; border-radius:0 0 8px 8px; padding:0;">
                  <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; color:#64748b; font-size:12px; font-weight:600; width:35%;">Nombre</td>
                      <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; font-weight:600; color:#1a1a2e;">{{ $primary->customer_name }}</td>
                    </tr>
                    <tr>
                      <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; color:#64748b; font-size:12px; font-weight:600;">Email</td>
                      <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:13px;"><a href="mailto:{{ $primary->customer_email }}" style="color:#2980b9; text-decoration:none;">{{ $primary->customer_email }}</a></td>
                    </tr>
                    @if($primary->customer_phone)
                    <tr>
                      <td style="padding:8px 14px;{{ $primary->customer_country ? ' border-bottom:1px solid #f1f5f9;' : '' }} color:#64748b; font-size:12px; font-weight:600;">Telefono</td>
                      <td style="padding:8px 14px;{{ $primary->customer_country ? ' border-bottom:1px solid #f1f5f9;' : '' }} font-size:13px;"><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $primary->customer_phone) }}" style="color:#2980b9; text-decoration:none;">{{ $primary->customer_phone }}</a></td>
                    </tr>
                    @endif
                    @if($primary->customer_country)
                    <tr>
                      <td style="padding:8px 14px; color:#64748b; font-size:12px; font-weight:600;">Pais</td>
                      <td style="padding:8px 14px; font-size:13px; color:#1a1a2e;">{{ $primary->customer_country }}</td>
                    </tr>
                    @endif
                  </table>
                </td></tr>
              </table>

              {{-- Tours loop --}}
              @foreach($bookings as $b)
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:14px;">
                <tr><td style="padding:10px 14px; background:#1e3a5f; border-radius:8px 8px 0 0;">
                  <p style="margin:0; font-size:11px; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:1px;">Tour {{ $loop->iteration }}/{{ $bookings->count() }} &middot; {{ $b->booking_code }}</p>
                </td></tr>
                <tr><td style="border:1px solid #e2e8f0; border-top:none; border-radius:0 0 8px 8px; padding:0;">
                  <table width="100%" cellpadding="0" cellspacing="0">
                    <tr><td style="padding:10px 14px; border-bottom:1px solid #f1f5f9; font-size:14px; font-weight:700; color:#1a1a2e;" colspan="2">{{ $b->tour_title }}</td></tr>
                    <tr>
                      <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; color:#64748b; font-size:12px; font-weight:600; width:35%;">Fecha</td>
                      <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#1a1a2e;">{{ \Carbon\Carbon::parse($b->tour_date)->format('d/m/Y') }}</td>
                    </tr>
                    @if($b->tour_time)
                    <tr>
                      <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; color:#64748b; font-size:12px; font-weight:600;">Horario</td>
                      <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#1a1a2e;">
                        @php $h=(int)explode(':',$b->tour_time)[0]; $m=explode(':',$b->tour_time)[1]??'00'; @endphp
                        {{ $h%12?:12 }}:{{ $m }} {{ $h>=12?'PM':'AM' }}
                      </td>
                    </tr>
                    @endif
                    <tr>
                      <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; color:#64748b; font-size:12px; font-weight:600;">Participantes</td>
                      <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#1a1a2e;">{{ $b->adults }} adulto{{ $b->adults > 1 ? 's' : '' }}@if($b->children > 0), {{ $b->children }} nino{{ $b->children > 1 ? 's' : '' }}@endif</td>
                    </tr>
                    <tr>
                      <td style="padding:8px 14px; color:#64748b; font-size:12px; font-weight:600;">Subtotal tour</td>
                      <td style="padding:8px 14px; font-size:13px; font-weight:700; color:#1a1a2e;">{{ $b->currency }} {{ number_format($b->total, 2) }}</td>
                    </tr>
                  </table>
                </td></tr>
              </table>
              @endforeach

              {{-- Group payment --}}
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; margin-top:6px;">
                <tr><td style="padding:16px;">
                  <p style="margin:0 0 10px; font-size:11px; color:#94a3b8; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Pago (1 cargo)</p>
                  <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="padding:5px 0; font-size:13px; color:#64748b;">Subtotal ({{ $bookings->count() }} tours)</td>
                      <td style="padding:5px 0; font-size:13px; color:#1a1a2e; text-align:right;">{{ $currency }} {{ number_format($groupSubtotal, 2) }}</td>
                    </tr>
                    @if($groupTax > 0)
                    <tr>
                      <td style="padding:5px 0; font-size:13px; color:#64748b;">Tasas de transaccion</td>
                      <td style="padding:5px 0; font-size:13px; color:#1a1a2e; text-align:right;">{{ $currency }} {{ number_format($groupTax, 2) }}</td>
                    </tr>
                    @endif
                    @if($groupDiscount > 0)
                    <tr>
                      <td style="padding:5px 0; font-size:13px; color:#22c55e;">Descuento</td>
                      <td style="padding:5px 0; font-size:13px; color:#22c55e; text-align:right;">-{{ $currency }} {{ number_format($groupDiscount, 2) }}</td>
                    </tr>
                    @endif
                  </table>
                  <table width="100%" cellpadding="0" cellspacing="0" style="border-top:2px solid #e2e8f0; margin-top:8px;">
                    <tr>
                      <td style="padding:10px 0 2px; font-size:17px; font-weight:800; color:#1e3a5f;">Total pagado</td>
                      <td style="padding:10px 0 2px; font-size:17px; font-weight:800; color:#1e3a5f; text-align:right;">{{ $currency }} {{ number_format($groupTotal, 2) }}</td>
                    </tr>
                  </table>
                  <p style="margin:6px 0 0; font-size:11px; color:#94a3b8;">
                    {{ strtoupper($primary->payment_method ?? '-') }}@if($primary->transaction_id) &middot; {{ $primary->transaction_id }}@endif
                  </p>
                </td></tr>
              </table>

            </td>
          </tr>
          <tr>
            <td style="background:#f1f5f9; padding:16px 30px; text-align:center; border-top:1px solid #e2e8f0;">
              <p style="margin:0; font-size:11px; color:#94a3b8;">Incalake Tours &middot; Copia interna &middot; &copy; {{ date('Y') }}</p>
            </td>
          </tr>

@else
          {{-- ===================== CLIENT ===================== --}}
          <tr>
            <td style="background:linear-gradient(135deg,#1e3a5f 0%,#2980b9 100%); padding:32px 30px; text-align:center;">
              <h1 style="margin:0; font-size:24px; font-weight:800; color:#ffffff; letter-spacing:-0.5px;">Incalake</h1>
              <p style="margin:4px 0 0; font-size:11px; color:rgba(255,255,255,0.7); text-transform:uppercase; letter-spacing:2px;">Tours & Experiences</p>
            </td>
          </tr>
          <tr>
            <td style="padding:32px 30px 24px;">

              <p style="font-size:15px; color:#555; margin:0 0 20px; line-height:1.6;">
                Hola <strong style="color:#1a1a2e;">{{ $primary->customer_name }}</strong>,<br>
                Tu reserva de <strong>{{ $bookings->count() }} tours</strong> ha sido confirmada. Aqui tienes el resumen.
              </p>

              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:24px;">
                <tr><td style="padding:20px; text-align:center;">
                  <p style="margin:0 0 6px; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:2px; font-weight:700;">Codigos de reserva</p>
                  <p style="margin:0 0 10px; font-size:16px; font-weight:800; color:#1e3a5f; letter-spacing:1px; font-family:'Courier New',monospace;">{{ $bookings->pluck('booking_code')->implode(' · ') }}</p>
                  <span style="display:inline-block; background:#22c55e; color:#fff; padding:4px 14px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:0.5px;">PAGADO</span>
                </td></tr>
              </table>

              {{-- Each tour --}}
              @foreach($bookings as $b)
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                <tr><td style="padding:10px 14px; background:#1e3a5f; border-radius:8px 8px 0 0;">
                  <p style="margin:0; font-size:12px; font-weight:700; color:#ffffff; text-transform:uppercase; letter-spacing:1px;">Tour {{ $loop->iteration }} de {{ $bookings->count() }}</p>
                </td></tr>
                <tr><td style="border:1px solid #e2e8f0; border-top:none; border-radius:0 0 8px 8px; padding:0;">
                  <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="padding:12px 14px; border-bottom:1px solid #f1f5f9; color:#64748b; font-size:13px; font-weight:600; width:40%;">Tour</td>
                      <td style="padding:12px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; font-weight:700; color:#1a1a2e;">{{ $b->tour_title }}</td>
                    </tr>
                    <tr>
                      <td style="padding:12px 14px; border-bottom:1px solid #f1f5f9; color:#64748b; font-size:13px; font-weight:600;">Fecha</td>
                      <td style="padding:12px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#1a1a2e;">{{ \Carbon\Carbon::parse($b->tour_date)->format('d/m/Y') }}</td>
                    </tr>
                    @if($b->tour_time)
                    <tr>
                      <td style="padding:12px 14px; border-bottom:1px solid #f1f5f9; color:#64748b; font-size:13px; font-weight:600;">Horario</td>
                      <td style="padding:12px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#1a1a2e;">
                        @php $h=(int)explode(':',$b->tour_time)[0]; $m=explode(':',$b->tour_time)[1]??'00'; @endphp
                        {{ $h%12?:12 }}:{{ $m }} {{ $h>=12?'PM':'AM' }}
                      </td>
                    </tr>
                    @endif
                    <tr>
                      <td style="padding:12px 14px; border-bottom:1px solid #f1f5f9; color:#64748b; font-size:13px; font-weight:600;">Participantes</td>
                      <td style="padding:12px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#1a1a2e;">{{ $b->adults }} adulto{{ $b->adults > 1 ? 's' : '' }}@if($b->children > 0), {{ $b->children }} nino{{ $b->children > 1 ? 's' : '' }}@endif</td>
                    </tr>
                    <tr>
                      <td style="padding:12px 14px; color:#64748b; font-size:13px; font-weight:600;">Subtotal</td>
                      <td style="padding:12px 14px; font-size:13px; font-weight:700; color:#1a1a2e;">{{ $b->currency }} {{ number_format($b->total, 2) }}</td>
                    </tr>
                  </table>
                </td></tr>
              </table>

              {{-- Per-tour Google Calendar (separate, as requested) --}}
              @php
                $dateOnly  = \Carbon\Carbon::parse($b->tour_date)->format('Y-m-d');
                $timeOnly  = \Carbon\Carbon::parse($b->tour_time)->format('H:i:s');
                $startDt   = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateOnly . ' ' . $timeOnly, 'America/Lima');
                $endDt     = $startDt->copy()->addMinutes(30);
                $gcalStart = $startDt->format('Ymd\THis');
                $gcalEnd   = $endDt->format('Ymd\THis');
                $participants = (int)($b->adults ?? 0) + (int)($b->children ?? 0);
                $gcalTitle   = urlencode($b->customer_name . ' (' . $participants . ') | ' . $b->tour_title);
                $gcalDetails = urlencode('Reserva #' . $b->booking_code . ' - Incalake Tours');
                $gcalUrl     = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . $gcalTitle . '&dates=' . $gcalStart . '/' . $gcalEnd . '&details=' . $gcalDetails . '&ctz=America/Lima';
              @endphp
              <table width="100%" cellpadding="0" cellspacing="0" style="margin:-6px 0 24px;">
                <tr><td align="center">
                  <a href="{{ $gcalUrl }}" target="_blank" style="display:inline-block; background:#ffffff; color:#1a1a2e; text-decoration:none; padding:8px 20px; border-radius:8px; font-size:12px; font-weight:600; border:1px solid #e2e8f0;">
                    + Agendar este tour en Google Calendar
                  </a>
                </td></tr>
              </table>
              @endforeach

              {{-- Group payment summary --}}
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:24px;">
                <tr><td style="padding:16px;">
                  <p style="margin:0 0 12px; font-size:11px; color:#94a3b8; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Resumen de pago &middot; 1 cargo por {{ $bookings->count() }} tours</p>
                  <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="padding:6px 0; font-size:14px; color:#64748b;">Subtotal</td>
                      <td style="padding:6px 0; font-size:14px; color:#1a1a2e; text-align:right;">{{ $currency }} {{ number_format($groupSubtotal, 2) }}</td>
                    </tr>
                    @if($groupTax > 0)
                    <tr>
                      <td style="padding:6px 0; font-size:14px; color:#64748b;">Tasas de transaccion</td>
                      <td style="padding:6px 0; font-size:14px; color:#1a1a2e; text-align:right;">{{ $currency }} {{ number_format($groupTax, 2) }}</td>
                    </tr>
                    @endif
                    @if($groupDiscount > 0)
                    <tr>
                      <td style="padding:6px 0; font-size:14px; color:#22c55e;">Descuento</td>
                      <td style="padding:6px 0; font-size:14px; color:#22c55e; text-align:right;">-{{ $currency }} {{ number_format($groupDiscount, 2) }}</td>
                    </tr>
                    @endif
                  </table>
                  <table width="100%" cellpadding="0" cellspacing="0" style="border-top:2px solid #e2e8f0; margin-top:10px;">
                    <tr>
                      <td style="padding:12px 0 4px; font-size:18px; font-weight:800; color:#1e3a5f;">Total pagado</td>
                      <td style="padding:12px 0 4px; font-size:18px; font-weight:800; color:#1e3a5f; text-align:right;">{{ $currency }} {{ number_format($groupTotal, 2) }}</td>
                    </tr>
                  </table>
                  <p style="margin:8px 0 0; font-size:11px; color:#94a3b8;">
                    Metodo: {{ strtoupper($primary->payment_method ?? '-') }}@if($primary->transaction_id) &middot; ID: {{ $primary->transaction_id }}@endif
                  </p>
                </td></tr>
              </table>

              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                <tr><td align="center" style="padding:24px 20px; background:#f8fafc; border-radius:12px;">
                  <a href="{{ config('app.frontend_url') }}/es/booking-confirmation/{{ $primary->booking_code }}?token={{ $primary->confirmation_token }}"
                     style="display:inline-block; background:#1e3a5f; color:#ffffff; text-decoration:none; padding:14px 36px; border-radius:10px; font-size:14px; font-weight:700; letter-spacing:0.3px;">
                    Ver detalles de mi reserva
                  </a>
                  <p style="margin:10px 0 0; font-size:11px; color:#94a3b8;">Enlace seguro y unico para tu reserva</p>
                </td></tr>
              </table>

              <table width="100%" cellpadding="0" cellspacing="0">
                <tr><td style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:16px;">
                  <p style="margin:0 0 6px; font-size:12px; font-weight:700; color:#92400e;">Informacion importante</p>
                  <p style="margin:0; font-size:12px; color:#78350f; line-height:1.6;">
                    Presentate 15 minutos antes del horario indicado en cada tour.<br>
                    Dudas o consultas: <a href="https://wa.me/51982769453" style="color:#1e3a5f; font-weight:600;">+51 982 769 453</a> (WhatsApp) o <a href="mailto:reservas@incalake.com" style="color:#1e3a5f; font-weight:600;">reservas@incalake.com</a>
                  </p>
                </td></tr>
              </table>

            </td>
          </tr>
          <tr>
            <td style="background:#f8fafc; padding:24px 30px; text-align:center; border-top:1px solid #e2e8f0;">
              <p style="margin:0 0 4px; font-size:14px; font-weight:700; color:#1e3a5f;">Incalake Tours</p>
              <p style="margin:0 0 12px; font-size:12px; color:#94a3b8;">
                <a href="mailto:reservas@incalake.com" style="color:#64748b; text-decoration:none;">reservas@incalake.com</a>
                &nbsp;&middot;&nbsp;
                <a href="https://wa.me/51982769453" style="color:#64748b; text-decoration:none;">+51 982 769 453</a>
              </p>
              <p style="margin:0; font-size:11px; color:#cbd5e1;">&copy; {{ date('Y') }} Incalake Tours. Todos los derechos reservados.</p>
            </td>
          </tr>
@endif

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
