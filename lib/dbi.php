<?php

/**
 * Application.
 */
class Dbi
{
	public $dbname = "config";
	public $dbuser = "dbadmin";
	public $dbpass = "admindb";
	public $dbhost = "192.168.1.111";
	public $db;
	public $system;

	/**
	 * コンストラクタ
	 *
	 * @param boolean $debug
	 */
	public function __construct()
	{
		$this->system = $GLOBALS['gEnvList']['system'];
		$this->dbConnect();
	}
	/**
	 * アプリケーションの設定
	 */
	protected function dbConnect()
	{
		$DB_PORT = "" ; // default:5432
		$persistent = ""; //持続的接続をする場合 p:を設定する
		$gEnvList = $GLOBALS['gEnvList'];

		$this->db = mysqli_init();
		$this->db->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
		$this->db->real_connect($persistent.$this->dbhost, $this->dbuser, $this->dbpass, $this->dbname, intval($DB_PORT));

		if ( $this->db ) {
			mysqli_set_charset($this->db , "utf8") ;
		}
		else {
			@TXT_LOG("dberr", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "mysqli_connect",  $gEnvList["DB_host"], $gEnvList["DB_user"], "xxxx", $gEnvList["DB_name"], intval($DB_PORT) );
			return false;
		}
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
							$_SESSION['sInsertId'] = $res[$i]["insert_id"];
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

}
