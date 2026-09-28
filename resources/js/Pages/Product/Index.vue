<script setup>
import {
  ref,
  watch,
  computed,
  onMounted,
  onUnmounted,
  nextTick
} from 'vue'

import {
  router,
  Link,
  usePage
} from '@inertiajs/vue3'

const page = usePage()

const props = defineProps({
  products: Object,
  filters: Object,
  statistics: Object
})

/*
|--------------------------------------------------------------------------
| Search & Filters
|--------------------------------------------------------------------------
*/

const search = ref(props.filters.search || '')
const minPrice = ref(props.filters.min_price || '')
const maxPrice = ref(props.filters.max_price || '')
const dateFrom = ref(props.filters.date_from || '')
const dateTo = ref(props.filters.date_to || '')

const sort = ref(
  props.filters.sort || 'newest'
)

const perPage = ref(
  Number(props.filters.per_page || 5)
)

/*
|--------------------------------------------------------------------------
| Loading
|--------------------------------------------------------------------------
*/

const searchLoading = ref(false)

/*
|--------------------------------------------------------------------------
| Delete Modal
|--------------------------------------------------------------------------
*/

const showDeleteModal = ref(false)
const deleteId = ref(null)

/*
|--------------------------------------------------------------------------
| Toasts
|--------------------------------------------------------------------------
*/

const toasts = ref([])
const lastFlashMessage = ref(null)

/*
|--------------------------------------------------------------------------
| Suggestions
|--------------------------------------------------------------------------
*/

const suggestions = ref([])
const showSuggestions = ref(false)
const suggestionLoading = ref(false)

/*
|--------------------------------------------------------------------------
| CSV Export
|--------------------------------------------------------------------------
*/

const exportLoading = ref(false)

/*
|--------------------------------------------------------------------------
| Search Timer
|--------------------------------------------------------------------------
*/

let searchTimer = null
let suggestionTimer = null

/*
|--------------------------------------------------------------------------
| Toast
|--------------------------------------------------------------------------
*/

const showToast = (message) => {
  const id = Date.now()

  toasts.value.push({
    id,
    message
  })

  setTimeout(() => {
    toasts.value = toasts.value.filter(
      toast => toast.id !== id
    )
  }, 3000)
}

/*
|--------------------------------------------------------------------------
| Flash Message
|--------------------------------------------------------------------------
*/

const flashMessage = computed(() => {
  try {
    return page.props?.flash?.success || null
  } catch (e) {
    return null
  }
})

watch(
  flashMessage,
  (newValue) => {
    if (
      newValue &&
      newValue !== lastFlashMessage.value
    ) {
      lastFlashMessage.value = newValue
      showToast(newValue)
    }
  }
)

onMounted(async () => {
  await nextTick()

  if (
    flashMessage.value &&
    !lastFlashMessage.value
  ) {
    lastFlashMessage.value =
      flashMessage.value

    showToast(flashMessage.value)
  }
})

/*
|--------------------------------------------------------------------------
| Close Suggestions
|--------------------------------------------------------------------------
*/

const closeSuggestions = (event) => {
  if (
    !event.target.closest('.search-container')
  ) {
    showSuggestions.value = false
  }
}

onMounted(() => {
  document.addEventListener(
    'click',
    closeSuggestions
  )
})

onUnmounted(() => {
  document.removeEventListener(
    'click',
    closeSuggestions
  )
})

/*
|--------------------------------------------------------------------------
| Current Filter Parameters
|--------------------------------------------------------------------------
*/

const getFilterParams = () => {
  return {
    search: search.value || undefined,

    min_price:
      minPrice.value !== ''
        ? minPrice.value
        : undefined,

    max_price:
      maxPrice.value !== ''
        ? maxPrice.value
        : undefined,

    date_from:
      dateFrom.value || undefined,

    date_to:
      dateTo.value || undefined,

    sort: sort.value,

    per_page: perPage.value
  }
}

/*
|--------------------------------------------------------------------------
| Export Filtered Products to CSV
|--------------------------------------------------------------------------
*/

