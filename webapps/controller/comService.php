<?php

class comService extends Controller
{
	protected $auth_actions = array('');

	public function search($params){
		$result = $this->badRequestResponce();
		if(isset($params['query_code'])){
			if ($this->request->isGet()) {
				$result = $this->application->dbi->execConfigSearch($params['query_code'], $_GET);
			}
		}
		return $result;
	}
	public function query($params){
		$result = $this->badRequestResponce();
		if(isset($params['query_code'])){
			if (!empty($params["method"])) $params['query_code'] .= $params['method'];
			if ($this->request->isGet()) {
				$params = array_merge($params, $_GET);
			}
			else if ($this->request->isPost()) {
				$params = array_merge($params, $_POST);
			}
			$result = $this->application->dbi->execConfigQuery($params['query_code'], $params);
		}
		return $result;
	}
	public function download($params){
		if ($this->request->isGet()) {
			if($this->_getfile($params, false) === true){
				$this->exitProc();
			}
		}
		return $this->notFoundResponce();
	}
	public function getfile($params){
		if ($this->request->isGet()) {
			if($this->_getfile($params, true) === true){
				$this->exitProc();
			}
		}
		view("notfound.html", array());
		$this->exitProc();
	}
	private function _getfile($params, $is_inline){
		global $gPathList;
		$values = $this->application->dbi->execConfigQuery("get_file", $params);
		if($values["status"] =="success" && count($values["data"]) > 0){
			$save_file_path = $gPathList["upload"].$values["data"][0]["fileid"];
			$fileid = $values["data"][0]['fileid'];
			$filename = $values["data"][0]['filename'];
			$values = $this->application->dbi->downloadfile($fileid, $filename, $save_file_path, $is_inline);
			return true;
		}
		return false;
	}
	public function upload($params){
		global $gPathList;
		$result = $this->badRequestResponce();
		if ($this->request->isPost()) {
			$todate = date("Ymd_His");
			$fileid = $todate."_".getSecureKey(6);
			$result = $this->uploadfile($_POST["formid"], $gPathList["upload"], $fileid);
			if($result["status"] ==="success"){
				$values = $this->application->dbi->addConfigFile($values["data"], $_POST["remark"]);
				$result["status"] = $values["status"];
				$result["message"] = $values["message"];
				$result["description"] = $values["description"];
			}
		}
		return $result;
	}
	public function export($params){
		$result = $this->badRequestResponce();
		if(isset($params['query_code'])){
			if ($this->request->isGet()) {
				$outputfile = "csv";
				if (!empty($_GET["outputfile"])) $outputfile = $_GET["outputfile"];
				$encode = "";
				if (!empty($_GET["encode"])) $encode = $_GET["encode"];
				$result = $this->application->dbi->exportFile($query_code, $_GET, $outputfile, $encode);
			}
		}
		return $result;
	}
	public function import($params){
		$result = $this->upload($params);
		if($result["status"] ==="success"){
			$path = $gPathList["upload"].$fileid;
			$values = $this->application->dbi->importFile($query_code, $path, $_POST);
			$result["status"] = $values["status"];
			$result["message"] = $values["message"];
			$result["description"] = $values["description"];
		}
		return $result;
	}
	public function downloadfile($fileid, $filename, $savefile, $is_inline=false){
		$values = array(
			'status'      => "success",
			'data'     => "",
			'message'     => "",
			'description' => ""
		);
		if (!file_exists($savefile)) {
			@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "aws_s3->getfile",$values["message"], $values["description"]) ;
			return $values;
		}

