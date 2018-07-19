<?php
require_once($gPathList["lib"].'dbi.php');

// ===========================================================
/**
 * comDbi
 * @return void
 */
class comDbi extends Dbi
{
	public $config_db = "control";
	public $system = "";

	protected $hidden_post_paramater = array(
		"password", "password_confirm", "password_old"
	);

	public function getSystem(){
		$result = array();
		if(!isset($_SESSION["getSystem"])){
			$host = $_SERVER["SERVER_NAME"];
			//masterから、ホストによりsystemとconnectを取得
			$query = 'select * from master.m_system s';
			$query .= ' inner join master.m_connect c on c.SYSTEM_CODE=s.SYSTEM_CODE';
			$query .= ' WHERE HOST_NAME ='."'".$host."'";
			$query .= ' limit 1';
			$ret = $this->getDatatable($query);
			if($ret["status"] !== "success" || count($ret["result"])<1){
				// dupe
				$status = "failed";
				$message = "ERROR" ;
				$description = $query ;
				@TXT_LOG("err", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "setSystem ".$message, $description) ;
			}
			$result = $ret["result"][0];
			$_SESSION["getSystem"] = $result;
		}
		else {
			$result = $_SESSION["getSystem"];
		}
		$this->dbhost = $result["DB_HOST"];
		$this->dbname = $result["DB_NAME"];
		$this->dbuser = $result["DB_USER"];
		$this->dbpass = $result["DB_PASS"];
		$this->system = $result["SYSTEM_CODE"];
		$this->dbConnect();
		return $result;
	}
	public function execConfigSearch($query_code, $data=null){
		$status = "success";
		$description = "";
		$message = "";
		$ret = "";
		$result = "";
		$wherequery = "";
		$wherequery = $wherequery." WHERE CODE=".$this->_esn_sq($query_code)." and DELETE_FLAG=0";
		/*
		$wherequery = $wherequery." AND (SYSTEM_CODE=".$this->_esn_sq($this->system);
		$wherequery = $wherequery." OR 'control' = ".$this->_esn_sq($this->system).")";
		*/
		$wherequery = $wherequery." ORDER BY SORT_NO";

		$count = 0;
		$name = "";
		$option = "";
		$type = "page";
		$logging = true;
		$query = "SELECT * FROM ".$this->config_db.".m_search ".$wherequery;
		@TXT_LOG("debug", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "execConfigSearch", $query) ;
		$ret = $this->getDatatable($query);
		if($ret["status"] !== "success"){
			// dupe
			$status = "failed";
			$message = "ERROR" ;
			$description = "DEF QUERY ERROR(".$wherequery.")" ;
			//@TXT_LOG("err", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "execConfigSearch_".$message, $description) ;
			@$this->addConfigLog("err", "execConfigSearch_".$message, $description) ;
		}
		else {
			$m_query = $ret["result"];
		}

		if($status==="success"){
			$decodeField = array("OPTION_STRING", "SELECT_STRING", "TABLE_STRING", "ORDER_STRING", "WHERE_STRING");
			for($i=0;$i<count($decodeField);$i++){
				if($m_query[0][$decodeField[$i]]!=""){
					//DBの静的設定のためデコードしても安全
					$m_query[0][$decodeField[$i]] = __WEBIO_return($m_query[0][$decodeField[$i]]);
					$m_query[0][$decodeField[$i]] = $this->queryEscape($m_query[0][$decodeField[$i]], $data);
				}
			}
			$system = $m_query[0]["SYSTEM_CODE"];
			$name = $m_query[0]["NAME"];
			$option = $m_query[0]["OPTION_STRING"];

			$this->dbChange($system);
			$countquery = "SELECT COUNT(*) AS COUNT FROM ".$m_query[0]["TABLE_STRING"];
			$wherequery = " WHERE TRUE ".$m_query[0]["WHERE_STRING"];
			$countquery = $countquery.$wherequery;
			@TXT_LOG("db", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "------m_search------".$query_code."-------------") ;
			$ret = $this->getDatatable($countquery,false);
			if($ret["status"] !== "success"){
				$status = "failed";
				$message = "ERROR" ;
				$description = $countquery ;
				//@TXT_LOG("err", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "execConfigSearch_".$message, $description) ;
				@$this->addConfigLog("err", "execConfigSearch ".$message, $description) ;
			}
			else {
				$count = $ret["result"][0]["COUNT"];
				if($count>0){
					$query = "SELECT ".$m_query[0]["SELECT_STRING"];
					$query = $query." FROM ".$m_query[0]["TABLE_STRING"];
					$query = $query.$wherequery;
					$query = $query." ORDER BY ".str_replace("'", "", $m_query[0]["ORDER_STRING"]);
					//@TXT_LOG("debug", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "execConfigSearch", $query) ;
					$ret = $this->getDatatable($query );
					if($ret["status"] !== "success"){
						// dupe
						$status = "failed";
						$message = "ERROR" ;
						$description = $query ;
						@TXT_LOG("err", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "execConfigSearch_".$message, $description) ;
					}
					else {
						$result = $ret["result"];
					}
				}
			}
		}

		$values = array(
			'data' => ($result),
			'count' => ($count),
			'name' => ($name),
			'option' => ($option),
			'type' => ($type),
			'status' => ($status),
			'message' => ($message),
			'description' => json_encode($description)
		);
		return $values;
	}
	public function execConfigQuery($query_code, array $data = null){
	  // init return values
		$status      = "success";
		$message     = "";
		$description = "";
		$ret         = "";

		// get $m_query by $query_code
		$wherequery  = " WHERE CODE =" . $this->_esn_sq($query_code);
		$wherequery = $wherequery." AND DELETE_FLAG = 0";
/*
		$wherequery = $wherequery." AND SYSTEM_CODE in(".$this->_esn_sq($this->config_db);
		$wherequery = $wherequery.", ".$this->_esn_sq($this->system).")";
*/
		$wherequery = $wherequery." ORDER BY SORT_NO";
		$query = "SELECT * FROM ".$this->config_db.".m_query ".$wherequery;
		$ret = $this->getDatatable($query);

		// validate
		if($ret["status"] !== "success"){
			$status = "failed";
			$message = "ERROR" ;
			$description = "QUERY ERROR($wherequery)" ;
			@TXT_LOG("err", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "execConfigQuery_".$message, $description);
		}
		else {
			$m_query = $ret["result"];
		}

		$exec_query = array();
		$search_query = "";
		$system = "";
		if($status=="success"){
			// set logging setting
			$logging = true;
			for($i=0;$i<count($m_query);$i++){
				$query = __WEBIO_html($m_query[$i]["QUERY_STRING"]);
				$type  = $m_query[$i]["TYPE"];
				if(empty($system)) $system=$m_query[$i]["SYSTEM_CODE"];
				if(trim($query) == "") continue;
				if($type==0){
					$search_query = $query;
				}
				else {
					$querys = explode(';', $query.";");
					$exec_query = array_merge($exec_query, $querys);
				}
			}
			$this->dbChange($system);
			$search_query = $this->queryEscape($search_query, $data);
			for($i=0;$i<count($exec_query);$i++){
				if(empty(trim($exec_query[$i]))) continue;
				$exec_query[$i] = $this->queryEscape($exec_query[$i], $data);
			}
			@TXT_LOG("db", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "------m_query------".$query_code."----".count($exec_query)."---------") ;
			if($status=="success" && count($exec_query) > 0){
				$ret = $this->sqlExec($exec_query);
				if($ret["status"] !== "success"){
					$status = "failed";
					$message = "ERROR" ;
					$description = isset($ret["status"]) ? "QUERY EXEC ERROR(".$ret["status"].")" : "QUERY EXEC ERROR()";
					@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "execConfigQuery_".$message, $description) ;
					$ret = $ret["result"];
				}
			}
			if($status=="success" && $search_query != ""){
				$ret = $this->getDatatable($search_query );
				if($ret["status"] !== "success"){
					$status = "failed";
					$message = "ERROR" ;
					$description = "QUERY EXEC ERROR FOR SEARH($search_query)" ;
					@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "execConfigQuery_".$message, $description) ;
				}
				else {
					$ret = $ret["result"];
				}
			}
		}
		$values = array(
			'data' => ($ret),
			'status' => ($status),
			'message' => ($message),
			'description' => json_encode($description)
		);
		return $values;
	}
	public function execQuery($query, array $data = null){
	  // init return values
		$status      = "success";
		$message     = "";
		$description = "";
		$ret         = "";
		$query = $this->queryEscape($query, $data);
		$ret = $this->sqlExec($query);
		if($ret["status"] !== "success"){
			$status = "failed";
			$message = "ERROR" ;
			$description = isset($ret["status"]) ? "QUERY EXEC ERROR(".$ret["status"].")" : "QUERY EXEC ERROR()";
			@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "execConfigQuery_".$message, $description) ;
			$ret = $ret["result"];
		}
		$values = array(
			'data' => $ret,
			'status' => $status,
			'message' => $message,
			'description' => $description
		);
		return $values;
	}
	public function addConfigLog($logType,$logTitle,$logRemark=''){
		$logs = array();
		$logs["LOG_TYPE"] = $logType;
		$logs["URL"] = $_SERVER["PHP_SELF"];
		$logs["TITLE"] = $logTitle;
		$logs["REMARK"] = $logRemark;
		$postParam = "-";
		if(isset($_POST) && !empty($_POST)){
			$post_cp = $_POST;
			for($i=0;$i<count($this->hidden_post_paramater);$i++){
				if(isset($post_cp[$this->hidden_post_paramater[$i]])){
					$post_cp[$this->hidden_post_paramater[$i]] = "xxxxxxxxxxxxxxxx" ;
				}
			}
			$postParam = print_r($post_cp, true);
		}
		$logs["POST_PARAM"] = $postParam;
		$logs["SESSIONID"] = session_id();
		$logs["HTTP_HOST"] = $_SERVER["HTTP_HOST"];
		$logs["HTTP_USER_AGENT"] = $_SERVER["HTTP_USER_AGENT"];
		if(isset($_SERVER["HTTP_REFERER"])){
			$logs["HTTP_REFERER"] = $_SERVER["HTTP_REFERER"];
		}
		$logs["HTTP_COOKIE"] = $_SERVER["HTTP_COOKIE"];
		$logs["REMOTE_ADDR"] = $_SERVER["REMOTE_ADDR"];
		$logs["REMOTE_PORT"] = $_SERVER["REMOTE_PORT"];
		$logs["REQUEST_METHOD"] = $_SERVER["REQUEST_METHOD"];
		$logs["REQUEST_URI"] = $_SERVER["REQUEST_URI"];
		$this->execConfigQuery('t_logs_ins', $logs);
		@TXT_LOG($logType,$_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "$this->addConfigLog",$logTitle,$logRemark);
	}
	public function addConfigFile($file, $remark){
		$data = array();
		$data["fileid"] = $file["fileid"];
		$data["filesize"] = $file["filesize"];
		$data["filetype"] = $file["filetype"];
		$data["remark"] = $remark;
		if(isset($file["filename"]) && !empty($file["filename"])){
			//引数上にファイル名の設定がある場合
			$data["filename"] = $file["filename"];
		}
		return $this->execConfigQuery('m_file_ins', $data);
	}
	public function exportFile($query_code, $params, $outputfile="", $encode=""){
		$export_data = $this->execConfigQuery($query_code, $params);
		if($export_data["status"] !=="success"){
			return $export_data;
		}
		$result = array(
			'status' => "success",
			'message' => "",
			'description' => ""
		);
		$file = array_output_file($file_prefix, $input_array, $file_type, $encode);
		if(!isset($file)){
			$result = array(
				'status' => "failed",
				'message' => "E_EXP_DATA",
				'description' => ""
			);
			return $result;
		}
		//m_fileに登録する
		$values = $this->addConfigFile($file, $file_prefix);
		if($values["status"] =="success"){
			$result["url"] = "/download/".$file["fileid"];
		}
		return $result;
	}
	public function importFile($temp_table, $path, $params){
		$delimita = "\t";
		if(!empty($params["IMPORT_FILE"])){
			if($params["IMPORT_FILE"]==="csv") $delimita = ",";
			if($params["IMPORT_FILE"]==="txt") $delimita = " ";
		}
		if(empty($params["rowCount"])){
			$params["rowCount"] = 1;
		}
		if(empty($params["encode"])){
			$params["encode"] = "UTF-8";
		}
		//ファイルチェック
		$result = $this->import_file_check($path, $params["fieldCount"], $params["rowCount"], $delimita, $params["encode"]);
		if($result["status"] !== "success"){
			return $result;
		}
		//一次テーブルにファイルデータを登録
		$result = $this->import_file_data($DBCON, $temp_table, $params["fieldCount"], $result["data"]["file_data"], $params["rowCount"]);
		return $result;
	}
	private function bindParams($param){
		$param = str_replace('*', '', $param);
		$chkstr  = explode(' ', $param);
		$ret = array();
		for($j=1;$j<count($chkstr);$j++){
			if(trim($chkstr[$j]) == "") continue;
			$ret[] = $chkstr[$j];
		}
		return $chkstr;
	}
	private function queryEscape($query, $data=null){
		extract($GLOBALS, EXTR_SKIP);
		$ret = "";
		$sqlp = explode('/', $query);
		$isIf = 0;
		$keyName = "";
		if($data === null) $data = array();
		for($i=0;$i<count($sqlp);$i++){
			$param = $sqlp[$i];
			//@TXT_LOG("debug",$_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "queryEscape1", "[".$param."][isIf=".$isIf."][chk=".$chk."]");
			if(substr($param, 0, 1)=='*' && substr($param, -1, 1)=='*'){
				//コメントブロック
				if(substr($param, 0, 4)=='*if '){
					//ifブロック
					$bindParam  = $this->bindParams($param);
					$isIf = 1;
					$keyName = $bindParam[1];
					$chkType = $bindParam[2];
					$chkVal = $bindParam[3];
					$val = $this->getParamValue($keyName, $data, 0);
					if(substr($chkVal, 0, 1)=="'" && substr($chkVal, -1, 1)=="'") $chkVal = str_replace("'", "", $chkVal);
					else if(substr($chkVal, 0, 1)=='"' && substr($chkVal, -1, 1)=='"') $chkVal = str_replace('"', "", $chkVal);
					if($chkVal=="null" || $chkVal=="NULL") $chkVal="";
					//else if(ctype_digit($chkVal)) $chkVal = intval($chkVal);

					$chk = 0;
					switch($chkType){
						case "==" :
							if($val===$chkVal) $chk=1;
							break;
						case "!=" :
							if($val!==$chkVal) $chk=1;
							break;
						case "<" :
							if($val<$chkVal) $chk=1;
							break;
						case ">" :
							if($val>$chkVal) $chk=1;
							break;
						case "<=" :
							if($val<=$chkVal) $chk=1;
							break;
						case ">=" :
							if($val>=$chkVal) $chk=1;
							break;
					}
					//@TXT_LOG("debug",$_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "queryEscape", "[".$keyName."][".$chkType."][".$chkVal."][".$val."][".$chk."]");
					$param = "";
				}
				else if($param=='*end if*'){
					$isIf=0;
					$chk = 1;
					$param = "";
				}
				else {
					//パラメータの設定
					$keyname = str_replace('*', '', $param);
					$val = $this->getParamValue($keyname, $data);
					$param = str_replace($param, $val, $param);
				}
			}
			else {
				if($i<count($sqlp)-1){
					//後続の文字列がパラメータない場合＝除算のケース
					if(substr($sqlp[$i+1], 0, 1)!='*'){
						$param=$param.'/';
					}
				}
			}
			if(trim($param)=="") continue;
			//@TXT_LOG("debug",$_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "queryEscape2", "[".$param."][isIf=".$isIf."][chk=".$chk."]");
			if($isIf && !$chk) continue;
			$ret = $ret.$param;
		}
		if($ret=="NULL") $ret=$query;
		//@TXT_LOG("debug",$_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "queryEscape", $ret."[".$query."]");
		return $ret;
	}
	private function getParamValue($keyname, $data=null, $escape=1){
		extract($GLOBALS, EXTR_SKIP);
		extract($_SESSION, EXTR_SKIP);
		$val = "";

		$wild = 0;
		$dollar=0;
		if(substr($keyname, 0, 1)=='%'){
			//like文等利用想定、ワイルドカード付けてシングルクォートで囲む
			$wild = 1;
		}
		if(substr($keyname, 0, 1)=='$'){
			//数値扱い（数値チェックし、シングルクォートで囲まない）
			$dollar = 1;
		}
		if(substr($keyname, -1, 1)=='%'){
			if($wild==0) $wild = 2;
			else $wild = 3;
		}
		if($wild>0) $keyname = str_replace('%', "", $keyname);
		if($dollar==1) $keyname = str_replace('$', "", $keyname);

		$pos = strpos($keyname, '[');
		if($pos){
			//対象が配列 /*param[0]*/
			$index = str_replace('[', '' , substr($keyname, $pos));
			$index = str_replace(']', '' , $index);
			if(is_numeric($index)) $index = intval($index);
			$arrayKey = substr($keyname, 0, $pos);
			if( $data!=null && isset($data[$arrayKey])){
				$val = $data[$arrayKey];
			}
			else if( isset( ${$arrayKey}) ){
				$val = ${$arrayKey};
			}
			else if( isset( $_SESSION[$arrayKey]) ){
				$val = $_SESSION[$arrayKey];
			}
			if(is_array($val)) $val = $val[$index];
			else return "";
		}
		else {

			if( $data!=null && isset($data[$keyname])){
				$val = $data[$keyname];
			}
			else if( isset( ${$keyname}) ){
				$val = ${$keyname};
			}
			else if( isset( $_SESSION[$keyname]) ){
				$val = $_SESSION[$keyname];
			}
		}

		if(is_array($val)){
			$val = $this->getParamArray($val);
			$escape=0;
		}

		if($wild==1 || $wild==3) $val = '%'.$val;
		if($wild==2 || $wild==3) $val = $val.'%';

		if($dollar==1 && !preg_match("/^[0-9]+$/", $val)) return "";

		if($val=="null" || $val=="NULL") $val="";
		else if($dollar==0 && $escape==1) $val = $this->_esn_sq($val);

		$decodeField = array("OPTION_STRING");
		for($i=0;$i<count($decodeField);$i++){
			if($keyname==$decodeField[$i]){
				//DB更新のためデコードして利用、シングルクォートのインジェクション対策($this->_esn_sq)があるため安全
				$val = __WEBIO_return($val);
				break;
			}
		}

		//@TXT_LOG("debug",$_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "getParamValue",$keyname."=".$val."(escape=$escape,wild=$wild,dollar=$dollar)");

		return ($val);
	}
	private function getParamArray($array, $_delimita=","){
		$val = "";
		if(is_array($array)){
			foreach ( $array as $key=>$arrayval ){
				if(is_array($arrayval)){
					$val = $val."(".$this->getParamArray($arrayval, $_delimita).")".$_delimita;
				}
				else {
					$val = $val.$this->_esn_sq($arrayval).$_delimita;
				}
			}
			$val =trim($val, $_delimita);
		}
		return $val;
	}
	private function import_file_check($path, $field_count, $header_row_count=1, $delimita=",", $encode='UTF-8'){
		//パラメータチェック
		if(empty($path) || !is_numeric($field_count)
			|| !is_numeric($header_row_count) || empty($delimita)){
			$result = array(
				'status' => "failed",
				'message' => "E_IMP_FORMAT",
				'description' => "import_file_check PARAM ERROR FAILED[($path)($field_count)($header_row_count)($delimita)]"
			);
			@TXT_LOG("import", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__,  $result["description"]);
			return $result;
		}
		//アップロードファイルチェック
		if(!file_exists($path) ){
			$result = array(
				'status' => "failed",
				'message' => "E_IMP_FORMAT",
				'description' => "FILE NOT FOUND".filesize($path)." FAILED"
			);
			@TXT_LOG("import", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__,  $result["description"]);
			return $result;
		}
		//ファイルサイズチェック
		if(filesize($path)<=2){
			$result = array(
				'status' => "failed",
				'message' => "E_IMP_FORMAT",
				'description' => "FILE SIZE".filesize($path)." FAILED"
			);
			@TXT_LOG("import", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__,  $result["description"]);
			return $result;
		}
		if($encode !== 'UTF-8'){
			$_data = file_get_contents($path);
			$_data = mb_convert_encoding($_data, 'UTF-8', $encode);
			$fp=tmpfile();
			$meta=stream_get_meta_data($fp);
			fwrite($fp, $_data);
			rewind($fp);
			$path = $meta['uri'];
		}
		$file_data = new SplFileObject($path, 'rb');
		$file_data->setFlags(SplFileObject::READ_CSV);
		$file_data->setCsvControl($delimita, "\"");
		foreach ($file_data as $data) {
			if(count($data)!=$field_count){
				$result = array(
					'status' => "failed",
					'message' => "E_IMP_FORMAT",
					'description' => "FIELD COUNT [".count($data)."!=".$field_count."] FAILED"
				);
				@TXT_LOG("import", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__,  $result["description"]);
				return $result;
			}
			break;
		}

		$result = array(
			'status' => "success",
			'message' => "",
			'description' => "",
			'data' => array(
				'field_count' => $field_count,
				'header_row_count' => $header_row_count,
				'delimita' => $delimita,
				'file_data' => $file_data
			)
		);
		return $result;
	}
	private function import_file_data($temp_table, $field_count, $file_data, $header_row_count=1)
	{
		if(empty($temp_table) || !is_numeric($field_count)
			|| !is_numeric($header_row_count) || empty($file_data)){
			$result = array(
				'status' => "failed",
				'message' => "E_IMP_FORMAT",
				'description' => "import_file_data PARAM ERROR FAILED"
			);
			@TXT_LOG("import", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__,  $result["description"]);
			return $result;
		}
		//インポート用テーブル作成（存在しない場合）
		$header_row_count = intval($header_row_count);
		$query = "SHOW TABLES LIKE '$temp_table'";
		$ret = $this->getDatatable($query);
		if($ret["status"] === "success"){
			if(empty($ret["result"])){
				//インポート用テーブルが存在しない場合、作成する
				//一時テーブルは以下を持つ
				//ファイルの行全体のチェック状態：_rst :
				//0=INSERT
				//1=UPDATE
				//-1=ERROR(列型チェック）
				//-2 = ERROR(テーブル存在チェック）
				//ファイルの列のチェック状態：_fst[N] : 後述変数タイプを設定する
				$query = "CREATE TABLE $temp_table(";
				for($j=0;$j<$field_count;$j++){
					$query = $query."_col".$j." text,";
				}
				$query = $query."_id int(11) auto_increment primary key,";
				$query = $query."_data text ,";
				$query = $query."_rst int(11) NOT NULL DEFAULT '0',";
				for($j=0;$j<$field_count;$j++){
					$query = $query."_fst".$j." int(11) NOT NULL DEFAULT '0',";
				}
				$query = $query."messageParam varchar(255) NOT NULL DEFAULT '',";
				$query = $query."message varchar(255) NOT NULL DEFAULT '' )";
			}
			else {
				$query = "DELETE FROM ".$temp_table;
			}
			$ret = $this->sqlExec($query);
		}

		if($ret["status"]!="success"){
			$result = array(
				'status' => "failed",
				'message' => "E_IMP_FORMAT",
				'description' => "TEMP TABLE(".$temp_table.") CREATE FAILED"
			);
			@TXT_LOG("import", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__,  $result["description"]);
			return $result;
		}
		//一時テーブルにファイル内容をINSERTする
		//if($delimita==="\t") $delimita='\t';
		$queryTemplate = "INSERT INTO $temp_table (";
		for($j=0;$j<$field_count;$j++){
			$queryTemplate = $queryTemplate."_col".$j.",";
		}
		$queryTemplate = $queryTemplate."_data";
		$queryTemplate = $queryTemplate.") VALUES";
		$query = "";
		foreach ($file_data as $data) {
			if($header_row_count>0){
				$header_row_count--;
			}
			else if($field_count==count($data)) {
				$strdata = "";
				for($j=0;$j<count($data);$j++){
					$strdata = $strdata.$this->_esn_sq(trim($data[$j])).",";
				}
				$strdata = $strdata.$this->_esn_sq(trim(implode(",", $data)));
				$query .= "(".$strdata."),";
			}
		}
		$query = $queryTemplate.trim($query, ",").";";
		//INSERT INTO table_name VALUES(...),(...),(...);
		$ret = $this->sqlExec($query);
		if($ret["status"]!== "success"){
			$result = array(
				'status' => "failed",
				'message' => "E_IMP_FORMAT",
				'description' => "FILE IMPORT INSERT FAILED"
			);
			@TXT_LOG("import", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__,  $result["description"]);
			return $result;
		}
		//一時テーブル内の列の状態を設定する
		$dataquery = "";
		$query = array();
		for($j=0;$j<$field_count;$j++){
			$dataquery = $dataquery."_col".$j;
			if($j < $field_count-1) $dataquery = $dataquery.",',',";
			$query[] = "UPDATE $temp_table SET _fst".$j."=0;";
			/*fst(n) = 0 is null or blank*/
			$query[] = "UPDATE $temp_table SET _col".$j."='', _fst".$j."=-1 WHERE _col".$j." IS NULL;";
			/*fst(n) = 1 is int*/
			$query[] = "UPDATE $temp_table SET _fst".$j."=1 WHERE _fst".$j."=0 AND _col".$j." REGEXP '[[:alpha:]]+'=0 AND _col".$j."  REGEXP '[[:digit:]]+'=1;";
			/*fst(n) = 2 is double*/
			$query[] = "UPDATE $temp_table SET _fst".$j."=2 WHERE _fst".$j."=1 AND _col".$j." REGEXP '^[0-9]+[.][0-9]+'=1;";
			$query[] = "UPDATE $temp_table SET _fst".$j."=2 WHERE _fst".$j."=0 AND _col".$j."+0.0!=0;";
			/*fst(n) = 3 is single-byte-character*/
			$query[] = "UPDATE $temp_table SET _fst".$j."=3 WHERE _fst".$j."=0 AND _col".$j."  REGEXP '[[:digit:]]+'=0;";
			/*fst(n) = 4 is multi-byte-character*/
			$query[] = "UPDATE $temp_table SET _fst".$j."=4 WHERE _fst".$j."=3 AND LENGTH(_col".$j.")!=CHARACTER_LENGTH(_col".$j.");";
		}
		$query[] = "UPDATE $temp_table set _data = concat(".$dataquery.");";
		$ret = $this->sqlExec($query);
		if($ret["status"]!== "success"){
			$result = array(
				'status' => "failed",
				'message' => "E_IMP_FORMAT",
				'description' => "FILE IMPORT FIECLD CHECK FAILED"
			);
			@TXT_LOG("import", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__,  $result["description"]);
			return $result;
		}
		//各インポート処理ごと独自にチェックするクエリを実行する
		//列に対し、型の妥当性チェック、値チェック、他テーブルの存在チェックなどを行う
		//チェッククエリ側で一時テーブルの_rstを更新する
		$ret = $this->execConfigQuery('upd_'.$temp_table);
		if($ret["status"]!== "success"){
			$result = array(
				'status' => "failed",
				'message' => "E_IMP_FORMAT",
				'description' => "FILE IMPORT DATA CHECK FAILED"
			);
			@TXT_LOG("import", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__,  $result["description"]);
			return $result;
		}
		//エラーメッセージの設定
		$query = array();
		//_rst=0 : insert
		//_rst=1: update
		$query[] = "UPDATE $temp_table SET message='E_IMP_TYPE' WHERE _rst=-1;";
		$query[] = "UPDATE $temp_table SET message='E_IMP_EXIST' WHERE _rst=-2;";
		$ret = $this->sqlExec($query);
		if($ret["status"]!== "success"){
			$result = array(
				'status' => "failed",
				'message' => "E_IMP_FORMAT",
				'description' => "FILE IMPORT ERROR SET FAILED"
			);
			@TXT_LOG("import", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__,  $result["description"]);
			return $result;
		}

		$result = array(
			'status' => "success",
			'message' => "",
			'description' => ""
		);
		return $result;
	}

}
