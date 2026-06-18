@extends('layouts.app')

@section('content')
    <div>
        <x-common.page-breadcrumb pageTitle="Editar Promo" />

        <x-common.component-card title="Editar Promo" desc="Modifica los datos de la promo.">
            <form method="POST" action="{{ route('admin.promos.update', $promo) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')
                @if (request('view_id'))
                    <input type="hidden" name="view_id" value="{{ request('view_id') }}">
                @endif

                @include('promos._form', ['promo' => $promo])

                <div class="flex gap-3">
                    <x-ui.button type="submit" size="md" variant="primary">
                        <i class="ri-save-line"></i> Actualizar
                    </x-ui.button>
                    <x-ui.link-button size="md" variant="outline" href="{{ route('admin.promos.index', request('view_id') ? ['view_id' => request('view_id')] : []) }}">
                        <i class="ri-close-line"></i> Cancelar
                    </x-ui.link-button>
                </div>
            </form>
        </x-common.component-card>
    </div>
@endsection
