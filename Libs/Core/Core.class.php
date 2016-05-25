<?php
require(APP_PATH."config.php");
require(APP_PATH."functions.php");
AutoLoadClass::Register();
APP::Init();
//自动装载类
class AutoLoadClass{
	public static function Register(){
		if(function_exists('__autoload')){
			spl_autoload_register('__autoload');
		}

		return spl_autoload_register(array('AutoLoadClass','Load'));
	}

	public static function Load($pClassName){
		//echo "load:$pClassName<br>";
		$pClassName = str_replace("\\", "/", $pClassName);
		$class = substr($pClassName, strrpos($pClassName, '/')+1);
		//echo "$class\n";
		if(class_exists($class,FALSE)){
			return false;
		}
		$classPath = APP_PATH . $pClassName.'.class.php';
		if((file_exists($classPath) === FALSE) || (is_readable($classPath) === FALSE)){
			return false;
		}
		require_once($classPath);
	}
}

//路由类
class Route{

	public function __construct(){
		$this->Analyse();
	}

	/*
	*获得方法的参数列表(*)
	*/
	private function get_func_params($class,$func){
		$r = new ReflectionClass($class);
		$els = $r->getMethod($func);
		$listobj = $els->getParameters();
		$params = array();
		foreach ($listobj as $key => $val) {
			$params[$val->name] = $val->getDefaultValue();
		}
		return $params;
	}

	private function bindActionValue($params){
		if(empty($params)) return array();
		$retarr = array();
		foreach($params as $key => $val){
			$value = I("{$key}",$val);
			$retarr[] = $value;
		}
		return $retarr;
	}

	/*
	*通过参数选择要执行的方法(区分大小写)
	*方法标识 action
	*如url = index.php?action=savepage&field=value...
	*action 表示要执行的方法，后面为传入的参数
	*支持参数绑定
	*/
	private function Analyse(){
		$ControllName = I("get.".C_N,"Index");
		$ActionName = I("get.".A_N,"index");
		if(class_exists("\\Controller\\".$ControllName)!== FALSE){
			defined("CTR_NAME") or define("CTR_NAME", $ControllName);
			if(method_exists("\\Controller\\".CTR_NAME,$ActionName)){
				defined("ACTION_NAME") or define("ACTION_NAME", $ActionName);
				//获得参数
				$params = $this->get_func_params("\\Controller\\".CTR_NAME,ACTION_NAME);
				//执行绑定
				$paramValue = $this->bindActionValue($params);
				$spaceControll = "\\Controller\\".CTR_NAME;
				$Controller = new $spaceControll;
				call_user_func_array(array($Controller,ACTION_NAME),$paramValue);
			}else{
				dies($ActionName."方法不存在");
			}
		}else{
			dies($ControllName."控制器不存在");
		}
	}
}

//装载类
class APP{
	public static function Init(){
		new Route();
	}
}