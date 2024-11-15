<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\MyController;
use App\Models\TipoTransaccion;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class TipoTransaccionController extends Controller
{
	/*******************************************************************************************************************************
	*******************************************************************************************************************************/
	public function index(Request $request, MyController $myController): View
	{
/* 		$permiso_listar_roles = $myController->tiene_permiso('list_roles');
		if (!$permiso_listar_roles) {
			abort(403, '.');
			return false;
		}
 */		$tipos_transacciones = TipoTransaccion::paginate();
		return view('tipos_transacciones.index', compact('tipos_transacciones'))
			->with('i', ($request->input('page', 1) - 1) * $tipos_transacciones->perPage());
	}

	/*******************************************************************************************************************************
	*******************************************************************************************************************************/
	public function ajax_listado(Request $request)
	{
		#dd($request);
		$tipos_transacciones = TipoTransaccion::all();
		#dd($tipos_transacciones);
		$data = array();
        foreach($tipos_transacciones as $r) {
            $accion = '<a class="btn btn-sm btn-primary" href="javascript:void(0)" title="Editar" onclick="edit_rol('."'".$r->rol_id.
				"'".')"><i class="bi bi-pencil"></i></a>';

            if($r->id != 1){
                $accion .= '<a class="btn btn-sm btn-danger" href="javascript:void(0)" title="Borrar" onclick="delete_rol('."'".$r->rol_id.
					"'".')"><i class="bi bi-trash"></i></a>';
            }

            $data[] = array(
				$r->nombre,
				$r->descripcion,
                $accion
            );
        }
        $output = array(
            "recordsTotal" => $tipos_transacciones->count(),
            "recordsFiltered" => $tipos_transacciones->count(),
            "data" => $data
        );

		return response()->json($output);

	}

	/*******************************************************************************************************************************
	*******************************************************************************************************************************/
	public function ajax_edit($id){

        $data = TipoTransaccion::find($id);
		return response()->json($data);
    }

	/*******************************************************************************************************************************
	*******************************************************************************************************************************/
	public function ajax_delete($id, MyController $myController){
        $permiso_eliminar_roles = $myController->tiene_permiso('del_rol');
		/* 		if (!$permiso_eliminar_roles) {
			abort(403, '.');
			return false;
		} */
		$tipos_transacciones = TipoTransaccion::find($id);
		$nombre = $tipos_transacciones->nombre;
		$clientIP = \Request::ip();
		$userAgent = \Request::userAgent();
		$username = Auth::user()->username;
		$message = $username . " borró el tipo de transacción " . $nombre;
		$myController->loguear($clientIP, $userAgent, $username, $message);

		$tipos_transacciones->delete();
		return response()->json(["status"=>true]);
    }

	/*******************************************************************************************************************************
	*******************************************************************************************************************************/
	public function create( MyController $myController): View
	{
		/* 		$permiso_agregar_roles = $myController->tiene_permiso('add_rol');
		if (!$permiso_agregar_roles) {
			abort(403, '.');
			return false;
		} */
		$tipos_transacciones = new TipoTransaccion();
		return view('rol.create', compact('roles'));
	}

	/*******************************************************************************************************************************
	 * 
	********************************************************************************************************************************/

	public function store(Request $request, MyController $myController): RedirectResponse
{
	#dd($request->input('nombre'));
/*     $permiso_agregar_roles = $myController->tiene_permiso('add_rol');
    if (!$permiso_agregar_roles) {
        abort(403, '.');
        return false;
    }
 */
    // Validar los datos del usuario
	$validatedData = $request->validate([
		'nombre' => [
			'required',
			'string',
			'max:255',
			'min:3',
			'regex:/^[\pL\s]+$/u', // Permitir solo letras y espacios
			Rule::unique('tipos_transacciones'),
		],
	], [
		'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
		'nombre.unique' => 'Este nombre de tipo de transacción ya está en uso.',
	]);

	$tipoTransacciónExistente = TipoTransaccion::where('nombre', $request->input('nombre'))->first();
	if ($tipoTransacciónExistente) {
		return redirect()->back()->withErrors(['nombre' => 'Este nombre de tipo de transacción ya está en uso.'])->withInput();
	}

    TipoTransaccion::create($validatedData);

    $clientIP = \Request::ip();
    $userAgent = \Request::userAgent();
    $username = Auth::user()->username;
    $message = $username . " creó el tipo de transacción " . $request->input('nombre');
    $myController->loguear($clientIP, $userAgent, $username, $message);

    return Redirect::route('tipos_transacciones.index')->with('success', 'Tipo de transacción creado exitosamente.');
}
	/*******************************************************************************************************************************
	*******************************************************************************************************************************/
	public function show($id): View
	{
		$rol = Rol::find($id);
		return view('rol.show', compact('rol'));
	}

	/*******************************************************************************************************************************
	*******************************************************************************************************************************/
	public function edit($id, MyController $myController): View
	{
		#dd($id);
		$permiso_editar_roles = $myController->tiene_permiso('edit_rol');
		if (!$permiso_editar_roles) {
			abort(403, '.');
			return false;
		}
		$roles = Rol::find($id);
		#dd($roles);
		return view('rol.edit', compact('roles'));
	}

	/*******************************************************************************************************************************
	*******************************************************************************************************************************/
	public function update(Request $request, Rol $rol, MyController $myController): RedirectResponse
	{
		#dd($request);
		$permiso_editar_roles = $myController->tiene_permiso('edit_rol');
		if (!$permiso_editar_roles) {
			abort(403, '.');
			return false;
		}
		// Validar los datos del usuario
		$validatedData = $request->validate([
			'nombre' => 'required|string|max:255|unique:roles,nombre',
		]);
		#dd($validatedData);
		$rol->update($validatedData);
		$clientIP = \Request::ip();
		$userAgent = \Request::userAgent();
		$username = Auth::user()->username;
		$message = $username . " actualizó el rol " . $_POST['nombre'];
		$myController->loguear($clientIP, $userAgent, $username, $message);
		return Redirect::route('roles.index')
			->with('success', 'Rol updated successfully');
	}

	/*******************************************************************************************************************************
	*******************************************************************************************************************************/
	public function destroy($id, MyController $myController): RedirectResponse
	{
		$permiso_eliminar_roles = $myController->tiene_permiso('del_rol');
		if (!$permiso_eliminar_roles) {
			abort(403, '.');
			return false;
		}
		$rol = Rol::find($id);
		// Almacena el nombre de rol antes de eliminarlo
		$nombre = $rol->nombre;
		// Elimina el rol
		$rol->delete();
		$message = Auth::user()->username . " borró el rol " . $nombre;
		Log::info($message);
		return Redirect::route('roles.index')
			->with('success', 'Rol deleted successfully');
	}

}