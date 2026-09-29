<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },

    filters: {
        type: Object,
        default: () => ({}),
    },

    statistics: {
        type: Object,
        default: () => ({}),
    },

    categories: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const search = ref(props.filters.search || '')
const minPrice = ref(props.filters.min_price || '')
const maxPrice = ref(props.filters.max_price || '')
const dateFrom = ref(props.filters.date_from || '')
const dateTo = ref(props.filters.date_to || '')

const category = ref(props.filters.category || '')
const status = ref(props.filters.status || '')
const featured = ref(props.filters.featured || '')
const stockFilter = ref(props.filters.stock_filter || '')

const sort = ref(props.filters.sort || 'newest')
const perPage = ref(Number(props.filters.per_page || 10))

/*
|--------------------------------------------------------------------------
| Search Suggestions
|--------------------------------------------------------------------------
*/

const suggestions = ref([])
const showSuggestions = ref(false)
const loadingSuggestions = ref(false)
let suggestionTimer = null
let searchTimer = null

/*
|--------------------------------------------------------------------------
| Loading
|--------------------------------------------------------------------------
*/

const loading = ref(false)
const exporting = ref(false)

/*
|--------------------------------------------------------------------------
| Selection
|--------------------------------------------------------------------------
*/

const selectedIds = ref([])
const bulkLoading = ref(false)

/*
|--------------------------------------------------------------------------
| Delete Modal
|--------------------------------------------------------------------------
*/

const showDeleteModal = ref(false)
const deleteId = ref(null)
const deleting = ref(false)

/*
|--------------------------------------------------------------------------
| Bulk Delete Modal
|--------------------------------------------------------------------------
*/

const showBulkDeleteModal = ref(false)

/*
|--------------------------------------------------------------------------
| Bulk Price Modal
|--------------------------------------------------------------------------
*/

const showBulkPriceModal = ref(false)
const bulkPriceMode = ref('set')
const bulkPriceValue = ref('')

/*
|--------------------------------------------------------------------------
| Toast
|--------------------------------------------------------------------------
*/

const toast = ref({
    show: false,
    message: '',
    type: 'success',
})

let toastTimer = null

const showToast = (message, type = 'success') => {
    toast.value = {
        show: true,
        message,
        type,
    }

    clearTimeout(toastTimer)

    toastTimer = setTimeout(() => {
        toast.value.show = false
    }, 3500)
}

/*
|--------------------------------------------------------------------------
| Flash Message
|--------------------------------------------------------------------------
*/

const flashSuccess = computed(() => page.props.flash?.success || '')

watch(
    flashSuccess,
    (message) => {
        if (message) {
            showToast(message, 'success')
        }
    },
    { immediate: true }
)

/*
|--------------------------------------------------------------------------
| Filter Parameters
|--------------------------------------------------------------------------
*/

const getFilterParams = () => {
    return {
        search: search.value || undefined,
        min_price: minPrice.value || undefined,
        max_price: maxPrice.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,

        category: category.value || undefined,
        status: status.value || undefined,
        featured: featured.value || undefined,
        stock_filter: stockFilter.value || undefined,

        sort: sort.value || undefined,
        per_page: perPage.value || undefined,
    }
}

/*
|--------------------------------------------------------------------------
| Load Products
|--------------------------------------------------------------------------
*/

const loadProducts = () => {
    loading.value = true

    router.get(
        '/products',
        getFilterParams(),
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,

            onFinish: () => {
                loading.value = false
            },
        }
    )
}

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

watch(search, () => {
    clearTimeout(searchTimer)

    searchTimer = setTimeout(() => {
        loadProducts()
    }, 400)

    fetchSuggestions()
})

/*
|--------------------------------------------------------------------------
| Suggestions
|--------------------------------------------------------------------------
*/

const fetchSuggestions = () => {
    clearTimeout(suggestionTimer)

    if (!search.value || search.value.length < 2) {
        suggestions.value = []
        showSuggestions.value = false
        return
    }

    suggestionTimer = setTimeout(async () => {
        loadingSuggestions.value = true

        try {
            const response = await fetch(
                `/products/suggestions?q=${encodeURIComponent(search.value)}`
            )

            if (!response.ok) {
                throw new Error('Unable to load suggestions.')
            }

            suggestions.value = await response.json()

            showSuggestions.value = suggestions.value.length > 0
        } catch (error) {
            suggestions.value = []
            showSuggestions.value = false
        } finally {
            loadingSuggestions.value = false
        }
    }, 250)
}

