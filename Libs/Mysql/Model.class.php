<?php
namespace Libs\Mysql;
use Libs\Mysql\DbMysqli;
function __autoload($pClassName){
	$pClassName = str_replace("\\", "/", $pClassName);
	require_once(APP_PATH.$pClassName.'.class.php');
}
class Model{
	private $db;
	private $prex = '';//前缀
	private $pk;//主键
	private $table;//不带前缀的表名
	private $tablename;//真实表名
	public  $error;//错误信息
	private $fields;//字段名
	private $fields_type;//字段类型
	private $sqlinfo = array(
		's_field' => '*',//查询字段
		's_insert'  => '',//新增数据
		's_update'  => '',//修改数据
		's_where' => '',//查询条件
		's_group' => '',//分组字段，只支持传入一个
		's_order' => '',
		's_limit' => '',
		's_sql'   => '',//构造后的SQL语句
		's_action'=> '',//当前使用的方法，查询或者插入...
	);
	public function __construct($table = ""){
		$this->db = new DbMysqli(DB_HOST,DB_USER,DB_PWD,DB_NAME,DB_CHARSET);
		$this->prex = DB_PREX;
		if(!empty($table)){
			$this->table($table);
		}
	}

	public function __get($name){
		return $this->sqlinfo[$name];
	}

	public function __set($name,$value){
		if(isset($name)){
			$this->sqlinfo[$name] = $value;
		}
	}

	public function __isset($name){
		return isset($this->sqlinfo[$name]);
	}


	//判断表是否存在
	protected function table_is_exists(){
		return $this->db->table_is_exists($this->tablename);
	}

	//获取表的字段信息
	protected function getTableFields(){
		$fields = $this->db->getTableFields($this->tablename);
		foreach($fields as $key => $val){
			if($val['Key'] === "PRI") $this->pk = $val['Field'];
			$this->fields[] = $val['Field'];
			$this->fields_type[$val['Field']] = $this->getFieldType($val['Type']);
		}
	}

	protected function getFieldType($type){
		$integer = array(
			'tinyint',
			'smallint',
			'mediumint',
			'int',
			'integer',
			'bigint',
			'float',
			'double',
			'decimal',
			'year',
		);
		$string = array(
			'date',
			'time',
			'datetime',
			'timestamp',
			'char',
			'varchar',
			'tinyblob',
			'tinytext',
			'blob',
			'text',
			'mediumblob',
			'mediumtext',
			'longblob',
			'longtext'
		);
		$type = strtolower($type);
		foreach($integer as $val){
			if(strpos($type,$val) === FALSE){
				continue;
			}else{
				return 'number';
			}
		}
		foreach($string as $val){
			if(strpos($type,$val) === FALSE){
				continue;
			}else{
				return 'string';
			}
		}
	}

	//检查字段
	protected function checkField($data){
		$arr = array();
		foreach ($data as $key => $val) {
			if(!in_array($key,$this->fields)){
				continue;
			}else{
				$arr[$key] = ($this->fields_type[$key]=='number') ? $val:"'".$val."'";
			}
		}
		return $arr;
	}

	/*构造sql语句
	选择：select * from table1 where order范围
	插入：insert into table1 (field1,field2) values (value1,value2)
	删除：delete from table1 where 范围
	更新：update table1 set field1=value1 where 范围
	*/

	//处理新增数据
	protected function dealAddData($data){
		//过滤字段
		$data = $this->checkField($data);
		if(isset($data[$this->pk])) unset($data[$this->pk]);
		$field = array();
		$value = array();
		foreach ($data as $key => $val) {
			$field[] = $key;
			$value[] = $val;
		}
		$this->s_insert = "(".implode(',',$field).") VALUES (".implode(',',$value).")";
	}

	//处理更新数据
	protected function dealSaveData($data){
		//过滤字段
		$data = $this->checkField($data);
		if(isset($data[$this->pk])){
			$map[$this->pk] = $data[$this->pk];
			$this->where($map);
			unset($data[$this->pk]);
		}
		$arr = array();
		foreach($data as $key=>$val){
			$arr[] = $key." = ".$val;
		}
		$this->s_update = "SET ".implode(',',$arr);
	}

	//查询条件字段处理
	protected function paramWhereField($key,$val){
		if(!is_array($val)){
			$val = explode(',', $val);
		}
		if($this->fields_type[$key] == 'string'){
			foreach ($val as $k => $vv) {
				$val[$k] = "'".$vv."'";
			}
		}
		$val = implode(',', $val);		
		return $val;
	}

	//构造语句
	protected function buildSql(){
		$sql = $this->s_action." ";
		switch ($this->s_action) {
			case 'INSERT INTO'://insert into table1 (field1,field2) values (value1,value2)
				$sql .= $this->tablename." ".$this->s_insert;
				break;
			case 'UPDATE'://update table1 set field1=value1 where
				$sql .= $this->tablename." ".$this->s_update." ".$this->s_where;
				break;
			case 'DELETE':
				$sql .= "FROM ".$this->tablename.' '.$this->s_where;
				break;
			case 'SELECT':
				$sql .= $this->s_field.' FROM '.$this->tablename;
				if(!empty($this->s_where)) $sql .= " ".$this->s_where;
				if(!empty($this->s_group)) $sql .= " ".$this->s_group;
				if(!empty($this->s_order)) $sql .= " ".$this->s_order;
				if(!empty($this->s_limit)) $sql .= " ".$this->s_limit;
				break;
		}
		$this->s_sql = $sql;
		//echo $sql."<br/>";
	}