		$isStream = 0;
		$filename = ' filename*=UTF-8\'\''.rawurlencode($filename);
		if($is_inline === false) $filename = "attachment; ".$filename;
		if($isStream == 1){
			header("Content-Disposition: inline; ".$filename );
			header("Content-Type: application/octet-stream");
		}
		else {
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$filetype = $finfo->file($savefile);
			header("Content-Disposition: ".$filename );
			header("Content-Type: ".$filetype);
		}
		$filesize = filesize($savefile);
		header("Content-Length: ".$filesize);
		readfile($savefile);
		return $values;
	}
	public function uploadfile($formid,$folder,$fileid){
		$result = $this->save_uploadfile($formid,$folder,$fileid);
		if($result["status"]!=="success"){
			@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "save_uploadfile",$result["message"], $result["description"]) ;
			return $result;
		}
		return $result;
	}
	private function save_uploadfile($formid,$folder,$fileid){
		$values = array(
			'status'      => "success",
			'data'     => array(),
			'message'     => "",
			'description' => ""
		);

		if (!is_uploaded_file($_FILES[$formid]["tmp_name"])) {
			$values["status"] = "failed";
			$values["message"] = "ERROR" ;
			$values["description"] = "NO FILE" ;
			return $values;
		}
		//uploadしたファイルを保存
		$path = $folder.$fileid;
		if (!move_uploaded_file($_FILES[$formid]["tmp_name"], $path)) {
			$values["status"] = "failed";
			$values["message"] = "ERROR" ;
			$values["description"] = "UPLOADFILE MOVE FAILED" ;
			return $values;
		}
		$filename = basename($_FILES[$formid]["name"]);
		if($values["status"]==="success"){
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$filetype = $finfo->file($path);
			$values["data"] = array(
				"fileid"=>$fileid,
				"filename"=>$filename,
				"filesize"=>filesize($path),
				"filetype"=>$filetype
			);
		}
		else {
			@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "aws_s3->putfile",$values["message"], $values["description"]) ;
		}
		return $values;
	}
	public function exitProc(){
		exitProc($this->application->dbi->db);
	}
	/**
	 * ログイン認証を行い認証結果を返却する
	 * @param dbconnect $DBCON
	 **/
	public function auth($params){
		$status = "success";
		$description = "";
		$message = "";

		// get user
		$this->application->dbi->addConfigLog("auth", "login start", $params["login_id"]);
		$user = null;
		if(!isset($params["login_id"]) && isset($params["user_id"])){
			//login_idをuser_idで補完
			$params["login_id"] = $params["user_id"];
		}
		if(!isset($params["login_id"]) && isset($params["email"])){
			//login_idをemailで補完
			$params["login_id"] = $params["email"];
		}
		else if(isset($params["password"])){
			$params["password"] = $params["password"];
		}

		if(isset($params["login_id"]) && isset($params["password"])){
			//login_id, passwordを利用したログイン
			$user = $this->application->dbi->execConfigQuery("login_by_id_pass", array(
				'login_id'  => $params["login_id"],
				'password' => $params["password"]
			));
		}
		if(!isset($user) || $user["status"] !== "success"){
			$status = "failed";
			$message = "E_BAD_REQUEST";
			$description = "paramater error:".$user["status"];
		}
		else if(count($user["data"]) !== 1) {
			$status = "failed";
			$message = "E_EXIST_NODATA";
			$description = "";
		}

		if($status === "success") {
			$user_id = $user["data"][0]["user_id"];
			$auth_id = $user["data"][0]["auth_id"];
			$token = $this->get_token($user_id);
			$result = $this->application->dbi->execConfigQuery("user_token_update", array(
				'log_type' => 'login',
				'token' => $token,
				'auth_id' => $auth_id,
				'user_id' => $user_id
			));
			if ($result["status"] !== "success") {
				$status = "failed";
				$message = "ERROR";
			}
			else {
				$this->application->dbi->addConfigLog("auth", "login end", "token=[$token] / user[$user_id]");
				session_regenerate_id(true) ;
				$_SESSION["user_token"] = $token;
				$_SESSION["user_id"] = $user_id;
			}
		}
		$result = array(
			'status'      => $status,
			'message'     => $message,
			'description' => $description
		);
		return $result;
	}
	/**
	 * API用 Token発行ロジック
	 * @param integer $user_id
	 * @return string
	 */
	private function get_token($key)
	{
		$tmp = $key.bin2hex(openssl_random_pseudo_bytes(16));
		$token = substr($tmp,0 ,32);
		return $token;
	}
	/**
	 * 認証処理
	 * @return Array
	 */
	public function auth_token() {
		$response = array(
			'status'      => "success",
			'message'     => "",
			'description' => ""
		);
		$auth_check_time = 300;
		$diff_time = $auth_check_time+1;
		if(!empty($_SESSION["sAccessTime"])) $diff_time    = (time() - $_SESSION["sAccessTime"]);
		if ($diff_time < $auth_check_time) {
			return $response;
		}
		// validate
		if (!isset($_SESSION["user_token"])) {
			$response = array(
			'status'      => "failed",
			'message'     => "E_AUTH_FAIL",
			'description' => "undefined index: required"
			);
			return $response;
		}
		$result = $this->application->dbi->execConfigQuery("login_by_token", array(
			'token' => $_SESSION["user_token"]
		));
		if ($result["status"] !== "success" || count($result["data"]) === 0) {
			$response = array(
				'status'      => "failed",
				'message'     => "E_AUTH_FAIL",
				'description' => ""
			);
			return $response;
		}
		$this->application->dbi->addConfigLog("auth", "authenticate start", 'token=['.$_SESSION["user_token"].'],'.'user_id=['.$result["data"][0]["user_id"].']');
		// Authenticate sceess
		$_SESSION["sAccessTime"] = time(); // 最後にアクセスした時間をセット（タイムアウトに使用する）
		return $response;
	}

	public function auth_clear(){

		$result = $this->application->dbi->execConfigQuery("login_by_token", array(
			'token' => $_SESSION["user_token"]
		));
		if ($result["status"] !== "success" || count($result["data"]) === 0) {
			$response = array(
				'status'      => "failed",
				'message'     => "E_AUTH_FAIL",
				'description' => ""
			);
			return $response;
		}
		$user_id = $result["data"][0]["user_id"];
		$auth_id = $result["data"][0]["auth_id"];
		$result = $this->application->dbi->execConfigQuery("user_token_update", array(
			'log_type' => 'logout',
			'token' => '',
			'auth_id' => $auth_id,
			'user_id' => $user_id
		));

		session_cache_limiter("nocache");
		session_cache_expire (0);
		session_regenerate_id( true ) ;

		$_SESSION = array() ;
		$sesName = session_name() ;
		if ( isset( $_COOKIE[$sesName] ) ) {
			$setExpTime = ( time() - ( 60 * 60 * 24 * 365 ) ) ; // 有効期限を１年前にセット
			setcookie( $sesName, "", $setExpTime, "/" ) ;
		}
		session_destroy();
		return $this->getResponce();
	}

}
