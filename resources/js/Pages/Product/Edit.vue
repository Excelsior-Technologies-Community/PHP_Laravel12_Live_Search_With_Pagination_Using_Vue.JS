<script setup>
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
  product: Object
})

const form = useForm({
  name: props.product.name,
  detail: props.product.detail,
  price: props.product.price
})

const submit = () => {
  form.put(`/products/${props.product.id}`)
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex justify-center pt-12">
    <div class="bg-white w-full max-w-3xl p-8 rounded-xl shadow">

      <!-- Title -->
      <h2 class="text-2xl font-semibold text-center mb-8">
        Edit Product
      </h2>

      <form @submit.prevent="submit" class="space-y-6">

        <!-- Product Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Product Name
          </label>
          <input
            v-model="form.name"
            type="text"
            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <p v-if="form.errors.name" class="text-red-600 text-sm mt-1">
            {{ form.errors.name }}
          </p>
        </div>

        <!-- Product Detail -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Product Detail
          </label>
          <textarea
            v-model="form.detail"
            rows="4"
            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          ></textarea>
          <p v-if="form.errors.detail" class="text-red-600 text-sm mt-1">
            {{ form.errors.detail }}
          </p>
        </div>

        <!-- Product Price -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Product Price
          </label>
          <input
            v-model="form.price"
            type="number"
            class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <p v-if="form.errors.price" class="text-red-600 text-sm mt-1">
            {{ form.errors.price }}
          </p>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-4 pt-4">
          <button
            type="submit"
            :disabled="form.processing"
            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
          >
            {{ form.processing ? 'Updating...' : 'Update Product' }}
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