const selectSuggestion = (suggestion) => {
    search.value = suggestion.name || suggestion
    suggestions.value = []
    showSuggestions.value = false

    loadProducts()
}

/*
|--------------------------------------------------------------------------
| Apply Filters
|--------------------------------------------------------------------------
*/

const applyFilters = () => {
    showSuggestions.value = false
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

    category.value = ''
    status.value = ''
    featured.value = ''
    stockFilter.value = ''

    sort.value = 'newest'
    perPage.value = 10

    selectedIds.value = []

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

const changePerPage = () => {
    loadProducts()
}

/*
|--------------------------------------------------------------------------
| Stock Quick Filters
|--------------------------------------------------------------------------
*/

const setStockFilter = (value) => {
    stockFilter.value = value
    loadProducts()
}

/*
|--------------------------------------------------------------------------
| Export CSV
|--------------------------------------------------------------------------
*/

const exportCsv = () => {
    exporting.value = true

    const params = new URLSearchParams()

    Object.entries(getFilterParams()).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            params.append(key, value)
        }
    })

    window.location.href = `/products/export/csv?${params.toString()}`

    setTimeout(() => {
        exporting.value = false
    }, 1500)
}

/*
|--------------------------------------------------------------------------
| Delete
|--------------------------------------------------------------------------
*/

const openDeleteModal = (id) => {
    deleteId.value = id
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    if (deleting.value) {
        return
    }

    showDeleteModal.value = false
    deleteId.value = null
}

const deleteProduct = () => {
    if (!deleteId.value) {
        return
    }

    deleting.value = true

    router.delete(`/products/${deleteId.value}`, {
        preserveScroll: true,

        onSuccess: () => {
            showToast('Product deleted successfully.')

            selectedIds.value = selectedIds.value.filter(
                id => id !== deleteId.value
            )

            closeDeleteModal()
        },

        onFinish: () => {
            deleting.value = false
        },
    })
}

/*
|--------------------------------------------------------------------------
| Selection
|--------------------------------------------------------------------------
*/

const isSelected = (id) => {
    return selectedIds.value.includes(id)
}

const toggleSelection = (id) => {
    if (isSelected(id)) {
        selectedIds.value = selectedIds.value.filter(
            selectedId => selectedId !== id
        )
    } else {
        selectedIds.value.push(id)
    }
}

const allCurrentPageSelected = computed(() => {
    if (!props.products.data || props.products.data.length === 0) {
        return false
    }

    return props.products.data.every(product =>
        selectedIds.value.includes(product.id)
    )
})

const toggleSelectAll = () => {
    const pageIds = props.products.data.map(product => product.id)

    if (allCurrentPageSelected.value) {
        selectedIds.value = selectedIds.value.filter(
            id => !pageIds.includes(id)
        )
    } else {
        const merged = new Set([
            ...selectedIds.value,
            ...pageIds,
        ])

        selectedIds.value = Array.from(merged)
    }
}

const clearSelection = () => {
    selectedIds.value = []
}

/*
|--------------------------------------------------------------------------
| Bulk Delete
|--------------------------------------------------------------------------
*/

const openBulkDeleteModal = () => {
    if (selectedIds.value.length === 0) {
        showToast('Please select at least one product.', 'error')
        return
    }

    showBulkDeleteModal.value = true
}

const closeBulkDeleteModal = () => {
    if (bulkLoading.value) {
        return
    }

    showBulkDeleteModal.value = false
}

const bulkDelete = () => {
    if (selectedIds.value.length === 0) {
        return
    }

    bulkLoading.value = true

    router.post(
        '/products/bulk-delete',
        {
            ids: selectedIds.value,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                showToast(
                    `${selectedIds.value.length} product(s) deleted successfully.`
                )

                selectedIds.value = []
                showBulkDeleteModal.value = false
            },

            onFinish: () => {
                bulkLoading.value = false
            },
        }
    )
}

/*
|--------------------------------------------------------------------------
| Bulk Status
|--------------------------------------------------------------------------
*/

