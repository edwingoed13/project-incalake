<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirmacion de Reserva</title>
</head>
{{--
  ONE body for both audiences. The office asked to see exactly what the
  customer received, but arriving as "nueva reserva": so the admin variant is
  the SAME client email with an ops strip prepended (contact data the client
  section doesn't show) — not a second, different rendering. That also means
  any future edit to this template automatically applies to both.
--}}
<body style="margin:0; padding:0; background:#f0f2f5; font-family:'Segoe UI',Arial,sans-serif; color:#1a1a2e; -webkit-font-smoothing:antialiased;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f2f5; padding:24px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.06);">

@if($isAdminCopy)
          {{-- ═══ OPS STRIP — only the office sees this block ═══ --}}
          <tr>
            <td style="background:#1e293b; padding:14px 24px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="color:#fff; font-size:13px; font-weight:800; letter-spacing:0.5px;">NUEVA RESERVA CONFIRMADA</td>
                  <td style="text-align:right;"><span style="display:inline-block; background:#22c55e; color:#fff; padding:3px 12px; border-radius:20px; font-size:11px; font-weight:700;">PAGADO</span></td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background:#f8fafc; border-bottom:2px solid #1e293b; padding:14px 24px;">
              <table width="100%" cellpadding="0" cellspacing="0" style="font-size:12px;">
                <tr>
                  <td style="padding:3px 0; width:50%;">
                    <span style="color:#94a3b8; font-weight:700;">Cliente&nbsp;</span>
                    <span style="color:#1a1a2e; font-weight:700;">{{ $booking->customer_name }}</span>
                  </td>
                  <td style="padding:3px 0; text-align:right;">
                    <span style="color:#94a3b8; font-weight:700;">Pais&nbsp;</span>
                    <span style="color:#1a1a2e;">{{ $booking->customer_country ?: '—' }}</span>
                  </td>
                </tr>
                <tr>
                  <td style="padding:3px 0;">
                    <a href="mailto:{{ $booking->customer_email }}" style="color:#2980b9; text-decoration:none;">{{ $booking->customer_email }}</a>
                  </td>
                  <td style="padding:3px 0; text-align:right;">
                    @if($booking->customer_phone)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->customer_phone) }}" style="color:#16a34a; text-decoration:none; font-weight:700;">WhatsApp {{ $booking->customer_phone }}</a>
                    @else
                    <span style="color:#94a3b8;">Sin telefono</span>
                    @endif
                  </td>
                </tr>
                @if($booking->customer_notes)
                <tr>
                  <td colspan="2" style="padding:6px 0 3px;">
                    <span style="color:#94a3b8; font-weight:700;">Notas&nbsp;</span>
                    <span style="color:#1a1a2e;">{{ $booking->customer_notes }}</span>
                  </td>
                </tr>
                @endif
                <tr>
                  <td colspan="2" style="padding:6px 0 0; color:#94a3b8; font-size:11px;">
                    {{ strtoupper($booking->payment_method ?? '-') }}@if($booking->transaction_id) &middot; {{ $booking->transaction_id }}@endif
                    &middot; Debajo: copia exacta del correo que recibio el cliente.
                  </td>
                </tr>
              </table>
            </td>
          </tr>
