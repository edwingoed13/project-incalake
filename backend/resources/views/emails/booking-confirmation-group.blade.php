<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirmacion de Reserva</title>
</head>
{{--
  ONE body for both audiences — same rule as booking-confirmation.blade.php:
  the admin variant is the exact client email plus an ops strip on top, never a
  second rendering that drifts out of sync.
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
                  <td style="color:#fff; font-size:13px; font-weight:800; letter-spacing:0.5px;">NUEVA RESERVA CONFIRMADA &middot; {{ $bookings->count() }} TOURS</td>
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
                    <span style="color:#1a1a2e; font-weight:700;">{{ $primary->customer_name }}</span>
                  </td>
                  <td style="padding:3px 0; text-align:right;">
                    <span style="color:#94a3b8; font-weight:700;">Pais&nbsp;</span>
                    <span style="color:#1a1a2e;">{{ $primary->customer_country ?: '—' }}</span>
                  </td>
                </tr>
                <tr>
                  <td style="padding:3px 0;">
                    <a href="mailto:{{ $primary->customer_email }}" style="color:#2980b9; text-decoration:none;">{{ $primary->customer_email }}</a>
                  </td>
                  <td style="padding:3px 0; text-align:right;">
                    @if($primary->customer_phone)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $primary->customer_phone) }}" style="color:#16a34a; text-decoration:none; font-weight:700;">WhatsApp {{ $primary->customer_phone }}</a>
                    @else
                    <span style="color:#94a3b8;">Sin telefono</span>
                    @endif
                  </td>
                </tr>
                @if($primary->customer_notes)
                <tr>
                  <td colspan="2" style="padding:6px 0 3px;">
                    <span style="color:#94a3b8; font-weight:700;">Notas&nbsp;</span>
                    <span style="color:#1a1a2e;">{{ $primary->customer_notes }}</span>
                  </td>
                </tr>
                @endif
                <tr>
                  <td colspan="2" style="padding:6px 0 0; color:#94a3b8; font-size:11px;">
                    {{ strtoupper($primary->payment_method ?? '-') }}@if($primary->transaction_id) &middot; {{ $primary->transaction_id }}@endif
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

              <p style="font-size:15px; color:#555; margin:0 0 16px; line-height:1.55;">
                Hola <strong style="color:#1a1a2e;">{{ $primary->customer_name }}</strong>,<br>
                Tu reserva de <strong>{{ $bookings->count() }} tours</strong> ha sido confirmada. Aqui tienes el resumen.
              </p>

              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:18px;">
                <tr><td style="padding:14px 16px; text-align:center;">
                  <p style="margin:0 0 4px; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:2px; font-weight:700;">Reserva confirmada</p>
                  <p style="margin:0 0 8px; font-size:20px; font-weight:800; color:#1e3a5f;">{{ $bookings->count() }} tours</p>
                  <span style="display:inline-block; background:#22c55e; color:#fff; padding:4px 14px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:0.5px;">PAGADO</span>
                  <p style="margin:8px 0 0; font-size:11px; color:#94a3b8;">El codigo de cada tour esta en su tarjeta &darr;</p>
                </td></tr>
              </table>

              {{-- Each tour: the three facts share one row instead of a 35%
                   label column repeating "Fecha / Horario / Participantes". --}}
              @foreach($bookings as $b)
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:10px;">
                <tr><td style="padding:12px 16px; background:#1e3a5f; border-radius:8px 8px 0 0;">
                  <p style="margin:0 0 2px; font-size:10px; font-weight:700; color:rgba(255,255,255,0.6); text-transform:uppercase; letter-spacing:1px;">Tour {{ $loop->iteration }} de {{ $bookings->count() }} &middot; Codigo: {{ $b->booking_code }}</p>
                  <p style="margin:0; font-size:14px; font-weight:700; color:#ffffff; line-height:1.35;">{{ $b->tour_title }}</p>
                </td></tr>
                <tr><td style="border:1px solid #e2e8f0; border-top:none; border-radius:0 0 8px 8px; padding:0;">
                  <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="padding:10px 14px; width:33%; border-right:1px solid #f1f5f9;">
                        <p style="margin:0 0 2px; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px; font-weight:700;">Fecha</p>
                        <p style="margin:0; font-size:13px; font-weight:700; color:#1a1a2e;">{{ \Carbon\Carbon::parse($b->tour_date)->format('d/m/Y') }}</p>
                      </td>
                      <td style="padding:10px 14px; width:33%; border-right:1px solid #f1f5f9;">
                        <p style="margin:0 0 2px; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px; font-weight:700;">Horario</p>
                        <p style="margin:0; font-size:13px; font-weight:700; color:#1a1a2e;">
                          @if($b->tour_time)
                          @php $h=(int)explode(':',$b->tour_time)[0]; $m=explode(':',$b->tour_time)[1]??'00'; @endphp
                          {{ $h%12?:12 }}:{{ $m }} {{ $h>=12?'PM':'AM' }}
                          @else
                          —
                          @endif
                        </p>
                      </td>
                      <td style="padding:10px 14px;">
                        <p style="margin:0 0 2px; font-size:10px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px; font-weight:700;">Viajeros</p>
                        <p style="margin:0; font-size:13px; font-weight:700; color:#1a1a2e;">{{ $b->adults }} adulto{{ $b->adults > 1 ? 's' : '' }}@if($b->children > 0) + {{ $b->children }} nino{{ $b->children > 1 ? 's' : '' }}@endif</p>
                      </td>
                    </tr>
                    @php $lists = $tourLists[$b->id] ?? ['includes' => [], 'excludes' => []]; @endphp
                    @if(!empty($lists['includes']) || !empty($lists['excludes']))
                    <tr>
                      <td colspan="3" style="padding:10px 14px 12px; border-top:1px solid #f1f5f9;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                          <tr>
                            @if(!empty($lists['includes']))
                            <td style="vertical-align:top; width:{{ !empty($lists['excludes']) ? '50%' : '100%' }}; {{ !empty($lists['excludes']) ? 'padding-right:10px; border-right:1px solid #f1f5f9;' : '' }}">
                              <p style="margin:0 0 4px; font-size:10px; font-weight:700; color:#0f766e; text-transform:uppercase; letter-spacing:0.5px;">Incluye</p>
                              <table width="100%" cellpadding="0" cellspacing="0">
                                @foreach($lists['includes'] as $inc)
                                <tr>
                                  <td style="vertical-align:top; width:12px; padding:2px 0; color:#16a34a; font-size:14px; line-height:1.4;">&bull;</td>
                                  <td style="padding:2px 0 2px 5px; font-size:12px; color:#334155; line-height:1.45;">{{ $inc }}</td>
                                </tr>
                                @endforeach
                              </table>
                            </td>
                            @endif
                            @if(!empty($lists['excludes']))
                            <td style="vertical-align:top; width:{{ !empty($lists['includes']) ? '50%' : '100%' }}; {{ !empty($lists['includes']) ? 'padding-left:12px;' : '' }}">
                              <p style="margin:0 0 4px; font-size:10px; font-weight:700; color:#b91c1c; text-transform:uppercase; letter-spacing:0.5px;">No incluye</p>
                              <table width="100%" cellpadding="0" cellspacing="0">
                                @foreach($lists['excludes'] as $exc)
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
                    @endif
                    {{-- Per-tour calendar link lives INSIDE the card now: it
                         was a separate floating table between cards, which read
                         as belonging to the next tour. --}}
                    @php
                      $dateOnly  = \Carbon\Carbon::parse($b->tour_date)->format('Y-m-d');
                      $timeOnly  = \Carbon\Carbon::parse($b->tour_time)->format('H:i:s');
                      $startDt   = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateOnly . ' ' . $timeOnly, 'America/Lima');
                      $endDt     = $startDt->copy()->addMinutes(30);
                      $participants = (int)($b->adults ?? 0) + (int)($b->children ?? 0);
                      $gcalUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . urlencode($b->customer_name . ' (' . $participants . ') | ' . $b->tour_title) . '&dates=' . $startDt->format('Ymd\THis') . '/' . $endDt->format('Ymd\THis') . '&details=' . urlencode('Reserva #' . $b->booking_code . ' - Incalake Tours') . '&ctz=America/Lima';
                    @endphp
                    <tr>
                      <td colspan="3" style="padding:8px 14px 10px; border-top:1px solid #f1f5f9; text-align:center;">
                        <a href="{{ $gcalUrl }}" target="_blank" style="color:#2980b9; text-decoration:none; font-size:12px; font-weight:600;">+ Agendar este tour en Google Calendar</a>
                      </td>
                    </tr>
                  </table>
                </td></tr>
              </table>
              @endforeach

              {{-- Group payment summary --}}
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; margin:18px 0;">
                <tr><td style="padding:16px;">
                  <p style="margin:0 0 10px; font-size:11px; color:#94a3b8; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Resumen de pago &middot; 1 cargo por {{ $bookings->count() }} tours</p>
                  <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="padding:5px 0; font-size:13px; color:#64748b;">Subtotal</td>
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
                      <td style="padding:10px 0 2px; font-size:14px; font-weight:700; color:#64748b;">Total ({{ $bookings->count() }} tours)</td>
                      <td style="padding:10px 0 2px; font-size:14px; font-weight:700; color:#64748b; text-align:right;">{{ $currency }} {{ number_format($groupTotal, 2) }}</td>
                    </tr>
                    <tr>
                      <td style="padding:3px 0; font-size:17px; font-weight:800; color:#16a34a;">{{ $groupRemaining > 0.009 ? 'Pagado ahora' : 'Total pagado' }}</td>
                      <td style="padding:3px 0; font-size:17px; font-weight:800; color:#16a34a; text-align:right;">{{ $currency }} {{ number_format($groupPaid, 2) }}</td>
                    </tr>
                    @if($groupRemaining > 0.009)
                    <tr>
                      <td style="padding:3px 0; font-size:14px; font-weight:800; color:#b45309;">A pagar el dia del tour</td>
                      <td style="padding:3px 0; font-size:14px; font-weight:800; color:#b45309; text-align:right;">{{ $currency }} {{ number_format($groupRemaining, 2) }}</td>
                    </tr>
                    @endif
                  </table>
                  <p style="margin:8px 0 0; font-size:11px; color:#94a3b8;">
                    Metodo: {{ strtoupper($primary->payment_method ?? '-') }}@if($primary->transaction_id) &middot; ID: {{ $primary->transaction_id }}@endif{{ $groupRemaining > 0.009 ? ' · Se paga en efectivo al operador.' : '' }}
                  </p>
                </td></tr>
              </table>

              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                <tr><td align="center" style="padding:18px 20px; background:#f8fafc; border-radius:12px;">
                  <a href="{{ config('app.frontend_url') }}/es/booking-confirmation/{{ $primary->booking_code }}?token={{ $primary->confirmation_token }}"
                     style="display:inline-block; background:#1e3a5f; color:#ffffff; text-decoration:none; padding:13px 32px; border-radius:10px; font-size:14px; font-weight:700; letter-spacing:0.3px;">
                    Ver detalles de mi reserva
                  </a>
                  <p style="margin:10px 0 0; font-size:11px; color:#94a3b8;">Enlace seguro y unico para tu reserva</p>
                </td></tr>
              </table>

              <table width="100%" cellpadding="0" cellspacing="0">
                <tr><td style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:14px 16px;">
                  <p style="margin:0 0 6px; font-size:12px; font-weight:700; color:#92400e;">Informacion importante</p>
                  <p style="margin:0; font-size:12px; color:#78350f; line-height:1.6;">
                    Presentate 15 minutos antes del horario indicado en cada tour.<br>
                    Dudas o consultas: <a href="https://wa.me/51982769453" style="color:#1e3a5f; font-weight:600;">+51 982 769 453</a> (WhatsApp) o <a href="mailto:reservas@incalake.com" style="color:#1e3a5f; font-weight:600;">reservas@incalake.com</a>
                  </p>
                </td></tr>
              </table>

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
