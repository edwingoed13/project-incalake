<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Datos de pasajeros · {{ $booking->booking_code }}</title>
</head>
<body style="margin:0;padding:0;background:#f5f7f8;font-family:'Helvetica Neue',Arial,sans-serif;color:#1f2937">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7f8;padding:24px 12px">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden">
          <!-- Header -->
          <tr>
            <td style="background:#0077cc;padding:18px 24px;color:#fff">
              <p style="margin:0;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;opacity:0.85">Datos de pasajeros completados</p>
              <h1 style="margin:6px 0 0;font-size:18px;font-weight:800">Reserva #{{ $booking->booking_code }}</h1>
            </td>
          </tr>

          <!-- Booking details -->
          <tr>
            <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0">
              <h2 style="margin:0 0 12px;font-size:14px;color:#0f172a">Detalles de la reserva</h2>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;line-height:1.55">
                <tr>
                  <td style="color:#64748b;padding:3px 0;width:42%">Código de reserva</td>
                  <td style="color:#0f172a;font-weight:600;padding:3px 0">{{ $booking->booking_code }}</td>
                </tr>
                <tr>
                  <td style="color:#64748b;padding:3px 0">Tour</td>
                  <td style="color:#0f172a;font-weight:600;padding:3px 0">{{ $booking->tour_title ?? optional($booking->tour)->title }}</td>
                </tr>
                <tr>
                  <td style="color:#64748b;padding:3px 0">Fecha del tour</td>
                  <td style="color:#0f172a;font-weight:600;padding:3px 0">
                    {{ \Carbon\Carbon::parse($booking->tour_date)->locale('es')->isoFormat('ddd, D MMM YYYY') }}
                    @if($booking->tour_time) · {{ \Carbon\Carbon::parse($booking->tour_time)->format('H:i') }} @endif
                  </td>
                </tr>
                <tr>
                  <td style="color:#64748b;padding:3px 0">Viajeros</td>
                  <td style="color:#0f172a;font-weight:600;padding:3px 0">
                    {{ $booking->adults ?? 0 }} {{ ($booking->adults ?? 0) === 1 ? 'adulto' : 'adultos' }}@if(($booking->children ?? 0) > 0), {{ $booking->children }} {{ $booking->children === 1 ? 'niño' : 'niños' }}@endif
                  </td>
                </tr>
                <tr>
                  <td style="color:#64748b;padding:3px 0">Estado de pago</td>
                  <td style="color:#0f172a;font-weight:600;padding:3px 0">
                    {{ $booking->payment_status === 'paid' ? '✓ Pagado' : ucfirst($booking->payment_status ?? '—') }}
                    @if($booking->total) · {{ $booking->currency ?? 'USD' }} {{ number_format($booking->total, 2) }} @endif
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Lead customer -->
          <tr>
            <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0">
              <h2 style="margin:0 0 12px;font-size:14px;color:#0f172a">Cliente que reservó</h2>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;line-height:1.55">
                <tr><td style="color:#64748b;padding:3px 0;width:42%">Nombre</td><td style="color:#0f172a;font-weight:600;padding:3px 0">{{ $booking->customer_name }}</td></tr>
                <tr><td style="color:#64748b;padding:3px 0">Email</td><td style="color:#0f172a;padding:3px 0">{{ $booking->customer_email }}</td></tr>
                @if($booking->customer_phone)
                  <tr><td style="color:#64748b;padding:3px 0">WhatsApp / Tel.</td><td style="color:#0f172a;padding:3px 0">{{ $booking->customer_phone }}</td></tr>
                @endif
              </table>
            </td>
          </tr>

          <!-- Travelers -->
          @if($booking->travelers && $booking->travelers->count())
            <tr>
              <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0">
                <h2 style="margin:0 0 12px;font-size:14px;color:#0f172a">Pasajeros ({{ $booking->travelers->count() }})</h2>
                @foreach($booking->travelers as $i => $t)
                  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:{{ $i < $booking->travelers->count() - 1 ? '14px' : '0' }};padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;line-height:1.55">
                    <tr>
                      <td>
                        <p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#0f172a">
                          {{ $t->full_name }}
                          @if($t->is_leader)<span style="margin-left:6px;font-size:9px;font-weight:800;color:#0077cc;background:#dbeafe;padding:2px 6px;border-radius:4px;letter-spacing:0.06em">RESPONSABLE</span>@endif
                        </p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:12px">
                          @if($t->nationality)<tr><td style="color:#64748b;width:42%;padding:2px 0">Nacionalidad</td><td style="color:#0f172a;padding:2px 0">{{ $t->nationality }}</td></tr>@endif
                          @if(is_array($t->extra_data ?? null))
                            @foreach($t->extra_data as $k => $v)
                              @if(trim((string) $v) !== '' && $k !== 'special_requests')
                                <tr><td style="color:#64748b;width:42%;padding:2px 0">{{ str_replace('_', ' ', ucfirst($k)) }}</td><td style="color:#0f172a;padding:2px 0">{{ $v }}</td></tr>
                              @endif
                            @endforeach
                            @if(!empty($t->extra_data['special_requests']))
                              <tr><td colspan="2" style="color:#a16207;background:#fef9c3;padding:6px 8px;border-radius:6px;margin-top:6px"><strong>Pedidos especiales:</strong> {{ $t->extra_data['special_requests'] }}</td></tr>
                            @endif
                          @endif
                        </table>
                      </td>
                    </tr>
                  </table>
                @endforeach
              </td>
            </tr>
          @endif

          <!-- Pickup -->
          @if(optional($booking->pickupDetail)->id)
            <tr>
              <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0">
                <h2 style="margin:0 0 12px;font-size:14px;color:#0f172a">Punto de encuentro / recojo</h2>
                <p style="margin:0;font-size:13px;line-height:1.55;color:#0f172a">
                  @if($booking->pickupDetail->final_choice === 'hotel_pickup')
                    <strong>Recojo en hotel:</strong> {{ $booking->pickupDetail->hotel_name }}
                    @if($booking->pickupDetail->hotel_address)<br><span style="color:#64748b">{{ $booking->pickupDetail->hotel_address }}</span>@endif
                  @else
                    <strong>Punto de encuentro:</strong> indicado por el cliente
                  @endif
                </p>
              </td>
            </tr>
          @endif

          <!-- CTA -->
          <tr>
            <td style="padding:24px;text-align:center">
              <a href="{{ $adminUrl }}" style="display:inline-block;padding:10px 22px;background:#0077cc;color:#fff;text-decoration:none;font-weight:700;font-size:13px;border-radius:8px">Ver en el panel admin</a>
              <p style="margin:12px 0 0;font-size:11px;color:#94a3b8">Busca por el código <strong>{{ $booking->booking_code }}</strong> en el listado.</p>
            </td>
          </tr>
        </table>
        <p style="margin:14px 0 0;font-size:11px;color:#94a3b8">Notificación automática · Inca Lake · reservas@incalake.com</p>
      </td>
    </tr>
  </table>
</body>
</html>
