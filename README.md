# PHP_Laravel12_Live_Search_With_Pagination_Using_Vue.JS
---

##  OVERVIEW

This project implements **Live Search with Pagination** using:

- Laravel 12
- Breeze Authentication
- Inertia.js
- Vue 3

### What it does
- Search products LIVE (no submit button)
- Search by name, detail, price
- Pagination with search query preserved
- SPA experience (no reload)

---

##  FEATURES

- Laravel 12 backend
- Vue 3 frontend
- Inertia.js SPA
- Live search (debounced)
- Pagination
- Clean Tailwind UI
- Query persistence

---

##  FULL FOLDER STRUCTURE

```
app/
├── Http/
│   └── Controllers/
│       └── ProductController.php
├── Models/
│   └── Product.php

database/
└── migrations/
    └── xxxx_create_products_table.php

resources/
├── js/
│   ├── app.js
│   └── Pages/
│       └── Product/
│           ├── Index.vue
│           ├── Create.vue
│           └── Edit.vue
└── views/
    └── app.blade.php

routes/
└── web.php

.env
README.md
```

---

##  STEP 1: INSTALL LARAVEL 12

```bash
composer create-project laravel/laravel product-crud
```

---

##  STEP 2: DATABASE CONFIGURATION

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=live
DB_USERNAME=root
DB_PASSWORD=
```

Create database `live` in phpMyAdmin.

---

##  STEP 3: INSTALL BREEZE + INERTIA + VUE

```bash
composer require laravel/breeze --dev

php artisan breeze:install vue

npm install

php artisan migrate

npm run dev

php artisan serve

```

---

##  STEP 4: CREATE MODEL & MIGRATION

```bash
php artisan make:model Product -m
```

### database/migrations/create_products_table.php

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('detail')->nullable();
    $table->decimal('price',10,2)->nullable();
    $table->timestamps();
});
```

Run migration:

```bash
php artisan migrate
```

---

##  STEP 5: PRODUCT MODEL

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name','detail','price'];
}
```

---

##  STEP 6: PRODUCT CONTROLLER (FULL)

```bash
php artisan make:controller ProductController
```

```php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $products = Product::where(function ($q) use ($search) {
            if ($search) {
                $q->where('name','like',"%{$search}%")
                  ->orWhere('detail','like',"%{$search}%")
                  ->orWhere('price','like',"%{$search}%");
            }
        })
        ->latest()
        ->paginate(5)
        ->withQueryString();

        return Inertia::render('Product/Index', [
            'products' => $products,
            'filters' => ['search' => $search]
        ]);
    }

    public function create()
    {
        return Inertia::render('Product/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required',
            'detail'=>'nullable',
            'price'=>'nullable|numeric'
        ]);

        Product::create($request->only('name','detail','price'));
        return redirect()->route('products.index');
    }

    public function edit(Product $product)
    {
        return Inertia::render('Product/Edit', [
            'product'=>$product
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name'=>'required',
            'detail'=>'nullable',
            'price'=>'nullable|numeric'
        ]);

        $product->update($request->only('name','detail','price'));
        return redirect()->route('products.index');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return back();
    }
}
```

---

##  STEP 7: ROUTES

```php
use App\Http\Controllers\ProductController;

Route::get('/products', [ProductController::class,'index'])->name('products.index');
Route::get('/products/create', [ProductController::class,'create'])->name('products.create');
Route::post('/products', [ProductController::class,'store'])->name('products.store');
Route::get('/products/{product}/edit', [ProductController::class,'edit'])->name('products.edit');
Route::put('/products/{product}', [ProductController::class,'update'])->name('products.update');
Route::delete('/products/{product}', [ProductController::class,'destroy'])->name('products.destroy');
```

---

##  STEP 8: INERTIA SETUP

### resources/js/app.js

```js
import '../css/app.css'
import './bootstrap'

import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createApp, h } from 'vue'

createInertiaApp({
  resolve: name =>
    resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
})
```

### resources/views/app.blade.php

```blade
<!DOCTYPE html>
<html>
<head>
    <title>Live Search</title>
    @vite(['resources/js/app.js'])
</head>
<body>
    @inertia
</body>
</html>
```

---

##  STEP 9: VUE FILES (FULL)

### Product/Index.vue

```vue
<script setup>
import { ref, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'

const props = defineProps({
  products:Object,
  filters:Object
})

const search = ref(props.filters.search || '')
let timer = null

watch(search, (value) => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    router.get('/products', { search: value }, {
      preserveState: true,
      replace: true
    })
  }, 400)
})
</script>

<template>
  <input v-model="search" placeholder="Search..." />

  <div v-for="p in products.data" :key="p.id">
    {{ p.name }} - {{ p.price }}
    <Link :href="`/products/${p.id}/edit`">Edit</Link>
  </div>

  <div v-html="products.links"></div>
</template>
```

---

### Product/Create.vue

```vue
<script setup>
import { useForm, Link } from '@inertiajs/vue3'

const form = useForm({
  name:'',
  detail:'',
  price:''
})

const submit = () => {
  form.post('/products')
}
</script>

<template>
<form @submit.prevent="submit">
  <input v-model="form.name" placeholder="Name" />
  <textarea v-model="form.detail"></textarea>
  <input v-model="form.price" type="number" />
  <button>Save</button>
  <Link href="/products">Back</Link>
</form>
</template>
```

---

### Product/Edit.vue

```vue
<script setup>
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({ product:Object })

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
<form @submit.prevent="submit">
  <input v-model="form.name" />
  <textarea v-model="form.detail"></textarea>
  <input v-model="form.price" type="number" />
  <button>Update</button>
  <Link href="/products">Back</Link>
</form>
</template>
```

---

##  FINAL URL

```
http://127.0.0.1:8000/products
```
INDEX PAGE:-

<img width="1245" height="621" alt="Screenshot 2025-12-18 114845" src="https://github.com/user-attachments/assets/543ca08e-e541-42b8-b8fc-ad4d266097f6" />

CREATE PAGE:-

<img width="913" height="638" alt="Screenshot 2025-12-18 114832" src="https://github.com/user-attachments/assets/c8b3c6cb-eed6-4962-8395-475f2353483f" />

EDIT PAGE:-

<img width="894" height="657" alt="Screenshot 2025-12-18 114858" src="https://github.com/user-attachments/assets/b91e8df9-9bdd-4951-aa39-f01b6e85fc7e" />

SEARCH:-

<img width="1186" height="413" alt="Screenshot 2025-12-17 171010" src="https://github.com/user-attachments/assets/317f61d9-85c3-444a-ad5c-a67726ccbe4a" />

PAGINATION:-

<img width="1184" height="559" alt="Screenshot 2025-12-18 115204" src="https://github.com/user-attachments/assets/b96060ab-41db-48ee-b4a2-29f7f6d34df3" />


---

