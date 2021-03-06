<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Root extends CI_Controller {

	/* CONTROLADOR DEL MODULO */

	var $variables = array();

	public function __construct()
	{
		parent::__construct();
		if (!$this->session->userdata('id_usuario'))  {   redirect( 'login/root/iniciar_sesion/'.base64_encode(current_url()) );  }
		
		/* configuracion generica del modulo */
		$this->variables=array('modulo'=>'modulos','id'=>'id_modulos','modelo'=>'model_modulos','llave'=>'{modulo}');
		$this->load->model($this->variables['modelo']);

		$mispermisos=$this->model_generico->mispermisos($this->session->userdata('id_roles'),$this->variables['modulo']);
		$this->variables['mispermisos']=json_decode($mispermisos->id_roles);
		if (!in_array($this->session->userdata('id_roles'), $this->variables['mispermisos'])) {  redirect( 'inicio/root'); }   	$this->variables['diccionario']=$diccionario=$this->model_generico->diccionario(); 

		 
	}

	/* funcion por defecto del controlador */
	public function index($id_cursos=null)
	{ /* llamo a la funcion lista para traer el listado de informacion */
		$this->lista($id_cursos);
	}

	/* FUNCION LISTA PARA TRAER EL LISTADO DE INFORMACION DE LA TABLA ASIGNADA AL MODULO */
	public function lista($id_cursos=null)
	{
		if (!$id_cursos)  { redirect( 'cursos/root'); }
		/* Cargo variables globales */
		$variables = $this->variables; $data['diccionario']=$this->variables['diccionario'];

	//	$data['titulo']=asignar_frase_diccionario ($data['diccionario'],$variables['llave'],$variables['modulo'],2);
		$data['titulo']=$variables['modulo'];


		$data['menus']=$this->model_generico->menus_root_categorias();
		foreach ($data['menus'] as $key => $value) {
			$data['menus'][$key]->submenus=$this->model_generico->menus_root($value->id_categorias_modulos_app,$this->session->userdata('id_roles'));

		}
		/* Llamo ala funcion generica para traer el listado de informacion del modulo */
		$data['lista']=$this->{$variables['modelo']}->listado($variables['modulo'],array('id_cursos',$id_cursos),array('orden','asc'));
		/* Envio header de la tabla de los campos que necesito mostrar */
		$data['titulos']=array("Nombre del módulo","Descripción corta","Tipo de plan","Estado","Opciones");
		/* Cargo vista de listado de informacion */


		$trmp=$this->model_generico->mispermisos($this->session->userdata('id_roles'),'actividades');	
		$data['if_actividades']=json_decode($trmp->id_roles);
		$data['if_descargables']=json_decode($trmp->id_roles);

		$this->load->view('root/view_'.$variables['modulo'].'_lista',$data);
	}

	/* FUNCION NUEVO DATO */
	public function nuevo()
	{
		/* Cargo variables globales del modulo*/
		$variables = $this->variables; $data['diccionario']=$this->variables['diccionario'];

		/* Titulo = nombre del modulo */
		$data['titulo']=$variables['modulo'];
		$data['menus']=$this->model_generico->menus_root_categorias();
		foreach ($data['menus'] as $key => $value) {
			$data['menus'][$key]->submenus=$this->model_generico->menus_root($value->id_categorias_modulos_app,$this->session->userdata('id_roles'));

		}
		$data['tipo_planes']=$this->model_generico->listado('tipo_planes',array('tipo_planes.id_estados','1'),array('orden','asc'));
		/* Cargo vista del modulo */
		$this->load->view('root/view_'.$variables['modulo'].'_nuevo',$data);
	}

	/* FUNCION GUARDAR INFORMACION */
	public function guardar()

	{ /* Cargo variables globales */
		$variables = $this->variables; $data['diccionario']=$this->variables['diccionario'];

		/* asigno variable id de lo que voy  aguardar (solo si es modo editar) */
		$id=$this->input->post ('id');

		/* Validaciones  basicas de lo que voy a editar */
		$this->form_validation->set_rules('nombre_modulo', 'Nombre', 'required|xss_clean');
		$this->form_validation->set_rules('introduccion_modulo', 'Introduccion', 'required|xss_clean');
		$this->form_validation->set_rules('id_estados', 'Estado', 'required|xss_clean');
		$this->form_validation->set_rules('id_tipo_planes', 'Tipo de plan', 'required|xss_clean');
		$this->form_validation->set_rules('id_cursos', 'Cursos', 'required|xss_clean');
		/* Si existe error en las validaciones, los muestra */
		if($this->form_validation->run() == FALSE)
		{ 

	#echo validation_errors();
			if ($id)  { $this->editar($id); } else { $this->nuevo();  }

		}

		else {
			/* Asigno en array los valores de los campos llegados por el formulario */
			$data = array(
				'nombre_modulo' => $this->input->post ('nombre_modulo'),
				'introduccion_modulo' => $this->input->post ('introduccion_modulo'),
				'contenido_modulo' => $this->input->post ('contenido_modulo'),
				'id_estados' => $this->input->post ('id_estados'),
				'id_tipo_planes' => $this->input->post ('id_tipo_planes'),
				'id_cursos' => $this->input->post ('id_cursos'),
				);

			/* Si tiene id, es porque es de editar y guarda la fecha de modificacion, de lo contrario, guarda fecha modificado y creado */
			if ($id) { $data[$variables['id']]=$id; $data['fecha_modificado']=date('Y-m-d H:i:s',time());  $data['id_usuario_modificado']=$this->session->userdata('id_usuario');  } else {  $data['fecha_modificado']=date('Y-m-d H:i:s',time());  $data['id_usuario_modificado']=$this->session->userdata('id_usuario');  $data['fecha_creado']=date('Y-m-d H:i:s',time()); $data['id_usuario_creado']=$this->session->userdata('id_usuario');   }

			/* Guardo la informacion */
			$id=$this->model_generico->guardar($variables['modulo'],$data,$variables['id'],array($variables['id'],$id));

			/* Redirecciono al listado */
			$accion_url=base_url().$this->uri->segment(1).'/'.$this->uri->segment(2).'/lista/'.$this->input->post('id_cursos').'/'.$id.'/guardado_ok';
			redirect($accion_url);
		}
	}



// funcion para validar datos en cascada
	public function check_validador($id)
	{
		/* consulto en cascada si hay datos para asi evitar error entre llaves foraneas */
		$tmp=$this->model_generico->listado('actividades_barra',array('id_modulos',$id));

#krumo ($tmp); 

		foreach ($tmp as $key => $tmp_value) {

			$tipo_actividades=$this->model_generico->listado('tipo_actividades',array('id_tipo_actividades',$tmp_value->id_tipo_actividades));
#krumo ($tipo_actividades);
			foreach ($tipo_actividades as $key => $tipo_actividades_value) {


				$mis_actividades=$this->model_generico->listado($tipo_actividades_value->tabla_actividad,array("id_".$tipo_actividades_value->tabla_actividad,$tmp_value->id_actividades));
#krumo ($mis_actividades);
				foreach ($mis_actividades as $key => $actividades_value) {

					$actividades_borrar[]=$actividades_value->nombre_actividad."(".$tipo_actividades_value->nombre_tipo_actividades.")";

				}



			}





		}

#krumo ($actividades_borrar);
	#	exit;			

		if (count ($tmp)==0)  {
			return true;
		}
		else {

			$this->form_validation->set_message('check_validador', 'Existe actividades en este modulo <b>'.implode(",", $actividades_borrar).'</b>, borrelos primero');
			return false;
		}

	}



	/* FUNCION BORRAR */
	public function borrar()
	{
		
		/*Cargo variables globales*/
		$variables = $this->variables; $data['diccionario']=$this->variables['diccionario'];
		/* Validacion basica del id */
		$this->form_validation->set_rules('id', 'Id', 'required|xss_clean');
		/*Asigno valor del id a una variable*/
		$id=$this->input->post('id');
		/*Llamo funcion borrar */

		if ($this->form_validation->run() == FALSE)
		{
			$this->lista($this->input->post('id_cursos'));
		}
		else
		{
			$this->model_generico->borrar($variables['modulo'],array($variables['id']=>$this->input->post ('id')));
			/*Preparo redireccion al listado de informacion del modulo*/
			$accion_url=base_url().$this->uri->segment(1).'/'.$this->uri->segment(2).'/lista/'.$this->input->post ('id_cursos').'/borrado_ok';
			redirect($accion_url);
		}


	}

	/*FUNCION EDITAR */
	public function editar($id,$error_extra=null)
	{
		/*Cargo variables globales*/
		$variables = $this->variables; $data['diccionario']=$this->variables['diccionario'];
		$data['titulo']=$variables['modulo'];
		$data['menus']=$this->model_generico->menus_root_categorias();
		foreach ($data['menus'] as $key => $value) {
			$data['menus'][$key]->submenus=$this->model_generico->menus_root($value->id_categorias_modulos_app,$this->session->userdata('id_roles'));

		}
		/*LLamo funcion para cargar informacion detalle para editarlo*/
		$data['detalle']=$this->model_generico->detalle($variables['modulo'],array($variables['id']=>$id));
		$data['tipo_planes']=$this->model_generico->listado('tipo_planes',array('tipo_planes.id_estados','1'),array('orden','asc'));

		/*En caso de algun error extra, lo llevo a la vista para cargarlo*/
		$data['error_extra']=$error_extra;
		/*Llamo a la vista de edicion*/
		$this->load->view('root/view_'.$variables['modulo'].'_editar',$data);

	}

	/*FUNCION ORDENAR PARA EL LISTADO (ordena con arrastrar y soltar filas de la tabla de listado)*/
	public function ordenar($id_cursos)
	{
		/*Cargo variables globales*/
		$variables = $this->variables; $data['diccionario']=$this->variables['diccionario'];
		/*Cargo orden de listas*/
		$data = $this->input->post('data');
		$this->load->model('model_modulos');
		/*Separo el orden traido por la coma*/
		$dataarray=explode (",",$data);
		/* por medio de un ciclo, empezar a actualizar el nuevo orden */
		foreach ($dataarray as $key => $value) {
			/* Actualizo nuevo orden de cada uno de los elementos */
			$this->model_modulos->ordenar($variables['modulo'],array("orden"=>$key+1),array($variables['id'],$value),$id_cursos);
		}

	}

}