// The booking-confirmation API endpoints are id-based but access-gated
// server-side: every request must carry the booking's confirmation token or
// the customer's email (both arrive in the page URL from the email link).
// This composable exposes that proof as a ready-to-append query string so all
// confirmation calls (travelers, pickup, full-details, notify) authorize.
export function useBookingAccess() {
  const route = useRoute()
  const token = (route.query.token as string) || ''
  const email = (route.query.email as string) || ''

  const accessQs = token
    ? `?token=${encodeURIComponent(token)}`
    : email
      ? `?email=${encodeURIComponent(email)}`
      : ''

  return { accessQs, token, email }
}
