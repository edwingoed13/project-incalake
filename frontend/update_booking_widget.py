with open('components/tour/BookingWidget.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# Actualizar imports y agregar cartStore
old_script_start = '''<script setup lang="ts">
interface Props {
  tour: any
}

const props = defineProps<Props>()
const router = useRouter()
const config = useRuntimeConfig()'''

new_script_start = '''<script setup lang="ts">
import { useCartStore } from '~/stores/cart'
import { getImageUrl } from '~/utils/formatters'

interface Props {
  tour: any
}

const props = defineProps<Props>()
const router = useRouter()
const config = useRuntimeConfig()
const cartStore = useCartStore()'''

content = content.replace(old_script_start, new_script_start)

# Actualizar función handleBooking
old_handle_booking = '''function handleBooking() {
  if (!selectedDate.value) {
    alert('Por favor selecciona una fecha')
    return
  }

  if (!selectedTime.value) {
    alert('Por favor selecciona un horario')
    return
  }

  // TODO: Navigate to checkout
  console.log('Booking:', { selectedDate: selectedDate.value, selectedTime: selectedTime.value, adults: adults.value })
}'''

new_handle_booking = '''function handleBooking() {
  if (!selectedDate.value) {
    alert('Please select a date')
    return
  }

  if (!selectedTime.value) {
    alert('Please select a time')
    return
  }

  // Get tour image
  const tourImage = props.tour.media_gallery && props.tour.media_gallery.length > 0
    ? getImageUrl(props.tour.media_gallery[0].url)
    : ''

  // Add to cart
  cartStore.addItem({
    tourId: props.tour.id,
    tourTitle: props.tour.title,
    tourSlug: props.tour.slug,
    tourImage,
    selectedDate: selectedDate.value,
    selectedTime: selectedTime.value,
    timezone: props.tour.timezone || 'America/Lima',
    adults: adults.value,
    children: 0,
    basePrice: basePrice.value,
    childPrice: 0,
    total: total.value,
    currency: currency.value,
    policies: props.tour.policies || '',
    cancellationPolicy: props.tour.cancellation_policy || '',
    taxPercentage: props.tour.tax_percentage || 0,
    advancePaymentPercentage: props.tour.advance_payment_percentage || 100
  })

  // Navigate directly to checkout
  router.push('/checkout')
}'''

content = content.replace(old_handle_booking, new_handle_booking)

# Eliminar función addToCart ya que no se usa
remove_add_to_cart = '''
function addToCart() {
  if (!selectedDate.value) {
    alert('Por favor selecciona una fecha')
    return
  }

  if (!selectedTime.value) {
    alert('Por favor selecciona un horario')
    return
  }

  // TODO: Add to cart store
  console.log('Add to cart:', { selectedDate: selectedDate.value, selectedTime: selectedTime.value, adults: adults.value })
}

function handleConsult() {
  console.log('Consult')
}

function handleSave() {
  console.log('Save to wishlist')
}'''

new_section = ''

content = content.replace(remove_add_to_cart, new_section)

with open('components/tour/BookingWidget.vue', 'w', encoding='utf-8') as f:
    f.write(content)

print("BookingWidget actualizado con funcionalidad de carrito")
