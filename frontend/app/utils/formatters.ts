/**
 * Format a number as a currency value
 * @param value - The numeric value to format
 * @param currency - The currency code (USD, PEN, etc.)
 * @returns Formatted currency string
 */
export function formatPrice(value: number | null | undefined, currency: string = 'USD'): string {
  if (value === null || value === undefined || isNaN(value)) {
    return '-'
  }

  const currencySymbols: Record<string, string> = {
    USD: '$',
    PEN: 'S/.',
    EUR: '€',
    GBP: '£'
  }

  const symbol = currencySymbols[currency] || currency
  const formatted = Number(value).toFixed(2)

  return `${symbol}${formatted}`
}

/**
 * Format a date string to a readable format
 * @param dateString - ISO date string
 * @param locale - Locale for formatting (default: es-PE)
 * @returns Formatted date string
 */
export function formatDate(dateString: string, locale: string = 'es-PE'): string {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat(locale, {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  }).format(date)
}

/**
 * Format a time string to 12-hour format
 * @param timeString - Time string in HH:MM format
 * @returns Formatted time string
 */
export function formatTime(timeString: string): string {
  const [hours, minutes] = timeString.split(':')
  const hour = parseInt(hours)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const hour12 = hour % 12 || 12
  return `${hour12}:${minutes} ${ampm}`
}

/**
 * Get the full URL for an image
 * @param path - Relative path or full URL
 * @returns Full image URL
 */
export function getImageUrl(path: string): string {
  if (!path) return ''

  // If already a full URL, return as is
  if (path.startsWith('http://') || path.startsWith('https://')) {
    return path
  }

  const baseUrl = 'http://127.0.0.1:8000/storage'

  // If starts with /storage/, use base URL without /storage
  if (path.startsWith('/storage/')) {
    return `${baseUrl.replace('/storage', '')}${path}`
  }

  // If starts with /, use base URL parent
  if (path.startsWith('/')) {
    return `${baseUrl.replace('/storage', '')}${path}`
  }

  // Otherwise, prepend storage path
  return `${baseUrl}/${path}`
}

/**
 * Format currency (alias for formatPrice)
 * @param amount - The numeric value to format
 * @param currency - The currency code (USD, PEN, etc.)
 * @returns Formatted currency string
 */
export function formatCurrency(amount: number | null | undefined, currency: string = 'USD'): string {
  return formatPrice(amount, currency)
}

/**
 * Format datetime
 * @param date - Date string or Date object
 * @returns Formatted datetime string
 */
export function formatDateTime(date: string | Date): string {
  if (!date) return ''
  const d = new Date(date)
  return new Intl.DateTimeFormat('es-PE', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  }).format(d)
}

/**
 * Get relative time (hace 2 horas, hace 3 días, etc)
 * @param date - Date string or Date object
 * @returns Relative time string
 */
export function formatRelativeTime(date: string | Date): string {
  if (!date) return ''
  const d = new Date(date)
  const now = new Date()
  const diffMs = now.getTime() - d.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 60) {
    return `hace ${diffMins} minuto${diffMins !== 1 ? 's' : ''}`
  } else if (diffHours < 24) {
    return `hace ${diffHours} hora${diffHours !== 1 ? 's' : ''}`
  } else {
    return `hace ${diffDays} día${diffDays !== 1 ? 's' : ''}`
  }
}

/**
 * Format duration to human readable format
 * @param daysOrMinutes - Duration in days (if hours param is provided) or minutes (legacy)
 * @param hours - Duration in hours (optional)
 * @returns Formatted duration string
 */
export function formatDuration(daysOrMinutes: number, hours?: number): string {
  // New API: formatDuration(days, hours)
  if (hours !== undefined) {
    const days = daysOrMinutes
    const parts = []

    if (days > 0) {
      parts.push(`${days} día${days !== 1 ? 's' : ''}`)
    }

    if (hours > 0) {
      parts.push(`${hours} hora${hours !== 1 ? 's' : ''}`)
    }

    return parts.length > 0 ? parts.join(' ') : '0 horas'
  }

  // Legacy API: formatDuration(minutes)
  const minutes = daysOrMinutes
  if (!minutes) return '0 horas'

  const hrs = Math.floor(minutes / 60)
  const mins = minutes % 60

  if (hrs === 0) {
    return `${mins} min`
  } else if (mins === 0) {
    return `${hrs} hora${hrs !== 1 ? 's' : ''}`
  } else {
    return `${hrs} hora${hrs !== 1 ? 's' : ''} ${mins} min`
  }
}
