// Single source of truth for booking-form validation, shared by the mobile
// inline panel and the desktop sticky widget so both give identical, localized,
// inline feedback (no more browser alert() or hardcoded English strings).
export function useBookingValidation() {
  const { t } = useI18n()
  const error = ref('')

  /** Validate date + time. Returns true when OK; sets `error` (i18n) otherwise. */
  function validate(date: string, time: string): boolean {
    if (!date) { error.value = t('booking_err_date'); return false }
    if (!time) { error.value = t('booking_err_time'); return false }
    error.value = ''
    return true
  }

  function clear() { error.value = '' }

  return { error, validate, clear }
}
