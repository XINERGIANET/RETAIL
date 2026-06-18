<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-white/90">Nombre <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="{{ old('name', $promo->name ?? '') }}" required 
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:text-white/90">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-white/90">Precio Promocional <span class="text-red-500">*</span></label>
        <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">S/</span>
            <input type="number" step="0.01" name="price" value="{{ old('price', $promo->price ?? '') }}" required 
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent pl-10 px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:text-white/90">
        </div>
        @error('price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-white/90">Fecha de Vigencia (Opcional)</label>
        <input type="date" name="end_date" value="{{ old('end_date', isset($promo->end_date) ? $promo->end_date->format('Y-m-d') : '') }}" 
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:text-white/90">
        @error('end_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex flex-col justify-center mt-2">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-white/90">Estado</label>
        <label class="relative inline-flex items-center cursor-pointer mt-1">
            <input type="checkbox" name="status" value="1" {{ old('status', $promo->status ?? true) ? 'checked' : '' }} class="sr-only peer">
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-300 dark:peer-focus:ring-brand-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand-600"></div>
            <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">Activo</span>
        </label>
    </div>

    <div class="md:col-span-2" x-data="{ fileName: '', isDragging: false }">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-white/90">Imagen (Opcional)</label>
        <div class="relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-4 py-6 transition-all duration-200"
            :class="isDragging ? 'border-brand-500 bg-brand-50' : 'border-gray-300 bg-gray-50 hover:bg-gray-100'"
            @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
            @drop.prevent="isDragging = false; fileName = $event.dataTransfer.files[0].name; $refs.fileInput.files = $event.dataTransfer.files">
            
            <i class="ri-image-add-line text-3xl text-gray-400" :class="isDragging ? 'text-brand-500' : ''"></i>
            <div class="text-center">
                <p class="text-sm font-medium text-gray-700">
                    <span class="text-brand-600 hover:text-brand-700">Haz clic para subir</span>
                    o arrastra y suelta
                </p>
                <p class="mt-1 text-xs text-gray-500">PNG, JPG, WEBP (Max. 5MB)</p>
            </div>
            <input x-ref="fileInput" type="file" name="image" accept="image/*"
                class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                @change="fileName = $event.target.files[0].name">
            
            <div x-show="fileName" class="mt-3 flex items-center gap-2 rounded-lg bg-white px-3 py-2 shadow-sm border border-gray-200" style="display: none;">
                <i class="ri-file-image-line text-brand-500"></i>
                <span class="text-sm font-medium text-gray-700" x-text="fileName"></span>
            </div>
        </div>
        @if(isset($promo) && $promo->image)
            <div class="mt-3 flex items-center gap-4 p-3 border border-gray-200 rounded-lg bg-white shadow-sm">
                <img src="{{ asset('storage/' . $promo->image) }}" alt="Imagen actual" class="h-16 w-16 object-cover rounded-md border border-gray-200">
                <div>
                    <p class="text-sm font-medium text-gray-800">Imagen actual</p>
                    <p class="text-xs text-gray-500">Se reemplazará si subes una nueva.</p>
                </div>
            </div>
        @endif
        @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2" x-data="promoProducts({{ isset($promo) ? $promo->products->map(function($p) { return ['id' => $p->id, 'name' => $p->description, 'qty' => $p->pivot->quantity]; })->toJson() : '[]' }})">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-white/90">
            Productos Relacionados 
            <span class="text-gray-400 font-normal ml-1">(Se descontarán automáticamente del stock)</span>
        </label>
        
        <div class="flex flex-col sm:flex-row gap-3 mb-4 p-4 bg-gray-50 dark:bg-dark-900 border border-gray-200 dark:border-gray-800 rounded-xl">
            <div class="flex-1">
                <select x-ref="productSelect" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:text-white/90">
                    <option value="">-- Seleccionar Producto --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" data-name="{{ $product->description }}">{{ $product->description }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-32">
                <input type="number" x-model="selectedQty" min="1" placeholder="Cantidad" 
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:text-white/90">
            </div>
            <button type="button" @click="addProduct" class="h-11 px-6 bg-brand-50 text-brand-600 border border-brand-200 hover:bg-brand-100 font-medium rounded-lg transition-colors flex items-center gap-2">
                <i class="ri-add-line"></i> Añadir
            </button>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 font-semibold text-gray-700">Producto</th>
                        <th class="px-5 py-3 font-semibold text-gray-700 w-32">Cantidad</th>
                        <th class="px-5 py-3 font-semibold text-gray-700 w-16 text-center"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(prod, index) in list" :key="index">
                        <tr class="border-b border-gray-100 last:border-none hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded bg-gray-100 flex items-center justify-center text-gray-500 flex-shrink-0">
                                        <i class="ri-box-3-line"></i>
                                    </div>
                                    <span class="font-medium text-gray-800" x-text="prod.name"></span>
                                </div>
                                <input type="hidden" name="products[]" :value="prod.id">
                            </td>
                            <td class="px-5 py-3">
                                <input type="number" name="quantities[]" x-model="prod.qty" min="1" 
                                    class="h-9 w-full rounded border-gray-300 bg-white px-3 py-1.5 text-sm shadow-sm focus:border-brand-300 focus:ring-brand-500/10 transition-colors">
                            </td>
                            <td class="px-5 py-3 text-center">
                                <button type="button" @click="removeProduct(index)" class="h-8 w-8 inline-flex items-center justify-center rounded bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-600 transition-colors">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="list.length === 0">
                        <td colspan="3" class="px-5 py-8 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <i class="ri-shopping-basket-line text-3xl mb-2"></i>
                                <p class="text-sm font-medium text-gray-500">Ningún producto agregado a esta promo.</p>
                                <p class="text-xs">Utiliza el buscador de arriba para agregar componentes.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    window.promoProducts = function(initialList) {
        return {
            list: initialList,
            selectedQty: 1,

            addProduct() {
                const selectEl = this.$refs.productSelect;
                const val = selectEl.value;
                
                if (!val) return;
                
                const selectedOption = selectEl.options[selectEl.selectedIndex];
                const name = selectedOption.getAttribute('data-name');
                
                const existingIndex = this.list.findIndex(p => p.id == val);
                if (existingIndex > -1) {
                    this.list[existingIndex].qty = parseInt(this.list[existingIndex].qty) + parseInt(this.selectedQty);
                } else {
                    this.list.push({
                        id: val,
                        name: name,
                        qty: parseInt(this.selectedQty)
                    });
                }
                
                // Reset select
                selectEl.value = '';
                
                // Refresh plugin if it exists
                if (window.jQuery) {
                    const $select = $(selectEl);
                    if (typeof $select.selectpicker === 'function') {
                        $select.selectpicker('refresh');
                    } else if (typeof $select.select2 === 'function') {
                        $select.val('').trigger('change');
                    }
                }
                
                this.selectedQty = 1;
            },

            removeProduct(index) {
                this.list.splice(index, 1);
            }
        };
    };
</script>