const exportCsv = () => {
  exportLoading.value = true

  const params = new URLSearchParams()

  /*
  |----------------------------------------------------------------------
  | Search
  |----------------------------------------------------------------------
  */

  if (search.value) {
    params.append(
      'search',
      search.value
    )
  }

  /*
  |----------------------------------------------------------------------
  | Minimum Price
  |----------------------------------------------------------------------
  */

  if (minPrice.value !== '') {
    params.append(
      'min_price',
      minPrice.value
    )
  }

  /*
  |----------------------------------------------------------------------
  | Maximum Price
  |----------------------------------------------------------------------
  */

  if (maxPrice.value !== '') {
    params.append(
      'max_price',
      maxPrice.value
    )
  }

  /*
  |----------------------------------------------------------------------
  | Date From
  |----------------------------------------------------------------------
  */

  if (dateFrom.value) {
    params.append(
      'date_from',
      dateFrom.value
    )
  }

  /*
  |----------------------------------------------------------------------
  | Date To
  |----------------------------------------------------------------------
  */

  if (dateTo.value) {
    params.append(
      'date_to',
      dateTo.value
    )
  }

  /*
  |----------------------------------------------------------------------
  | Sorting
  |----------------------------------------------------------------------
  */

  if (sort.value) {
    params.append(
      'sort',
      sort.value
    )
  }

  /*
  |----------------------------------------------------------------------
  | Create Download URL
  |----------------------------------------------------------------------
  */

  const queryString = params.toString()

  const url = queryString
    ? `/products/export/csv?${queryString}`
    : '/products/export/csv'

  /*
  |----------------------------------------------------------------------
  | Start CSV Download
  |----------------------------------------------------------------------
  */

  window.location.href = url

  /*
  |----------------------------------------------------------------------
  | Reset Loading State
  |----------------------------------------------------------------------
  */

  setTimeout(() => {
    exportLoading.value = false
  }, 1000)
}

/*
|--------------------------------------------------------------------------
| Load Products
|--------------------------------------------------------------------------
*/

const loadProducts = (
  options = {}
) => {
  searchLoading.value = true

  router.get(
    '/products',
    getFilterParams(),
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,

      ...options,

      onFinish: () => {
        searchLoading.value = false

        if (options.onFinish) {
          options.onFinish()
        }
      }
    }
  )
}

/*
|--------------------------------------------------------------------------
| Live Search
|--------------------------------------------------------------------------
*/

watch(
  search,
  (value) => {
    clearTimeout(searchTimer)

    searchTimer = setTimeout(() => {
      loadProducts()
    }, 400)
  }
)

/*
|--------------------------------------------------------------------------
| Search Suggestions
|--------------------------------------------------------------------------
*/

watch(
  search,
  (value) => {
    clearTimeout(suggestionTimer)

    if (!value || value.length === 0) {
      suggestions.value = []
      showSuggestions.value = false
      suggestionLoading.value = false

      return
    }

    suggestionTimer = setTimeout(
      async () => {
        suggestionLoading.value = true

        try {
          const response = await fetch(
            `/products/suggestions?q=${encodeURIComponent(value)}`
          )

          const data =
            await response.json()

          suggestions.value = data

          showSuggestions.value =
            data.length > 0
        } catch (error) {
          suggestions.value = []
          showSuggestions.value = false
        }

        suggestionLoading.value = false
      },
      250
    )
  }
)

/*
|--------------------------------------------------------------------------
| Select Suggestion
|--------------------------------------------------------------------------
*/

const selectSuggestion = (name) => {
  search.value = name
  showSuggestions.value = false
}

/*
|--------------------------------------------------------------------------
| Apply Advanced Filters
|--------------------------------------------------------------------------
*/

const applyFilters = () => {
  loadProducts()
}

/*
|--------------------------------------------------------------------------
| Clear Filters
|--------------------------------------------------------------------------
*/

const clearFilters = () => {
  search.value = ''
  minPrice.value = ''
  maxPrice.value = ''
  dateFrom.value = ''
  dateTo.value = ''
  sort.value = 'newest'
  perPage.value = 5

  loadProducts()
}

/*
|--------------------------------------------------------------------------
| Sorting
|--------------------------------------------------------------------------
*/

const changeSort = () => {
  loadProducts()
}

/*
|--------------------------------------------------------------------------
| Page Size
|--------------------------------------------------------------------------
*/

const changePageSize = () => {
  loadProducts()
}

/*
|--------------------------------------------------------------------------
| Delete Product
|--------------------------------------------------------------------------
*/

