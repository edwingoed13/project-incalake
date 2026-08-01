// Booking-anticipation window, shared by the tour detail page and the cart
// editor. A departure is bookable only when its moment (date + departure time,
// in the TOUR's timezone) is at least the anticipation ahead of "now" — a
// day-granularity min-date alone still offered today's already-departed tours.
// The backend re-validates this on booking creation; these helpers only keep
// the UI from offering slots the API will reject.

// Peru and Bolivia don't observe DST, so fixed offsets are safe.
const TZ_OFFSETS: Record<string, string> = {
  'America/Lima': '-05:00',
  'America/La_Paz': '-04:00',
}

function tourTz(detail: any): string {
  return detail?.timezone || detail?.city?.timezone || 'America/Lima'
}

function tzOffset(detail: any): string {
  return TZ_OFFSETS[tourTz(detail)] || '-05:00'
}

function normTime(raw: any): string {
  const [h = '0', m = '00'] = String(raw || '').split(':')
  return `${h.padStart(2, '0')}:${m.slice(0, 2).padEnd(2, '0')}`
}

export function anticipationMsFor(detail: any): number {
  // Admin Step 6 writes quantity+unit; the legacy hours column is only a
  // fallback for tours never re-saved by the new wizard.
  const qty = Number(detail?.booking_anticipation_quantity)
  const unit = detail?.booking_anticipation_unit
  if (Number.isFinite(qty) && qty > 0 && unit) {
    const perUnitMs =
      unit === 'minutes' ? 60_000 :
      unit === 'hours'   ? 3_600_000 :
      unit === 'days'    ? 86_400_000 :
      unit === 'weeks'   ? 604_800_000 :
      3_600_000
    return qty * perUnitMs
  }
  const hours = Number(detail?.booking_anticipation_hours)
  if (Number.isFinite(hours) && hours > 0) return hours * 3_600_000
  return 24 * 3_600_000 // sensible default: 24h
}

// Raw 'HH:MM' departure times; empty when the tour has none configured.
export function departureTimesFor(detail: any): string[] {
  const out: string[] = []
  const multi = detail?.departure_times
  if (Array.isArray(multi) && multi.length) {
    for (const it of multi) {
      if (!it) continue
      const t = typeof it === 'string' ? it : it.time
      if (t) out.push(normTime(t))
    }
  } else if (detail?.departure_time) {
    out.push(normTime(detail.departure_time))
  }
  return out
}

export function isDepartureBookable(detail: any, date: string, time?: string): boolean {
  if (!date) return false
  // No time yet (or none configured): judge by end of day so the date isn't
  // rejected while some later departure could still qualify.
  const at = time ? normTime(time) : '23:59'
  const dep = new Date(`${date}T${at}:00${tzOffset(detail)}`).getTime()
  return Number.isFinite(dep) && dep >= Date.now() + anticipationMsFor(detail)
}

// First calendar day with at least one bookable departure. With no configured
// times it degrades to day granularity (any moment of the day counts).
export function minBookableDateFor(detail: any): string {
  const times = departureTimesFor(detail)
  let todayStr: string
  try {
    todayStr = new Intl.DateTimeFormat('en-CA', { timeZone: tourTz(detail) }).format(new Date())
  } catch {
    todayStr = new Date().toISOString().split('T')[0] as string
  }
  const [y = 0, m = 1, d = 1] = todayStr.split('-').map(Number)
  for (let i = 0; i < 366; i++) {
    const dateStr = new Date(Date.UTC(y, m - 1, d + i)).toISOString().split('T')[0] as string
    const ok = times.length
      ? times.some(t => isDepartureBookable(detail, dateStr, t))
      : isDepartureBookable(detail, dateStr)
    if (ok) return dateStr
  }
  return todayStr
}
