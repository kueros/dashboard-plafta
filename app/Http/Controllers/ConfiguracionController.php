<?php

namespace App\Http\Controllers;

use App\Models\Variable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Http\Controllers\MyController;
use Illuminate\Http\RedirectResponse;
use App\Models\User;
use App\Models\LogAdministracion;
use Illuminate\Support\Facades\Auth;

class ConfiguracionController extends Controller
{
	/**
	 * Display a listing of the resource.
	 */
	public function index(): View
	{
		$variables = Variable::where('nombre', 'like', '%noti%')
			->orWhere('nombre', 'like', '%opav%')
			->orWhere('nombre', 'like', '%copa%')
			->get(['nombre', 'nombre_menu', 'valor']);
		$variables = $variables->filter(function ($variable) {
			return !is_null($variable);
		});
		return view('configuracion.index', compact('variables'));
	}

	/**
	 * Muestro la vista de variables.
	 */
	public function variables()
	{
		$variables = Variable::all();
		return view('configuracion.variables', compact('variables'));
	}

	public function guardar_estado(Request $request, MyController $myController)
	{
		try {
			// Guardo los datos de los estados.
			foreach ($request->all() as $nombre => $valor) {
				// Saltar _token
				if ($nombre === '_token') {
					continue;
				}
				// Encuentra la variable y actualiza su valor
				$variable = Variable::where('nombre', $nombre)->first();
				if ($variable) {
					$variable->valor = $valor;
					$variable->save();
				}
			}
			$message = "Guardar estado recibido. " . json_encode($request->all());
			$users = User::find(Auth::user()->user_id);
			Log::info($message);
			$log = LogAdministracion::create([
				'username' => Auth::user()->username,
				'action' => "guardar_estado",
				'detalle' => $message,
				'ip_address' => $_SERVER['REMOTE_ADDR'],
				'user_agent' => $_SERVER['HTTP_USER_AGENT']
			]);
			$username = $users->username;
			$subject = "Guardar estado";
			$body = "Usuario " . $username . " ha guardado los estados correctamente.";
			$to = "omarliberatto@yafoconsultora.com";
			$myController->enviar_email($to, $body, $subject);
			$log->save();
			return response()->json(['success' => 'Estado guardado correctamente']);
		} catch (\Exception $e) {
			// Registrar el error para depuración
			$message = "Guardar estado recibido. " . json_encode($request->all());
			$users = User::find(Auth::user()->user_id);
			Log::info($message);
			$log = LogAdministracion::create([
				'username' => Auth::user()->username,
				'action' => "guardar_estado",
				'detalle' => $message,
				'ip_address' => $_SERVER['REMOTE_ADDR'],
				'user_agent' => $_SERVER['HTTP_USER_AGENT']
			]);
			$username = $users->username;
			$subject = "Guardar estado";
			$body = "Usuario " . $username . " no ha podido guardar los estados.";
			$to = Auth::user()->email;
			$myController->enviar_email($to, $body, $subject);
			$log->save();
			Log::error('Error al guardar estado: ' . $e->getMessage());
			return response()->json(['error' => 'Hubo un error al guardar el estado'], 500);
		}
	}

	public function guardar_remitente(Request $request, MyController $myController)
	{
		$message = "Guardar remitente recibido. " . json_encode($request->all());
		$users = User::find(Auth::user()->user_id);
		Log::info($message);
		$log = LogAdministracion::create([
			'username' => Auth::user()->username,
			'action' => "guardar_remitente",
			'detalle' => $message,
			'ip_address' => $_SERVER['REMOTE_ADDR'],
			'user_agent' => $_SERVER['HTTP_USER_AGENT']
		]);
		$username = $users->username;
		$subject = "Guardar remitente";
		$body = "Usuario " . $username . " ha guardado el remitente correctamente.";
		$to = Auth::user()->email;
		$myController->enviar_email($to, $body, $subject);
		$log->save();

		$from = trim($_POST['from']);
		$from_name = trim($_POST['from_name']);
		if ($from != '' || $from_name != '') {
			Variable::where('nombre', '_notificaciones_email_from')->update(['valor' => $from]);
			Variable::where('nombre', '_notificaciones_email_from_name')->update(['valor' => $from_name]);
			return response()->json(['success' => 'Datos del remitente guardados.']);
		} else {
			return response()->json(['error' => 'Ninguno de los 2 valores puede quedar vacío.']);
		}
		return; // redirect()->route('configuracion');
	}

	public function add_parametro_email(Request $request)
	{

		$parametro = trim($_POST['parametro']);
		$valor = trim($_POST['valor']);
		#dd($parametro, $valor); //234 y 234
		if ($parametro != '' && $valor != '') {
			$notificaciones_email_config = Variable::where('nombre', 'notificaciones_email_config')->first();
			$configs = json_decode($notificaciones_email_config->valor);
			$mail_config = array();
			if ($configs != '') {
				foreach ($configs as $key => $config) {
					$mail_config[$key] = $config;
				}
			}

			if (!isset($mail_config[$parametro])) {
				$mail_config[$parametro] = $valor;

				Variable::where('nombre', 'notificaciones_email_config')->update(['valor' => json_encode($mail_config)]);
				return redirect('/configuracion')->with('success', 'Parametro guardado.');
			} else {
				return redirect('/configuracion')->with('error', 'El parametro ya existe.');
			}

			#$this->guardar_log("Modificó los parametros para envio de emails");
		} else {
			return redirect('/configuracion')->with('error', 'Los valores no pueden estar vacios.');
		}
		return redirect('/configuracion');
	}

	public function ajax_delete_parametro_email()
	{

		$parametro = trim($_POST['parametro']);

		if ($parametro != '') {
			$notificaciones_email_config = Variable::where('nombre', 'notificaciones_email_config')->first();
			$configs = json_decode($notificaciones_email_config->valor);
			$mail_config = array();
			foreach ($configs as $key => $config) {
				$mail_config[$key] = $config;
			}

			if (isset($mail_config[$parametro])) {
				unset($mail_config[$parametro]);
			}

			Variable::where('nombre', 'notificaciones_email_config')->update(['valor' => json_encode($mail_config)]);

			return true;
		} else {
			return false;
		}
	}
}
