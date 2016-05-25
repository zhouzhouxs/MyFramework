<?php
namespace Libs\Mysql;
/**
* 数据库工厂
*/
class DbMysqli
{
	private $link;
	private $result;
	private $error;
	function __construct($host,$user,$pwd,$name,$charset){
		$link = @mysqli_connect($host,$user,$pwd,$name);
		if(!$link){
			die("Connect error:".mysqli_connect_errno().'--'.mysqli_connect_error());
		}
		if(!@mysqli_set_charset($link,$charset)){
			die("error:".mysqli_errno($link)."--".mysqli_error($link));
		}
		$this->link = $link;
	}

	public function query($sql){
		$this->result = mysqli_query($this->link,$sql);
		if(!$this->result){
			$this->error = mysqli_errno($this->link).'--'.mysqli_error($this->link);
			return false;
		}
		return true;
	}

	public function fetch_array(){
		$data = $this->result->fetch_all(MYSQLI_ASSOC);
		$this->result->free();
		return $data;
	}
	public function fetch_row(){
		$data = $this->result->fetch_assoc();
		$this->result->free();
		return $data;
	}

	//返回受影响的行数
	public function affected_rows(){
		$row = mysqli_affected_rows($this->link);
		//$this->result->free();
		if($row < 0) return false;
		return $row;
	}

	//返回最后插入的ID
	public function insert_id(){
		$id = mysqli_insert_id($this->link);
		//$this->result->free();
		return $id;
	}

	public function getError(){
		return $this->error;
	}

	public function table_is_exists($table = ""){
		if(empty($table)) return false;
		$sql = "SHOW TABLES LIKE '".$table."'";
		if($this->query($sql)){
			if($this->result->num_rows){
				return true;
			}else{
				return false;
			}
			$this->result->free();
		}else{
			return false;
		}
	}

	public function getTableFields($table = ""){
		if(empty($table)) return false;
		$sql = "SHOW COLUMNS FROM ".$table;
		if($this->query($sql)){
			return $this->fetch_array();
		}else{
			return false;
		}
	}
}