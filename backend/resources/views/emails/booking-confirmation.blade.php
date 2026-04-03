<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirmacion de Reserva</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f4f4f4; color: #333; }
    .wrap { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .header { background: #2980b9; color: #fff; padding: 28px 30px; text-align: center; }
    .header h1 { margin: 0 0 4px; font-size: 22px; }
    .header p { margin: 0; font-size: 13px; opacity: 0.9; }
    .body { padding: 30px; }
    .greeting { font-size: 16px; margin-bottom: 20px; }
    .section-title { background: #2980b9; color: #fff; padding: 8px 14px; font-weight: bold; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; margin: 24px 0 0; border-radius: 4px 4px 0 0; }
    table { width: 100%; border-collapse: collapse; }
    table td { padding: 9px 14px; border-bottom: 1px solid #eee; font-size: 14px; }
    table td:first-child { color: #666; font-weight: bold; width: 40%; }
    .price-box { border: 2px solid #2980b9; border-radius: 4px; padding: 16px; margin-top: 24px; }
    .price-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 15px; }
    .price-row.total { border-top: 2px solid #2980b9; margin-top: 8px; padding-top: 10px; font-weight: bold; font-size: 18px; color: #2980b9; }
    .badge { display: inline-block; background: #27ae60; color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }
    .notice { background: #fff8e1; border-left: 4px solid #f39c12; padding: 12px 16px; margin-top: 24px; font-size: 13px; border-radius: 0 4px 4px 0; }
    .footer { background: #f4f4f4; text-align: center; padding: 20px; font-size: 12px; color: #888; border-top: 1px solid #eee; }
    .footer a { color: #2980b9; text-decoration: none; }
    .code-box { text-align: center; background: #eaf4fb; border: 1px dashed #2980b9; border-radius: 6px; padding: 14px; margin: 24px 0; }
    .code-box .code { font-size: 22px; font-weight: bold; color: #2980b9; letter-spacing: 3px; }
    .admin-banner { background: #e74c3c; color: #fff; text-align: center; padding: 8px; font-weight: bold; font-size: 13px; }
    .policy-box { background: #fef5e7; border: 1px solid #f39c12; border-radius: 4px; padding: 16px; margin-top: 16px; }
    .policy-title { font-weight: bold; color: #d68910; margin-bottom: 10px; font-size: 14px; text-transform: uppercase; }
    .policy-content { font-size: 13px; line-height: 1.6; color: #555; }
    .policy-table { width: 100%; margin-top: 10px; border-collapse: collapse; }
    .policy-table th { background: #f39c12; color: #fff; padding: 8px; text-align: left; font-size: 12px; }
    .policy-table td { padding: 8px; border-bottom: 1px solid #f4d03f; font-size: 12px; }
  </style>
</head>
<body>
<div class="wrap">

  @if($isAdminCopy)
  <div class="admin-banner">&#128276; COPIA INTERNA - NUEVA RESERVA CONFIRMADA</div>
  @endif

  <div class="header">
    <h1>Inca Lake</h1>
    <p>reservas@incalake.com</p>
  </div>

  <div class="body">

    @if($isAdminCopy)
      <div class="greeting">Se ha confirmado una nueva reserva con pago exitoso.</div>
    @else
      <div class="greeting">
        Estimado/a <strong>{{ $booking->customer_name }}</strong>,<br><br>
        &#127881; Tu reserva ha sido <strong>confirmada y pagada</strong> exitosamente. A continuacion encontraras todos los detalles.
      </div>
    @endif

    <!-- Booking code -->
    <div class="code-box">
      <div style="font-size:12px; color:#666; margin-bottom:4px;">CODIGO DE RESERVA</div>
      <div class="code">{{ $booking->booking_code }}</div>
      <div style="margin-top:6px;"><span class="badge">&#10003; PAGADO</span></div>
    </div>

    <!-- Tour details -->
    <div class="section-title">&#127758; Detalles del Tour</div>
    <table>
      <tr><td>Tour</td><td>{{ $booking->tour_title }}</td></tr>
      <tr><td>Fecha del tour</td><td>{{ \Carbon\Carbon::parse($booking->tour_date)->format('d/m/Y') }}</td></tr>
      @if($booking->tour_time)
      <tr><td>Horario</td><td>{{ $booking->tour_time }}</td></tr>
      @endif
      <tr><td>Adultos</td><td>{{ $booking->adults }}</td></tr>
      @if($booking->children > 0)
      <tr><td>Ninos</td><td>{{ $booking->children }}</td></tr>
      @endif
      @if($booking->infants > 0)
      <tr><td>Bebes</td><td>{{ $booking->infants }}</td></tr>
      @endif
      <tr><td>Total participantes</td><td>{{ $booking->total_participants }}</td></tr>
    </table>

    <!-- Client details -->
    <div class="section-title">&#128100; Datos del Cliente</div>
    <table>
      <tr><td>Nombre</td><td>{{ $booking->customer_name }}</td></tr>
      <tr><td>Email</td><td>{{ $booking->customer_email }}</td></tr>
      @if($booking->customer_phone)
      <tr><td>Telefono</td><td>{{ $booking->customer_phone }}</td></tr>
      @endif
      @if($booking->customer_country)
      <tr><td>Pais</td><td>{{ $booking->customer_country }}</td></tr>
      @endif
      @if($booking->customer_notes)
      <tr><td>Notas</td><td>{{ $booking->customer_notes }}</td></tr>
      @endif
    </table>

    <!-- Payment -->
    <div class="price-box">
      <div style="font-weight:bold; margin-bottom:10px; font-size:14px; color:#666;">RESUMEN DE PAGO</div>
      <div class="price-row">
        <span>Subtotal</span>
        <span>{{ $booking->currency }} {{ number_format($booking->subtotal, 2) }}</span>
      </div>
      @if($booking->tour && $booking->tour->tax_percentage && $booking->tour->tax_percentage > 0)
      @php
        $taxAmount = ($booking->subtotal * $booking->tour->tax_percentage) / 100;
      @endphp
      <div class="price-row">
        <span>Tasas e impuestos ({{ number_format($booking->tour->tax_percentage, 0) }}%)</span>
        <span>{{ $booking->currency }} {{ number_format($taxAmount, 2) }}</span>
      </div>
      @endif
      <div class="price-row total">
        <span>TOTAL PAGADO</span>
        <span>{{ $booking->currency }} {{ number_format($booking->total, 2) }}</span>
      </div>
      <div style="font-size:12px; color:#888; margin-top:8px;">
        Metodo de pago: {{ strtoupper($booking->payment_method ?? '-') }}
        @if($booking->transaction_id)
          | ID transaccion: {{ $booking->transaction_id }}
        @endif
      </div>
    </div>

    <!-- Tour Cancellation Policies -->
    @if($booking->tour && ($booking->tour->policy_description || $booking->tour->policy_description_custom))
    <div class="section-title">⚠️ POLITICAS DE CANCELACION IMPORTANTES</div>

    @php
      $policyContent = $booking->tour->policy_type === 'custom'
        ? $booking->tour->policy_description_custom
        : $booking->tour->policy_description;
    @endphp

    @if($policyContent)
    <div class="policy-box">
      <div class="policy-title">
        POLITICAS DE CANCELACION Y CAMBIOS
      </div>

      @if($booking->tour->policy_type === 'standard')
        <!-- Tabla de políticas estándar -->
        <table class="policy-table">
          <thead>
            <tr>
              <th>Periodo de Anticipacion</th>
              <th>Penalidad</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Hasta 48 horas antes del tour</td>
              <td><strong>20% del total</strong> (gastos administrativos)</td>
            </tr>
            <tr>
              <td>Menos de 48 horas antes del tour</td>
              <td><strong>100% del total</strong> (sin reembolso)</td>
            </tr>
          </tbody>
        </table>
        <div style="margin-top:10px; font-size:11px; color:#666; font-style:italic;">
          * Las cancelaciones deben ser notificadas por escrito a reservas@incalake.com<br>
          * Los reembolsos se procesaran en un plazo de 7 a 15 dias habiles
        </div>
      @else
        <!-- Políticas personalizadas -->
        <div class="policy-content">
          {!! nl2br(e(strip_tags($policyContent))) !!}
        </div>
      @endif
    </div>
    @endif
    @endif

    @if(!$isAdminCopy)
    {{-- View Booking Button with secure token --}}
    <div style="text-align:center; margin: 24px 0; padding: 20px; background: #f8f9fa; border-radius: 8px;">
      <p style="margin: 0 0 12px 0; color: #666; font-size: 14px;">
        Puedes ver los detalles completos de tu reserva en cualquier momento:
      </p>
      <a href="{{ config('app.frontend_url') }}/es/booking-confirmation/{{ $booking->booking_code }}?token={{ $booking->confirmation_token }}"
         style="display:inline-block; background:#ff6b35; color:#fff; text-decoration:none; padding:14px 32px; border-radius:8px; font-size:16px; font-weight:bold; box-shadow: 0 2px 4px rgba(255,107,53,0.3);">
        &#128274; Ver Mi Reserva
      </a>
      <p style="margin: 12px 0 0 0; color: #999; font-size: 12px;">
        Este enlace es seguro y único para tu reserva
      </p>
    </div>

    {{-- Important Notice for Client --}}
    <div class="notice">
      <strong>IMPORTANTE:</strong> Por favor presentese 15 minutos antes del horario indicado.
      Si tiene alguna pregunta, contactenos al +51 984 434 731 o escribanos a reservas@incalake.com
    </div>

    {{-- Google Calendar button for client --}}
    @php
      // tour_date cast='date' → Carbon, tour_time cast='datetime' → Carbon (use only time part)
      $dateOnly    = \Carbon\Carbon::parse($booking->tour_date)->format('Y-m-d');
      $timeOnly    = \Carbon\Carbon::parse($booking->tour_time)->format('H:i:s');
      $startDt     = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateOnly . ' ' . $timeOnly, 'America/Lima');
      $endDt       = $startDt->copy()->addMinutes(30);
      $gcalStart   = $startDt->format('Ymd\THis');
      $gcalEnd     = $endDt->format('Ymd\THis');
      $participants = (int)($booking->adults ?? 0) + (int)($booking->children ?? 0);
      $gcalTitle   = urlencode($booking->customer_name . ' (' . $participants . ') | ' . $booking->tour_title . ' | ' . strtoupper($booking->payment_method ?? 'CULQI'));
      $gcalDetails = urlencode('Reserva #' . $booking->booking_code . ' - Inca Lake. Total: ' . $booking->currency . ' ' . number_format($booking->total, 2));
      $gcalUrl     = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . $gcalTitle . '&dates=' . $gcalStart . '/' . $gcalEnd . '&details=' . $gcalDetails . '&ctz=America/Lima';
    @endphp
    <div style="text-align:center; margin: 24px 0;">
      <a href="{{ $gcalUrl }}" target="_blank"
         style="display:inline-block; background:#4285F4; color:#fff; text-decoration:none; padding:12px 24px; border-radius:6px; font-size:14px; font-weight:bold;">
        &#128197; Agregar al Google Calendar
      </a>
    </div>
    @endif

  </div>

  <div class="footer">
    <p><strong>Inca Lake</strong></p>
    <p><a href="mailto:reservas@incalake.com">reservas@incalake.com</a></p>
    <p style="margin-top:10px; color:#bbb;">&copy; {{ date('Y') }} Inca Lake. Todos los derechos reservados.</p>
  </div>

</div>
</body>
</html>