	public function table($name = ""){
		if(empty($name)){
			die("表名不能为空");
		}
		$this->table = $name;
		$this->tablename = $this->prex.$this->table;
		//判断表是否存在
		if(!$this->table_is_exists()){
			die($this->tablename."不存在");
		}
		//获取表的字段信息
		$this->getTableFields();
		return $this;
	}

	//查询字段
	public function field($field = ''){
		if(!empty($field)){
			if(is_array($field)){
				$field = implode(',', $field);
			}
			$this->s_field = $field;
		}
		return $this;
	}

	//分析查询条件
	//支持字符串
	//不支持not查询，只支持一级条件，AND 与 OR 
	public function where($where = ''){
		$_where = "";
		if(!is_array($where)){
			$_where = $where;
		}elseif(is_array($where) && !empty($where)){
			if(isset($where['_logic'])){
				$logic = " ".strtoupper($where['_logic'])." ";
				unset($where['_logic']);
			}else{
				$logic = " AND ";
			}
			$length = count($where);
			$i = 1;
			foreach ($where as $key => $val) {
				if(is_array($val)){
					switch ($val[0]) {
						case 'in':
							$val_1 = $this->paramWhereField($key,$val[1]);
							$_where .= $key." IN (".$val_1.")";
							break;
						case 'notin':
							$val_1 = $this->paramWhereField($key,$val[1]);
							$_where .= $key." NOT IN (".$val_1.")";
							break;
						case 'between':
							$val_1 = $this->paramWhereField($key,$val[1]);
							$_where .= $key." BETWEEN (".$val_1.")";
							break;
						case 'notbetween':
							$val_1 = $this->paramWhereField($key,$val[1]);
							$_where .= $key." NOT BETWEEN (".$val_1.")";
							break;
						case 'like':
							$_where .= $key." LIKE '".$val[1]."'";
							break;
						case 'notlike':
							$_where .= $key." NOT LIKE '".$val[1]."'";
							break;						
						default:
							$_where .= $key.' '.$val[0].' '.($this->fields_type[$key] == 'number') ? $val[1]:"'".$val[1]."'";
							break;
					}
				}else{
					$val = ($this->fields_type[$key] == 'number') ? $val:"'".$val."'";
					$_where .= $key.' = '.$val;
				}
				if($i < $length){
					$_where .= $logic;
				}
				$i++;
			}

		}
		$this->s_where = (empty($_where)) ? '':"WHERE ".$_where;
		return $this;
	}

	//只支持一个字段分组
	public function group($field){
		$group = '';
		if(!empty($field)) $group = "GROUP BY ".$field;
		$this->s_group = $group;
		return $this;
	}

	public function order($order){
		if(!empty($order)){
			$this->s_order = "ORDER BY ".$order;
		}
		return $this;
	}

	public function limit($limit){
		if(!empty($limit)){
			$this->s_limit = "LIMIT ".$limit;
		}
		return $this;
	}

	public function add($data = ''){
		$this->s_action = "INSERT INTO";
		if(empty($data)){
			$this->error = '插入数据为空';
			return false;
		}
		$this->dealAddData($data);
		$this->buildSql();
		if($this->db->query($this->s_sql)){
			return $this->db->insert_id();
		}else{
			$this->error = $this->db->getError();
			return false;
		}
	}

	public function save($data = ''){
		$this->s_action = "UPDATE";
		if(empty($data)){
			$this->error = '插入数据为空';
			return false;
		}
		$this->dealSaveData($data);
		$this->buildSql();
		if($this->db->query($this->s_sql)){
			return $this->db->affected_rows();
		}else{
			$this->error = $this->db->getError();
			return false;
		}
	}

	public function delete($id = 0){
		$this->s_action = "DELETE";
		if($id && is_numeric($id)){
			$map[$this->pk] = $id;
			$this->where($map);
		}
		$this->buildSql();
		if($this->db->query($this->s_sql)){
			return $this->db->affected_rows();
		}else{
			$this->error = $this->db->getError();
			return false;
		}
	}

	public function select(){
		$this->s_action = "SELECT";
		$this->buildSql();
		if($this->db->query($this->s_sql)){
			return $this->db->fetch_array();
		}else{
			$this->error = $this->db->getError();
			return false;
		}
	}

	public function find($id = 0){
		if($id && is_numeric($id)){
			$map[$this->pk] = $id;
			$this->where($map);
		}
		$this->s_action = "SELECT";
		$this->buildSql();
		if($this->db->query($this->s_sql)){
			return $this->db->fetch_row();
		}else{
			$this->error = $this->db->getError();
			return false;
		}
	}
}