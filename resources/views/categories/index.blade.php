@extends('layouts.app')

@section('content')
    <div x-data="{ importModalOpen: false }">
        @php
            use Illuminate\Support\Facades\Route;

            $viewId = request('view_id');
            $operacionesCollection = collect($operaciones ?? []);
            $topOperations = $operacionesCollection->where('type', 'T');
            $rowOperations = $operacionesCollection->where('type', 'R');

            $resolveActionUrl = function ($action, $category = null, $operation = null) use ($viewId) {
                if (!$action) {
                    return '#';
                }

                if (str_starts_with($action, '/') || str_starts_with($action, 'http')) {
                    $url = $action;
                } else {
                    $routeCandidates = [$action];
                    if (!str_starts_with($action, 'admin.')) {
                        $routeCandidates[] = 'admin.' . $action;
                    }
                    $routeCandidates = array_merge(
                        $routeCandidates,
                        array_map(fn($name) => $name . '.index', $routeCandidates),
                    );

                    $routeName = null;
                    foreach ($routeCandidates as $candidate) {
                        if (Route::has($candidate)) {
                            $routeName = $candidate;
                            break;
                        }
                    }

                    if ($routeName) {
                        try {
                            $url = $category ? route($routeName, $category) : route($routeName);
                        } catch (\Exception $e) {
                            $url = '#';
                        }
                    } else {
                        $url = '#';
                    }
                }

                $targetViewId = $viewId;
                if ($operation && !empty($operation->view_id_action)) {
                    $targetViewId = $operation->view_id_action;
                }

                if ($targetViewId && $url !== '#') {
                    $separator = str_contains($url, '?') ? '&' : '?';
                    $url .= $separator . 'view_id=' . urlencode($targetViewId);
                }

                return $url;
            };

            $resolveTextColor = function ($operation) {
                $action = $operation->action ?? '';
                if (str_contains($action, 'categories.create')) {
                    return '#111827';
                }
                return '#FFFFFF';
            };
        @endphp
        <x-common.page-breadcrumb pageTitle="Categorias" />

        <x-common.component-card title="Categorias" desc="Gestiona las categorias de productos.">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    @if ($viewId)
                        <input type="hidden" name="view_id" value="{{ $viewId }}">
                    @endif
                    <div class="w-36 flex-none">
                        <select name="per_page"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            onchange="this.form.submit()">
                            @foreach ([10, 20, 50, 100] as $size)
                                <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }} /
                                    página</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="relative flex-1">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="ri-search-line"></i>
                        </span>
                        <input type="text" name="search" value="{{ $search }}"
                            placeholder="Buscar por descripcion o abreviatura"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-12 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button size="md" variant="primary" type="submit"
                            class="flex-1 sm:flex-none h-11 px-6 shadow-sm hover:shadow-md transition-all duration-200 active:scale-95 color_button">
                            <i class="ri-search-line text-gray-100"></i>
                            <span class="font-medium text-gray-100">Buscar</span>
                        </x-ui.button>
                        <x-ui.link-button size="md" variant="outline"
                            href="{{ route('admin.categories.index', $viewId ? ['view_id' => $viewId] : []) }}"
                            class="flex-1 sm:flex-none h-11 px-6 border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200">
                            <i class="ri-refresh-line"></i>
                            <span class="font-medium">Limpiar</span>
                        </x-ui.link-button>
                    </div>
                </form>

                <div class="flex items-center gap-2">
                    {{-- Botón Importar Excel --}}
                    <x-ui.button size="md" variant="outline" type="button"
                        class="h-11 px-4 border-gray-300 text-gray-700 hover:bg-gray-50 transition-all"
                        @click="importModalOpen = true">
                        <i class="ri-file-excel-2-line text-green-600"></i>
                        <span>Importar Excel</span>
                    </x-ui.button>

                    @foreach ($topOperations as $operation)
                        @php
                            $topTextColor = $resolveTextColor($operation);
                            $topColor = $operation->color ?: '#3B82F6';
                            $topStyle = "background-color: {$topColor}; color: {$topTextColor};";
                            $topActionUrl = $resolveActionUrl($operation->action ?? '', null, $operation);
                            $isCreate = str_contains($operation->action ?? '', 'categories.create');
                        @endphp
                        @if ($isCreate)
                            <x-ui.button size="md" variant="primary" type="button" style="{{ $topStyle }}"
                                @click="$dispatch('open-category-modal')">
                                <i class="{{ $operation->icon }}"></i>
                                <span>{{ $operation->name }}</span>
                            </x-ui.button>
                        @else
                            <x-ui.link-button size="md" variant="primary" style="{{ $topStyle }}"
                                href="{{ $topActionUrl }}">
                                <i class="{{ $operation->icon }}"></i>
                                <span>{{ $operation->name }}</span>
                            </x-ui.link-button>
                        @endif
                    @endforeach
                    @if ($topOperations->isEmpty())
                        <x-ui.button size="md" variant="primary" type="button"
                            style=" background-color: #12f00e; color: #111827;" @click="$dispatch('open-category-modal')">
                            <i class="ri-add-line"></i>
                            <span>Nueva categoria</span>
                        </x-ui.button>
                    @endif
                </div>
            </div>

            <div
                class="table-responsive mt-4 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden dark:border-gray-800 dark:bg-white/[0.03]">
                <table class="w-full min-w-[1100px]">
                    <thead>
                        <tr class="text-white">
                            <th class="px-5 py-3 text-left sm:px-6 first:rounded-tl-xl transition-colors">
                                <p class="font-semibold text-white text-theme-xs">Imagen</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-white text-theme-xs">Descripción</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-white text-theme-xs uppercase">Abreviatura</p>
                            </th>
                            <th class="px-5 py-3 text-center sm:px-6 last:rounded-tr-xl transition-colors">
                                <p class="font-semibold text-white text-theme-xs">Acciones</p>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr
                                class="group/row border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5 relative hover:z-[60]">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if ($category->image)
                                            <img class="h-12 w-12 object-cover rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm"
                                                src="{{ asset('storage/' . $category->image) }}"
                                                alt="{{ $category->description }}">
                                        @else
                                            <div
                                                class="h-12 w-12 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center border border-dashed border-gray-300 dark:border-gray-600">
                                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                                        {{ $category->description }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $category->abbreviation }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center justify-center gap-2">
                                        @if ($rowOperations->isNotEmpty())
                                            @foreach ($rowOperations as $operation)
                                                @php
                                                    $action = $operation->action ?? '';
                                                    $isDelete = str_contains($action, 'destroy');
                                                    $actionUrl = $resolveActionUrl($action, $category, $operation);
                                                    $textColor = $resolveTextColor($operation);
                                                    $buttonColor = $operation->color ?: '#3B82F6';
                                                    $buttonStyle = "background-color: {$buttonColor}; color: {$textColor};";
                                                    $variant = $isDelete
                                                        ? 'eliminate'
                                                        : (str_contains($action, 'edit')
                                                            ? 'edit'
                                                            : 'primary');
                                                @endphp
                                                @if ($isDelete)
                                                    <form method="POST" action="{{ $actionUrl }}"
                                                        class="relative group js-swal-delete"
                                                        data-swal-title="Eliminar categoria?"
                                                        data-swal-text="Se eliminara {{ $category->description }}. Esta accion no se puede deshacer."
                                                        data-swal-confirm="Si, eliminar" data-swal-cancel="Cancelar"
                                                        data-swal-confirm-color="#ef4444" data-swal-cancel-color="#6b7280">
                                                        @csrf
                                                        @method('DELETE')
                                                        @if ($viewId)
                                                            <input type="hidden" name="view_id"
                                                                value="{{ $viewId }}">
                                                        @endif
                                                        <x-ui.button size="icon" variant="{{ $variant }}"
                                                            type="submit" className="rounded-xl"
                                                            style="{{ $buttonStyle }}"
                                                            aria-label="{{ $operation->name }}">
                                                            <i class="{{ $operation->icon }}"></i>
                                                        </x-ui.button>
                                                        <span
                                                            class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-3 whitespace-nowrap rounded-md bg-gray-900 px-2.5 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-[100] shadow-xl">
                                                            {{ $operation->name }}
                                                            <span
                                                                class="absolute top-full left-1/2 -ml-1 border-4 border-transparent border-t-gray-900"></span>
                                                        </span>
                                                    </form>
                                                @else
                                                    <div class="relative group">
                                                        <x-ui.link-button size="icon" variant="{{ $variant }}"
                                                            href="{{ $actionUrl }}" className="rounded-xl"
                                                            style="{{ $buttonStyle }}"
                                                            aria-label="{{ $operation->name }}">
                                                            <i class="{{ $operation->icon }}"></i>
                                                        </x-ui.link-button>
                                                        <span
                                                            class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-3 whitespace-nowrap rounded-md bg-gray-900 px-2.5 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-[100] shadow-xl">
                                                            {{ $operation->name }}
                                                            <span
                                                                class="absolute top-full left-1/2 -ml-1 border-4 border-transparent border-t-gray-900"></span>
                                                        </span>
                                                    </div>
                                                @endif
                                            @endforeach
                                        @else
                                            <div class="relative group">
                                                <x-ui.link-button size="icon" variant="edit"
                                                    href="{{ route('admin.categories.edit', array_merge([$category], $viewId ? ['view_id' => $viewId] : [])) }}"
                                                    className="bg-warning-500 text-white hover:bg-warning-600 ring-0 rounded-xl"
                                                    style="background-color: #FBBF24; color: #111827;"
                                                    aria-label="Editar">
                                                    <i class="ri-pencil-line"></i>
                                                </x-ui.link-button>
                                                <span
                                                    class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 whitespace-nowrap rounded-md bg-gray-900 px-2.5 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50 shadow-xl">
                                                    Editar
                                                    <span
                                                        class="absolute top-full left-1/2 -ml-1 border-4 border-transparent border-t-gray-900"></span>
                                                </span>
                                            </div>
                                            <form method="POST"
                                                action="{{ route('admin.categories.destroy', array_merge([$category], $viewId ? ['view_id' => $viewId] : [])) }}"
                                                class="relative group js-swal-delete"
                                                data-swal-title="Eliminar categoria?"
                                                data-swal-text="Se eliminara {{ $category->description }}. Esta accion no se puede deshacer."
                                                data-swal-confirm="Si, eliminar" data-swal-cancel="Cancelar"
                                                data-swal-confirm-color="#ef4444" data-swal-cancel-color="#6b7280">
                                                @csrf
                                                @method('DELETE')
                                                @if ($viewId)
                                                    <input type="hidden" name="view_id" value="{{ $viewId }}">
                                                @endif
                                                <x-ui.button size="icon" variant="eliminate" type="submit"
                                                    className="bg-error-500 text-white hover:bg-error-600 ring-0 rounded-xl"
                                                    style="background-color: #EF4444; color: #FFFFFF;"
                                                    aria-label="Eliminar">
                                                    <i class="ri-delete-bin-line"></i>
                                                </x-ui.button>
                                                <span
                                                    class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 whitespace-nowrap rounded-md bg-gray-900 px-2.5 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50 shadow-xl">
                                                    Eliminar
                                                    <span
                                                        class="absolute top-full left-1/2 -ml-1 border-4 border-transparent border-t-gray-900"></span>
                                                </span>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12">
                                    <div class="flex flex-col items-center gap-3 text-center text-sm text-gray-500">
                                        <div
                                            class="rounded-full bg-gray-100 p-3 text-gray-400 dark:bg-gray-800 dark:text-gray-300">
                                            <i class="ri-folder-line"></i>
                                        </div>
                                        <p class="text-base font-semibold text-gray-700 dark:text-gray-200">No hay
                                            categorias registradas.</p>
                                        <p class="text-gray-500">Crea la primera categoria para comenzar.</p>
                                        <x-ui.button size="sm" variant="primary" type="button"
                                            @click="$dispatch('open-category-modal')">
                                            <i class="ri-add-line"></i>
                                            <span>Registrar categoria</span>
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($categories->count() > 0)
                    @endif
                </table>
            </div>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-gray-500">
                    Mostrando
                    <span
                        class="font-semibold text-gray-700 dark:text-gray-200">{{ $categories->firstItem() ?? 0 }}</span>
                    -
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $categories->lastItem() ?? 0 }}</span>
                    de
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $categories->total() }}</span>
                </div>
                <div class="flex-none pagination-simple">
                    {{ $categories->links('vendor.pagination.forced') }}
                </div>
            </div>
        </x-common.component-card>

        {{-- Flash messages --}}
        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
                class="mt-4 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                <i class="ri-checkbox-circle-line mt-0.5 text-green-500 text-base flex-shrink-0"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 8000)"
                class="mt-4 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <i class="ri-error-warning-line mt-0.5 text-red-500 text-base flex-shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Modal Importar Excel --}}
        <template x-teleport="body">
        <div
            x-show="importModalOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[99999] flex items-center justify-center backdrop-blur-sm bg-black/30 px-4"
            style="display:none"
            @click.self="importModalOpen = false"
        >
            <div
                x-show="importModalOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="w-full max-w-lg rounded-2xl bg-white shadow-2xl"
            >
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-50">
                            <i class="ri-file-excel-2-line text-xl text-green-600"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-800">Importar categorías desde Excel</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Se agregarán a la sucursal actual</p>
                        </div>
                    </div>
                    <button type="button" @click="importModalOpen = false"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-700 transition-colors">
                        <i class="ri-close-line text-lg"></i>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-6 py-5">
                    {{-- Formato esperado --}}
                    <div class="mb-5 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-700 space-y-1">
                        <p class="font-semibold text-blue-800 mb-1">Formato esperado del archivo:</p>
                        <div class="overflow-x-auto">
                            <table class="text-xs border-collapse">
                                <thead>
                                    <tr class="text-blue-800">
                                        <th class="border border-blue-200 bg-blue-100 px-3 py-1 font-bold">Columna A</th>
                                        <th class="border border-blue-200 bg-blue-100 px-3 py-1 font-bold">Columna B <span class="font-normal">(opcional)</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="text-blue-600">
                                        <td class="border border-blue-200 px-3 py-1">Descripción</td>
                                        <td class="border border-blue-200 px-3 py-1">Abreviatura</td>
                                    </tr>
                                    <tr class="text-blue-600">
                                        <td class="border border-blue-200 px-3 py-1">Tequila</td>
                                        <td class="border border-blue-200 px-3 py-1">TEQ</td>
                                    </tr>
                                    <tr class="text-blue-600">
                                        <td class="border border-blue-200 px-3 py-1">Ron</td>
                                        <td class="border border-blue-200 px-3 py-1">RON</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-blue-600 mt-2">Si la columna B está vacía, la abreviatura se genera automáticamente con los primeros 5 caracteres de la descripción. Las categorías que ya existan en esta sucursal serán omitidas.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.categories.import-excel') }}"
                        enctype="multipart/form-data" class="space-y-4"
                        x-data="{ fileName: '', isDragging: false }">
                        @csrf
                        @if ($viewId)
                            <input type="hidden" name="view_id" value="{{ $viewId }}">
                        @endif

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">
                                Archivo Excel <span class="text-red-500">*</span>
                            </label>
                            <div
                                class="relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-4 py-8 transition-all duration-200"
                                :class="isDragging
                                    ? 'border-green-400 bg-green-50 scale-[1.01]'
                                    : (fileName ? 'border-green-300 bg-green-50' : 'border-gray-200 bg-gray-50 hover:border-green-300 hover:bg-green-50')"
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="
                                    isDragging = false;
                                    const f = $event.dataTransfer.files[0];
                                    if (f) {
                                        fileName = f.name;
                                        const dt = new DataTransfer();
                                        dt.items.add(f);
                                        $refs.fileInput.files = dt.files;
                                    }
                                "
                                @click="$refs.fileInput.click()"
                            >
                                <i class="ri-upload-cloud-2-line text-3xl transition-colors"
                                    :class="isDragging ? 'text-green-500' : (fileName ? 'text-green-400' : 'text-gray-300')"></i>
                                <div class="text-center pointer-events-none">
                                    <p class="text-sm font-medium text-gray-700"
                                        x-text="fileName || (isDragging ? 'Suelta el archivo aquí' : 'Arrastra tu archivo o haz clic para seleccionar')"></p>
                                    <p class="mt-0.5 text-xs text-gray-400">.xlsx, .xls, .csv &bull; Máximo 5 MB</p>
                                </div>
                                <input type="file" name="file" accept=".xlsx,.xls,.csv" class="sr-only"
                                    x-ref="fileInput"
                                    @change="fileName = $event.target.files[0]?.name || ''" required>
                            </div>
                            @error('file')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-3 pt-1">
                            <button type="submit"
                                class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-green-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700 active:scale-95 transition-all">
                                <i class="ri-upload-2-line"></i>
                                Importar
                            </button>
                            <button type="button" @click="importModalOpen = false"
                                class="flex items-center justify-center gap-2 rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 active:scale-95 transition-all">
                                <i class="ri-close-line"></i>
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </template>

        <x-ui.modal x-data="{ open: false }" @open-category-modal.window="open = true"
            @close-category-modal.window="open = false" :isOpen="false" :showCloseButton="false" class="max-w-3xl">
            <div class="p-6 sm:p-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/10">
                            <i class="ri-folder-line text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Registrar categoria</h3>
                            <p class="mt-1 text-sm text-gray-500">Ingresa la informacion de la categoria.</p>
                        </div>
                    </div>
                    <button type="button" @click="open = false"
                        class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                        aria-label="Cerrar">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>

                @if ($errors->any())
                    <div
                        class="mb-5 rounded-lg border border-error-500/30 bg-error-500/10 px-4 py-3 text-sm text-error-700 dark:border-error-500/50 dark:bg-error-500/20 dark:text-error-300">
                        <p class="font-semibold mb-2">Revisa los campos</p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-6"
                    enctype="multipart/form-data">
                    @csrf
                    @if ($viewId)
                        <input type="hidden" name="view_id" value="{{ $viewId }}">
                    @endif

                    @include('categories._form', ['category' => null])

                    <div class="flex flex-wrap gap-3">
                        <x-ui.button type="submit" size="md" variant="primary">
                            <i class="ri-save-line"></i>
                            <span>Guardar</span>
                        </x-ui.button>
                        <x-ui.button type="button" size="md" variant="outline" @click="open = false">
                            <i class="ri-close-line"></i>
                            <span>Cancelar</span>
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </x-ui.modal>
    </div>
@endsection
