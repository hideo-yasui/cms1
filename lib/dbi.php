<?php

/**
 * Application.
 */
class Dbi
{
	public $dbname = "master";
	public $dbuser = "dbadmin";
	public $dbpass = "admindb";
	//public $dbhost = "192.168.1.111";
	public $dbhost = "192.168.100.20";
	public $db;

	/**
	 * コンストラクタ
	 *
	 * @param boolean $debug
	 */
	public function __construct()
	{
		$this->dbConnect();
	}
	/**
	 * アプリケーションの設定
	 */
	public function dbConnect()
	{
		$DB_PORT = "" ; // default:5432
		$persistent = ""; //持続的接続をする場合 p:を設定する

		$this->db = mysqli_init();
		$this->db->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
		$this->db->real_connect($persistent.$this->dbhost, $this->dbuser, $this->dbpass, $this->dbname, intval($DB_PORT));
		if ( $this->db ) {
			mysqli_set_charset($this->db , "utf8") ;
		}
		else {
			@TXT_LOG("dberr", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "mysqli_connect",  $persistent.$this->dbhost, $this->dbuser, $this->dbpass, $this->dbname, intval($DB_PORT));
			return false;
		}
		@TXT_LOG("db", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "mysqli_connect",  $persistent.$this->dbhost, $this->dbuser, $this->dbpass, $this->dbname, intval($DB_PORT));
	}
	public function dbChange($schema)
	{
		if(empty($schema)) return true;
		if($this->dbname===$schema) return true;

		if(!mysqli_select_db($this->db,$schema)){
			@TXT_LOG("dberr", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "dbChange[".$schema."]:errno:[".mysqli_errno($this->db)."]", "err:[".mysqli_error($this->db)."]" );
			return false;
		}
		@TXT_LOG("db", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "dbChange",  $schema);
		$this->dbname = $schema;
		return true;
	}
	public function getDatatable($query, $logging=false){
		$status = "success";
		$description = "";
		$message = "";
		$lists = array();
		$logging=true;
		if($logging) @TXT_LOG("db", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "__getDatatable", $query );
		$sql_res = @mysqli_query($this->db, $query, MYSQLI_STORE_RESULT);
		if($sql_res === false && (mysqli_errno($this->db)!=0 || mysqli_error($this->db)!="")){
			$status = "failed";
			$message = "ERROR" ;
			$description = "dbi->getDatatable EXEC ERROR" ;
			if(function_exists("TXT_LOG")){
				TXT_LOG("dberr", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "__getDatatable ", $query."\terrno:[".mysqli_errno($this->db)."]", "err:[".mysqli_error($this->db)."]" );
			}
		}
	  if($sql_res){
	    // SELECT(SEARCH)
	    // CHANGE TO common FUNCTION
	    $rows = @mysqli_num_rows($sql_res);
	    if($rows){
	      $lists = array() ;
	      while($list = mysqli_fetch_array($sql_res, MYSQLI_ASSOC )){
	        foreach ($list as $key => $val) {
	          if (is_string($val)) {
	            $val = __WEBIO_in($val);
	            $list[$key] = __WEBIO_out($val);
	          } else {
	            $list[$key] = $val;
	          }
	        }
	        $lists[] = $list;
	      }
	    }
	    // メモリ解放
	    if(is_object($sql_res)){
	      mysqli_free_result($sql_res);
	    }
	  }
		$result = array(
			'status' => ($status),
			'result' => ($lists),
			'message' => ($message),
			'description' => ($description)
		);
		return $result;
	}
	public function sqlExec($query, $resultmode=MYSQLI_STORE_RESULT, $logging=true)
	{
		$status = "success";
		$description = "";
		$message = "";
		$res = array();

		if($resultmode !== MYSQLI_USE_RESULT)  $resultmode  = MYSQLI_STORE_RESULT;

		if(!(@mysqli_query($this->db, "set autocommit = 0", $resultmode))){
			@TXT_LOG("dberr", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "\terrno:[".mysqli_errno($this->db)."]", "err:[".mysqli_error($this->db)."]" );
			$status = "failed";
			$message = "ERROR" ;
			$description = "QUERY EXEC ERROR" ;
		}
		else if(!(@mysqli_query($this->db, "begin", $resultmode))){
			@TXT_LOG("dberr", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "\terrno:[".mysqli_errno($this->db)."]", "err:[".mysqli_error($this->db)."]" );
			$status = "failed";
			$message = "ERROR" ;
			$description = "QUERY EXEC ERROR" ;
		}

		$sqllog = "";
		if($status === "success"){
			try {
				if(is_array($query)){
					for($i=0;$i<count($query);$i++){
						if(trim($query[$i])=="") continue;
						if($logging) @TXT_LOG("db", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "__SQL_Exec__", $query[$i] );
						$res[$i]["error"] = 0;
						$sqllog .= $query[$i]."\n";
						$result = @mysqli_query($this->db, $query[$i], $resultmode);
						if($result === false && (mysqli_errno($this->db)!=0 || mysqli_error($this->db)!="")){
							// エラーロギング
							$res[$i]["error"] = 1;
							$status = "failed";
							$message = "ERROR" ;
							$description = "QUERY EXEC ERROR" ;
							if(function_exists("TXT_LOG")){
								TXT_LOG("dberr", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "__SQL_Exec__ ", $query[$i]."\terrno:[".mysqli_errno($this->db)."]", "err:[".mysqli_error($this->db)."]" );
							}
							break;
						}
						else {
							$res[$i]["count"]     = @mysqli_affected_rows($this->db);
							$res[$i]["insert_id"] = @mysqli_insert_id($this->db);
						}
					}
				}
				else {
					if($logging) @TXT_LOG("db", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "__SQL_Exec__", $query );
					if(!empty($query)){
						$sqllog .= $query."\n";
						$result = @mysqli_query($this->db, $query, $resultmode);
						$res[0] = array();
						$res[0]["error"] = 0;
						if($result === false){
							// エラーロギング
							$res[0]["error"] = 1;
							$status = "failed";
							$message = "ERROR" ;
							$description = "QUERY EXEC ERROR" ;
							@TXT_LOG("dberr", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, $query."\terrno:[".mysqli_errno($this->db)."]", "err:[".mysqli_error($this->db)."]" );
						}
						else {
							$res[0]["count"]     = @mysqli_affected_rows($this->db);
							$res[0]["insert_id"] = @mysqli_insert_id($this->db);
							$_SESSION['sInsertId'] = $res[0]["insert_id"];
						}
					}
				}
			}
			catch(Exception $e){
				$status = "failed";
				$message = "ERROR" ;
				$description = "QUERY EXEC Exception ERROR".$e->getMessage() ;
				if(function_exists("TXT_LOG")){
					TXT_LOG("dberr", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "__SQL_Exec__ ", $e->getMessage());
				}
			}
		}
		if($status === "success"){
			if(!(@mysqli_query($this->db, "commit", $resultmode))){
				@TXT_LOG("dberr", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "\terrno:[".mysqli_errno($this->db)."]", "err:[".mysqli_error($this->db)."]" );
				$status = "failed";
				$message = "ERROR" ;
				$description = "QUERY EXEC COMMIT ERROR" ;
			}
		}
		else {
			if(!(@mysqli_query($this->db, "rollback", $resultmode))){
				@TXT_LOG("dberr", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "\terrno:[".mysqli_errno($this->db)."]", "err:[".mysqli_error($this->db)."]" );
				$status = "failed";
				$message = "ERROR" ;
				$description = "QUERY EXEC COMMIT ERROR" ;
			}
		}

		$result_all = array(
			'status' => ($status),
			'result' => ($res),
			'message' => ($message),
			'description' => ($description)
		);
		return $result_all;
	}
	public function load_cache(){
		global $gPathList;
		$path = $this->get_request_cache_name();
		$path = $gPathList["_cache"].$path.'.json';
		$result = json_file_read($path);
		if($result!==null){
			$mtime = filemtime($path);
			$mtime = date("Y/m/d H:i:s",$mtime);
			$now =date("Y-m-d H:i:s");
			$mtime=strtotime($mtime);
			$now=strtotime($now);
			$d = intval($now-$mtime);
			//1分
			if($d > 60){
				unlink($path);
				return null;
			}
		}
		return $result;
	}
	public function clear_cache($path){
		global $gPathList;
		TXT_LOG("request", "clear_cache", $path);

		$path = $gPathList["_cache"].$path;
		delfiles($path.'/');
	}
	public function save_cache($result){
		global $gPathList;
		$path = $this->get_request_cache_name();
		mksubdir($path, $gPathList["_cache"]);
		$path = $gPathList["_cache"].$path.'.json';
		json_file_write($path, $result);
	}
	private function get_request_cache_name(){
		$path = $_SERVER["REQUEST_URI"];
		$path = str_replace('+', '_', $path);
		$path = str_replace('<', '_', $path);
		$path = str_replace('>', '_', $path);
		$path = str_replace(':', '_', $path);
		$path = str_replace('*', '_', $path);
		$path = str_replace('|', '_', $path);
		$path = str_replace('?', '/', $path);
		$paths = explode('/', $path);
		$path = $_SERVER["SERVER_NAME"].'/';
		for($i=2;$i<count($paths);$i++){
			$path.=$paths[$i].'/';
		}
		$path = trim($path, '/');
		TXT_LOG("request", $path);
		return $path;
	}
	public function _esn_sq($str){
		if ( is_null($str) || ($str === "") ) {
			return "NULL";
		}
		else if ($str === 0 && $numflg) {
			return "NULL";
		}

		$str = str_replace(array("\r\n", "\r", "\n"), "<BR>", $str);
		$str = str_replace("\t", " ", $str);

		return "'".mysqli_real_escape_string($this->db, $str)."'";
	}
}
