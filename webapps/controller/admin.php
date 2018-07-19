<?php
require_once($_SERVER["DOCUMENT_ROOT"] . '/controller/comService.php');

class Admin extends comService
{
	protected $auth_actions = array('');
	protected $_label = array(
		"TYPE" => "タイプ",
		"NAME" => "名称",
		"REMARK" => "備考",
		"DELETE_FLAG" => "有効/無効",
		"SORT_NO" => "並び順",
		"CODE" => "コード"
	);

	/**
	* GET :
	* POST :
	* @param  array $params
	* @return json response
	*/
	public function setup($params)
	{
		if ($this->request->isPost()) {
			$result = $this->post_setup($_POST);
		}
		if ($this->request->isGet()) {
			$result = $this->get_setup();
		}
		return $result;
	}
	private function get_setup($params)
	{
		$schema = $this->application->dbi->config_db;
		if(!empty($params["system"])) $schema = $params["system"];

		$query = "";
		$ret = $this->application->dbi->getDatatable($query);
		if($ret["status"]!=="success") return $this->notfoundResponce();
		return $ret;
	}
	private function post_setup($params)
	{
		$schema = $this->application->dbi->config_db;
		if(!empty($params["system"])) $schema = $params["system"];
		$ret = $this->get_setup();
		if($ret["status"]!=="success") return $this->errorResponce();
		$is_success = true;
		foreach($ret["result"] as $key => $val){
			$table = $val["Tables_in_".$schema];
			$ret = $this->post_crud(array("table"=>$table, "name"=>$table));
			if($ret["status"]!=="success") return $this->errorResponce();
		}
		return $this->getResponce();
	}

	/**
	* GET :
	* POST :
	* @param  array $params
	* @return json response
	*/
	public function crud($params)
	{
		if ($this->request->isPost()) {
			$result = $this->post_crud($_POST);
		}
		if ($this->request->isGet()) {
			$result = $this->get_crud($_GET);
		}
		return $result;
	}

