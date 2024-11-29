<x-app-layout title="Roles" :breadcrumbs="[['title' => 'Inicio', 'url' => 'dashboard']]">
		<x-slot name="header">
			<h2 class="font-semibold text-xl text-gray-800 leading-tight">
				{{ __('Funciones') }}
			</h2>
		</x-slot>
		<!-- Primary Navigation Menu -->
	<!--	@php
		$user = Auth::user()->username;
		$email = Auth::user()->email;
		$permiso_agregar_roles = tiene_permiso('add_rol');
		$permiso_editar_roles = tiene_permiso('edit_rol');
		$permiso_eliminar_roles = tiene_permiso('del_rol');
		@endphp
	-->
		<script type="text/javascript">
			var table;
			var save_method;

			jQuery(document).ready(function($){
			/*******************************************************************************************************************************
			 *******************************************************************************************************************************/
				table = $('#funciones-table').DataTable({
					"ajax": {
						url : "{{ url('funciones/ajax_listado') }}",
						type : 'GET'
					},
					language: traduccion_datatable,
					dom: 'Bfrtip',
					columnDefs: [
						{
							"targets": 'no-sort',
							"orderable": false
						}
					],
					buttons: [
						{"extend": 'pdf', "text":'Export',"className": 'btn btn-danger', "orientation": 'landscape', title: 'Funciones'},
						{"extend": 'copy', "text":'Export',"className": 'btn btn-primary', title: 'Funciones'},
						{"extend": 'excel', "text":'Export',"className": 'btn btn-success', title: 'Funciones'},
						{"extend": 'print', "text":'Export',"className": 'btn btn-secondary', title: 'Funciones'}
					],
					initComplete: function () {
						$('.buttons-copy').html('<i class="fas fa-copy"></i> Portapapeles');
						$('.buttons-pdf').html('<i class="fas fa-file-pdf"></i> PDF');
						$('.buttons-excel').html('<i class="fas fa-file-excel"></i> Excel');
						$('.buttons-print').html('<span class="bi bi-printer" data-toggle="tooltip" title="Exportar a PDF"/> Imprimir');
					}
				});
			});

			/*******************************************************************************************************************************
			 *******************************************************************************************************************************/
			function reload_table()
			{
				table.ajax.reload(null,false);
			}

			/*******************************************************************************************************************************
			 *******************************************************************************************************************************/
			function add_funcion()
			{
				save_method = 'add';
				$('#form_add')[0].reset();
				$('.form-group').removeClass('has-error');
				$('.help-block').empty();
				$('#modal_form_add').modal('show');
				$('.modal-title').text('Agregar funciones');
				$('#accion').val('add');
				$('#form_add').attr('action', "{{ url('funciones') }}");
				$('#method').val('POST');
			}

			/*******************************************************************************************************************************
			 *******************************************************************************************************************************/
			function edit_funcion(id) {
				save_method = 'update';
				$('#form_edit')[0].reset();
				$('.form-group').removeClass('has-error');
				$('.help-block').empty();
				$('#accion').val('edit');

				$.ajax({
					url: "{{ url('funciones/ajax_edit/') }}" + "/" + id,
					type: "GET",
					dataType: "JSON",
					success: function(data) {
						console.log('Respuesta completa del servidor:', data);
						// Si data es un string, intenta convertirlo a un objeto JSON
					if (typeof data === 'string') {
						try {
							data = JSON.parse(data);
						} catch (e) {
							console.error('Error al parsear JSON:', e);
							return;
						}
					}

					console.log('Tipo de data:', typeof data); // Asegúrate de que ahora es un objeto

					if (data && data.formula) {
						console.log('Tipo de data.formula:', typeof data.formula);
						console.log('data.formula:', data.formula);

						if (Array.isArray(data.formula)) {
							data.formula.forEach(function(item) {
								console.log(item);
							});
						} else {
							console.error("La propiedad 'formula' no es un array.");
						}
					} else {
						console.error("La propiedad 'formula' no existe en la respuesta.");
					}

					// Procesa los demás datos
					$('[name="id"]').val(data.id);
					$('[name="nombre"]').val(data.nombre);

					// Limpia los campos dinámicos anteriores
					const container = document.getElementById('input-container_edit');
					container.innerHTML = '';

					// Genera los campos dinámicos según la fórmula
					if (Array.isArray(data.formula)) {
						data.formula.forEach(element => {
							console.log('element: ', element);
							if (element.type === 'value') {
								addOption('Valor numérico', element.value);
							} else if (element.type === 'operator') {
								addOption('Operador', element.value);
							} else {
								addOption('Campo estático', element.value);
							}
						});
					}

					$('#modal_form_edit').modal('show');
					$('.modal-title').text('Editar función');
					$('#form_edit').attr('action', "{{ url('funciones') }}" + "/" + id);
					$('#method').val('PUT');
				},
					error: function(jqXHR, textStatus, errorThrown) {
						show_ajax_error_message(jqXHR, textStatus, errorThrown);
					}
				});
			}

			/*******************************************************************************************************************************
			 *******************************************************************************************************************************/
			function delete_funcion(id)
			{
				if(confirm('¿Desea borrar la función?'))
				{
					$.ajax({
						url : "{{ url('funciones/ajax_delete') }}"+"/"+id,
						type: "POST",
						dataType: "JSON",
						headers: {
							'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
						},
						success: function(data)
						{
							swal.fire("Aviso", "Función eliminada con éxito.", "success");
							//$('#modal_form_edit').modal('hide');
							$('#modal_form_add').modal('hide');
							reload_table();
						},
						error: function (jqXHR, textStatus, errorThrown)
						{
							show_ajax_error_message(jqXHR, textStatus, errorThrown);
						}
					});

				}
			}

			/*******************************************************************************************************************************
			 *******************************************************************************************************************************/
			function guardar_datos() 
			{
				if (!cargarFormula()) {
					console.log('añsdkjfañdkfjañdkjfañdkfjañdkfjañldkfjañdkjf')
					return false;
				}
				let form_data = $('#form_add').serializeArray();
				console.log('Contenido de form_data (JSON):', JSON.stringify(form_data, null, 2));
				console.log('accion: ', $('#accion').val());
				let url_guarda_datos = "{{ url('funciones/ajax_store') }}";
				//let url_guarda_datos = "{{ url('alertasIndex') }}";
				let type_guarda_datos = "POST";

				if ($('#accion').val() != "add") {
					console.log('form_data: ', form_data);
/* 					let alertasIdValue = form_data.find(item => item.name == 'alertas_id')?.value;
					console.log('alertasIdValue: ', alertasIdValue);
*/
					url_guarda_datos = "{{ url('funcionesUpdate') }}" + "/" + 2;
					type_guarda_datos = "PUT";
					form_data.push({ name: '_method', value: 'PUT' });
					console.log('url_guarda_datos: ', url_guarda_datos);
				}

				show_loading();
				$.ajax({
					url: url_guarda_datos,
					type: type_guarda_datos,
					data: $.param(form_data), 
					dataType: "JSON",
					headers: {
						'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
					},
					success: function(data) {
						hide_loading();
						if (data.status == 0) {
							let errorMessage = data.message + "</br>";
							mostrarErroresPorFila(data.errors);
							if (data.errors && Object.keys(data.errors).length > 0) {
								// Recorre cada campo y sus mensajes de error
								for (let field in data.errors) {
									if (data.errors.hasOwnProperty(field)) {
										errorMessage += `${field}: ${data.errors[field].join(", ")}</br>`;
									}
								}
							} else {
								errorMessage += "No se encontraron errores específicos para los campos.";
							}

							swal.fire("Aviso", errorMessage, "warning");
							return false;
						} else {
							swal.fire({
								title: "Aviso",
								text: data.message,
								icon: "success"
							}).then(() => {
								// Recargar la tabla DataTables al cerrar el modal de éxito
								location.reload();
							});
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						show_ajax_error_message(jqXHR, textStatus, errorThrown);
					}
				});
			}


			/*******************************************************************************************************************************
			 *******************************************************************************************************************************/
			document.addEventListener('DOMContentLoaded', function() {
				const firstInput = document.querySelector('input[name="nombre"]');
				const secondInput = document.querySelector('input[name="segundo_nombre"]');

				firstInput.addEventListener('input', function() {
					secondInput.value = firstInput.value;
				});
			});

			/*******************************************************************************************************************************
			 *******************************************************************************************************************************/
			// Array para almacenar la fórmula
			let formulaElements = [];
			function cargarFormula() {
				console.log('Cargando fórmula...');
				// Array para almacenar la fórmula
				//let formulaElements = [];
				//document.getElementById('form_add').addEventListener('submit', function (event) {
					console.log('Formulario enviado');
					// Evita el envío automático para procesar los datos
					event.preventDefault();

					// Recopilar todos los inputs del contenedor
					const inputs = document.querySelectorAll('#input-container input');
					let formulaString = '';

					// Concatenar los valores de cada input al string
					inputs.forEach(input => {
						if (input.value.trim() !== '') {
							formulaString += input.value.trim()+',';
						}
					});
					console.log('formulaString: ', formulaString);
					// Asignar el string concatenado al campo oculto
					document.getElementById('formula').value = formulaString;

					// Enviar el formulario
					//this.submit();
				//});
				return true
			};

			/*******************************************************************************************************************************
			 *******************************************************************************************************************************/
			// Función para agregar elementos a la fórmula
			function addOption(optionText, defaultValue = '') {
				let formulaElements = [];
				let addedElements = []; 
				const container = document.getElementById('input-container');

				const wrapper = document.createElement('div');
				wrapper.className = 'd-flex align-items-center me-2';

				const newInput = document.createElement('input');
				newInput.type = 'number';
				newInput.className = 'form-control';
				newInput.style.width = '100px';
				if (optionText === 'Valor numérico') {
					newInput.type = 'number';
					newInput.value = defaultValue; // Asigna el valor predeterminado
					console.log('newInput.value1: ', newInput.value);
				} else if (optionText === 'Operador') {
					newInput.type = 'text';
					newInput.value = defaultValue; // Asigna el valor predeterminado
					newInput.readOnly = true; // Solo lectura para operadores
					console.log('newInput.value2: ', newInput.value);
				} else {
					newInput.type = 'text';
					newInput.value = defaultValue; // Asigna el valor predeterminado
					console.log('newInput.value3: ', newInput.value);
				}
				if (optionText === 'Valor numérico') {
					newInput.placeholder = 'Ingrese un valor';
					//newInput.type = 'number';

					// Crear un objeto asociado en formulaElements
					const elementIndex = formulaElements.length;
					formulaElements.push({ type: 'value', value: '' }); // Inicializar en vacío
					console.log('formulaElements0: ', formulaElements);
					// Actualizar el valor en formulaElements cuando se cambie el input
					newInput.oninput = function () {
						formulaElements[elementIndex].value = newInput.value;
						console.log('formulaElements1: ',formulaElements) 
					};
					newInput.removeAttribute('readonly');
				} else if (optionText === 'Operador') {
					newInput.setAttribute('readonly', true);
					newInput.type = 'text';
					const operatorDropdown = document.createElement('div');
					operatorDropdown.className = 'dropdown ms-2';

					const dropdownButton = document.createElement('button');
					dropdownButton.className = 'btn btn-primary dropdown-toggle';
					dropdownButton.type = 'button';
					dropdownButton.id = `dropdownOperator-${Date.now()}`;
					dropdownButton.setAttribute('data-bs-toggle', 'dropdown');
					dropdownButton.innerText = 'Seleccionar operador';

					const dropdownMenu = document.createElement('ul');
					dropdownMenu.className = 'dropdown-menu';
					dropdownMenu.setAttribute('aria-labelledby', dropdownButton.id);

					const operators = ['+', '-', '*', '/'];
					operators.forEach(op => {
						const li = document.createElement('li');
						const button = document.createElement('button');
						button.className = 'dropdown-item';
						button.type = 'button';
						button.innerText = op;
						button.onclick = () => {
							newInput.value = op;
							operatorDropdown.style.display = 'none';
							formulaElements.push({ type: 'operator', value: op });
							console.log('formulaElements2: ', formulaElements);
						};
						li.appendChild(button);
						dropdownMenu.appendChild(li);
					});

					operatorDropdown.appendChild(dropdownButton);
					operatorDropdown.appendChild(dropdownMenu);

					wrapper.appendChild(operatorDropdown);
				} else {
					newInput.value = optionText;
					//newInput.setAttribute('readonly', false); // Esto asegura que sea editable
					formulaElements.push({ type: 'static', value: optionText });
					console.log('formulaElements3: ', formulaElements);
				}
				console.log('formulaElements4: ', formulaElements);
				wrapper.appendChild(newInput);
				container.insertBefore(wrapper, container.lastElementChild);
				serializeFormula();
			}

			/*******************************************************************************************************************************
			 *******************************************************************************************************************************/
			// Función para serializar la fórmula
			function serializeFormula() {
				const serializedFormula = JSON.stringify(formulaElements);
				document.getElementById('formula').value = serializedFormula;
			}

			/*******************************************************************************************************************************
			 *******************************************************************************************************************************/
			function backInput() {
				const container = document.getElementById('input-container');
				
				// Selecciona todos los divs con las clases específicas
				const inputDivs = container.querySelectorAll('div.d-flex.align-items-center.me-2');
				console.log(inputDivs);   
				// Verifica si hay elementos
				if (inputDivs.length > 0) {
					// Seleccionamos el último div con el input
					const lastInputDiv = inputDivs[inputDivs.length - 1];

					// Lo eliminamos del contenedor
					lastInputDiv.remove();

					// También eliminamos el último elemento del array formulaElements
					formulaElements.pop();
					serializeFormula(); // Actualizamos el campo de fórmula serializada
				} else {
					console.warn('No hay elementos para eliminar');
				}
			}

			/*******************************************************************************************************************************
			 *******************************************************************************************************************************/
			function clearDynamicFields() {
				// Selecciona el contenedor de los campos dinámicos
				const container = document.getElementById('input-container');
				
				// Selecciona todos los divs con las clases específicas dentro del contenedor
				const inputDivs = container.querySelectorAll('div.d-flex.align-items-center.me-2');
				
				// Itera y elimina cada elemento dinámico
				inputDivs.forEach(div => div.remove());
				
				// Limpia el array formulaElements si es necesario
				formulaElements = [];
				serializeFormula(); // Actualiza el campo de fórmula serializada

				// Cierra la modal manualmente en caso de que `data-bs-dismiss="modal"` no funcione
				const modal = document.getElementById('modal_form_add');
				if (modal) {
					const bootstrapModal = bootstrap.Modal.getInstance(modal);
					if (bootstrapModal) {
						bootstrapModal.hide();
					}
				}
			}
	</script>
	<style>
		.modal-body .form-body .row {
			align-items: center;
		}
		.modal-body .form-body .d-flex {
			display: flex;
			align-items: center;
		}
		.modal-body .form-body .ms-3 {
			margin-left: 1rem;
		}
		.btn-sm {
			padding: 0.25rem 0.5rem;
			font-size: 0.875rem;
			line-height: 1.5;
			border-radius: 0.2rem;
		}
	</style>

	<!--LISTADO-->
	<div class="container">
		
		<div class="row">
			<div class="col-md-12">
				<h2>Funciones</h2>
				@include('layouts.partials.message')
				@if ($errors->any())
					<div class="alert alert-danger">
						@foreach ($errors->all() as $error)
							{{ $error }}</br>
						@endforeach
					</div>
				@endif
				<br>
				<div class="table-responsive">
					<div class="float-right">
						<button id="agregar" class="btn btn-success" onclick="add_funcion()">
							<i class="bi bi-plus"></i> {{ __('Agregar funcion') }}
						</button>
					</div>
					<br>
					<table id="funciones-table" class="table table-striped table-bordered" cellspacing="0" width="100%">
						<thead>
							<th>Nombre</th>
							<th>Fórmula</th>
						<th style="width:20%;" class="no-sort">Acción</th>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
				
			</div>
		</div>
	</div>
	<div class="modal fade modal-lg" id="modal_form_add" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Formulario funciones</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<form id="form_add" method="POST" enctype="multipart/form-data" class="form-horizontal" action="">
					@csrf
					<input name="_method" type="hidden" id="method">
					<input type="hidden" id="formula" name="formula">
					<div class="modal-body form">
						<input type="hidden" value="" name="id"/>
						<input name="accion" id="accion" class="form-control" type="hidden">
						<div class="form-body">
							<div class="row">
								<div class="col-md-3">&nbsp;</div>
								<label class="col-form-label col-md-3">Nombre</label>
								<div class="col-md-6">
									<input name="nombre" maxlength="255" placeholder="Nombre de la función" class="form-control" type="text" />
									<span class="help-block"></span>
								</div>
							</div>
						</div>
						<div class="form-body">
							<div class="row align-items-center">
								<div class="col-md-3 d-flex align-items-center">
									<input name="segundo_nombre" maxlength="255" class="form-control" type="text" readonly="readonly" />
									<button class="btn btn-info btn-sm ms-2" type="button" id="equalMenuButton" disabled>
										<i class="fas fa-equals"></i>
									</button>
									<span class="help-block"></span>
								</div>
								<!-- Mueve el bloque input-container aquí -->
								<div class="col-md-9 d-flex align-items-center" id="input-container">
									<!-- Dropdown -->
									<div class="dropdown">
										<button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
											<i class="far fa-plus-square"></i>
										</button>
										<button class="btn btn-secondary" type="button" id="backMenuButton" onclick="backInput()">
											<i class="fas fa-undo"></i>
										</button>
										<ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
											<li><button class="dropdown-item" type="button" onclick="addOption('1')">Valor numérico</button></li>
											<li><button class="dropdown-item" type="button" onclick="addOption('Operador')">Operador</button></li>
											<li><button class="dropdown-item" type="button" onclick="addOption('Contador')">Contador</button></li>
											<li><button class="dropdown-item" type="button" onclick="addOption('Acumulador')">Acumulador</button></li>
											<li><button class="dropdown-item" type="button" onclick="addOption('Condición')">Condición</button></li>
											<li><button class="dropdown-item" type="button" onclick="addOption('Otra función')">Otra función</button></li>
										</ul>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<!--a onclick="if (validarFormulario()) guardar_datos();" class="btn btn-primary">Guardar</a-->
							<a onclick=" guardar_datos();" class="btn btn-primary">Guardar</a>
							<button type="button" class="btn btn-danger" onclick="clearDynamicFields()">Cancelar</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>


	<div class="modal fade modal-lg" id="modal_form_edit" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title_edit">Formulario funciones</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<form id="form_edit" method="POST" enctype="multipart/form-data" class="form-horizontal" action="">
					@csrf
					<input name="_method" type="hidden" id="method">
					<input type="hidden" id="formula" name="formula">
					<div class="modal-body form">
						<input type="hidden" value="" name="id"/>
						<input name="accion" id="accion" class="form-control" type="hidden">
						<div class="form-body">
							<div class="row">
								<div class="col-md-3">&nbsp;</div>
								<label class="col-form-label col-md-3">Nombre</label>
								<div class="col-md-6">
									<input name="nombre" maxlength="255" placeholder="Nombre de la función" class="form-control" type="text" />
									<span class="help-block"></span>
								</div>
							</div>
						</div>
						<div class="form-body">
							<div class="row align-items-center">
								<div class="col-md-3 d-flex align-items-center">
									<input name="segundo_nombre" maxlength="255" class="form-control" type="text" readonly="readonly" />
									<button class="btn btn-info btn-sm ms-2" type="button" id="equalMenuButton" disabled>
										<i class="fas fa-equals"></i>
									</button>
									<span class="help-block"></span>
								</div>
								<!-- Mueve el bloque input-container aquí -->
								<div class="col-md-9 d-flex align-items-center" id="input-container_edit">
									<!-- Dropdown -->
									<div class="dropdown">
										<button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
											<i class="far fa-plus-square"></i>
										</button>
										<button class="btn btn-secondary" type="button" id="backMenuButton" onclick="backInput()">
											<i class="fas fa-undo"></i>
										</button>
										<ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
											<li><button class="dropdown-item" type="button" onclick="addOption('1')">Valor numérico</button></li>
											<li><button class="dropdown-item" type="button" onclick="addOption('Operador')">Operador</button></li>
											<li><button class="dropdown-item" type="button" onclick="addOption('Contador')">Contador</button></li>
											<li><button class="dropdown-item" type="button" onclick="addOption('Acumulador')">Acumulador</button></li>
											<li><button class="dropdown-item" type="button" onclick="addOption('Condición')">Condición</button></li>
											<li><button class="dropdown-item" type="button" onclick="addOption('Otra función')">Otra función</button></li>
										</ul>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<!--a onclick="if (validarFormulario()) guardar_datos();" class="btn btn-primary">Guardar</a-->
							<a onclick=" guardar_datos();" class="btn btn-primary">Guardar</a>
							<button type="button" class="btn btn-danger" onclick="clearDynamicFields()">Cancelar</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>	
	</x-app-layout>
