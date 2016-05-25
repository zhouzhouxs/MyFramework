<?php
namespace Controller;
use Libs\Core\Controller;
use Libs\Mysql\Model;

class Index extends Controller{
	public function index(){
		$M = new Model('emoji');
		$list = $M->select();
		$emoji = array();
		foreach ($list as $key => $value) {
			$emoji[$value['unicode']] = $value['emoji'];
		}
		$emoji = json_encode($emoji);
		$this->assign('emoji',$emoji);
		$this->display();
	}

	public function indexsave(){
		// $emoji = I("post.emoji");
		$M = new Model('emoji');
		// $agent = $_SERVER['HTTP_USER_AGENT'];
		// $data['emoji'] = $emoji;
		// $data['useragent'] = $agent;
		// $r = $M->add($data);
		// if(!$r) dies($M->error);
		// $this->redirect("index.php");
		$emoji = I("post.emoji");
		$emoji = trim($emoji,',');
		$emoji = explode(',', $emoji);
		foreach ($emoji as $val) {
			$data['emoji'] = $val;
			$jsoncode = json_encode($val);
			$data['unicode'] = mb4Tounicode($jsoncode);
			$M->add($data);
			print_r($data);
			echo '<br>';
		}
		//print_r($emoji);
	}

	public function indexajax(){
		$emoji = I('get.emoji');
		echo '"'.json_encode($emoji).'"';
	}
}