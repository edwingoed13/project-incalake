with open('components/tour/BookingWidget.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# Cambio 1: p-4 a p-6
content = content.replace('p-4 border', 'p-6 border')

# Cambio 2: Precio en una línea
old_price = '''    <!-- Price -->
    <div class="mb-4">
      <div class="flex items-baseline space-x-2">
        <span class="text-3xl font-bold text-gray-900">${{ basePrice.toFixed(2) }}</span>
        <span class="text-sm text-gray-600">{{ currency }}</span>
        <span class="text-sm text-gray-500">per person</span>
      </div>
    </div>'''

new_price = '''    <!-- Price in single line -->
    <div class="mb-6">
      <p class="text-2xl font-bold text-gray-900">
        ${{ basePrice.toFixed(2) }} {{ currency }} <span class="text-lg font-normal text-gray-600">per person</span>
      </p>
    </div>'''

content = content.replace(old_price, new_price)

# Cambio 3: Eliminar botón "Agregar al carrito"
remove_cart = '''
    <button
      @click="addToCart"
      class="w-full bg-white text-cyan-500 border-2 border-cyan-500 font-bold py-3 rounded-lg transition hover:bg-cyan-50 mb-2 flex items-center justify-center"
    >
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
      </svg>
      Agregar al carrito
    </button>'''

content = content.replace(remove_cart, '')

# Cambio 4: Eliminar botones Consultar & Guardar
remove_buttons = '''
    <!-- Consult & Wishlist -->
    <div class="flex space-x-2 mt-3">
      <button
        @click="handleConsult"
        type="button"
        class="flex-1 border-2 border-gray-300 text-gray-700 font-semibold py-2 text-sm rounded-lg hover:border-cyan-500 hover:text-cyan-500 transition flex items-center justify-center"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
          <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
        </svg>
        Consultar
      </button>
      <button
        @click="handleSave"
        type="button"
        class="flex-1 border-2 border-gray-300 text-gray-700 font-semibold py-2 text-sm rounded-lg hover:border-cyan-500 hover:text-cyan-500 transition flex items-center justify-center"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
        </svg>
        Guardar
      </button>
    </div>'''

content = content.replace(remove_buttons, '')

# Cambio 5: Cambiar "Reservar ahora" a "Book Now"
content = content.replace('Reservar ahora', 'Book Now')

# Cambio 6: Cambiar labels a inglés en el template
content = content.replace('Fecha de inicio', 'Tour Date')
content = content.replace('Horario', 'Departure Time')
content = content.replace('Viajeros', 'Travelers')

# Cambio 7: Cambiar "adultos" a "adults" solo en el template (no en JS)
# Solo en las líneas del template
lines = content.split('\n')
new_lines = []
in_template = False
for line in lines:
    if '<template>' in line:
        in_template = True
    elif '</template>' in line:
        in_template = False
    
    if in_template and '{{ adults }}' in line:
        line = line.replace('adultos', 'adults')
    
    new_lines.append(line)

content = '\n'.join(new_lines)

with open('components/tour/BookingWidget.vue', 'w', encoding='utf-8') as f:
    f.write(content)

print("Archivo actualizado correctamente")