const bulkStatus = (newStatus) => {
    if (selectedIds.value.length === 0) {
        showToast('Please select at least one product.', 'error')
        return
    }

    const actionText =
        newStatus === 'active'
            ? 'activated'
            : 'deactivated'

    if (
        !confirm(
            `Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} ${selectedIds.value.length} selected product(s)?`
        )
    ) {
        return
    }

    bulkLoading.value = true

    router.post(
        '/products/bulk-status',
        {
            ids: selectedIds.value,
            status: newStatus,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                showToast(
                    `${selectedIds.value.length} product(s) ${actionText} successfully.`
                )

                selectedIds.value = []
            },

            onFinish: () => {
                bulkLoading.value = false
            },
        }
    )
}

/*
|--------------------------------------------------------------------------
| Bulk Price Update
|--------------------------------------------------------------------------
*/

const openBulkPriceModal = () => {
    if (selectedIds.value.length === 0) {
        showToast('Please select at least one product.', 'error')
        return
    }

    bulkPriceMode.value = 'set'
    bulkPriceValue.value = ''
    showBulkPriceModal.value = true
}

const closeBulkPriceModal = () => {
    if (bulkLoading.value) {
        return
    }

    showBulkPriceModal.value = false
}

const bulkPriceUpdate = () => {
    if (!bulkPriceValue.value || Number(bulkPriceValue.value) < 0) {
        showToast('Please enter a valid price value.', 'error')
        return
    }

    bulkLoading.value = true

    router.post(
        '/products/bulk-price-update',
        {
            ids: selectedIds.value,
            price_mode: bulkPriceMode.value,
            price_value: bulkPriceValue.value,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                showToast(
                    `${selectedIds.value.length} product(s) price updated successfully.`
                )

                selectedIds.value = []
                showBulkPriceModal.value = false
                bulkPriceValue.value = ''
            },

            onFinish: () => {
                bulkLoading.value = false
            },
        }
    )
}

/*
|--------------------------------------------------------------------------
| Duplicate / Clone
|--------------------------------------------------------------------------
*/

const duplicateProduct = (id) => {
    if (
        !confirm(
            'Are you sure you want to duplicate this product?'
        )
    ) {
        return
    }

    router.post(
        `/products/${id}/duplicate`,
        {},
        {
            preserveScroll: true,

            onSuccess: () => {
                showToast('Product duplicated successfully.')
            },
        }
    )
}

/*
|--------------------------------------------------------------------------
| Featured Toggle
|--------------------------------------------------------------------------
*/

const toggleFeatured = (product) => {
    router.post(
        `/products/${product.id}/toggle-featured`,
        {},
        {
            preserveScroll: true,

            onSuccess: () => {
                showToast(
                    product.is_featured
                        ? 'Product removed from featured.'
                        : 'Product marked as featured.'
                )
            },
        }
    )
}

/*
|--------------------------------------------------------------------------
| Status Toggle
|--------------------------------------------------------------------------
*/

const toggleStatus = (product) => {
    const newStatus =
        product.status === 'active'
            ? 'inactive'
            : 'active'

    router.post(
        `/products/${product.id}/toggle-status`,
        {
            status: newStatus,
        },
        {
            preserveScroll: true,

            onSuccess: () => {
                showToast(
                    newStatus === 'active'
                        ? 'Product activated successfully.'
                        : 'Product deactivated successfully.'
                )
            },
        }
    )
}

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

const numericPaginationLinks = computed(() => {
    if (!props.products?.links) {
        return []
    }

    return props.products.links.filter(link => {
        if (!link.url) {
            return false
        }

        return /^\d+$/.test(
            String(link.label).replace(/&hellip;/g, '')
        )
    })
})

const goToPage = (url) => {
    if (!url) {
        return
    }

    router.get(
        url,
        {},
        {
            preserveState: true,
            preserveScroll: true,
        }
    )
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const formatPrice = (value) => {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 2,
    }).format(Number(value || 0))
}