	public function post_crud(array $params)
	{
		$config_db = $this->application->dbi->config_db;
		$schema = $this->application->dbi->config_db;
		if(empty($params["table"])) return $this->badRequestResponce("not table");
		if(empty($params["name"])) return $this->badRequestResponce("not name");
		if(!empty($params["system"])) $schema = $params["system"];
		$table = $params["table"];
		$name = $params["name"];
		$ret = $this->get_crud($params);
		if($ret["status"]!=="success") return $this->errorResponce("get_crud error");
		$is_success = true;
		$m_search_ins = <<<EOT
INSERT
INTO $config_db.m_search(
  SYSTEM_CODE
  ,CODE
  , NAME
  , TABLE_STRING
  , SELECT_STRING
  , WHERE_STRING
  , ORDER_STRING
  , TYPE
  , OPTION_STRING
  , SORT_NO
)
VALUES (
  /*SYSTEM_CODE*/
  ,/*SEARCH_CODE*/
  , /*SEARCH_NAME*/
  , /*TABLE_STRING*/
  , /*SELECT_STRING*/
  , /*WHERE_STRING*/
  , /*ORDER_STRING*/
  , /*SEARCH_TYPE*/
  , /*OPTION_STRING*/
  , /*SORT_NO*/
);
EOT;
		$m_query_ins = <<<EOT
INSERT
INTO $config_db.m_query(
  SYSTEM_CODE
 , CODE
  , TYPE
  , NAME
  , QUERY_STRING
  , SORT_NO
, DELETE_FLAG
)
VALUES (
  /*SYSTEM_CODE*/
  ,/*QUERY_CODE*/
  , /*QUERY_TYPE*/
  , /*QUERY_NAME*/
  , /*QUERY_STRING*/
  , /*SORT_NO*/
, /*DELETE_FLAG*/
);
EOT;
		$m_page_ins = <<<EOT
INSERT
INTO $config_db.m_page(
  SYSTEM_CODE
  ,CODE
  ,TYPE
  , NAME
  , OPTION_STRING
  , REMARK
  , SORT_NO
)
VALUES (
  /*SYSTEM_CODE*/
  ,/*PAGE_CODE*/
  ,/*PAGE_TYPE*/
  , /*NAME*/
  , /*OPTION_STRING*/
  , /*REMARK*/
  , /*SORT_NO*/
);
EOT;
		foreach($ret["data"] as $key=>$d){
			$req = $d;
			$req["SYSTEM_CODE"] = $schema;
			$req["PUBLIC_FLAG"] = "0";
			$req["DELETE_FLAG"] = "0";

			if(isset($d["SEARCH_CODE"])){
				$ret = $this->application->dbi->execQuery($m_search_ins, $req);
			}
			else if(isset($d["QUERY_CODE"])){
				$ret = $this->application->dbi->execQuery($m_query_ins, $req);
			}
			else if(isset($d["PAGE_CODE"])){
				$ret = $this->application->dbi->execQuery($m_page_ins, $req);
			}
			if($ret["status"]!=="success"){
				$is_success = false;
			}
		}
		if($is_success===false) return $this->request->errorResponce("data create error");
		return $this->getResponce();
	}
	private function get_crud($params)
	{
		$schema = $this->application->dbi->config_db;
		if(empty($params["table"])) return $this->badRequestResponce();
		if(empty($params["name"])) return $this->badRequestResponce();
		if(!empty($params["system"])) $schema = $params["system"];
		$table = $params["table"];
		$name = $params["name"];
		$query = "show columns from $schema.".$table;
		$ret = $this->application->dbi->getDatatable($query);
		if($ret["status"]!=="success") return $this->notfoundResponce();
		$columns = $ret["result"];
		$ret["data"] = $this->get_query_string($table, $name, $columns);
		return $ret;
	}
	private function get_query_string($table, $name, $columns)
	{
		if(empty($table)) return "";
		if(empty($columns) || count($columns)<1) return "";
		$select = '';
		$keyword = '';
		$where = '';
		$option_header = "";
		$update = "";
		$insert = "";
		$insert_values = "";
		$page_add_form = "";
		$page_search_form = "";
		foreach($columns as $key=>$column ){
			$field = $column["Field"];
			$sel_field = $field;
			if($column["Type"]==="timestamp"){
				//$sel_field = 'date_format('.$field.','."'".'%Y/%m/%d'."'".') as '.$field;
			}
			$select .= $sel_field.',';
			if($field!=="ID" && $field!=="ADD_TIME" &&
				$field!=="UPD_TIME"){
				if($field !=="DELETE_FLAG"){
					$insert .= $field.',';
					$insert_values .= '/*'.$field.'*/,';
				}
				$update .= $field.'=/*'.$field.'*/,';
			}

			if($field==="ADD_TIME" || $field==="UPD_TIME"){
				continue;
			}

			$_txt = $field;
			if(isset($_label[$_txt])) $_txt = $_lable[$_txt];
			$_cl = "col-lg-12";
			$_tp = explode("(", str_replace(')', '', $column["Type"])."(");
			$_tp_nm = $_tp[0];
			$_tp_len = 3000;
			if(count($_tp)>1 && is_numeric($_tp[1])) $_tp_len=intval($_tp[1]);
			$_page_form = '<BR>"'.$key.'" : {';
			$_page_form .= '"field":"'.$field.'",';
			if($column["Null"]!=="YES"){
				$_page_form .= '"required": "true",';
			}
			$is_search = false;
			switch($_tp_nm){
				case "text":
				case "varchar":
				case "char":
					$is_search = true;
					if($_tp_len > 128){
						$is_search = false;
						$_page_form .= '"type": "textarea",';
						$_page_form .= '"maxlength": "'.$_tp_len.'",';
					}
					else if($_tp_len < 33) {
						if($field==="SYSTEM_CODE"){
							$_page_form .= '"type": "select",';
							$_page_form .= '"accesskey": "m_query",';
							$_page_form .= '"query": "get_system_code",';
							$_page_form .= '"code_field": "SYSTEM_CODE",';
							$_page_form .= '"name_field": "SYSTEM_NAME",';
						}
						else {
							$_page_form .= '"type": "select",';
							$_page_form .= '"accesskey": "m_code",';
							if($field === "TYPE" && $table==="m_search"){
								$_page_form .= '"target": "SEARCH_TYPE",';
							}
							if($field === "TYPE" && $table==="m_query"){
								$_page_form .= '"target": "QUERY_TYPE",';
							}
							if($field === "TYPE" && $table==="m_page"){
								$_page_form .= '"target": "PAGE_TYPE",';
							}
						}
					}
					else {
						$_page_form .= '"type": "text",';
						$_page_form .= '"maxlength": "'.$_tp_len.'",';
					}
					if($_tp_nm==="char" && $_tp_len > 32){
						$_page_form .= '"inputtype": "hankaku",';
					}
					else {
						$_page_form .= '"inputtype": "zenkaku",';
						//文字列タイプは、キーワード検索にする
						$keyword .= ''.$field.' like /*%searchword%*/ OR <BR>';
					}
					break;
				case "int":
					$is_search = true;
					if($field==="ID"){
						$_page_form .= '"type": "hidden",';
						$is_search = false;
					}
					else if($field==="DELETE_FLAG"){
						$_page_form .= '"type": "select",';
						$_page_form .= '"accesskey": "m_code",';
					}
					else {
						$_page_form .= '"type": "number",';
					}
					break;
				case "timestamp":
				case "date":
					$_page_form .= '"type": "date",';
					break;
				default:
					$_page_form .= '"type": "text",';
			}
			$_page_form .= '"text":"'.$_txt.'",';
			$_page_form .= '"class":"'.$_cl.'"},';
			if($_tp_len < 128){
				$option_header .= '"'.$key.'" : {';
				$option_header .= '"field":"'.$field.'",';
				$option_header .= '"text":"'.$field.'",';
				$option_header .= '"sort":"'.$field.'",';
				$option_header .= '"type":""},<BR>';
			}
			if($field==="NAME" || $field==="SORT_NO" || $field==="LV"){
				$is_search=false;
			}
			if($is_search===true){
				//code類をwhere句に追加
				$where .= '/*if '.$field.' != null*/<BR>';
				$where .= ' AND '.$field.'=/*'.$field.'*/<BR>';
				$where .= '/*end if*/<BR>';
				$page_search_form .=$_page_form;
			}
			$page_add_form .=$_page_form;
		}
		$page_add_form = trim($page_add_form,",");
		$page_search_form = trim($page_search_form,",");
		$where .= ' /*if searchword != null */<BR>';
		$where .= ' AND('.trim($keyword, ' OR <BR>').')<BR>';
		$where .= '/*end if*/<BR>';
		$order = <<<EOT
/*if _order_ == 1*/
UPD_TIME desc
/*end if*/
/*if _order_ != 1*/
 /*_order_*/
/*end if*/
EOT;
		$order .=' LIMIT /*$_limit_*/ OFFSET /*$_offset_*/';
		$select = trim($select, ",");
		$option = '"header" : {'.$option_header;
		$option .= '"99" : {"text" : "操作", "class" : "", "type" : "edit_copy_delete", "field" : null}<br>';
		$option .= '},<br>';
		$option .= '"control" : ["newadd", "refresh","search","detailsearch","back","pageprev","pagenext","pagestart", "pageend"]';
		$insert = "INSERT INTO ".$table."(".trim($insert,",").")";
		$insert .= "VALUES(".trim($insert_values,",").");";
		$update = "UPDATE ".$table." SET ".trim($update,",").'<BR> WHERE ID=/*ID*/';
		$delete = "DELETE FROM ".$table.'<BR> WHERE ID=/*ID*/';
		$edit = "SELECT * FROM ".$table.'<BR> WHERE ID=/*ID*/';
		$page_add ='"button" : {<br>';
		$page_add .='"0" : { "text" : "保存", "accesskey" : "save", "class" : "save"},<br>';
		$page_add .='"1" : { "text" : "キャンセル", "accesskey" : "close", "class" : "close"}<br>';
		$page_add .='},<br>';
		$page_add .='"form" : {<br>'. $page_add_form;
		$page_add .='}';
		$page_search ='"button" : {<br>';
		$page_search .='"0" : { "text" : "検索", "accesskey" : "search", "class" : "search"},<br>';
		$page_search .='"1" : { "text" : "クリア", "accesskey" : "clear", "class" : "clear"},<br>';
		$page_search .='"2" : { "text" : "閉じる", "accesskey" : "close", "class" : "close"}<br>';
		$page_search .='},<br>';
		$page_search .='"form" : {<br>'.str_replace('"required": "true",', '', $page_search_form);
		$page_search .='}';

 		return array(
			$table => array(
				'SEARCH_CODE' => $table,
				'SEARCH_NAME' => $name,
				'SEARCH_TYPE' => "0",
				'SELECT_STRING' => $select,
				'TABLE_STRING' => $table,
				'WHERE_STRING' => $where,
				'ORDER_STRING' => $order,
				'OPTION_STRING' => $option,
				"SORT_NO" => "1"
			),
			$table."_ins" => array(
				'QUERY_CODE' => $table."_ins",
				"QUERY_STRING" => $insert,
				"QUERY_TYPE" => "1",
				"QUERY_NAME" => $name."登録",
				"SORT_NO" => "1"
			),
			$table."_del" => array(
				'QUERY_CODE' => $table."_del",
				"QUERY_STRING" => $delete,
				"QUERY_TYPE" => "1",
				"QUERY_NAME" => $name."削除",
				"SORT_NO" => "2"
			),
			$table."_upd" => array(
				'QUERY_CODE' => $table."_upd",
				"QUERY_STRING" => $update,
				"QUERY_TYPE" => "1",
				"QUERY_NAME" => $name."更新",
				"SORT_NO" => "3"
			),
			$table."_edit" => array(
				'QUERY_CODE' => $table."_edit",
				"QUERY_STRING" => $edit,
				"QUERY_TYPE" => "0",
				"QUERY_NAME" => $name."編集",
				"SORT_NO" => "4"
			),
			$table."_add" => array(
				'PAGE_CODE' => $table."_add",
				"OPTION_STRING" => $page_add,
				"PAGE_TYPE" => "param",
				"NAME" => $name."登録画面",
				"SORT_NO" => "1"
			),
			$table."_search" => array(
				'PAGE_CODE' => $table."_search",
				"OPTION_STRING" => $page_search,
				"PAGE_TYPE" => "param",
				"NAME" => $name."検索画面",
				"SORT_NO" => "2"
			)
		);
	}
}
