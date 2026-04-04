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
          {{-- ============================================== --}}
          {{-- ADMIN VERSION --}}
          {{-- ============================================== --}}

          <!-- Admin Header -->
          <tr>
            <td style="background:#1e293b; color:#fff; text-align:center; padding:14px 20px;">
              <p style="margin:0; font-size:13px; font-weight:700; letter-spacing:0.5px;">NUEVA RESERVA CONFIRMADA</p>
            </td>
          </tr>

          <tr>
            <td style="padding:28px 30px 24px;">

              <!-- Booking Code + Status -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                <tr>
                  <td>
                    <p style="margin:0 0 4px; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:2px; font-weight:700;">Codigo</p>
                    <p style="margin:0; font-size:22px; font-weight:800; color:#1e3a5f; letter-spacing:1px; font-family:'Courier New',monospace;">{{ $booking->booking_code }}</p>
                  </td>
                  <td style="text-align:right; vertical-align:bottom;">
                    <span style="display:inline-block; background:#22c55e; color:#fff; padding:5px 16px; border-radius:20px; font-size:11px; font-weight:700;">PAGADO</span>
                  </td>
                </tr>
              </table>

              <!-- Tour Details -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                <tr>
                  <td style="padding:10px 14px; background:#1e3a5f; border-radius:8px 8px 0 0;">
                    <p style="margin:0; font-size:11px; font-weight:700; color:#ffffff; text-transform:uppercase; letter-spacing:1px;">Tour</p>
                  </td>
                </tr>
                <tr>
                  <td style="border:1px solid #e2e8f0; border-top:none; border-radius:0 0 8px 8px; padding:0;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr><td style="padding:10px 14px; border-bottom:1px solid #f1f5f9; font-size:14px; font-weight:700; color:#1a1a2e;" colspan="2">{{ $booking->tour_title }}</td></tr>
                      <tr>
                        <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; color:#64748b; font-size:12px; font-weight:600; width:35%;">Fecha</td>
                        <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#1a1a2e;">{{ \Carbon\Carbon::parse($booking->tour_date)->format('d/m/Y') }}</td>
                      </tr>
                      @if($booking->tour_time)
                      <tr>
                        <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; color:#64748b; font-size:12px; font-weight:600;">Horario</td>
                        <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#1a1a2e;">
                          @php $h=(int)explode(':',$booking->tour_time)[0]; $m=explode(':',$booking->tour_time)[1]??'00'; @endphp
                          {{ $h%12?:12 }}:{{ $m }} {{ $h>=12?'PM':'AM' }}
                        </td>
                      </tr>
                      @endif
                      <tr>
                        <td style="padding:8px 14px; color:#64748b; font-size:12px; font-weight:600;">Participantes</td>
                        <td style="padding:8px 14px; font-size:13px; color:#1a1a2e;">{{ $booking->adults }} adulto{{ $booking->adults > 1 ? 's' : '' }}@if($booking->children > 0), {{ $booking->children }} nino{{ $booking->children > 1 ? 's' : '' }}@endif</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- Client Details -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                <tr>
                  <td style="padding:10px 14px; background:#475569; border-radius:8px 8px 0 0;">
                    <p style="margin:0; font-size:11px; font-weight:700; color:#ffffff; text-transform:uppercase; letter-spacing:1px;">Cliente</p>
                  </td>
                </tr>
                <tr>
                  <td style="border:1px solid #e2e8f0; border-top:none; border-radius:0 0 8px 8px; padding:0;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; color:#64748b; font-size:12px; font-weight:600; width:35%;">Nombre</td>
                        <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; font-weight:600; color:#1a1a2e;">{{ $booking->customer_name }}</td>
                      </tr>
                      <tr>
                        <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; color:#64748b; font-size:12px; font-weight:600;">Email</td>
                        <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:13px;"><a href="mailto:{{ $booking->customer_email }}" style="color:#2980b9; text-decoration:none;">{{ $booking->customer_email }}</a></td>
                      </tr>
                      @if($booking->customer_phone)
                      <tr>
                        <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; color:#64748b; font-size:12px; font-weight:600;">Telefono</td>
                        <td style="padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:13px;"><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->customer_phone) }}" style="color:#2980b9; text-decoration:none;">{{ $booking->customer_phone }}</a></td>
                      </tr>
                      @endif
                      @if($booking->customer_country)
                      <tr>
                        <td style="padding:8px 14px;{{ $booking->customer_notes ? ' border-bottom:1px solid #f1f5f9;' : '' }} color:#64748b; font-size:12px; font-weight:600;">Pais</td>
                        <td style="padding:8px 14px;{{ $booking->customer_notes ? ' border-bottom:1px solid #f1f5f9;' : '' }} font-size:13px; color:#1a1a2e;">{{ $booking->customer_country }}</td>
                      </tr>
                      @endif
                      @if($booking->customer_notes)
                      <tr>
                        <td style="padding:8px 14px; color:#64748b; font-size:12px; font-weight:600;">Notas</td>
                        <td style="padding:8px 14px; font-size:13px; color:#1a1a2e;">{{ $booking->customer_notes }}</td>
                      </tr>
                      @endif
                    </table>
                  </td>
                </tr>
              </table>

              <!-- Payment Summary -->
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px;">
                <tr>
                  <td style="padding:16px;">
                    <p style="margin:0 0 10px; font-size:11px; color:#94a3b8; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Pago</p>
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="padding:5px 0; font-size:13px; color:#64748b;">Subtotal</td>
                        <td style="padding:5px 0; font-size:13px; color:#1a1a2e; text-align:right;">{{ $booking->currency }} {{ number_format($booking->subtotal, 2) }}</td>
                      </tr>
                      @if(($booking->tax_amount ?? 0) > 0)
                      <tr>
                        <td style="padding:5px 0; font-size:13px; color:#64748b;">Tasas ({{ number_format($booking->tax_percentage, 0) }}%)</td>
                        <td style="padding:5px 0; font-size:13px; color:#1a1a2e; text-align:right;">{{ $booking->currency }} {{ number_format($booking->tax_amount, 2) }}</td>
                      </tr>
                      @elseif($booking->tour && ($booking->tour->tax_percentage ?? 0) > 0)
                      @php $taxAmount = ($booking->subtotal * $booking->tour->tax_percentage) / 100; @endphp
                      <tr>
                        <td style="padding:5px 0; font-size:13px; color:#64748b;">Tasas ({{ number_format($booking->tour->tax_percentage, 0) }}%)</td>
                        <td style="padding:5px 0; font-size:13px; color:#1a1a2e; text-align:right;">{{ $booking->currency }} {{ number_format($taxAmount, 2) }}</td>
                      </tr>
                      @endif
                      @if(($booking->discount ?? 0) > 0)
                      <tr>
                        <td style="padding:5px 0; font-size:13px; color:#22c55e;">Descuento</td>
                        <td style="padding:5px 0; font-size:13px; color:#22c55e; text-align:right;">-{{ $booking->currency }} {{ number_format($booking->discount, 2) }}</td>
                      </tr>
                      @endif
                    </table>
                    <table width="100%" cellpadding="0" cellspacing="0" style="border-top:2px solid #e2e8f0; margin-top:8px;">
                      <tr>
                        <td style="padding:10px 0 2px; font-size:17px; font-weight:800; color:#1e3a5f;">Total</td>
                        <td style="padding:10px 0 2px; font-size:17px; font-weight:800; color:#1e3a5f; text-align:right;">{{ $booking->currency }} {{ number_format($booking->total, 2) }}</td>
                      </tr>
                    </table>
                    <p style="margin:6px 0 0; font-size:11px; color:#94a3b8;">
                      {{ strtoupper($booking->payment_method ?? '-') }}@if($booking->transaction_id) &middot; {{ $booking->transaction_id }}@endif
                    </p>
                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <!-- Admin Footer -->
          <tr>
            <td style="background:#f1f5f9; padding:16px 30px; text-align:center; border-top:1px solid #e2e8f0;">
              <p style="margin:0; font-size:11px; color:#94a3b8;">Incalake Tours &middot; Copia interna &middot; &copy; {{ date('Y') }}</p>
            </td>
          </tr>

