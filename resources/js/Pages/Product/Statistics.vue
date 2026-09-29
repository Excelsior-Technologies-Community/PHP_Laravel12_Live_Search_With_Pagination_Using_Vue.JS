<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  statistics: Object,
  recentProducts: Array
})

const formatPrice = (price) => {
  if (
    price === null ||
    price === undefined
  ) {
    return '0.00'
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

    <div class="max-w-7xl mx-auto">

      <!-- Header -->

      <div class="bg-white rounded-xl shadow p-6 mb-6">

        <div
          class="flex flex-col md:flex-row md:justify-between md:items-center gap-4"
        >

          <div>

            <h1 class="text-2xl font-bold text-gray-800">
              📊 Product Statistics Dashboard
            </h1>

            <p class="text-gray-500 mt-1">
              Product, inventory and status overview
            </p>

          </div>

          <Link
            href="/products"
            class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700"
          >
            Back to Products
          </Link>

        </div>

      </div>

      <!-- Statistics -->

      <div
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5"
      >

        <!-- Total -->

        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-blue-500">
          <p class="text-sm text-gray-500">
            Total Products
          </p>

          <p class="text-3xl font-bold mt-2">
            {{ statistics.total_products }}
          </p>
        </div>

        <!-- Inventory -->

        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-green-500">
          <p class="text-sm text-gray-500">
            Inventory Value
          </p>

          <p class="text-3xl font-bold text-green-600 mt-2">
            ₹{{ formatPrice(statistics.inventory_value) }}
          </p>
        </div>

        <!-- Average -->

        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-yellow-500">
          <p class="text-sm text-gray-500">
            Average Price
          </p>

          <p class="text-3xl font-bold text-yellow-600 mt-2">
            ₹{{ formatPrice(statistics.average_price) }}
          </p>
        </div>

        <!-- Featured -->

        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-purple-500">
          <p class="text-sm text-gray-500">
            Featured Products
          </p>

          <p class="text-3xl font-bold text-purple-600 mt-2">
            {{ statistics.featured_products }}
          </p>
        </div>

        <!-- Active -->

        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-green-500">
          <p class="text-sm text-gray-500">
            Active Products
          </p>

          <p class="text-3xl font-bold text-green-600 mt-2">
            {{ statistics.active_products }}
          </p>
        </div>

        <!-- Inactive -->

        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-gray-500">
          <p class="text-sm text-gray-500">
            Inactive Products
          </p>

          <p class="text-3xl font-bold text-gray-600 mt-2">
            {{ statistics.inactive_products }}
          </p>
        </div>

        <!-- Low Stock -->

        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-orange-500">
          <p class="text-sm text-gray-500">
            Low Stock
          </p>

          <p class="text-3xl font-bold text-orange-600 mt-2">
            {{ statistics.low_stock_products }}
          </p>
        </div>

        <!-- Out of Stock -->

        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-red-500">
          <p class="text-sm text-gray-500">
            Out of Stock
          </p>

          <p class="text-3xl font-bold text-red-600 mt-2">
            {{ statistics.out_of_stock_products }}
          </p>
        </div>

        <!-- Today -->

        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-indigo-500">
          <p class="text-sm text-gray-500">
            Added Today
          </p>

          <p class="text-3xl font-bold text-indigo-600 mt-2">
            {{ statistics.today_products }}
          </p>
        </div>

        <!-- Month -->

        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-pink-500">
          <p class="text-sm text-gray-500">
            Added This Month
          </p>

          <p class="text-3xl font-bold text-pink-600 mt-2">
            {{ statistics.this_month_products }}
          </p>
        </div>

        <!-- Lowest -->

        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-indigo-500">
          <p class="text-sm text-gray-500">
            Lowest Price
          </p>

          <p class="text-3xl font-bold text-indigo-600 mt-2">
            ₹{{ formatPrice(statistics.lowest_price) }}
          </p>
        </div>

        <!-- Highest -->

        <div class="bg-white rounded-xl shadow p-5 border-l-4 border-red-500">
          <p class="text-sm text-gray-500">
            Highest Price
          </p>

          <p class="text-3xl font-bold text-red-600 mt-2">
            ₹{{ formatPrice(statistics.highest_price) }}
          </p>
        </div>

      </div>

      <!-- Recent Products -->

      <div class="bg-white rounded-xl shadow mt-6">

        <div class="p-6 border-b">

          <h2 class="text-xl font-semibold">
            Recently Added Products
          </h2>

        </div>

        <div class="overflow-x-auto">

          <table class="w-full">

            <thead>

              <tr class="bg-gray-50 border-b text-left">

                <th class="p-4">
                  Product
                </th>

                <th class="p-4">
                  Category
                </th>

                <th class="p-4">
                  Price
                </th>

                <th class="p-4">
                  Stock
                </th>

                <th class="p-4">
                  Status
                </th>

                <th class="p-4">
                  Created
                </th>

              </tr>

            </thead>

            <tbody>

              <tr
                v-for="product in recentProducts"
                :key="product.id"
                class="border-b hover:bg-gray-50"
              >

                <td class="p-4 font-medium">
                  {{ product.name }}
                </td>

                <td class="p-4">
                  {{ product.category || '-' }}
                </td>

                <td class="p-4 text-green-600 font-semibold">
                  ₹{{ formatPrice(product.price) }}
                </td>

                <td class="p-4">
                  {{ product.stock }}
                </td>

                <td class="p-4">

                  <span
                    :class="
                      product.status === 'active'
                        ? 'bg-green-100 text-green-700'
                        : 'bg-gray-100 text-gray-700'
                    "
                    class="px-2 py-1 rounded text-xs"
                  >
                    {{ product.status }}
                  </span>

                </td>

                <td class="p-4 text-gray-500">
                  {{
                    new Date(
                      product.created_at
                    ).toLocaleDateString('en-IN')
                  }}
                </td>

              </tr>

              <tr
                v-if="recentProducts.length === 0"
              >

                <td
                  colspan="6"
                  class="p-8 text-center text-gray-500"
                >
                  No products available.
                </td>

              </tr>

            </tbody>

          </table>

        </div>

      </div>

    </div>

  </div>

</template>