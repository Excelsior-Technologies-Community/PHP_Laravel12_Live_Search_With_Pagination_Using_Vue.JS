<script setup>
import { ref, watch, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const props = defineProps({
  products: Object,
  filters: Object
})

const search = ref(props.filters.search || '')
let timer = null
const searchLoading = ref(false)
const showDeleteModal = ref(false)
const deleteId = ref(null)
const toasts = ref([])
const suggestions = ref([])
const showSuggestions = ref(false)
const suggestionLoading = ref(false)
const lastFlashMessage = ref(null)

const showToast = (message) => {
  const id = Date.now()
  toasts.value.push({ id, message })
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }, 3000)
}

const flashMessage = computed(() => {
  try {
    return page.props?.flash?.success || null
  } catch (e) {
    return null
  }
})

watch(flashMessage, (newVal) => {
  if (newVal && newVal !== lastFlashMessage.value) {
    lastFlashMessage.value = newVal
    showToast(newVal)
  }
})

onMounted(async () => {
  await nextTick()
  if (flashMessage.value && !lastFlashMessage.value) {
    lastFlashMessage.value = flashMessage.value
    showToast(flashMessage.value)
  }
})

const closeSuggestions = (e) => {
  if (!e.target.closest('.search-container')) {
    showSuggestions.value = false
  }
}
onMounted(() => document.addEventListener('click', closeSuggestions))
onUnmounted(() => document.removeEventListener('click', closeSuggestions))

watch(search, (value) => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    searchLoading.value = true
    router.get('/products', { search: value }, {
      preserveState: true,
      onSuccess: () => { searchLoading.value = false }
    })
  }, 400)
})

watch(search, async (value) => {
  if (value.length > 0) {
    suggestionLoading.value = true
    try {
      const response = await fetch(`/products/suggestions?q=${encodeURIComponent(value)}`)
      const data = await response.json()
      suggestions.value = data
      showSuggestions.value = true
    } catch (e) {
      suggestions.value = []
    }
    suggestionLoading.value = false
  } else {
    suggestions.value = []
    showSuggestions.value = false
  }
})

const selectSuggestion = (name) => {
  search.value = name
  showSuggestions.value = false
}

const deleteProduct = (id) => {
  deleteId.value = id
  showDeleteModal.value = true
}

const confirmDelete = () => {
  router.delete(`/products/${deleteId.value}`, {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false
      deleteId.value = null
    }
  })
}

const cancelDelete = () => {
  showDeleteModal.value = false
  deleteId.value = null
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 p-8">

    <!-- TOASTS -->
    <div class="fixed top-4 right-4 z-[9999] space-y-2">
      <transition-group name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="px-4 py-3 rounded shadow-lg text-white bg-green-600 min-w-[200px]"
        >
          {{ toast.message }}
        </div>
      </transition-group>
    </div>

    <div class="max-w-6xl mx-auto bg-white rounded shadow p-6">

      <!-- HEADER -->
      <div class="flex justify-between items-center mb-6">
        <div class="flex items-center gap-2">
          <span class="text-xl">📦</span>
          <h1 class="text-xl font-semibold">Products</h1>
        </div>

        <Link
          href="/products/create"
          class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
        >
          + Create New Product
        </Link>
      </div>

      <!-- SUCCESS BANNER -->
      <div
        v-if="page.props?.flash?.success"
        class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded"
      >
        {{ page.props.flash.success }}
      </div>

      <!-- SEARCH -->
      <div class="search-container relative mb-4">
        <div class="relative">
          <input
            v-model="search"
            placeholder="Search by name, detail or price..."
            class="border p-2 w-full rounded pr-10"
          />
          <div v-if="searchLoading" class="absolute right-2 top-2">
            <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
        </div>

        <!-- SUGGESTIONS DROPDOWN -->
        <div
          v-if="showSuggestions && suggestions.length > 0"
          class="absolute z-10 w-full bg-white border rounded shadow mt-1"
        >
          <div
            v-for="(suggestion, index) in suggestions"
            :key="index"
            @mousedown.prevent="selectSuggestion(suggestion)"
            class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm"
          >
            {{ suggestion }}
          </div>
        </div>
      </div>

      <!-- TABLE -->
      <div class="overflow-x-auto">
        <table class="w-full border-collapse">
          <thead>
            <tr class="border-b bg-gray-50 text-left">
              <th class="p-3">Name</th>
              <th class="p-3">Detail</th>
              <th class="p-3">Price</th>
              <th class="p-3">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="p in products.data"
              :key="p.id"
              class="border-b hover:bg-gray-50"
            >
              <!-- NAME -->
              <td class="p-3 font-medium">
                {{ p.name }}
              </td>

              <!-- DETAIL -->
              <td class="p-3 text-gray-600">
                {{ p.detail ?? '-' }}
              </td>

              <!-- PRICE -->
              <td class="p-3 text-green-600 font-semibold">
                ₹ {{ Number(p.price).toLocaleString() }}
              </td>

              <!-- ACTIONS -->
              <td class="p-3">
                <Link
                  :href="`/products/${p.id}/edit`"
                  class="text-blue-600 mr-3"
                >
                  Edit
                </Link>

                <button
                  @click="deleteProduct(p.id)"
                  class="text-red-600"
                >
                  Delete
                </button>
              </td>
            </tr>

            <!-- EMPTY STATE -->
            <tr v-if="products.data.length === 0">
              <td colspan="4" class="p-6 text-center text-gray-500">
                No products found
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- PAGINATION -->
      <div
        v-if="products.links.length > 1"
        class="flex justify-center gap-2 mt-6"
      >
        <button
          v-for="link in products.links"
          :key="link.label"
          v-html="link.label"
          :disabled="!link.url"
          @click="router.get(link.url, {}, { preserveState: true })"
          class="px-3 py-1 border rounded"
          :class="{
            'bg-blue-600 text-white': link.active,
            'text-gray-400 cursor-not-allowed': !link.url
          }"
        />
      </div>

    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div
      v-if="showDeleteModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Delete Product</h3>
        <p class="text-gray-600 mb-6">Are you sure you want to delete this product? This action cannot be undone.</p>
        <div class="flex justify-end gap-3">
          <button
            @click="cancelDelete"
            class="px-4 py-2 border rounded hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            @click="confirmDelete"
            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
          >
            Delete
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>
