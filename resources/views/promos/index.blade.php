@extends('layouts.app')

@section('content')
    <div x-data="{ createModalOpen: {{ $errors->any() && !old('_method') ? 'true' : 'false' }} }">
        <x-common.page-breadcrumb pageTitle="Promos" />

        <x-common.component-card title="Promos" desc="Gestiona las promociones.">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    @if (request('view_id'))
                        <input type="hidden" name="view_id" value="{{ request('view_id') }}">
                    @endif
                    <div class="relative flex-1">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="ri-search-line"></i>
                        </span>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por nombre"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-12 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button size="md" variant="primary" type="submit"
                            class="flex-1 sm:flex-none h-11 px-6 shadow-sm hover:shadow-md transition-all duration-200 active:scale-95 color_button">
                            <i class="ri-search-line text-gray-100"></i>
                            <span class="font-medium text-gray-100">Buscar</span>
                        </x-ui.button>
                        <x-ui.link-button size="md" variant="outline" href="{{ route('admin.promos.index', request('view_id') ? ['view_id' => request('view_id')] : []) }}"
                            class="flex-1 sm:flex-none h-11 px-6 border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200">
                            <i class="ri-refresh-line"></i>
                            <span class="font-medium">Limpiar</span>
                        </x-ui.link-button>
                    </div>
                </form>

                <div class="flex items-center gap-2">
                    <x-ui.button size="md" variant="primary" style="background-color: #12f00e; color: #111827;"
                        @click="createModalOpen = true" type="button">
                        <i class="ri-add-line"></i>
                        <span>Nueva promo</span>
                    </x-ui.button>
                </div>
            </div>

            <div class="table-responsive mt-4 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden dark:border-gray-800 dark:bg-white/[0.03]">
                <table class="w-full min-w-[1100px]">
                    <thead>
                        <tr class="text-white">
                            <th class="px-5 py-3 text-left sm:px-6"><p class="font-semibold text-white text-theme-xs">Imagen</p></th>
                            <th class="px-5 py-3 text-left sm:px-6"><p class="font-semibold text-white text-theme-xs">Nombre</p></th>
                            <th class="px-5 py-3 text-left sm:px-6"><p class="font-semibold text-white text-theme-xs">Precio</p></th>
                            <th class="px-5 py-3 text-left sm:px-6"><p class="font-semibold text-white text-theme-xs">Estado</p></th>
                            <th class="px-5 py-3 text-left sm:px-6"><p class="font-semibold text-white text-theme-xs">Vigencia</p></th>
                            <th class="px-5 py-3 text-center sm:px-6"><p class="font-semibold text-white text-theme-xs">Acciones</p></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($promos as $promo)
                            <tr class="group/row border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5 relative hover:z-[60]">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($promo->image)
                                        <img class="h-12 w-12 object-cover rounded-lg border" src="{{ asset('storage/' . $promo->image) }}" alt="">
                                    @else
                                        <div class="h-12 w-12 rounded-lg bg-gray-100 flex items-center justify-center border border-dashed text-gray-400">
                                            <i class="ri-image-line text-xl"></i>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4"><p class="font-medium text-gray-800 text-theme-sm">{{ $promo->name }}</p></td>
                                <td class="px-5 py-4"><p class="text-gray-500 text-theme-sm">{{ $promo->price }}</p></td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $promo->status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $promo->status ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4"><p class="text-gray-500 text-theme-sm">{{ $promo->end_date ? $promo->end_date->format('d/m/Y') : 'Ilimitado' }}</p></td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <x-ui.link-button size="icon" variant="edit" href="{{ route('admin.promos.edit', array_merge([$promo], request('view_id') ? ['view_id' => request('view_id')] : [])) }}" className="bg-warning-500 text-white hover:bg-warning-600 rounded-xl" style="background-color: #FBBF24;">
                                            <i class="ri-pencil-line"></i>
                                        </x-ui.link-button>
                                        <form method="POST" action="{{ route('admin.promos.destroy', array_merge([$promo], request('view_id') ? ['view_id' => request('view_id')] : [])) }}" class="relative group js-swal-delete" data-swal-title="Eliminar promo?" data-swal-confirm="Si, eliminar">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button size="icon" variant="eliminate" type="submit" className="bg-error-500 text-white hover:bg-error-600 rounded-xl" style="background-color: #EF4444;">
                                                <i class="ri-delete-bin-line"></i>
                                            </x-ui.button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">No hay promos registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $promos->links('vendor.pagination.forced') }}
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

        {{-- Modal Crear Promo --}}
        <template x-teleport="body">
            <div x-show="createModalOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[99999] flex items-center justify-center backdrop-blur-sm bg-black/30 px-4"
                style="display:none"
                @click.self="createModalOpen = false">
                <div x-show="createModalOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="w-full max-w-4xl rounded-2xl bg-white shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                    
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5 bg-white">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50">
                                <i class="ri-add-line text-xl text-brand-600"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-800">Nueva Promo</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Ingresa los datos para la nueva promo</p>
                            </div>
                        </div>
                        <button type="button" @click="createModalOpen = false"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-700 transition-colors">
                            <i class="ri-close-line text-lg"></i>
                        </button>
                    </div>

                    <div class="px-6 py-5 overflow-y-auto">
                        <form id="createPromoForm" method="POST" action="{{ route('admin.promos.store') }}" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            @if (request('view_id'))
                                <input type="hidden" name="view_id" value="{{ request('view_id') }}">
                            @endif

                            @include('promos._form', ['promo' => null])
                        </form>
                    </div>
                    
                    <div class="border-t border-gray-100 px-6 py-4 bg-gray-50 flex gap-3 justify-end">
                        <x-ui.button type="button" @click="createModalOpen = false" size="md" variant="outline">
                            Cancelar
                        </x-ui.button>
                        <x-ui.button type="submit" form="createPromoForm" size="md" variant="primary">
                            <i class="ri-save-line"></i> Guardar
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endsection