const deleteProduct = (id) => {
  deleteId.value = id
  showDeleteModal.value = true
}

const confirmDelete = () => {
  if (!deleteId.value) {
    return
  }

  router.delete(
    `/products/${deleteId.value}`,
    {
      preserveScroll: true,

      onSuccess: () => {
        showDeleteModal.value = false
        deleteId.value = null
      }
    }
  )
}

const cancelDelete = () => {
  showDeleteModal.value = false
  deleteId.value = null
}

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

const goToPage = (url) => {
  if (!url) {
    return
  }

  router.get(
    url,
    {},
    {
      preserveState: true,
      preserveScroll: true
    }
  )
}

/*
|--------------------------------------------------------------------------
| Currency Formatting
|--------------------------------------------------------------------------
*/

const formatPrice = (price) => {
  if (
    price === null ||
    price === undefined ||
    price === ''
  ) {
    return '-'
  }

  return Number(price).toLocaleString(
    'en-IN',
    {
      maximumFractionDigits: 2
    }
  )
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 p-8">

    <!-- ===================================================== -->
    <!-- TOASTS -->
    <!-- ===================================================== -->

    <div
      class="fixed top-4 right-4 z-[9999] space-y-2"
    >
      <transition-group name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="px-4 py-3 rounded shadow-lg text-white bg-green-600 min-w-[220px]"
        >
          {{ toast.message }}
        </div>
      </transition-group>
    </div>


    <div
      class="max-w-7xl mx-auto bg-white rounded shadow p-6"
    >

      <!-- ===================================================== -->
      <!-- HEADER -->
      <!-- ===================================================== -->

      <div
        class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6"
      >

        <div class="flex items-center gap-2">
          <span class="text-xl">📦</span>

          <div>
            <h1
              class="text-xl font-semibold"
            >
              Products
            </h1>

            <p
              class="text-sm text-gray-500"
            >
              Live Search & Pagination
            </p>
          </div>
        </div>


<div class="flex flex-wrap gap-2">

    <!-- CSV EXPORT -->

    <button
        type="button"
        @click="exportCsv"
        :disabled="exportLoading"
        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
    >
        <span v-if="!exportLoading">
            📥 Export CSV
        </span>

        <span v-else>
            ⏳ Exporting...
        </span>
    </button>


    <!-- STATISTICS -->

    <Link
        href="/products/statistics"
        class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700"
    >
        📊 Statistics
    </Link>


    <!-- CREATE -->

    <Link
        href="/products/create"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
    >
        + Create New Product
    </Link>

</div>

      </div>


      <!-- ===================================================== -->
      <!-- STATISTICS CARDS -->
      <!-- ===================================================== -->

      <div
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6"
      >

        <!-- Total -->

        <div
          class="bg-blue-50 border border-blue-200 rounded-lg p-4"
        >
          <p
            class="text-sm text-blue-600"
          >
            Total Products
          </p>

          <p
            class="text-2xl font-bold text-blue-800"
          >
            {{ statistics.total_products }}
          </p>
        </div>


        <!-- Filtered -->

        <div
          class="bg-green-50 border border-green-200 rounded-lg p-4"
        >
          <p
            class="text-sm text-green-600"
          >
            Filtered Results
          </p>

          <p
            class="text-2xl font-bold text-green-800"
          >
            {{ statistics.filtered_products }}
          </p>
        </div>


        <!-- Average -->

        <div
          class="bg-yellow-50 border border-yellow-200 rounded-lg p-4"
        >
          <p
            class="text-sm text-yellow-600"
          >
            Average Price
          </p>

          <p
            class="text-2xl font-bold text-yellow-800"
          >
            ₹{{ formatPrice(statistics.average_price) }}
          </p>
        </div>


        <!-- Lowest -->

        <div
          class="bg-indigo-50 border border-indigo-200 rounded-lg p-4"
        >
          <p
            class="text-sm text-indigo-600"
          >
            Lowest Price
          </p>

          <p
            class="text-2xl font-bold text-indigo-800"
          >
            ₹{{ formatPrice(statistics.lowest_price) }}
          </p>
        </div>


        <!-- Highest -->

        <div
          class="bg-red-50 border border-red-200 rounded-lg p-4"
        >
          <p
            class="text-sm text-red-600"
          >
            Highest Price
          </p>

          <p
            class="text-2xl font-bold text-red-800"
          >
            ₹{{ formatPrice(statistics.highest_price) }}
          </p>
        </div>

      </div>


      <!-- ===================================================== -->
      <!-- SEARCH -->
      <!-- ===================================================== -->

      <div
        class="search-container relative mb-4"
      >

        <label
          class="block text-sm font-medium text-gray-700 mb-1"
        >
          Live Product Search
        </label>

        <div class="relative">

          <input
            v-model="search"
            placeholder="Search by name, detail or price..."
            class="border p-3 w-full rounded-lg pr-12 focus:ring-2 focus:ring-blue-500 focus:outline-none"
          />

          <!-- Search Loading -->

          <div
            v-if="searchLoading"
            class="absolute right-3 top-3"
          >
            <svg
              class="animate-spin h-5 w-5 text-gray-400"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />

              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              />
            </svg>
          </div>

        </div>


        <!-- ===================================================== -->
        <!-- SEARCH SUGGESTIONS -->
        <!-- ===================================================== -->

        <div
          v-if="
            showSuggestions &&
            suggestions.length > 0
          "
          class="absolute z-10 w-full bg-white border rounded-lg shadow mt-1"
        >

          <div
            v-for="(
              suggestion,
              index
            ) in suggestions"
            :key="index"
            @mousedown.prevent="
              selectSuggestion(suggestion)
            "
            class="px-4 py-3 hover:bg-gray-100 cursor-pointer text-sm"
          >
            🔎 {{ suggestion }}
          </div>

        </div>

      </div>


      <!-- ===================================================== -->
      <!-- ADVANCED FILTERS -->
      <!-- ===================================================== -->

      <div
        class="bg-gray-50 border rounded-lg p-5 mb-6"
      >

        <div
          class="flex justify-between items-center mb-4"
        >

          <h2
            class="font-semibold text-gray-800"
          >
            🔎 Advanced Filters
          </h2>

          <button
            type="button"
            @click="clearFilters"
            class="text-sm text-red-600 hover:underline"
          >
            Clear All
          </button>

        </div>


        <div
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"
        >

          <!-- Minimum Price -->

          <div>
            <label
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Minimum Price
            </label>

            <input
              v-model="minPrice"
              type="number"
              min="0"
              placeholder="₹ Minimum"
              class="w-full border rounded-lg px-3 py-2"
            />
          </div>


          <!-- Maximum Price -->

          <div>
            <label
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Maximum Price
            </label>

            <input
              v-model="maxPrice"
              type="number"
              min="0"
              placeholder="₹ Maximum"
              class="w-full border rounded-lg px-3 py-2"
            />
          </div>


          <!-- Date From -->

          <div>
            <label
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Created From
            </label>

            <input
              v-model="dateFrom"
              type="date"
              class="w-full border rounded-lg px-3 py-2"
            />
          </div>


          <!-- Date To -->

          <div>
            <label
              class="block text-sm font-medium text-gray-700 mb-1"
            >
              Created To
            </label>

            <input
              v-model="dateTo"
              type="date"
              class="w-full border rounded-lg px-3 py-2"
            />
          </div>

        </div>


        <div class="mt-4">

          <button
            type="button"
            @click="applyFilters"
            class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700"
          >
            Apply Filters
          </button>

        </div>

      </div>


      <!-- ===================================================== -->
      <!-- SORTING + PAGE SIZE -->
      <!-- ===================================================== -->

      <div
        class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-5"
      >

        <div class="flex items-center gap-2">

          <label
            class="text-sm font-medium text-gray-700"
          >
            Sort By:
          </label>

          <select
            v-model="sort"
            @change="changeSort"
            class="border rounded-lg px-3 py-2"
          >

            <option value="newest">
              Newest First
            </option>

            <option value="oldest">
              Oldest First
            </option>

            <option value="name_asc">
              Name A-Z
            </option>

            <option value="name_desc">
              Name Z-A
            </option>

            <option value="price_low">
              Price Low to High
            </option>

            <option value="price_high">
              Price High to Low
            </option>

          </select>

        </div>


        <div class="flex items-center gap-2">

          <label
            class="text-sm font-medium text-gray-700"
          >
            Show:
          </label>

          <select
            v-model="perPage"
            @change="changePageSize"
            class="border rounded-lg px-3 py-2"
          >

            <option :value="5">
              5
            </option>

            <option :value="10">
              10
            </option>

            <option :value="20">
              20
            </option>

            <option :value="50">
              50
            </option>

          </select>

          <span
            class="text-sm text-gray-500"
          >
            products per page
          </span>

        </div>

      </div>


      <!-- ===================================================== -->
      <!-- RESULT INFORMATION -->
      <!-- ===================================================== -->

      <div
        class="mb-4 text-sm text-gray-600"
      >

        Showing
        <span class="font-semibold">
          {{ products.from || 0 }}
        </span>

        -
        <span class="font-semibold">
          {{ products.to || 0 }}
        </span>

        of

        <span class="font-semibold">
          {{ products.total }}
        </span>

        products

      </div>


      <!-- ===================================================== -->
      <!-- TABLE -->
      <!-- ===================================================== -->

      <div class="overflow-x-auto">

        <table
          class="w-full border-collapse"
        >

          <thead>

            <tr
              class="border-b bg-gray-50 text-left"
            >

              <th class="p-3">
                Name
              </th>

              <th class="p-3">
                Detail
              </th>

              <th class="p-3">
                Price
              </th>

              <th class="p-3">
                Created
              </th>

              <th class="p-3">
                Actions
              </th>

            </tr>

          </thead>


          <tbody>

            <tr
              v-for="product in products.data"
              :key="product.id"
              class="border-b hover:bg-gray-50"
            >

              <!-- Name -->

              <td
                class="p-3 font-medium"
              >
                {{ product.name }}
              </td>


              <!-- Detail -->

              <td
                class="p-3 text-gray-600"
              >
                {{ product.detail ?? '-' }}
              </td>


              <!-- Price -->

              <td
                class="p-3 text-green-600 font-semibold"
              >
                {{
                  product.price !== null
                    ? `₹ ${formatPrice(product.price)}`
                    : '-'
                }}
              </td>


              <!-- Created -->

              <td
                class="p-3 text-gray-500 text-sm"
              >
                {{
                  new Date(
                    product.created_at
                  ).toLocaleDateString(
                    'en-IN'
                  )
                }}
              </td>


              <!-- Actions -->

              <td class="p-3">

                <Link
                  :href="`/products/${product.id}/edit`"
                  class="text-blue-600 mr-3 hover:underline"
                >
                  Edit
                </Link>

                <button
                  @click="
                    deleteProduct(product.id)
                  "
                  class="text-red-600 hover:underline"
                >
                  Delete
                </button>

              </td>

            </tr>


            <!-- Empty -->

            <tr
              v-if="products.data.length === 0"
            >

              <td
                colspan="5"
                class="p-8 text-center text-gray-500"
              >

                <div class="text-4xl mb-2">
                  🔍
                </div>

                <p
                  class="font-medium"
                >
                  No products found
                </p>

                <p
                  class="text-sm mt-1"
                >
                  Try changing your search or filters.
                </p>

              </td>

            </tr>

          </tbody>

        </table>

      </div>


      <!-- ===================================================== -->
      <!-- PAGINATION -->
      <!-- ===================================================== -->

      <div
        v-if="products.links.length > 1"
        class="flex flex-wrap justify-center gap-2 mt-6"
      >

        <button
          v-for="link in products.links"
          :key="link.label"
          v-html="link.label"
          :disabled="!link.url"
          @click="goToPage(link.url)"
          class="px-3 py-1 border rounded"
          :class="{
            'bg-blue-600 text-white':
              link.active,

            'text-gray-400 cursor-not-allowed':
              !link.url,

            'hover:bg-gray-100':
              link.url && !link.active
          }"
        />

      </div>

    </div>


    <!-- ===================================================== -->
    <!-- DELETE MODAL -->
    <!-- ===================================================== -->

    <div
      v-if="showDeleteModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >

      <div
        class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4"
      >

        <h3
          class="text-lg font-semibold mb-4"
        >
          Delete Product
        </h3>

        <p
          class="text-gray-600 mb-6"
        >
          Are you sure you want to delete this
          product? This action cannot be undone.
        </p>

        <div
          class="flex justify-end gap-3"
        >

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