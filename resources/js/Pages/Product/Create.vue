<script setup>
import { useForm, Link } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  category: '',
  detail: '',
  price: '',
  stock: 0,
  status: 'active',
  is_featured: false
})

const submit = () => {
  form.post('/products')
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex justify-center pt-12 p-4">

    <div class="bg-white w-full max-w-3xl p-8 rounded-xl shadow">

      <h2 class="text-2xl font-semibold text-center mb-8">
        Add New Product
      </h2>

      <form
        @submit.prevent="submit"
        class="space-y-6"
      >

        <!-- Name -->

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Product Name
          </label>

          <input
            v-model="form.name"
            type="text"
            placeholder="Enter product name"
            class="w-full border rounded-lg px-4 py-2"
          />

          <p
            v-if="form.errors.name"
            class="text-red-600 text-sm mt-1"
          >
            {{ form.errors.name }}
          </p>
        </div>

        <!-- Category -->

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Category
          </label>

          <input
            v-model="form.category"
            type="text"
            placeholder="Electronics"
            class="w-full border rounded-lg px-4 py-2"
          />

          <p
            v-if="form.errors.category"
            class="text-red-600 text-sm mt-1"
          >
            {{ form.errors.category }}
          </p>
        </div>

        <!-- Detail -->

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Product Detail
          </label>

          <textarea
            v-model="form.detail"
            rows="4"
            placeholder="Enter product details"
            class="w-full border rounded-lg px-4 py-2"
          ></textarea>
        </div>

        <!-- Price -->

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Product Price
          </label>

          <input
            v-model="form.price"
            type="number"
            min="0"
            step="0.01"
            placeholder="Enter price"
            class="w-full border rounded-lg px-4 py-2"
          />
        </div>

        <!-- Stock -->

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Stock Quantity
          </label>

          <input
            v-model="form.stock"
            type="number"
            min="0"
            class="w-full border rounded-lg px-4 py-2"
          />

          <p
            v-if="form.errors.stock"
            class="text-red-600 text-sm mt-1"
          >
            {{ form.errors.stock }}
          </p>
        </div>

        <!-- Status -->

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Status
          </label>

          <select
            v-model="form.status"
            class="w-full border rounded-lg px-4 py-2"
          >
            <option value="active">
              Active
            </option>

            <option value="inactive">
              Inactive
            </option>
          </select>
        </div>

        <!-- Featured -->

        <div class="flex items-center gap-3">

          <input
            v-model="form.is_featured"
            type="checkbox"
            class="w-5 h-5"
          />

          <label class="text-sm font-medium text-gray-700">
            Mark as Featured Product
          </label>

        </div>

        <!-- Actions -->

        <div class="flex items-center gap-4 pt-4">

          <button
            type="submit"
            :disabled="form.processing"
            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {{
              form.processing
                ? 'Saving...'
                : 'Save Product'
            }}
          </button>

          <Link
            href="/products"
            class="text-gray-600 hover:underline"
          >
            Cancel
          </Link>

        </div>

      </form>

    </div>

  </div>
</template>