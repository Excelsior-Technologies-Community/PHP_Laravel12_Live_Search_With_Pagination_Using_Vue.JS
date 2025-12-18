<script setup>
import { ref, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'

const props = defineProps({
  products: Object,
  filters: Object
})

const search = ref(props.filters.search || '')
let timer = null

// Live search (name + detail + price)
watch(search, (value) => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    router.get('/products', { search: value }, {
      preserveState: true,
      replace: true
    })
  }, 400)
})

const deleteProduct = (id) => {
  if (confirm('Are you sure you want to delete this product?')) {
    router.delete(`/products/${id}`, {
      preserveScroll: true
    })
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 p-8">
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

      <!-- SEARCH -->
      <input
        v-model="search"
        placeholder="Search by name, detail or price..."
        class="border p-2 w-full mb-4 rounded"
      />

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
  </div>
</template>
