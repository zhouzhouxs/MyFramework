<?php
namespace Libs\Core;

class Controller{
	public $error;
	public $viewData = array();//模板数据s
	public $viewContent;//渲染过后的模板内容

	protected function assign($field,$value = ''){
		if(is_array($field)){
			$this->viewData = array_merge($this->viewData,$field);
		}else{
			$this->viewData[$field] = $value;
		}
	}

	/*渲染页面，返回页面内容，可修改内容后用echo输出到页面显示*/
	protected function render($file = null){
		$path = Template;
		$file = $file ? $file : CTR_NAME.'/'.ACTION_NAME;
		$fullpath = $path.$file.Ext;
		if(!file_exists($fullpath)) dies("模板".$fullpath."不存在");
		extract($this->viewData);
		//ob_end_clean();
		ob_start();
		require_once $fullpath;
		$content = ob_get_contents();
		ob_end_clean();
		ob_start();
		$this->viewContent = $content;
		return $content;
	}

	/*渲染后输出内容到页面*/
	protected function display($file = null){
		$this->render($file);
		// 网页字符编码
        header("Content-type:text/html;charset=utf-8");
        header('Cache-control: private');  // 页面缓存控制
        header('X-Powered-By:Alxg');
		echo $this->viewContent;
	}

	//显示错误信息
	public function error($message){
		$this->assign("error",$message);
		$this->display('error');
		die();
	}

	public function redirect($url){
		header("Location:{$url}");
		die();
	}
}