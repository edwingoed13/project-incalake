// Maps a Material Symbols (font ligature) name to its Iconify @nuxt/icon name.
// We migrated off the Material Symbols web font (3.8 MB) to inline SVG via
// @nuxt/icon. Static usages were converted directly to <Icon name="material-symbols:…"/>;
// this helper covers the DATA-driven ones (e.g. `filter.icon`, social links,
// EmptyState/AppPopover props) so callers can keep passing semantic names.
//
// Outline-preferred to match the old "Material Symbols Outlined" (FILL 0) look.
const NAME_MAP: Record<string, string> = {
  account_balance_wallet: 'account-balance-wallet-outline', add: 'add', arrow_back: 'arrow-back',
  arrow_forward: 'arrow-forward', block: 'block-outline', bolt: 'bolt-outline',
  calendar_today: 'calendar-today-outline', cancel: 'cancel-outline', celebration: 'celebration-outline',
  chat: 'chat-outline', check: 'check', check_circle: 'check-circle-outline', chevron_left: 'chevron-left',
  chevron_right: 'chevron-right', close: 'close', confirmation_number: 'confirmation-number-outline',
  content_copy: 'content-copy-outline', credit_card: 'credit-card-outline', delete: 'delete-outline',
  description: 'description-outline', directions_bus: 'directions-bus-outline', done: 'done',
  download: 'download', edit: 'edit-outline', error: 'error-outline', expand_more: 'expand-more',
  explore: 'explore-outline', favorite: 'favorite-outline', grid_view: 'grid-view-outline',
  group: 'group-outline', home: 'home-outline', hotel: 'hotel-outline', hourglass_empty: 'hourglass-empty',
  image: 'image-outline', inbox: 'inbox-outline', info: 'info-outline', label: 'label-outline',
  local_offer: 'sell-outline', location_on: 'location-on-outline', lock: 'lock-outline', menu: 'menu',
  payment: 'credit-card-outline', payments: 'payments-outline', person: 'person-outline',
  photo_camera: 'photo-camera-outline', photo_library: 'photo-library', play_arrow: 'play-arrow-outline',
  policy: 'policy-outline', progress_activity: 'progress-activity', public: 'public',
  receipt_long: 'receipt-long-outline', record_voice_over: 'record-voice-over-outline', refresh: 'refresh',
  remove: 'remove', report: 'report-outline', schedule: 'schedule-outline', search: 'search',
  search_off: 'search-off', security: 'security', sell: 'sell-outline', share: 'share-outline',
  shield: 'shield-outline', shopping_cart: 'shopping-cart-outline', sort: 'sort', star: 'star-outline',
  tour: 'tour-outline', trending_flat: 'trending-flat', tune: 'tune', verified: 'verified-outline',
  verified_user: 'verified-user-outline', view_list: 'view-list-outline', warning: 'warning-outline',
  wifi_off: 'wifi-off',
}

export function msIcon(name?: string | null): string {
  if (!name) return 'material-symbols:help-outline'
  // Already an Iconify name (e.g. "material-symbols:…" or "i-lucide-…") — pass through.
  if (name.includes(':') || name.startsWith('i-')) return name
  const mapped = NAME_MAP[name] || name.replace(/_/g, '-')
  return `material-symbols:${mapped}`
}
