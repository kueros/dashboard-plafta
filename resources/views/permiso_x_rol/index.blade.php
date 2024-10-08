<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Permisos x Rol') }}
        </h2>
    </x-slot>

    <div style="background-image: url('/build/assets/images/dashboard_bg.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; padding-top: 3rem; padding-bottom: 3rem;">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    Opciones
                </div>
            </div>

            <div class="p-12 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="table-responsive">
                    <form action="{{ route('permisos_x_rol.update') }}" method="POST">
                        @csrf
                        <table id="example2" class="cell-border" style="width:100%">
                            <thead class="thead">
                                <tr>
                                    <th>Permisos</th>
                                    @foreach ($rols as $rol)
                                        <th>{{ $rol->nombre }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($permisos as $permiso)
                                    <tr>
                                        <td>{{ $permiso->nombre }}</td>
                                        @foreach ($rols as $rol)
                                            <td>
                                                <input type="checkbox" name="permisos[{{ $rol->rol_id }}][{{ $permiso->id }}]"
                                                       value="1"
                                                       @if($rol->permisos && $rol->permisos->pluck('id')->contains($permiso->id)) checked @endif>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button type="submit" class="btn btn-primary mt-4">Guardar cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>