@else
          {{-- ============================================== --}}
          {{-- CLIENT VERSION --}}
          {{-- ============================================== --}}

          <!-- Header -->
          <tr>
            <td style="background:linear-gradient(135deg,#1e3a5f 0%,#2980b9 100%); padding:32px 30px; text-align:center;">
              <h1 style="margin:0; font-size:24px; font-weight:800; color:#ffffff; letter-spacing:-0.5px;">Incalake</h1>
              <p style="margin:4px 0 0; font-size:11px; color:rgba(255,255,255,0.7); text-transform:uppercase; letter-spacing:2px;">Tours & Experiences</p>
            </td>
          </tr>

          <tr>
            <td style="padding:32px 30px 24px;">

              <!-- Greeting -->
              <p style="font-size:15px; color:#555; margin:0 0 20px; line-height:1.6;">
                Hola <strong style="color:#1a1a2e;">{{ $booking->customer_name }}</strong>,<br>
                Tu reserva ha sido confirmada. Aqui tienes el resumen.
              </p>

              <!-- Booking Code Card -->
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:24px;">
                <tr>
                  <td style="padding:20px; text-align:center;">
                    <p style="margin:0 0 6px; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:2px; font-weight:700;">Codigo de reserva</p>
                    <p style="margin:0 0 10px; font-size:24px; font-weight:800; color:#1e3a5f; letter-spacing:2px; font-family:'Courier New',monospace;">{{ $booking->booking_code }}</p>
                    <span style="display:inline-block; background:#22c55e; color:#fff; padding:4px 14px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:0.5px;">PAGADO</span>
                  </td>
                </tr>
              </table>

              <!-- Tour Details -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                <tr>
                  <td style="padding:10px 14px; background:#1e3a5f; border-radius:8px 8px 0 0;">
                    <p style="margin:0; font-size:12px; font-weight:700; color:#ffffff; text-transform:uppercase; letter-spacing:1px;">Detalles del Tour</p>
                  </td>
                </tr>
                <tr>
                  <td style="border:1px solid #e2e8f0; border-top:none; border-radius:0 0 8px 8px; padding:0;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="padding:12px 14px; border-bottom:1px solid #f1f5f9; color:#64748b; font-size:13px; font-weight:600; width:40%;">Tour</td>
                        <td style="padding:12px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; font-weight:700; color:#1a1a2e;">{{ $booking->tour_title }}</td>
                      </tr>
                      <tr>
                        <td style="padding:12px 14px; border-bottom:1px solid #f1f5f9; color:#64748b; font-size:13px; font-weight:600;">Fecha</td>
                        <td style="padding:12px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#1a1a2e;">{{ \Carbon\Carbon::parse($booking->tour_date)->format('d/m/Y') }}</td>
                      </tr>
                      @if($booking->tour_time)
                      <tr>
                        <td style="padding:12px 14px; border-bottom:1px solid #f1f5f9; color:#64748b; font-size:13px; font-weight:600;">Horario</td>
                        <td style="padding:12px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; color:#1a1a2e;">
                          @php $h=(int)explode(':',$booking->tour_time)[0]; $m=explode(':',$booking->tour_time)[1]??'00'; @endphp
                          {{ $h%12?:12 }}:{{ $m }} {{ $h>=12?'PM':'AM' }}
                        </td>
                      </tr>
                      @endif
                      <tr>
                        <td style="padding:12px 14px; color:#64748b; font-size:13px; font-weight:600;">Participantes</td>
                        <td style="padding:12px 14px; font-size:13px; color:#1a1a2e;">
                          {{ $booking->adults }} adulto{{ $booking->adults > 1 ? 's' : '' }}@if($booking->children > 0), {{ $booking->children }} nino{{ $booking->children > 1 ? 's' : '' }}@endif
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- Payment Summary -->
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:24px;">
                <tr>
                  <td style="padding:16px;">
                    <p style="margin:0 0 12px; font-size:11px; color:#94a3b8; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Resumen de pago</p>
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="padding:6px 0; font-size:14px; color:#64748b;">Subtotal</td>
                        <td style="padding:6px 0; font-size:14px; color:#1a1a2e; text-align:right;">{{ $booking->currency }} {{ number_format($booking->subtotal, 2) }}</td>
                      </tr>
                      @if(($booking->tax_amount ?? 0) > 0)
                      <tr>
                        <td style="padding:6px 0; font-size:14px; color:#64748b;">Tasas de transaccion ({{ number_format($booking->tax_percentage, 0) }}%)</td>
                        <td style="padding:6px 0; font-size:14px; color:#1a1a2e; text-align:right;">{{ $booking->currency }} {{ number_format($booking->tax_amount, 2) }}</td>
                      </tr>
                      @elseif($booking->tour && ($booking->tour->tax_percentage ?? 0) > 0)
                      @php $taxAmount = ($booking->subtotal * $booking->tour->tax_percentage) / 100; @endphp
                      <tr>
                        <td style="padding:6px 0; font-size:14px; color:#64748b;">Tasas de transaccion ({{ number_format($booking->tour->tax_percentage, 0) }}%)</td>
                        <td style="padding:6px 0; font-size:14px; color:#1a1a2e; text-align:right;">{{ $booking->currency }} {{ number_format($taxAmount, 2) }}</td>
                      </tr>
                      @endif
                      @if(($booking->discount ?? 0) > 0)
                      <tr>
                        <td style="padding:6px 0; font-size:14px; color:#22c55e;">Descuento</td>
                        <td style="padding:6px 0; font-size:14px; color:#22c55e; text-align:right;">-{{ $booking->currency }} {{ number_format($booking->discount, 2) }}</td>
                      </tr>
                      @endif
                    </table>
                    <table width="100%" cellpadding="0" cellspacing="0" style="border-top:2px solid #e2e8f0; margin-top:10px;">
                      <tr>
                        <td style="padding:12px 0 4px; font-size:18px; font-weight:800; color:#1e3a5f;">Total pagado</td>
                        <td style="padding:12px 0 4px; font-size:18px; font-weight:800; color:#1e3a5f; text-align:right;">{{ $booking->currency }} {{ number_format($booking->total, 2) }}</td>
                      </tr>
                    </table>
                    <p style="margin:8px 0 0; font-size:11px; color:#94a3b8;">
                      Metodo: {{ strtoupper($booking->payment_method ?? '-') }}@if($booking->transaction_id) &middot; ID: {{ $booking->transaction_id }}@endif
                    </p>
                  </td>
                </tr>
              </table>

              <!-- CTA: View Booking -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                <tr>
                  <td align="center" style="padding:24px 20px; background:#f8fafc; border-radius:12px;">
                    <a href="{{ config('app.frontend_url') }}/es/booking-confirmation/{{ $booking->booking_code }}?token={{ $booking->confirmation_token }}"
                       style="display:inline-block; background:#1e3a5f; color:#ffffff; text-decoration:none; padding:14px 36px; border-radius:10px; font-size:14px; font-weight:700; letter-spacing:0.3px;">
                      Ver detalles de mi reserva
                    </a>
                    <p style="margin:10px 0 0; font-size:11px; color:#94a3b8;">Enlace seguro y unico para tu reserva</p>
                  </td>
                </tr>
              </table>

              <!-- Google Calendar -->
              @php
                $dateOnly  = \Carbon\Carbon::parse($booking->tour_date)->format('Y-m-d');
                $timeOnly  = \Carbon\Carbon::parse($booking->tour_time)->format('H:i:s');
                $startDt   = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateOnly . ' ' . $timeOnly, 'America/Lima');
                $endDt     = $startDt->copy()->addMinutes(30);
                $gcalStart = $startDt->format('Ymd\THis');
                $gcalEnd   = $endDt->format('Ymd\THis');
                $participants = (int)($booking->adults ?? 0) + (int)($booking->children ?? 0);
                $gcalTitle   = urlencode($booking->customer_name . ' (' . $participants . ') | ' . $booking->tour_title);
                $gcalDetails = urlencode('Reserva #' . $booking->booking_code . ' - Incalake Tours. Total: ' . $booking->currency . ' ' . number_format($booking->total, 2));
                $gcalUrl     = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . $gcalTitle . '&dates=' . $gcalStart . '/' . $gcalEnd . '&details=' . $gcalDetails . '&ctz=America/Lima';
              @endphp
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                <tr>
                  <td align="center">
                    <a href="{{ $gcalUrl }}" target="_blank"
                       style="display:inline-block; background:#ffffff; color:#1a1a2e; text-decoration:none; padding:10px 24px; border-radius:8px; font-size:13px; font-weight:600; border:1px solid #e2e8f0;">
                      Agregar al Google Calendar
                    </a>
                  </td>
                </tr>
              </table>

              <!-- Important Notice -->
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:16px;">
                    <p style="margin:0 0 6px; font-size:12px; font-weight:700; color:#92400e;">Informacion importante</p>
                    <p style="margin:0; font-size:12px; color:#78350f; line-height:1.6;">
                      Presentate 15 minutos antes del horario indicado.<br>
                      Dudas o consultas: <a href="https://wa.me/51982769453" style="color:#1e3a5f; font-weight:600;">+51 982 769 453</a> (WhatsApp) o <a href="mailto:reservas@incalake.com" style="color:#1e3a5f; font-weight:600;">reservas@incalake.com</a>
                    </p>
                  </td>
                </tr>
              </table>

              <!-- Cancellation Policies -->
              @if($booking->tour && ($booking->tour->policy_description || $booking->tour->policy_description_custom))
              @php
                $policyContent = $booking->tour->policy_type === 'custom'
                  ? $booking->tour->policy_description_custom
                  : $booking->tour->policy_description;
              @endphp
              @if($policyContent)
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;">
                <tr>
                  <td style="background:#fef3c7; border:1px solid #fde68a; border-radius:10px; padding:16px;">
                    <p style="margin:0 0 8px; font-size:11px; font-weight:700; color:#92400e; text-transform:uppercase; letter-spacing:1px;">Politicas de cancelacion</p>
                    @if($booking->tour->policy_type === 'standard')
                    <table width="100%" cellpadding="0" cellspacing="0" style="font-size:12px; color:#78350f;">
                      <tr>
                        <td style="padding:6px 0; border-bottom:1px solid #fde68a;">Hasta 48h antes</td>
                        <td style="padding:6px 0; border-bottom:1px solid #fde68a; text-align:right; font-weight:600;">20% penalidad</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0;">Menos de 48h</td>
                        <td style="padding:6px 0; text-align:right; font-weight:600;">Sin reembolso</td>
                      </tr>
                    </table>
                    @else
                    <p style="margin:0; font-size:12px; color:#78350f; line-height:1.6;">{!! nl2br(e(strip_tags($policyContent))) !!}</p>
                    @endif
                  </td>
                </tr>
              </table>
              @endif
              @endif

            </td>
          </tr>

          <!-- Client Footer -->
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
