<section>
	<header>
		<h2 class="text-lg font-medium text-gray-900">
			{{ __('Editar Tipo de Transacción') }}
		</h2>

		<p class="mt-1 text-sm text-gray-600">
			{{ __('Edición de tipos de transacciones.') }}
		</p>
	</header>
	@php
	#dd($tipos_transacciones);
	@endphp
	<form method="post" action="{{ route('tipos_transacciones.update', $tipos_transacciones->id) }}" class="mt-6 space-y-6">
		@csrf
		@method('patch')

		<div>
			<x-input-label for="nombre" :value="__('Nombre')" />
			<x-text-input id="nombre" value="{{ $tipos_transacciones->nombre }}" name="nombre" type="text" class="mt-1 block w-full" autocomplete="nombre" />
			<x-input-error :messages="$errors->get('nombre')" class="mt-2" />
		</div>

		<div>
			<x-input-label for="descripcion" :value="__('Descripción')" />
			<x-text-input id="descripcion" value="{{ $tipos_transacciones->descripcion }}" name="descripcion" type="text" class="mt-1 block w-full" autocomplete="descripcion" />
			<x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
		</div>


		<div class="flex items-center gap-4">
			<x-primary-button>{{ __('Save') }}</x-primary-button>

			@if (session('status') === 'profile-updated')
			<p
				x-data="{ show: true }"
				x-show="show"
				x-transition
				x-init="setTimeout(() => show = false, 2000)"
				class="text-sm text-gray-600">{{ __('Saved.') }}</p>
			@endif
		</div>
	</form>
</section>