@endif

          {{-- ═══ CLIENT EMAIL — shared by both variants ═══ --}}

          <!-- Header -->
          <tr>
            <td style="background:linear-gradient(135deg,#1e3a5f 0%,#2980b9 100%); padding:26px 30px; text-align:center;">
              <h1 style="margin:0; font-size:24px; font-weight:800; color:#ffffff; letter-spacing:-0.5px;">Incalake</h1>
              <p style="margin:4px 0 0; font-size:11px; color:rgba(255,255,255,0.7); text-transform:uppercase; letter-spacing:2px;">Tours & Experiences</p>
            </td>
          </tr>

          <tr>
            <td style="padding:24px 28px 20px;">

              <!-- Greeting -->
              <p style="font-size:15px; color:#555; margin:0 0 16px; line-height:1.55;">
                Hola <strong style="color:#1a1a2e;">{{ $booking->customer_name }}</strong>,<br>
                Tu reserva ha sido confirmada. Aqui tienes el resumen.
              </p>

              <!-- Booking Code Card -->
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:18px;">
                <tr>
                  <td style="padding:14px 16px; text-align:center;">
                    <p style="margin:0 0 4px; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:2px; font-weight:700;">Codigo de reserva</p>
                    <p style="margin:0 0 8px; font-size:24px; font-weight:800; color:#1e3a5f; letter-spacing:2px; font-family:'Courier New',monospace;">{{ $booking->booking_code }}</p>
                    <span style="display:inline-block; background:#22c55e; color:#fff; padding:4px 14px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:0.5px;">PAGADO</span>
                  </td>
                </tr>
              </table>

              {{-- Tour card: the title lives in the header band and the three
                   facts share one row — the old label column ate 40% of the
                   width to say "Fecha" four times. --}}
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:18px;">
                <tr>
                  <td style="padding:12px 16px; background:#1e3a5f; border-radius:8px 8px 0 0;">
                    <p style="margin:0 0 2px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.6); text-transform:uppercase; letter-spacing:1px;">Tu tour</p>
                    <p style="margin:0; font-size:15px; font-weight:700; color:#ffffff; line-height:1.35;">{{ $booking->tour_title }}</p>
                  </td>
                </tr>
                <tr>
                  <td style="border:1px solid #e2e8f0; border-top:none; border-radius:0 0 8px 8px; padding:0;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="padding:12px 16px; width:33%; border-right:1px solid #f1f5f9;">
                          <p style="margin:0 0 2px; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px; font-weight:700;">Fecha</p>
                          <p style="margin:0; font-size:14px; font-weight:700; color:#1a1a2e;">{{ \Carbon\Carbon::parse($booking->tour_date)->format('d/m/Y') }}</p>
                        </td>
                        <td style="padding:12px 16px; width:33%; border-right:1px solid #f1f5f9;">
                          <p style="margin:0 0 2px; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px; font-weight:700;">Horario</p>
                          <p style="margin:0; font-size:14px; font-weight:700; color:#1a1a2e;">
                            @if($booking->tour_time)
                            @php $h=(int)explode(':',$booking->tour_time)[0]; $m=explode(':',$booking->tour_time)[1]??'00'; @endphp
                            {{ $h%12?:12 }}:{{ $m }} {{ $h>=12?'PM':'AM' }}
                            @else
                            —
                            @endif
                          </p>
                        </td>
                        <td style="padding:12px 16px;">
                          <p style="margin:0 0 2px; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px; font-weight:700;">Viajeros</p>
                          <p style="margin:0; font-size:14px; font-weight:700; color:#1a1a2e;">{{ $booking->adults }} adulto{{ $booking->adults > 1 ? 's' : '' }}@if($booking->children > 0) + {{ $booking->children }} nino{{ $booking->children > 1 ? 's' : '' }}@endif</p>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              {{-- Includes / excludes side by side: the stacked version pushed
                   the payment summary below the fold on tours with long lists. --}}
              @if(!empty($includes) || !empty($excludes))
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:18px;">
                <tr>
                  <td style="padding:10px 16px; background:#0f766e; border-radius:8px 8px 0 0;">
                    <p style="margin:0; font-size:12px; font-weight:700; color:#ffffff; text-transform:uppercase; letter-spacing:1px;">Que incluye tu tour</p>
                  </td>
                </tr>
                <tr>
                  <td style="border:1px solid #e2e8f0; border-top:none; border-radius:0 0 8px 8px; padding:12px 16px;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        @if(!empty($includes))
                        <td style="vertical-align:top; width:{{ !empty($excludes) ? '50%' : '100%' }}; {{ !empty($excludes) ? 'padding-right:10px; border-right:1px solid #f1f5f9;' : '' }}">
                          <p style="margin:0 0 6px; font-size:10px; font-weight:700; color:#16a34a; text-transform:uppercase; letter-spacing:0.5px;">Incluye</p>
                          <table width="100%" cellpadding="0" cellspacing="0">
                            @foreach($includes as $inc)
                            <tr>
                              <td style="vertical-align:top; width:12px; padding:2px 0; color:#16a34a; font-size:14px; line-height:1.4;">&bull;</td>
                              <td style="padding:2px 0 2px 5px; font-size:12px; color:#334155; line-height:1.45;">{{ $inc }}</td>
                            </tr>
                            @endforeach
                          </table>
                        </td>
                        @endif
                        @if(!empty($excludes))
                        <td style="vertical-align:top; width:{{ !empty($includes) ? '50%' : '100%' }}; {{ !empty($includes) ? 'padding-left:12px;' : '' }}">
                          <p style="margin:0 0 6px; font-size:10px; font-weight:700; color:#b91c1c; text-transform:uppercase; letter-spacing:0.5px;">No incluye</p>
                          <table width="100%" cellpadding="0" cellspacing="0">
                            @foreach($excludes as $exc)
                            <tr>
                              <td style="vertical-align:top; width:12px; padding:2px 0; color:#dc2626; font-size:14px; line-height:1.4;">&bull;</td>
                              <td style="padding:2px 0 2px 5px; font-size:12px; color:#94a3b8; line-height:1.45;">{{ $exc }}</td>
                            </tr>
                            @endforeach
                          </table>
                        </td>
                        @endif
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              @endif

              <!-- Payment Summary -->
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:18px;">
                <tr>
                  <td style="padding:16px;">
                    <p style="margin:0 0 10px; font-size:11px; color:#94a3b8; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Resumen de pago</p>
                    <table width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="padding:5px 0; font-size:13px; color:#64748b;">Subtotal</td>
                        <td style="padding:5px 0; font-size:13px; color:#1a1a2e; text-align:right;">{{ $booking->currency }} {{ number_format($booking->subtotal, 2) }}</td>
                      </tr>
                      @if(($booking->tax_amount ?? 0) > 0)
                      <tr>
                        <td style="padding:5px 0; font-size:13px; color:#64748b;">Tasas de transaccion ({{ number_format($booking->tax_percentage, 0) }}%)</td>
                        <td style="padding:5px 0; font-size:13px; color:#1a1a2e; text-align:right;">{{ $booking->currency }} {{ number_format($booking->tax_amount, 2) }}</td>
                      </tr>
                      @elseif($booking->tour && ($booking->tour->tax_percentage ?? 0) > 0)
                      @php $taxAmount = ($booking->subtotal * $booking->tour->tax_percentage) / 100; @endphp
                      <tr>
                        <td style="padding:5px 0; font-size:13px; color:#64748b;">Tasas de transaccion ({{ number_format($booking->tour->tax_percentage, 0) }}%)</td>
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
                    @php
                      $cliPaid = round($amountPaid ?? (float)$booking->total, 2);
                      $cliRemaining = round(max(0, (float)$booking->total - $cliPaid), 2);
                    @endphp
                    <table width="100%" cellpadding="0" cellspacing="0" style="border-top:2px solid #e2e8f0; margin-top:8px;">
                      <tr>
                        <td style="padding:10px 0 2px; font-size:14px; font-weight:700; color:#64748b;">Total</td>
                        <td style="padding:10px 0 2px; font-size:14px; font-weight:700; color:#64748b; text-align:right;">{{ $booking->currency }} {{ number_format($booking->total, 2) }}</td>
                      </tr>
                      <tr>
                        <td style="padding:3px 0; font-size:17px; font-weight:800; color:#16a34a;">{{ $cliRemaining > 0.009 ? 'Pagado ahora' : 'Total pagado' }}</td>
                        <td style="padding:3px 0; font-size:17px; font-weight:800; color:#16a34a; text-align:right;">{{ $booking->currency }} {{ number_format($cliPaid, 2) }}</td>
                      </tr>
                      @if($cliRemaining > 0.009)
                      <tr>
                        <td style="padding:3px 0; font-size:14px; font-weight:800; color:#b45309;">Saldo pendiente</td>
                        <td style="padding:3px 0; font-size:14px; font-weight:800; color:#b45309; text-align:right;">{{ $booking->currency }} {{ number_format($cliRemaining, 2) }}</td>
                      </tr>
                      @endif
                    </table>
                    <p style="margin:8px 0 0; font-size:11px; color:#94a3b8;">
                      Metodo: {{ strtoupper($booking->payment_method ?? '-') }}@if($booking->transaction_id) &middot; ID: {{ $booking->transaction_id }}@endif{{ $cliRemaining > 0.009 ? ' · El saldo se paga el dia del tour.' : '' }}
                    </p>
                  </td>
                </tr>
              </table>

              {{-- CTA + calendar share one card: two buttons, one purpose
                   (guardar/consultar la reserva) — before they were two stacked
                   boxes with their own padding. --}}
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                <tr>
                  <td align="center" style="padding:18px 20px; background:#f8fafc; border-radius:12px;">
                    <a href="{{ config('app.frontend_url') }}/es/booking-confirmation/{{ $booking->booking_code }}?token={{ $booking->confirmation_token }}"
                       style="display:inline-block; background:#1e3a5f; color:#ffffff; text-decoration:none; padding:13px 32px; border-radius:10px; font-size:14px; font-weight:700; letter-spacing:0.3px;">
                      Ver detalles de mi reserva
                    </a>
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
                    <p style="margin:10px 0 0;">
                      <a href="{{ $gcalUrl }}" target="_blank"
                         style="display:inline-block; background:#ffffff; color:#1a1a2e; text-decoration:none; padding:9px 22px; border-radius:8px; font-size:12px; font-weight:600; border:1px solid #e2e8f0;">
                        Agregar a Google Calendar
                      </a>
                    </p>
                    <p style="margin:10px 0 0; font-size:11px; color:#94a3b8;">Enlace seguro y unico para tu reserva</p>
                  </td>
                </tr>
              </table>

              <!-- Important Notice -->
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:14px 16px;">
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
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px;">
                <tr>
                  <td style="background:#fef3c7; border:1px solid #fde68a; border-radius:10px; padding:14px 16px;">
                    <p style="margin:0 0 8px; font-size:11px; font-weight:700; color:#92400e; text-transform:uppercase; letter-spacing:1px;">Politicas de cancelacion</p>
                    @if($booking->tour->policy_type === 'standard')
                    <table width="100%" cellpadding="0" cellspacing="0" style="font-size:12px; color:#78350f;">
                      <tr>
                        <td style="padding:5px 0; border-bottom:1px solid #fde68a;">Hasta 48h antes</td>
                        <td style="padding:5px 0; border-bottom:1px solid #fde68a; text-align:right; font-weight:600;">20% penalidad</td>
                      </tr>
                      <tr>
                        <td style="padding:5px 0;">Menos de 48h</td>
                        <td style="padding:5px 0; text-align:right; font-weight:600;">Sin reembolso</td>
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

          <!-- Footer -->
          <tr>
            <td style="background:#f8fafc; padding:20px 30px; text-align:center; border-top:1px solid #e2e8f0;">
              <p style="margin:0 0 4px; font-size:14px; font-weight:700; color:#1e3a5f;">Incalake Tours</p>
              <p style="margin:0 0 10px; font-size:12px; color:#94a3b8;">
                <a href="mailto:reservas@incalake.com" style="color:#64748b; text-decoration:none;">reservas@incalake.com</a>
                &nbsp;&middot;&nbsp;
                <a href="https://wa.me/51982769453" style="color:#64748b; text-decoration:none;">+51 982 769 453</a>
              </p>
              <p style="margin:0; font-size:11px; color:#cbd5e1;">&copy; {{ date('Y') }} Incalake Tours. Todos los derechos reservados.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
