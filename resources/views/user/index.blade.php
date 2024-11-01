<x-app-layout title="Usuarios" :breadcrumbs="[['title' => 'Inicio', 'url' => route('dashboard')]]">
	<x-slot name="header">
		<h2 class="font-semibold text-xl text-gray-800 leading-tight">
			{{ __('Usuarios') }}
		</h2>
	</x-slot>
	@php
		$user = Auth::user()->username;
		$email = Auth::user()->email;
		$permiso_agregar_usuarios = tiene_permiso('add_usr');
		$permiso_editar_usuarios = tiene_permiso('edit_usr');
		$permiso_deshabilitar_permisos = tiene_permiso('enable_usr');
		$permiso_blanquear_password = tiene_permiso('clean_pass');
		$permiso_borrar_usuarios = tiene_permiso('del_usr');
		$permiso_importar_usuarios = tiene_permiso('import_usr');
		#dd($configurar_claves);
	@endphp
	<script type="text/javascript">

		jQuery(document).ready(function($){
			table = $('#usuarios-table').DataTable({
			language: traduccion_datatable,
            dom: 'Bfrtip',
            buttons: [
                {"extend": 'pdf', "text":'Export',"className": 'btn btn-danger', "orientation": 'landscape', title: 'Usuarios'},
                {"extend": 'copy', "text":'Export',"className": 'btn btn-primary', title: 'Usuarios'},
                {"extend": 'excel', "text":'Export',"className": 'btn btn-success', title: 'Usuarios'},
                {"extend": 'print', "text":'Export',"className": 'btn btn-secondary', title: 'Usuarios'}
            ],
            initComplete: function () {
                $('.buttons-copy').html('<i class="fas fa-copy"></i> Portapapeles');
                $('.buttons-pdf').html('<i class="fas fa-file-pdf"></i> PDF');
                $('.buttons-excel').html('<i class="fas fa-file-excel"></i> Excel');
                $('.buttons-print').html('<span class="glyphicon glyphicon-print" data-toggle="tooltip" title="Exportar a PDF"/> Imprimir');
            }
			});
		});

		function limpiar_campos_requeridos(form_id){
			/*$($('#'+form_id).prop('elements')).each(function(){
				if($(this).prop("required") && !$(this).prop("disabled")){
					$(this).removeClass('field-required');
				}
			});*/
		}

		function reload_table()
		{
			table.ajax.reload(null,false);
		}

		function add_usuario()
		{
			save_method = 'add';
			limpiar_campos_requeridos('form');
			$('#tabla_roles tbody').html('');
			$('#form')[0].reset();
			$('.form-group').removeClass('has-error');
			$('.help-block').empty();
			roles_usuario = new Array();
			$('.modal-title').text('Agregar usuario');
			$('#accion').val('add');
			$('#password').prop("required",true);
			$('#repassword').prop("required",true);
			$('#modal_form').modal('show');
		}

		function edit_usuario(id)
		{
			save_method = 'update';
			limpiar_campos_requeridos('form');
			$('#form')[0].reset();
			$('.form-group').removeClass('has-error');
			$('.help-block').empty();
			$('#accion').val('edit');
			$('#tabla_roles tbody').html('');
			$('#password').prop("required",false);
			$('#repassword').prop("required",false);
			roles_usuario = new Array();

			$.ajax({
				url : "<?php echo base_url('usuarios/ajax_edit/')?>/" + id,
				type: "GET",
				dataType: "JSON",
				success: function(data)
				{
					$('[name="id"]').val(data.id);
					$('[name="nombre"]').val(data.nombre);
					$('[name="apellido"]').val(data.apellido);
					$('[name="username"]').val(data.username);
					$('[name="email"]').val(data.email);
					$('[name=area]').val(data.area_id);
					$('[name=enabled]').val(data.enabled);
					$('[name=habilita_api]').val(data.habilita_api);

					<?php
					foreach($campos_usuarios as $campo){
						if($campo->nombre_campo == 'password'){
							continue;
						}
						if($campo->campo_base == 0){
							?>
							$('[name=<?=$campo->nombre_campo?>]').val(data.<?=$campo->nombre_campo?>);
							<?php
						}
					}
					?>

					data.roles_asignados.forEach(function(rol){
						roles_usuario.push(rol.rol_id);

						row_roles = '';
						row_roles += '<tr id="tr_rol_'+rol.rol_id+'">';
						row_roles += '<td>'+rol.rol_nombre+'</td>';
						row_roles += '<td><a class="btn btn-danger" onclick="eliminar_rol_usuario('+rol.rol_id+')"><span class="glyphicon glyphicon-trash"></span></a></td>';
						row_roles += '</tr>';

						$('#tabla_roles tbody').append(row_roles);
					});

					$('#modal_form').modal('show');
					$('.modal-title').text('Editar usuario');
				},
				error: function (jqXHR, textStatus, errorThrown)
				{
					show_ajax_error_message(jqXHR, textStatus, errorThrown);
				}
			});
		}

		function deshabilitar_usuario(id, temporal = false) {
			var texto = temporal 
				? "¿Está seguro de que desea deshabilitar el usuario de forma temporal?" 
				: "¿Está seguro de que desea deshabilitar el usuario?";
			if (temporal) {
				url = "{{ route('users.deshabilitar_usuario_temporal', ':id') }}";
			} else {
				url = "{{ route('users.deshabilitar_usuario', ':id') }}";
			}
			swal.fire({
				title: texto,
				icon: "warning",
				buttons: true,
				dangerMode: true,
			}).then((confirmacion) => {
				if (confirmacion) {
					show_loading(); // Función personalizada que muestra un loader
					$.ajax({ headers: {
									'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
								},
						url: url.replace(':id', id),
						type: "PATCH",
						data: {temporal: temporal},
						dataType: "JSON",
						success: function(data) {
							swal.fire({
								title: "Aviso",
								text: "Usuario deshabilitado con éxito.",
								icon: "success"
							}).then(() => {
								// Recargar la tabla DataTables al cerrar el modal de éxito
								location.reload();
							});
							hide_loading(); // Función personalizada que oculta el loader
						},
						error: function (jqXHR, textStatus, errorThrown) {
							show_ajax_error_message(jqXHR, textStatus, errorThrown);
						}
					});
				}
			});
		}

		function blanquear_psw(id)
		{
			swal.fire({
				title: "¿Desea blanquear la contraseña del usuario?",
				icon: "warning",
				buttons: true,
				dangerMode: true,
			}).then((confirmacion) => {
				if (confirmacion) {
					show_loading();
					$.ajax({ 
						headers: {
									'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
								},
						url: "{{ route('users.blanquear_password', ':id') }}".replace(':id', id),
						type: "PATCH",
						dataType: "JSON",
						success: function(data) {
							swal.fire({
								title: "Aviso",
								text: "Contraseña blanqueada con éxito.",
								icon: "success"
							}).then(() => {
								// Recargar la tabla DataTables al cerrar el modal de éxito
								location.reload();
							});
							hide_loading(); // Función personalizada que oculta el loader
						},
						error: function (jqXHR, textStatus, errorThrown)
						{
							show_ajax_error_message(jqXHR, textStatus, errorThrown);
						}
					});
				}
			});
		}

	</script>

	<div class="container-full-width" id="pagina-permisos">
		<div class="row">
			<div class="col-md-12">
				<h2>Usuarios</h2>
				<br>
				<div class="accordion" id="accordionOpcionUsuarios">
					<div class="accordion-item">
						<h2 class="accordion-header" id="headingOne">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
								data-bs-target="#opciones-usuarios" aria-expanded="true" aria-controls="collapseOne">
								Opciones
							</button>
						</h2>
						<div id="opciones-usuarios" class="accordion-collapse collapse" aria-labelledby="headingOne" 
							data-bs-parent="#accordionOpcionUsuarios">
							<div class="accordion-body">
								<form action="{{ route('users.guardar_opciones') }}" method="POST">
									@csrf
									<div class="row">
										<div class="col-md-12">
											<div class="mb-3 row">
												<label class="form-check-label col-md-6">Requerir cambio de contraseña después de 30 días</label>
												<div class="col-md-6">
													<input type="checkbox" value="1" <?php if($reset_password_30_dias){ echo 'checked'; }?> 
														name="reset_password_30_dias" id="reset_password_30_dias">
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<div class="mb-3 row">
												<label class="form-check-label col-md-6">Configurar contraseñas</label>
												<div class="col-md-6">
													<input type="checkbox" value="1" <?php if($configurar_claves){ echo 'checked'; }?> 
														name="configurar_claves" id="configurar_claves">
												</div>
											</div>
										</div>
									</div>
									<br>
									<button type="submit" class="btn btn-success"><span class="bi bi-save"></span> Guardar opciones</button>
								</form>
								<hr>
							</div>
						</div>
					</div>
				</div>
				<br>

				<!-- Tabla de usuarios -->
				<div class="p-12 sm:p-8 bg-white shadow sm:rounded-lg">
									<!-- Manejo de errores -->
					<div style="margin-top:1rem;">
						@if (session('error')) 
						<div class="alert alert-danger">
							{{ session('error') }}
						</div>
						@endif
						@if (session('success'))
						<div class="alert alert-success" role="alert">
								{{ session('success') }}
							</div>
						@endif
					</div>

					<div class="table-responsive">
						<div class="float-left">
							@if ($permiso_agregar_usuario)
							<a href="#" class="btn btn-outline-primary float-right" data-placement="left" style="border-radius:20px;!important;margin-right:5px; ">
								<i class="fas fa-file-import"></i> {{ __('Importar Usuarios') }}
							</a>
							@endif
							@if ($permiso_agregar_usuario)
							<a href="{{ route('users.create') }}" class="btn btn-outline-success float-right" data-placement="left" style="border-radius:20px;!important;margin-right:5px;">
								<i class="fas fa-plus"></i> {{ __('Agregar Usuario') }}
							</a>
							@endif
						</div>
						<br>
						<table id="usuarios-table" class="table table-striped table-bordered" cellspacing="0" width="100%">
							<thead>
								<tr>
									<th>Nombre</th>
									<th>Apellido</th>
									<th>Username</th>
									<th>Email</th>
									<th>Rol</th>
									<th>Habilitado</th>
									<th>Bloqueado</th>
									<th class="text-center">Acciones</th>
								</tr>
							</thead>
							<tbody>
								@foreach ($users as $user)
								<?php 
								$candadito = "";
									if ($user->habilitado != 1){ 
										$user->habilitado = 'No'; 
										$candadito = "<i class='fas fa-lock'></i>";
									} else { 
										$user->habilitado = 'Si';  
										$candadito = "<i class='fas fa-lock-open'></i>";
									}
								?>
									<tr>
										<td>{{ $user->nombre }}</td>
										<td>{{ $user->apellido }}</td>
										<td>{{ $user->username }}</td>
										<td>{{ $user->email }}</td>
										<td>{{ $user->nombre_rol }}</td>
										<td>{{ $user->habilitado }}</td>
										<td>{{ $user->bloqueado ? 'Sí' : 'No' }}</td>
										<td>
											@if ($permiso_editar_usuario)
											<a class="btn btn-sm btn-outline-primary" title="Editar" href="{{ route('users.edit', $user->user_id) }}">
												<i class="fas fa-pencil-alt"></i>
											</a>
											@endif
											
											@if ($permiso_deshabilitar_usuario)
											<a class="btn btn-sm btn-outline-warning" href="javascript:void(0)" title="Deshabilitar" onclick="deshabilitar_usuario('{{ $user->user_id}}',0)">
												<?php echo $candadito; ?>
											</a>
											@endif

											@if ($permiso_deshabilitar_usuario)
											<a class="btn btn-sm btn-outline-warning" href="javascript:void(0)" title="Deshabilitar temporalmente" onclick="deshabilitar_usuario('{{ $user->user_id}}',2)">
												<span class="fas fa-clock"></span>
											</a>
											@endif

											@if ($permiso_blanquear_password)
											<a class="btn btn-sm btn-outline-info" href="javascript:void(0)" title="Blanquear" onclick="blanquear_psw('{{ $user->user_id }}')">
												<i class="fas fa-key"></i>
											</a>
											@endif

											@if ($permiso_eliminar_usuario)
												<form action="{{ route('users.destroy', $user->user_id) }}" method="POST" style="display:inline;">
													@csrf
													@method('DELETE')
													<button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Está seguro de que desea eliminar este usuario?')">
														<i class="fas fa-trash"></i>
													</button>
												</form>
											@endif		
										</td>
									</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>

				<!-- Mostrar formulario de cambio de contraseña si el usuario está establecido -->
				@if(isset($selectedUser))
					<div class="p-12 sm:p-8 bg-white shadow sm:rounded-lg">
						<h2 class="text-lg font-medium text-gray-900">
							{{ __('Cambiar contraseña de ') . $selectedUser->nombre }}
						</h2>

						<form method="post" action="{{ route('password.update', $selectedUser->user_id) }}" class="p-6">
							@csrf
							@method('patch')

							<!-- Campo para nueva contraseña -->
							<div class="mt-4">
								<x-input-label for="password" value="{{ __('Nueva Contraseña') }}" />
								<x-text-input id="password" name="password" type="password" class="mt-1 block w-3/4" placeholder="{{ __('Nueva Contraseña') }}" />
								<x-input-error :messages="$errors->get('password')" class="mt-2" />
							</div>

							<!-- Confirmar nueva contraseña -->
							<div class="mt-4">
								<x-input-label for="password_confirmation" value="{{ __('Confirmar Contraseña') }}" />
								<x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-3/4" placeholder="{{ __('Confirmar Contraseña') }}" />
								<x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
							</div>

							<div class="mt-6 flex justify-end">
								<a href="{{ route('users.index') }}" class="btn btn-secondary">
									{{ __('Cancelar') }}
								</a>

								<x-danger-button class="ml-3">
									{{ __('Cambiar contraseña') }}
								</x-danger-button>
							</div>
						</form>
					</div>
				@endif


				<div class="modal fade" id="modal_form" role="dialog">
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
								<h3 class="modal-title">Formulario de usuario</h3>
							</div>
							<form method="post" action="{{ route('users.store') }}" class="mt-6 space-y-6">
								<div class="modal-body form">
									@csrf
									<div class="form-body">
										<div class="mb-3 row">
											<label class="col-form-label col-md-3">{{ __('Nombre de Usuario') }}</label>
											<div class="col-md-9">
												<input name="username" maxlength="255" placeholder="Nombre de Usuario" 
													id="username" class="form-control" type="text">
												<span class="help-block"></span>
											</div>
										</div>
									</div>

									<div class="form-body">
										<div class="mb-3 row">
											<label class="col-form-label col-md-3">{{ __('Nombre') }}</label>
											<div class="col-md-9">
												<input name="nombre" maxlength="255" placeholder="Nombre" 
													id="nombre" class="form-control" type="text">
												<span class="help-block"></span>
											</div>
										</div>
									</div>

									<div class="form-body">
										<div class="mb-3 row">
											<label class="col-form-label col-md-3">{{ __('Apellido') }}</label>
											<div class="col-md-9">
												<input name="apellido" maxlength="255" placeholder="Apellido" 
													id="apellido" class="form-control" type="text">
												<span class="help-block"></span>
											</div>
										</div>
									</div>

									<div class="form-body">
										<div class="mb-3 row">
											<label class="col-form-label col-md-3">{{ __('Email') }}</label>
											<div class="col-md-9">
												<input name="email" maxlength="255" placeholder="Email" 
													id="email" class="form-control" type="email">
												<span class="help-block"></span>
											</div>
										</div>
									</div>

									<div>
										<x-input-label for="rol_id" :value="__('Rol')" />
										<select id="rol_id" name="rol_id" class="mt-1 block w-full">
											<option value="0" {{ old('rol_id', $user->rol_id) === null ? 'selected' : '' }}>
												{{ __('Elija un Rol') }}
											</option>
											@foreach($roles as $rol)
											<option value="{{ $rol->rol_id }}" {{ old('rol_id', $user->rol_id) == $rol->rol_id ? 'selected' : '' }}>
												{{ $rol->nombre }}
											</option>
											@endforeach
										</select>
										<x-input-error :messages="$errors->get('rol_id')" class="mt-2" />
									</div>

									<div>
										<x-input-label for="habilitado" :value="__('Habilitado')" />
										<div class="mt-1">
											<label>
												<input type="radio" name="habilitado" value="1"
													{{ old('habilitado', $user->habilitado) === null || old('habilitado', $user->habilitado) == 1 ? 'checked' : '' }}>
												Sí
											</label>
											<label class="ml-4">
												<input type="radio" name="habilitado" value="0"
													{{ old('habilitado', $user->habilitado) == 0 ? 'checked' : '' }}>
												No
											</label>
										</div> <x-input-error :messages="$errors->get('habilitado')" class="mt-2" />
									</div>
								</div>
								<div class="modal-footer">
									<button type="submit" class="btn btn-primary">{{ __('Guardar') }}</button>
									<button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
								</div>
							</form>
						</div>
					</div>
				</div>
				
			</div>
		</div>
	</div>

</x-app-layout>
