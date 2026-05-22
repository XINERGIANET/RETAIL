@extends('layouts.app')

@section('content')
    <div id="sale-order-create-view">
        <x-common.page-breadcrumb
            pageTitle="Nuevo Pedido de Venta"
            :crumbs="[
                ['label' => 'Pedidos de Venta', 'url' => route('admin.sale-orders.index')],
                ['label' => 'Nuevo pedido'],
            ]"
        />

        <x-common.component-card title="Nuevo Pedido de Venta"
            desc="Registra un pedido a crédito. El stock se descuenta al crear el pedido.">

            <div class="flex items-start gap-6" style="display:flex; align-items:flex-start; gap:1.5rem;">

                {{-- ── Columna izquierda: catálogo ─────────────────────────────── --}}
                <section class="min-w-0 space-y-5" style="flex: 0 0 60%; max-width: 60%; width: 60%;">

                    {{-- Catálogo --}}
                    <div class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="mb-5 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400">Catálogo</p>
                                <h3 class="mt-1 text-lg font-bold text-slate-900">Productos</h3>
                            </div>
                            <div id="so-category-filters" class="flex flex-wrap gap-2"></div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-stretch">
                                <div class="relative min-w-0 flex-1">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-800">
                                        <i class="ri-search-line text-[22px]"></i>
                                    </span>
                                    <input id="so-product-search" type="text"
                                        placeholder="Buscar por código, nombre o categoría"
                                        class="h-14 w-full rounded-[22px] border border-slate-200 bg-slate-50 pl-14 pr-4 text-sm font-medium text-slate-700 outline-none transition focus:border-orange-400 focus:bg-white focus:ring-4 focus:ring-orange-100">
                                </div>
                                <button type="button" id="so-clear-cart-button"
                                    class="inline-flex h-14 shrink-0 items-center justify-center gap-2 rounded-[22px] border border-rose-200 bg-rose-50 px-4 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 sm:px-5">
                                    <i class="ri-delete-bin-6-line"></i>
                                    <span>Limpiar orden</span>
                                </button>
                            </div>
                            <div id="so-products-grid" class="mt-5 grid gap-4"></div>
                        </div>
                    </div>

                </section>

                {{-- ── Columna derecha: resumen + datos del pedido ─────────────── --}}
                <aside class="min-w-0 xl:pr-5" style="flex: 0 0 40%; max-width: 40%; width: 40%;">
                    <div class="sticky top-6 rounded-2xl border border-slate-200 bg-white shadow-xl">

                        {{-- Header --}}
                        <div class="rounded-t-2xl border-b border-slate-800 bg-slate-900 px-4 py-3 text-white"
                            style="background-color: #334155">
                            <h3 class="text-sm font-bold text-white">Resumen del pedido</h3>
                        </div>

                        {{-- Carrito --}}
                        <div id="so-cart-container" class="max-h-[32vh] overflow-y-auto p-4 space-y-2">
                            <p id="so-cart-empty" class="py-6 text-center text-sm text-slate-400">
                                <i class="ri-shopping-cart-line text-2xl"></i><br>Agrega productos al pedido
                            </p>
                        </div>

                        {{-- Totales --}}
                        <div class="border-t border-slate-200 bg-slate-50 px-5 py-4">
                            <div class="space-y-2 rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-base font-bold text-slate-900">Total del pedido</span>
                                    <span id="so-total-display" class="text-3xl font-black" style="color:#f97316;">S/ 0.00</span>
                                </div>
                            </div>
                        </div>

                        {{-- Datos del pedido --}}
                        <div class="border-t border-slate-200 bg-white px-5 py-4 space-y-4">

                            {{-- Cliente --}}
                            <div class="space-y-1.5">
                                <label class="block text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">
                                    Cliente
                                </label>
                                <div class="flex items-start gap-2">
                                    <div class="relative flex-1" id="so-client-selector">
                                        <input id="so-client-autocomplete" type="text"
                                            placeholder="Buscar cliente por nombre o documento"
                                            autocomplete="off"
                                            class="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 pr-10 text-sm font-medium text-slate-700 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                        <button type="button" id="so-client-clear-button"
                                            class="absolute right-3 top-1/2 hidden -translate-y-1/2 text-slate-400 hover:text-slate-700"
                                            title="Limpiar cliente">
                                            <i class="ri-close-line"></i>
                                        </button>
                                        <div id="so-client-options"
                                            class="absolute z-50 mt-1 hidden max-h-56 w-full overflow-auto rounded-2xl border border-slate-200 bg-white shadow-xl">
                                        </div>
                                    </div>
                                    <button type="button" id="so-add-client-button"
                                        class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-white shadow-theme-xs"
                                        style="background:linear-gradient(90deg,#ff7a00,#ff4d00); box-shadow:0 12px 24px rgba(249,115,22,0.24);"
                                        title="Agregar nuevo cliente">
                                        <i class="ri-add-line text-lg"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Fecha de vencimiento --}}
                            <div class="space-y-1.5">
                                <label class="block text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">
                                    Fecha de vencimiento
                                </label>
                                <x-form.date-picker
                                    id="so-due-date"
                                    name="so-due-date"
                                    placeholder="dd/mm/aaaa"
                                    dateFormat="Y-m-d"
                                    :altInput="true"
                                    altFormat="d/m/Y"
                                    locale="es"
                                    :enableTime="false"
                                    inputClass="h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 outline-none transition focus:border-orange-400 focus:ring-4 focus:ring-orange-100"
                                />
                            </div>

                            {{-- Notas --}}
                            <div class="space-y-1.5">
                                <label for="so-notes" class="block text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">
                                    Notas
                                </label>
                                <textarea id="so-notes" rows="2"
                                    placeholder="Observaciones del pedido (opcional)"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 outline-none transition focus:border-orange-400 focus:bg-white focus:ring-4 focus:ring-orange-100"></textarea>
                            </div>

                            {{-- Pago inicial opcional --}}
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-bold text-slate-700">Adelanto (opcional)</p>
                                    <label class="flex cursor-pointer items-center gap-2">
                                        <span class="text-xs text-slate-500">Registrar</span>
                                        <div class="relative">
                                            <input type="checkbox" id="so-initial-payment-toggle" class="sr-only peer">
                                            <div class="h-5 w-9 rounded-full bg-slate-300 peer-checked:bg-orange-500 transition"></div>
                                            <div class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-4"></div>
                                        </div>
                                    </label>
                                </div>

                                <div id="so-initial-payment-fields" class="mt-3 hidden space-y-3">
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Método de pago</label>
                                        <select id="so-payment-method-select"
                                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                            <option value="">Seleccione método</option>
                                            @foreach ($paymentMethods as $pm)
                                                <option value="{{ $pm->id }}">{{ $pm->description }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Monto adelanto</label>
                                        <input type="number" id="so-initial-amount" min="0" step="0.01"
                                            placeholder="0.00"
                                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Referencia</label>
                                        <input type="text" id="so-initial-reference" maxlength="100"
                                            placeholder="Nro. operación (opcional)"
                                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-100">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Botones --}}
                        <div class="rounded-b-2xl border-t border-slate-200 px-5 py-4 grid grid-cols-2 gap-3">
                            <a href="{{ route('admin.sale-orders.index') }}"
                                class="inline-flex h-12 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-100">
                                <i class="ri-arrow-left-line"></i>
                                <span>Volver</span>
                            </a>
                            <button type="button" id="so-submit-button"
                                class="inline-flex h-12 items-center justify-center gap-2 rounded-2xl text-sm font-semibold text-white shadow-theme-xs"
                                style="background:linear-gradient(90deg,#ff7a00,#ff4d00); box-shadow:0 12px 24px rgba(249,115,22,0.24);">
                                <i class="ri-save-line"></i>
                                <span>Crear pedido</span>
                            </button>
                        </div>

                    </div>
                </aside>

            </div>
        </x-common.component-card>
    </div>

    {{-- Modal cliente rápido (reutiliza el partial de ventas) --}}
    @include('sales.partials.quick-client-modal')

    {{-- Toast --}}
    <div id="so-toast"
        class="pointer-events-none fixed right-6 top-24 z-50 translate-x-[140%] opacity-0 transition-all duration-300">
        <div class="flex min-w-[300px] items-start gap-3 rounded-2xl border bg-white px-4 py-4 shadow-2xl" id="so-toast-inner">
            <div id="so-toast-icon" class="flex h-10 w-10 items-center justify-center rounded-2xl text-lg"></div>
            <div class="flex-1">
                <p class="text-sm font-bold text-slate-900" id="so-toast-title">Aviso</p>
                <p class="mt-0.5 text-xs text-slate-500" id="so-toast-message"></p>
            </div>
            <button type="button" onclick="soHideToast()" class="text-slate-400 hover:text-slate-700">
                <i class="ri-close-line"></i>
            </button>
        </div>
    </div>

    <style>
        #sale-order-create-view #so-products-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.85rem;
        }
        @media (max-width: 1279px) {
            #sale-order-create-view .flex.items-start.gap-6[style*="display:flex"] {
                flex-direction: column !important;
            }
            #sale-order-create-view .flex.items-start.gap-6[style*="display:flex"] > section,
            #sale-order-create-view .flex.items-start.gap-6[style*="display:flex"] > aside {
                flex: 0 0 100% !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            #sale-order-create-view aside .sticky { position: static !important; }
        }
        @media (max-width: 767px) {
            #sale-order-create-view #so-products-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        .so-notification-show {
            transform: translateX(0) !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }
        #so-cart-container input[type=number]::-webkit-inner-spin-button,
        #so-cart-container input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>

    <script>
    (function () {
        const products        = @json($products ?? []);
        const productBranches = @json($productBranches ?? []);
        @php
            $peopleForJs = ($people ?? collect())->map(function ($p) {
                $fullName = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                return [
                    'id'       => (int) $p->id,
                    'label'    => $fullName !== '' ? $fullName : ($p->document_number ?: 'Sin nombre'),
                    'document' => ($p->document_number && $p->document_number !== '0')
                                    ? (string) $p->document_number
                                    : '',
                ];
            })->values();
        @endphp
        const people = @json($peopleForJs);
        const defaultClientId        = Number(@json($defaultClientId ?? 0)) || null;
        const quickClientStoreUrl    = @json($quickClientStoreUrl ?? '');
        const reniecApiUrl           = @json(route('api.reniec'));
        const rucApiUrl              = @json(route('api.ruc'));
        const departments            = @json($departments ?? []);
        const provinces              = @json($provinces ?? []);
        const districts              = @json($districts ?? []);
        const branchDepartmentId     = String(@json($selectedDepartmentId ?? ''));
        const branchProvinceId       = String(@json($selectedProvinceId ?? ''));
        const branchDistrictId       = String(@json($selectedDistrictId ?? ''));

        const priceMap = new Map();
        const stockMap = new Map();
        productBranches.forEach(pb => {
            priceMap.set(Number(pb.product_id), Number(pb.price ?? 0));
            stockMap.set(Number(pb.product_id), Number(pb.stock ?? 0));
        });

        let cartItems = [];
        let selectedClientId   = null;
        let selectedClientName = '';
        let clientQuery = '';
        let clientOpen  = false;

        const formatMoney = (v) => `S/ ${Number(v || 0).toFixed(2)}`;

        // ── Productos ─────────────────────────────────────────────────────────
        let categoryFilter = 'General';
        let productSearch  = '';

        function getCategories() {
            const unique = new Set();
            products.forEach(p => unique.add(p.category || 'Sin categoría'));
            return ['General', ...Array.from(unique).sort((a, b) => a.localeCompare(b))];
        }

        function renderCategoryFilters() {
            const container = document.getElementById('so-category-filters');
            if (!container) return;
            container.innerHTML = '';

            getCategories().forEach(cat => {
                const isActive = cat === categoryFilter;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'inline-flex h-12 items-center justify-center rounded-[22px] border px-6 text-sm font-bold transition'
                    + (isActive
                        ? ' border-transparent text-white shadow-theme-xs'
                        : ' border-slate-200 bg-white text-slate-800 hover:border-orange-300 hover:text-orange-700');
                btn.style.background  = isActive ? 'linear-gradient(90deg,#ff7a00,#ff4d00)' : '';
                btn.style.color       = isActive ? '#fff' : '';
                btn.style.boxShadow   = isActive ? '0 12px 24px rgba(249,115,22,0.22)' : '';
                btn.textContent = cat;
                btn.addEventListener('click', () => {
                    categoryFilter = cat;
                    renderCategoryFilters();
                    renderProductsGrid();
                });
                container.appendChild(btn);
            });
        }

        function filteredProducts() {
            const term = productSearch.toLowerCase();
            return products.filter(p => {
                const matchCat  = categoryFilter === 'General' || p.category === categoryFilter;
                const matchTerm = !term ||
                    (p.name || '').toLowerCase().includes(term) ||
                    (p.code || '').toLowerCase().includes(term) ||
                    (p.category || '').toLowerCase().includes(term);
                return matchCat && matchTerm;
            });
        }

        function renderProductsGrid() {
            const grid = document.getElementById('so-products-grid');
            if (!grid) return;
            grid.innerHTML = '';
            const list = filteredProducts().slice(0, 60);

            if (!list.length) {
                grid.innerHTML = '<p class="col-span-4 py-8 text-center text-sm text-slate-400">Sin productos</p>';
                return;
            }

            list.forEach(p => {
                const price    = priceMap.get(p.id) ?? 0;
                const stock    = stockMap.get(p.id) ?? 0;
                const hasImage = !!(p.image && String(p.image).trim() !== '');

                const card = document.createElement('button');
                card.type = 'button';
                card.style.cssText = 'border-radius:30px; border:1px solid #e4e9f1; background:#fff; box-shadow:0 10px 24px rgba(15,23,42,0.05); height:190px; min-height:190px; overflow:hidden; position:relative; width:100%; text-align:center; cursor:pointer; transition:all .2s;';

                card.addEventListener('click', () => soAddProduct(p.id));
                card.addEventListener('mouseenter', () => {
                    const orb = card.querySelector('[data-role="product-orb"]');
                    card.style.transform       = 'translateY(-4px)';
                    card.style.borderColor     = '#ffd1a4';
                    card.style.boxShadow       = '0 18px 34px rgba(249,115,22,0.12)';
                    card.style.backgroundColor = '#fffdfb';
                    if (orb) { orb.style.transform = 'translateY(-1px) scale(1.03)'; orb.style.boxShadow = '0 18px 30px rgba(249,115,22,0.12),0 8px 16px rgba(15,23,42,0.06)'; }
                });
                card.addEventListener('mouseleave', () => {
                    const orb = card.querySelector('[data-role="product-orb"]');
                    card.style.transform       = '';
                    card.style.borderColor     = '#e4e9f1';
                    card.style.boxShadow       = '0 10px 24px rgba(15,23,42,0.05)';
                    card.style.backgroundColor = '#ffffff';
                    if (orb) { orb.style.transform = ''; orb.style.boxShadow = '0 12px 24px rgba(249,115,22,0.08),0 6px 14px rgba(15,23,42,0.04)'; }
                });

                card.innerHTML = `
                    <div class="relative flex h-full w-full flex-col items-center px-3 pb-4 pt-4">
                        <div class="absolute right-3 top-4 z-20 inline-flex min-w-[78px] items-center justify-center rounded-full border border-orange-200 bg-orange-50 px-3 py-1.5 text-center text-[12px] font-bold leading-none text-orange-600"
                            style="box-shadow:0 6px 14px rgba(15,23,42,0.08);">
                            Stock: ${Number(stock).toFixed(0)}
                        </div>
                        <div class="flex h-[102px] w-full items-center justify-center pt-2">
                            <div data-role="product-orb"
                                class="mx-auto flex h-[92px] w-[92px] items-center justify-center overflow-hidden rounded-full bg-white transition-transform duration-200"
                                style="box-shadow:0 12px 24px rgba(249,115,22,0.08),0 6px 14px rgba(15,23,42,0.04);">
                                ${hasImage
                                    ? `<img src="${p.image}" alt="${p.name || 'Producto'}" class="h-16 w-16 object-contain" onerror="this.onerror=null;this.parentElement.innerHTML='<i class=\\'ri-shopping-bag-3-line text-[30px] text-orange-500\\'></i>'">`
                                    : `<i class="ri-shopping-bag-3-line text-[30px] text-orange-500"></i>`}
                            </div>
                        </div>
                        <div class="mt-2 flex h-[50px] w-full items-start justify-center px-1">
                            <h4 class="line-clamp-2 block w-full text-center text-[12px] font-black leading-[1.28] text-slate-900">${p.name || 'Sin nombre'}</h4>
                        </div>
                        <div class="mt-1 flex h-[24px] w-full items-center justify-center">
                            <p class="text-[0.95rem] font-black leading-none tracking-tight" style="color:#f97316;">${formatMoney(price)}</p>
                        </div>
                    </div>`;

                grid.appendChild(card);
            });
        }

        document.getElementById('so-product-search')?.addEventListener('input', e => {
            productSearch = e.target.value;
            renderProductsGrid();
        });

        document.getElementById('so-clear-cart-button')?.addEventListener('click', () => {
            if (!cartItems.length) return;
            cartItems = [];
            renderCart();
        });

        // ── Carrito ───────────────────────────────────────────────────────────
        window.soAddProduct = function (productId) {
            const product = products.find(p => p.id === productId);
            if (!product) return;
            const existing = cartItems.find(i => i.product_id === productId);
            if (existing) {
                existing.quantity += 1;
            } else {
                cartItems.push({
                    product_id: productId,
                    name: product.name,
                    quantity: 1,
                    unit_price: priceMap.get(productId) ?? 0,
                });
            }
            renderCart();
        };

        window.soUpdateQty = function (productId, delta) {
            const item = cartItems.find(i => i.product_id === productId);
            if (!item) return;
            item.quantity = Math.max(0, Math.round(item.quantity + delta));
            if (item.quantity <= 0) {
                cartItems = cartItems.filter(i => i.product_id !== productId);
            }
            renderCart();
        };

        window.soRemoveItem = function (productId) {
            cartItems = cartItems.filter(i => i.product_id !== productId);
            renderCart();
        };

        window.soSetQty = function (productId, value) {
            const item = cartItems.find(i => i.product_id === productId);
            if (!item) return;
            item.quantity = Math.max(1, parseInt(value) || 1);
            renderCart();
        };

        window.soSetPrice = function (productId, value) {
            const item = cartItems.find(i => i.product_id === productId);
            if (!item) return;
            item.unit_price = Math.max(0, parseFloat(value) || 0);
            renderCart();
        };

        function renderCart() {
            const container = document.getElementById('so-cart-container');
            const emptyMsg  = document.getElementById('so-cart-empty');
            if (!container) return;

            if (!cartItems.length) {
                container.innerHTML = '<p id="so-cart-empty" class="py-6 text-center text-sm text-slate-400"><i class="ri-shopping-cart-line text-2xl"></i><br>Agrega productos al pedido</p>';
                updateTotal();
                return;
            }

            container.innerHTML = cartItems.map(item => `
                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                    <div class="mb-2 flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-bold leading-tight text-slate-900 line-clamp-2">${item.name}</p>
                            <p class="mt-0.5 text-xs text-slate-400">Cantidad × precio de venta</p>
                        </div>
                        <button type="button" onclick="soRemoveItem(${item.product_id})"
                            class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500">
                            <i class="ri-close-line text-sm"></i>
                        </button>
                    </div>
                    <div class="flex items-end gap-2">
                        <div>
                            <p class="mb-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">Cant.</p>
                            <div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 px-1" style="height:36px;">
                                <button type="button" onclick="soUpdateQty(${item.product_id}, -1)"
                                    class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-white hover:text-slate-900">
                                    <i class="ri-subtract-line text-sm"></i>
                                </button>
                                <input type="number" value="${item.quantity}" min="1" step="1"
                                    onkeydown="if(event.key==='.'||event.key===',')event.preventDefault();"
                                    oninput="this.value=this.value.replace(/[^0-9]/g,'');"
                                    onchange="soSetQty(${item.product_id}, this.value)"
                                    class="w-10 bg-transparent text-center text-sm font-bold text-slate-900 outline-none"
                                    style="-moz-appearance:textfield;">
                                <button type="button" onclick="soUpdateQty(${item.product_id}, 1)"
                                    class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 hover:bg-white hover:text-slate-900">
                                    <i class="ri-add-line text-sm"></i>
                                </button>
                            </div>
                        </div>
                        <div class="flex flex-1 gap-2">
                            <div class="flex-1">
                                <p class="mb-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">C/U</p>
                                <input type="number" value="${item.unit_price}" min="0" step="0.01"
                                    onchange="soSetPrice(${item.product_id}, this.value)"
                                    class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-700 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100">
                            </div>
                            <div class="flex-1">
                                <p class="mb-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">Total</p>
                                <div class="flex h-9 items-center rounded-xl border border-orange-200 bg-orange-50 px-3">
                                    <span class="text-sm font-bold" style="color:#f97316;">${formatMoney(item.quantity * item.unit_price)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`).join('');

            updateTotal();
        }

        function updateTotal() {
            const total = cartItems.reduce((s, i) => s + i.quantity * i.unit_price, 0);
            const el = document.getElementById('so-total-display');
            if (el) el.textContent = formatMoney(total);
        }

        // ── Cliente autocomplete ──────────────────────────────────────────────
        const clientInput   = document.getElementById('so-client-autocomplete');
        const clientOptions = document.getElementById('so-client-options');
        const clientClear   = document.getElementById('so-client-clear-button');

        function filterPeople(term) {
            const t = term.toLowerCase();
            return people.filter(p =>
                p.label.toLowerCase().includes(t) || p.document.includes(t)
            ).slice(0, 30);
        }

        function renderClientOptions(term) {
            if (!clientOptions) return;
            const list = filterPeople(term);
            if (!list.length) {
                clientOptions.innerHTML = '<p class="px-4 py-3 text-sm text-slate-400">Sin resultados</p>';
            } else {
                clientOptions.innerHTML = list.map(p => `
                    <button type="button" onclick="soSelectClient(${p.id})"
                        class="flex w-full items-center justify-between px-4 py-2.5 text-left text-sm hover:bg-slate-50">
                        <span class="font-semibold text-slate-800">${p.label}</span>
                        ${p.document ? `<span class="text-xs text-slate-400">${p.document}</span>` : ''}
                    </button>`).join('');
            }
            clientOptions.classList.remove('hidden');
        }

        window.soSelectClient = function (id) {
            const p = people.find(x => x.id === id);
            if (!p) return;
            selectedClientId   = p.id;
            selectedClientName = p.label;
            if (clientInput) clientInput.value = p.label;
            if (clientOptions) clientOptions.classList.add('hidden');
            if (clientClear) clientClear.classList.remove('hidden');
        };

        clientInput?.addEventListener('focus', () => {
            renderClientOptions(clientInput.value ?? '');
        });

        clientInput?.addEventListener('input', e => {
            const term = e.target.value;
            if (!term.trim()) {
                selectedClientId = null;
                selectedClientName = '';
                clientClear?.classList.add('hidden');
                renderClientOptions('');
                return;
            }
            renderClientOptions(term);
        });

        clientInput?.addEventListener('blur', () => {
            setTimeout(() => clientOptions?.classList.add('hidden'), 200);
        });

        document.getElementById('so-add-client-button')?.addEventListener('click', () => {
            clientOptions?.classList.add('hidden');
            openQuickClientModal();
        });

        clientClear?.addEventListener('click', () => {
            selectedClientId = null;
            selectedClientName = '';
            if (clientInput) clientInput.value = '';
            clientClear.classList.add('hidden');
            clientOptions?.classList.add('hidden');
        });

        // ── Modal cliente rápido ─────────────────────────────────────────────
        let quickClientLoading = false;

        const normalizeLocationText = (v) => String(v || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();

        const renderSelectOptions = (select, items, selectedValue, placeholder) => {
            if (!select) return;
            select.innerHTML = `<option value="">${placeholder}</option>`;
            items.forEach(item => {
                const opt = document.createElement('option');
                opt.value = String(item.id);
                opt.textContent = String(item.name || '');
                if (String(opt.value) === String(selectedValue || '')) opt.selected = true;
                select.appendChild(opt);
            });
        };

        const getQCElements = () => ({
            modal:          document.getElementById('quick-client-modal'),
            error:          document.getElementById('quick-client-error'),
            personType:     document.getElementById('quick-client-person-type'),
            documentNumber: document.getElementById('quick-client-document-number'),
            firstName:      document.getElementById('quick-client-first-name'),
            lastName:       document.getElementById('quick-client-last-name'),
            lastNameWrap:   document.getElementById('quick-client-last-name-wrap'),
            firstNameLabel: document.getElementById('quick-client-first-name-label'),
            lastNameLabel:  document.getElementById('quick-client-last-name-label'),
            dateLabel:      document.getElementById('quick-client-date-label'),
            date:           document.getElementById('quick-client-date'),
            gender:         document.getElementById('quick-client-gender'),
            genderWrap:     document.getElementById('quick-client-gender-wrap'),
            phone:          document.getElementById('quick-client-phone'),
            email:          document.getElementById('quick-client-email'),
            address:        document.getElementById('quick-client-address'),
            department:     document.getElementById('quick-client-department'),
            province:       document.getElementById('quick-client-province'),
            district:       document.getElementById('quick-client-district'),
            saveButton:     document.getElementById('quick-client-save-button'),
            saveLabel:      document.getElementById('quick-client-save-label'),
            searchButton:   document.getElementById('quick-client-search-button'),
        });

        const renderQCLocationOptions = () => {
            const { department, province, district } = getQCElements();
            const deptId = String(department?.value || '');
            const provId = String(province?.value || '');
            const distId = String(district?.value || '');
            renderSelectOptions(department, departments, deptId, 'Seleccione departamento');
            renderSelectOptions(province, provinces.filter(p => String(p.parent_location_id || '') === deptId), provId, 'Seleccione provincia');
            renderSelectOptions(district, districts.filter(d => String(d.parent_location_id || '') === provId), distId, 'Seleccione distrito');
        };

        const setQCLocation = (deptId, provId, distId) => {
            const { department, province, district } = getQCElements();
            if (department) department.value = String(deptId || '');
            renderQCLocationOptions();
            if (province) province.value = String(provId || '');
            renderQCLocationOptions();
            if (district) district.value = String(distId || '');
            renderQCLocationOptions();
        };

        const syncQCPersonTypeUI = () => {
            const { personType, firstName, lastName, lastNameWrap, firstNameLabel, lastNameLabel, dateLabel, gender, genderWrap } = getQCElements();
            const isRuc = String(personType?.value || 'DNI').toUpperCase() === 'RUC';
            if (firstNameLabel) firstNameLabel.textContent = isRuc ? 'Razon social' : 'Nombres';
            if (firstName) firstName.placeholder = isRuc ? 'Razon social' : 'Nombres / Razon social';
            if (lastNameWrap) lastNameWrap.classList.toggle('hidden', isRuc);
            if (lastName) { lastName.required = !isRuc; if (isRuc) lastName.value = ''; }
            if (dateLabel) dateLabel.textContent = isRuc ? 'Fecha de inscripcion' : 'Fecha de nacimiento';
            if (genderWrap) genderWrap.classList.toggle('hidden', isRuc);
            if (gender && isRuc) gender.value = '';
        };

        const clearQCError = () => {
            const { error } = getQCElements();
            if (error) { error.textContent = ''; error.classList.add('hidden'); }
        };
        const showQCError = (msg) => {
            const { error } = getQCElements();
            if (error) { error.textContent = msg; error.classList.remove('hidden'); }
        };
        const toggleQCLoading = (loading) => {
            quickClientLoading = loading;
            const { saveButton, saveLabel, searchButton } = getQCElements();
            if (saveButton) saveButton.disabled = loading;
            if (searchButton) searchButton.disabled = loading;
            if (saveLabel) saveLabel.textContent = loading ? 'Guardando...' : 'Guardar cliente';
        };

        const resetQCForm = () => {
            const { personType, documentNumber, firstName, lastName, date, gender, phone, email, address } = getQCElements();
            if (personType) personType.value = 'DNI';
            if (documentNumber) documentNumber.value = '';
            if (firstName) firstName.value = '';
            if (lastName) lastName.value = '';
            if (date) date.value = '';
            if (gender) gender.value = '';
            if (phone) phone.value = '';
            if (email) email.value = '';
            if (address) address.value = '';
            clearQCError();
            syncQCPersonTypeUI();
            setQCLocation(branchDepartmentId, branchProvinceId, branchDistrictId);
        };

        const openQuickClientModal = () => {
            const { modal, documentNumber } = getQCElements();
            resetQCForm();
            if (!modal) return;
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            setTimeout(() => documentNumber?.focus(), 40);
        };

        const closeQuickClientModal = () => {
            const { modal } = getQCElements();
            if (!modal) return;
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            clearQCError();
            toggleQCLoading(false);
        };

        const normalizeApiDate = (v) => {
            const raw = String(v || '').trim();
            if (!raw) return '';
            const m1 = raw.match(/^(\d{4}-\d{2}-\d{2})/);
            if (m1) return m1[1];
            const m2 = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
            if (m2) return `${m2[3]}-${m2[2]}-${m2[1]}`;
            return '';
        };

        const fetchQCDocumentData = async () => {
            clearQCError();
            const { personType, documentNumber, firstName, lastName, date, gender, address } = getQCElements();
            const type    = String(personType?.value || 'DNI').toUpperCase();
            const docVal  = String(documentNumber?.value || '').trim();

            if (type === 'DNI') {
                if (!/^\d{8}$/.test(docVal)) { showQCError('Ingrese un DNI válido de 8 dígitos.'); return; }
                try {
                    toggleQCLoading(true);
                    const res  = await fetch(`${reniecApiUrl}?dni=${encodeURIComponent(docVal)}`, { headers: { Accept: 'application/json' } });
                    const data = await res.json();
                    if (!res.ok || data?.status === false) throw new Error(data?.message || 'Error consultando RENIEC.');
                    const fn   = String(data?.first_name ?? data?.nombres ?? '').trim();
                    const apPat = String(data?.apellido_paterno ?? '').trim();
                    const apMat = String(data?.apellido_materno ?? '').trim();
                    const ln   = String(data?.last_name ?? '').trim() || [apPat, apMat].filter(Boolean).join(' ');
                    if (firstName) firstName.value = fn;
                    if (lastName)  lastName.value  = ln;
                    if (date)      date.value       = normalizeApiDate(data?.fecha_nacimiento || '');
                    if (gender)    gender.value     = String(data?.genero || '').trim();
                } catch (e) { showQCError(e?.message || 'Error consultando RENIEC.'); }
                finally { toggleQCLoading(false); }
                return;
            }
            if (type === 'RUC') {
                if (!/^\d{11}$/.test(docVal)) { showQCError('Ingrese un RUC válido de 11 dígitos.'); return; }
                try {
                    toggleQCLoading(true);
                    const res  = await fetch(`${rucApiUrl}?ruc=${encodeURIComponent(docVal)}`, { headers: { Accept: 'application/json' } });
                    const data = await res.json();
                    if (!res.ok || data?.status === false) throw new Error(data?.message || 'Error consultando RUC.');
                    if (firstName) firstName.value = String(data?.legal_name || '').trim();
                    if (lastName)  lastName.value  = '';
                    if (address)   address.value   = String(data?.address || '').trim();
                    if (date)      date.value       = normalizeApiDate(data?.raw?.fecha_inscripcion || data?.raw?.fechaInscripcion || '');
                    // ubicación desde RUC
                    const findDept = (n) => departments.find(d => normalizeLocationText(d.name) === normalizeLocationText(n)) || null;
                    const findProv = (n, dId) => provinces.find(p => String(p.parent_location_id) === String(dId) && normalizeLocationText(p.name) === normalizeLocationText(n)) || null;
                    const findDist = (n, pId) => districts.find(d => String(d.parent_location_id) === String(pId) && normalizeLocationText(d.name) === normalizeLocationText(n)) || null;
                    const dept = findDept(data?.department || '');
                    if (dept) {
                        const prov = findProv(data?.province || '', dept.id);
                        const dist = prov ? findDist(data?.district || '', prov.id) : null;
                        setQCLocation(dept.id, prov ? prov.id : '', dist ? dist.id : '');
                    }
                } catch (e) { showQCError(e?.message || 'Error consultando RUC.'); }
                finally { toggleQCLoading(false); }
                return;
            }
            showQCError('La búsqueda automática solo aplica para DNI o RUC.');
        };

        const saveQuickClient = async () => {
            clearQCError();
            const { personType, documentNumber, firstName, lastName, date, gender, phone, email, address, district } = getQCElements();
            const payload = {
                person_type:     String(personType?.value || 'DNI').trim(),
                document_number: String(documentNumber?.value || '').trim(),
                first_name:      String(firstName?.value || '').trim(),
                last_name:       String(lastName?.value || '').trim(),
                fecha_nacimiento: String(date?.value || '').trim(),
                genero:          String(gender?.value || '').trim(),
                phone:           String(phone?.value || '').trim(),
                email:           String(email?.value || '').trim(),
                address:         String(address?.value || '').trim(),
                location_id:     String(district?.value || '').trim(),
            };
            const isRuc = payload.person_type === 'RUC';
            if (!payload.document_number || !payload.first_name || (!payload.last_name && !isRuc)) {
                showQCError('Completa los campos obligatorios del cliente.');
                return;
            }
            try {
                toggleQCLoading(true);
                const res    = await fetch(String(quickClientStoreUrl || ''), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify(payload),
                });
                const result = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(result?.message || 'Error registrando cliente.');

                // Agregar al array local y seleccionarlo
                const fullName = String(result.label || result.name || `${result.first_name || ''} ${result.last_name || ''}`).trim() || String(result.document || result.document_number || 'Sin nombre');
                const newClient = {
                    id:       Number(result.id),
                    label:    fullName,
                    document: (result.document_number && result.document_number !== '0') ? String(result.document_number) : '',
                };
                const existingIdx = people.findIndex(p => Number(p.id) === newClient.id);
                if (existingIdx >= 0) people[existingIdx] = newClient; else people.unshift(newClient);

                // Seleccionar el cliente recién creado
                selectedClientId   = newClient.id;
                selectedClientName = newClient.label;
                if (clientInput) clientInput.value = newClient.label;
                if (clientClear) clientClear.classList.remove('hidden');
                clientOptions?.classList.add('hidden');

                closeQuickClientModal();
                soShowToast('Cliente registrado correctamente.', 'success');
            } catch (e) {
                showQCError(e?.message || 'Error registrando cliente.');
            } finally {
                toggleQCLoading(false);
            }
        };

        // Listeners del modal
        document.getElementById('quick-client-close-button')?.addEventListener('click', closeQuickClientModal);
        document.getElementById('quick-client-cancel-button')?.addEventListener('click', closeQuickClientModal);
        document.getElementById('quick-client-modal-backdrop')?.addEventListener('click', closeQuickClientModal);
        document.getElementById('quick-client-search-button')?.addEventListener('click', fetchQCDocumentData);
        document.getElementById('quick-client-person-type')?.addEventListener('change', syncQCPersonTypeUI);
        document.getElementById('quick-client-department')?.addEventListener('change', () => {
            const { province, district } = getQCElements();
            if (province) province.value = '';
            if (district) district.value = '';
            renderQCLocationOptions();
        });
        document.getElementById('quick-client-province')?.addEventListener('change', () => {
            const { district } = getQCElements();
            if (district) district.value = '';
            renderQCLocationOptions();
        });
        document.getElementById('quick-client-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            saveQuickClient();
        });

        // ── Pago inicial toggle ───────────────────────────────────────────────
        document.getElementById('so-initial-payment-toggle')?.addEventListener('change', e => {
            const fields = document.getElementById('so-initial-payment-fields');
            if (fields) fields.classList.toggle('hidden', !e.target.checked);
        });

        // ── Toast ─────────────────────────────────────────────────────────────
        let toastTimer = null;
        window.soShowToast = function (message, type = 'success') {
            const toast   = document.getElementById('so-toast');
            const icon    = document.getElementById('so-toast-icon');
            const titleEl = document.getElementById('so-toast-title');
            const msgEl   = document.getElementById('so-toast-message');
            const inner   = document.getElementById('so-toast-inner');
            if (!toast) return;
            if (type === 'success') {
                icon.className  = 'flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 text-lg';
                icon.innerHTML  = '<i class="ri-check-line"></i>';
                inner.className = 'flex min-w-[300px] items-start gap-3 rounded-2xl border border-emerald-200 bg-white px-4 py-4 shadow-2xl';
                if (titleEl) titleEl.textContent = 'Éxito';
            } else {
                icon.className  = 'flex h-10 w-10 items-center justify-center rounded-2xl bg-red-100 text-red-700 text-lg';
                icon.innerHTML  = '<i class="ri-error-warning-line"></i>';
                inner.className = 'flex min-w-[300px] items-start gap-3 rounded-2xl border border-red-200 bg-white px-4 py-4 shadow-2xl';
                if (titleEl) titleEl.textContent = 'Error';
            }
            if (msgEl) msgEl.textContent = message;
            toast.classList.add('so-notification-show');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(soHideToast, 4000);
        };
        window.soHideToast = function () {
            document.getElementById('so-toast')?.classList.remove('so-notification-show');
        };

        // ── Submit ────────────────────────────────────────────────────────────
        document.getElementById('so-submit-button')?.addEventListener('click', async () => {
            if (!cartItems.length) {
                soShowToast('Agrega al menos un producto al pedido.', 'error');
                return;
            }

            const paymentToggle = document.getElementById('so-initial-payment-toggle');
            const withPayment   = paymentToggle?.checked;
            const pmId          = document.getElementById('so-payment-method-select')?.value;
            const pmAmount      = document.getElementById('so-initial-amount')?.value;

            if (withPayment && (!pmId || !pmAmount || parseFloat(pmAmount) <= 0)) {
                soShowToast('Completa los datos del adelanto.', 'error');
                return;
            }

            const btn = document.getElementById('so-submit-button');
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i><span>Guardando...</span>'; }

            const payload = {
                items: cartItems.map(i => ({
                    product_id: i.product_id,
                    quantity:   i.quantity,
                    unit_price: i.unit_price,
                })),
                person_id: selectedClientId,
                due_date:  document.getElementById('so-due-date')?.value || null,
                notes:     document.getElementById('so-notes')?.value    || null,
                currency:  'PEN',
            };

            if (withPayment) {
                payload.initial_payment = {
                    amount:            parseFloat(pmAmount),
                    payment_method_id: parseInt(pmId),
                    reference:         document.getElementById('so-initial-reference')?.value || null,
                };
            }

            try {
                const res = await fetch(@json(route('admin.sale-orders.store')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Error al crear el pedido.');
                }

                soShowToast('Pedido creado correctamente.', 'success');
                setTimeout(() => {
                    window.location.href = @json(route('admin.sale-orders.index'));
                }, 1200);

            } catch (err) {
                soShowToast(err.message || 'Error inesperado.', 'error');
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ri-save-line"></i><span>Crear pedido</span>'; }
            }
        });

        // ── Init ──────────────────────────────────────────────────────────────
        renderCategoryFilters();
        renderProductsGrid();

        // Pre-seleccionar cliente por defecto (CLIENTES VARIOS)
        if (defaultClientId) {
            const def = people.find(p => p.id === defaultClientId);
            if (def) {
                selectedClientId   = def.id;
                selectedClientName = def.label;
                if (clientInput) clientInput.value = def.label;
                if (clientClear) clientClear.classList.remove('hidden');
            }
        }

    })();
    </script>
@endsection