const formatDate = (date) => {
    if (!date) {
        return '-'
    }

    return new Date(date).toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

const stockClass = (stock) => {
    const value = Number(stock || 0)

    if (value === 0) {
        return 'bg-red-100 text-red-700'
    }

    if (value <= 5) {
        return 'bg-yellow-100 text-yellow-700'
    }

    return 'bg-green-100 text-green-700'
}

const stockLabel = (stock) => {
    const value = Number(stock || 0)

    if (value === 0) {
        return 'Out of Stock'
    }

    if (value <= 5) {
        return 'Low Stock'
    }

    return 'In Stock'
}

/*
|--------------------------------------------------------------------------
| Cleanup
|--------------------------------------------------------------------------
*/

onMounted(() => {
    document.addEventListener('click', handleOutsideClick)
})

const handleOutsideClick = (event) => {
    const target = event.target

    if (!target.closest('.suggestion-container')) {
        showSuggestions.value = false
    }
}

onUnmounted(() => {
    clearTimeout(searchTimer)
    clearTimeout(suggestionTimer)
    clearTimeout(toastTimer)

    document.removeEventListener(
        'click',
        handleOutsideClick
    )
})
</script>

<template>
    <div class="min-h-screen bg-gray-100 py-8">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        Product Management
                    </h1>

                    <p class="mt-1 text-sm text-gray-600">
                        Manage, search, filter, clone and export products
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">

                    <button
                        type="button"
                        @click="exportCsv"
                        :disabled="exporting"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ exporting ? 'Exporting...' : 'Export CSV' }}
                    </button>

                    <Link
                        href="/products/statistics"
                        class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-purple-700"
                    >
                        Statistics
                    </Link>

                    <Link
                        href="/products/create"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700"
                    >
                        + Create Product
                    </Link>

                </div>
            </div>

            <!-- Statistics -->
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">

                <div class="rounded-xl bg-white p-5 shadow">
                    <p class="text-sm text-gray-500">
                        Total Products
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        {{ statistics.total_products ?? 0 }}
                    </p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <p class="text-sm text-gray-500">
                        Filtered Products
                    </p>

                    <p class="mt-2 text-2xl font-bold text-blue-600">
                        {{ statistics.filtered_products ?? 0 }}
                    </p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <p class="text-sm text-gray-500">
                        Average Price
                    </p>

                    <p class="mt-2 text-2xl font-bold text-purple-600">
                        {{ formatPrice(statistics.average_price) }}
                    </p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <p class="text-sm text-gray-500">
                        Inventory Value
                    </p>

                    <p class="mt-2 text-2xl font-bold text-emerald-600">
                        {{ formatPrice(statistics.inventory_value) }}
                    </p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow">
                    <p class="text-sm text-gray-500">
                        Low / Out Stock
                    </p>

                    <p class="mt-2 text-2xl font-bold text-red-600">
                        {{ statistics.low_stock_products ?? 0 }}
                        /
                        {{ statistics.out_of_stock_products ?? 0 }}
                    </p>
                </div>

            </div>

            <!-- Search and Filters -->
            <div class="mb-6 rounded-xl bg-white p-6 shadow">

                <div class="mb-5">

                    <h2 class="text-lg font-bold text-gray-900">
                        Search & Filters
                    </h2>

                    <p class="text-sm text-gray-500">
                        Filter products using multiple conditions
                    </p>

                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">

                    <!-- Search -->
                    <div class="suggestion-container relative lg:col-span-2">

                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Search Product
                        </label>

                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search product name, detail or price..."
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                            @focus="showSuggestions = suggestions.length > 0"
                        />

                        <div
                            v-if="loadingSuggestions"
                            class="absolute right-3 top-9 text-xs text-gray-400"
                        >
                            Searching...
                        </div>

                        <!-- Suggestions -->
                        <div
                            v-if="showSuggestions && suggestions.length"
                            class="absolute z-50 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg"
                        >

                            <button
                                v-for="suggestion in suggestions"
                                :key="suggestion.id || suggestion.name"
                                type="button"
                                class="block w-full border-b border-gray-100 px-4 py-3 text-left text-sm hover:bg-gray-50"
                                @click="selectSuggestion(suggestion)"
                            >
                                <span class="font-medium text-gray-900">
                                    {{ suggestion.name }}
                                </span>

                                <span
                                    v-if="suggestion.price !== undefined"
                                    class="ml-2 text-gray-500"
                                >
                                    {{ formatPrice(suggestion.price) }}
                                </span>
                            </button>

                        </div>

                    </div>

                    <!-- Category -->
                    <div>

                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Category
                        </label>

                        <select
                            v-model="category"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                            @change="applyFilters"
                        >
                            <option value="">
                                All Categories
                            </option>

                            <option
                                v-for="item in categories"
                                :key="item"
                                :value="item"
                            >
                                {{ item }}
                            </option>
                        </select>

                    </div>

                    <!-- Status -->
                    <div>

                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Status
                        </label>

                        <select
                            v-model="status"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                            @change="applyFilters"
                        >
                            <option value="">
                                All Status
                            </option>

                            <option value="active">
                                Active
                            </option>

                            <option value="inactive">
                                Inactive
                            </option>
                        </select>

                    </div>

                    <!-- Featured -->
                    <div>

                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Featured
                        </label>

                        <select
                            v-model="featured"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                            @change="applyFilters"
                        >
                            <option value="">
                                All Products
                            </option>

                            <option value="yes">
                                Featured
                            </option>

                            <option value="no">
                                Not Featured
                            </option>
                        </select>

                    </div>

                    <!-- Min Price -->
                    <div>

                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Min Price
                        </label>

                        <input
                            v-model="minPrice"
                            type="number"
                            min="0"
                            placeholder="Minimum price"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                        />

                    </div>

                    <!-- Max Price -->
                    <div>

                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Max Price
                        </label>

                        <input
                            v-model="maxPrice"
                            type="number"
                            min="0"
                            placeholder="Maximum price"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                        />

                    </div>

                    <!-- Date From -->
                    <div>

                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Created From
                        </label>

                        <input
                            v-model="dateFrom"
                            type="date"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                        />

                    </div>

                    <!-- Date To -->
                    <div>

                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Created To
                        </label>

                        <input
                            v-model="dateTo"
                            type="date"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                        />

                    </div>

                </div>

                <!-- Stock Quick Filters -->
                <div class="mt-5">

                    <label class="mb-2 block text-sm font-medium text-gray-700">
                        Stock Filters
                    </label>

                    <div class="flex flex-wrap gap-2">

                        <button
                            type="button"
                            @click="setStockFilter('')"
                            :class="[
                                stockFilter === ''
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                'rounded-lg px-4 py-2 text-sm font-medium'
                            ]"
                        >
                            All Stock
                        </button>

                        <button
                            type="button"
                            @click="setStockFilter('low')"
                            :class="[
                                stockFilter === 'low'
                                    ? 'bg-yellow-500 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                'rounded-lg px-4 py-2 text-sm font-medium'
                            ]"
                        >
                            Low Stock
                        </button>

                        <button
                            type="button"
                            @click="setStockFilter('out')"
                            :class="[
                                stockFilter === 'out'
                                    ? 'bg-red-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                'rounded-lg px-4 py-2 text-sm font-medium'
                            ]"
                        >
                            Out of Stock
                        </button>

                    </div>

                </div>

                <!-- Sort + Buttons -->
                <div class="mt-5 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">

                    <div class="flex flex-col gap-3 sm:flex-row">

                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Sort
                            </label>

                            <select
                                v-model="sort"
                                @change="changeSort"
                                class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm"
                            >
                                <option value="newest">
                                    Newest
                                </option>

                                <option value="oldest">
                                    Oldest
                                </option>

                                <option value="name_asc">
                                    Name A-Z
                                </option>

                                <option value="name_desc">
                                    Name Z-A
                                </option>

                                <option value="price_low">
                                    Price Low-High
                                </option>

                                <option value="price_high">
                                    Price High-Low
                                </option>
                            </select>

                        </div>

                        <div>

                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Per Page
                            </label>

                            <select
                                v-model="perPage"
                                @change="changePerPage"
                                class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm"
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

                        </div>

                    </div>

                    <div class="flex gap-2">

                        <button
                            type="button"
                            @click="applyFilters"
                            :disabled="loading"
                            class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ loading ? 'Loading...' : 'Apply Filters' }}
                        </button>

                        <button
                            type="button"
                            @click="clearFilters"
                            class="rounded-lg bg-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-300"
                        >
                            Clear
                        </button>

                    </div>

                </div>

            </div>

            <!-- Bulk Actions -->
            <div
                v-if="selectedIds.length > 0"
                class="mb-5 rounded-xl border border-blue-200 bg-blue-50 p-4"
            >

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                    <div>
                        <p class="font-semibold text-blue-900">
                            {{ selectedIds.length }} product(s) selected
                        </p>

                        <p class="text-sm text-blue-700">
                            Choose a bulk operation below.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">

                        <button
                            type="button"
                            @click="bulkStatus('active')"
                            :disabled="bulkLoading"
                            class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50"
                        >
                            Activate
                        </button>

                        <button
                            type="button"
                            @click="bulkStatus('inactive')"
                            :disabled="bulkLoading"
                            class="rounded-lg bg-yellow-600 px-4 py-2 text-sm font-semibold text-white hover:bg-yellow-700 disabled:opacity-50"
                        >
                            Deactivate
                        </button>

                        <button
                            type="button"
                            @click="openBulkPriceModal"
                            :disabled="bulkLoading"
                            class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700 disabled:opacity-50"
                        >
                            Update Prices
                        </button>

                        <button
                            type="button"
                            @click="openBulkDeleteModal"
                            :disabled="bulkLoading"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50"
                        >
                            Delete Selected
                        </button>

                        <button
                            type="button"
                            @click="clearSelection"
                            class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50"
                        >
                            Clear Selection
                        </button>

                    </div>

                </div>

            </div>

            <!-- Product Table -->
            <div class="overflow-hidden rounded-xl bg-white shadow">

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200">

                        <thead class="bg-gray-50">

                            <tr>

                                <!-- Select All -->
                                <th class="px-4 py-3 text-left">

                                    <input
                                        type="checkbox"
                                        :checked="allCurrentPageSelected"
                                        @change="toggleSelectAll"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />

                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Product
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Category
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Price
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Stock
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Status
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Featured
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Created
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    Actions
                                </th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-gray-100">

                            <tr
                                v-for="product in products.data"
                                :key="product.id"
                                class="hover:bg-gray-50"
                            >

                                <!-- Checkbox -->
                                <td class="px-4 py-4">

                                    <input
                                        type="checkbox"
                                        :checked="isSelected(product.id)"
                                        @change="toggleSelection(product.id)"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />

                                </td>

                                <!-- Product -->
                                <td class="px-4 py-4">

                                    <div class="font-semibold text-gray-900">
                                        {{ product.name }}
                                    </div>

                                    <div
                                        v-if="product.detail"
                                        class="mt-1 max-w-xs truncate text-sm text-gray-500"
                                    >
                                        {{ product.detail }}
                                    </div>

                                </td>

                                <!-- Category -->
                                <td class="px-4 py-4">

                                    <span
                                        v-if="product.category"
                                        class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700"
                                    >
                                        {{ product.category }}
                                    </span>

                                    <span
                                        v-else
                                        class="text-sm text-gray-400"
                                    >
                                        —
                                    </span>

                                </td>

                                <!-- Price -->
                                <td class="px-4 py-4 font-semibold text-gray-900">
                                    {{ formatPrice(product.price) }}
                                </td>

                                <!-- Stock -->
                                <td class="px-4 py-4">

                                    <div class="flex flex-col gap-1">

                                        <span
                                            :class="[
                                                stockClass(product.stock),
                                                'inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold'
                                            ]"
                                        >
                                            {{ product.stock ?? 0 }}
                                        </span>

                                        <span class="text-xs text-gray-500">
                                            {{ stockLabel(product.stock) }}
                                        </span>

                                    </div>

                                </td>

                                <!-- Status -->
                                <td class="px-4 py-4">

                                    <button
                                        type="button"
                                        @click="toggleStatus(product)"
                                        :class="[
                                            product.status === 'active'
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-gray-200 text-gray-700',
                                            'rounded-full px-3 py-1 text-xs font-semibold'
                                        ]"
                                    >
                                        {{ product.status === 'active' ? 'Active' : 'Inactive' }}
                                    </button>

                                </td>

                                <!-- Featured -->
                                <td class="px-4 py-4">

                                    <button
                                        type="button"
                                        @click="toggleFeatured(product)"
                                        :class="[
                                            product.is_featured
                                                ? 'bg-yellow-100 text-yellow-700'
                                                : 'bg-gray-100 text-gray-500',
                                            'rounded-full px-3 py-1 text-xs font-semibold'
                                        ]"
                                    >
                                        {{ product.is_featured ? '★ Featured' : '☆ Not Featured' }}
                                    </button>

                                </td>

                                <!-- Created -->
                                <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500">
                                    {{ formatDate(product.created_at) }}
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-4">

                                    <div class="flex flex-wrap justify-end gap-2">

                                        <Link
                                            :href="`/products/${product.id}/edit`"
                                            class="rounded-lg bg-blue-100 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-200"
                                        >
                                            Edit
                                        </Link>

                                        <button
                                            type="button"
                                            @click="duplicateProduct(product.id)"
                                            class="rounded-lg bg-purple-100 px-3 py-1.5 text-xs font-semibold text-purple-700 hover:bg-purple-200"
                                        >
                                            Clone
                                        </button>

                                        <button
                                            type="button"
                                            @click="openDeleteModal(product.id)"
                                            class="rounded-lg bg-red-100 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-200"
                                        >
                                            Delete
                                        </button>

                                    </div>

                                </td>

                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!products.data || products.data.length === 0">

                                <td
                                    colspan="9"
                                    class="px-6 py-12 text-center"
                                >

                                    <div class="text-lg font-semibold text-gray-700">
                                        No products found
                                    </div>

                                    <p class="mt-1 text-sm text-gray-500">
                                        Try changing your search or filters.
                                    </p>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

                <!-- Pagination -->
                <div
                    v-if="numericPaginationLinks.length > 0"
                    class="flex flex-wrap justify-center gap-2 border-t border-gray-200 px-6 py-4"
                >

                    <button
                        v-for="link in numericPaginationLinks"
                        :key="link.label"
                        type="button"
                        @click="goToPage(link.url)"
                        :class="[
                            link.active
                                ? 'bg-blue-600 text-white'
                                : 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50',
                            'min-w-[40px] rounded-lg px-3 py-2 text-sm font-semibold'
                        ]"
                    >
                        {{ link.label }}
                    </button>

                </div>

            </div>

        </div>

        <!-- Delete Modal -->
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        >

            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">

                <h2 class="text-xl font-bold text-gray-900">
                    Delete Product
                </h2>

                <p class="mt-2 text-sm text-gray-600">
                    Are you sure you want to delete this product?
                    This action cannot be undone.
                </p>

                <div class="mt-6 flex justify-end gap-3">

                    <button
                        type="button"
                        @click="closeDeleteModal"
                        :disabled="deleting"
                        class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200 disabled:opacity-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="deleteProduct"
                        :disabled="deleting"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50"
                    >
                        {{ deleting ? 'Deleting...' : 'Delete' }}
                    </button>

                </div>

            </div>

        </div>

        <!-- Bulk Delete Modal -->
        <div
            v-if="showBulkDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        >

            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">

                <h2 class="text-xl font-bold text-gray-900">
                    Delete Selected Products
                </h2>

                <p class="mt-2 text-sm text-gray-600">
                    You selected
                    <strong>{{ selectedIds.length }}</strong>
                    product(s).
                    Are you sure you want to delete them?
                </p>

                <div class="mt-6 flex justify-end gap-3">

                    <button
                        type="button"
                        @click="closeBulkDeleteModal"
                        :disabled="bulkLoading"
                        class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="bulkDelete"
                        :disabled="bulkLoading"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50"
                    >
                        {{ bulkLoading ? 'Deleting...' : 'Delete Selected' }}
                    </button>

                </div>

            </div>

        </div>

        <!-- Bulk Price Modal -->
        <div
            v-if="showBulkPriceModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        >

            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">

                <h2 class="text-xl font-bold text-gray-900">
                    Bulk Price Update
                </h2>

                <p class="mt-2 text-sm text-gray-500">
                    Update prices for {{ selectedIds.length }} selected product(s).
                </p>

                <div class="mt-5">

                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Update Type
                    </label>

                    <select
                        v-model="bulkPriceMode"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"
                    >
                        <option value="set">
                            Set Price
                        </option>

                        <option value="increase">
                            Increase Price
                        </option>

                        <option value="decrease">
                            Decrease Price
                        </option>
                    </select>

                </div>

                <div class="mt-4">

                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Amount
                    </label>

                    <input
                        v-model="bulkPriceValue"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="Enter amount"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"
                    />

                </div>

                <div class="mt-6 flex justify-end gap-3">

                    <button
                        type="button"
                        @click="closeBulkPriceModal"
                        :disabled="bulkLoading"
                        class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="bulkPriceUpdate"
                        :disabled="bulkLoading"
                        class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-700 disabled:opacity-50"
                    >
                        {{ bulkLoading ? 'Updating...' : 'Update Prices' }}
                    </button>

                </div>

            </div>

        </div>

        <!-- Toast -->
        <div
            v-if="toast.show"
            class="fixed bottom-6 right-6 z-[60]"
        >

            <div
                :class="[
                    toast.type === 'error'
                        ? 'bg-red-600'
                        : 'bg-green-600',
                    'rounded-lg px-5 py-3 text-sm font-semibold text-white shadow-xl'
                ]"
            >
                {{ toast.message }}
            </div>

        </div>

    </div>
</template>