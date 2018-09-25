<?php

class comService extends Controller
{
	protected $auth_actions = array('');

	public function search($params){
		/*
		$values = $this->_auth_token();
		$is_auth = false;
		if($values["status"] ==="success") {
			$is_auth = true;
		}
		*/
		/*
		$result = $this->application->dbi->load_cache();
		if($result!==null) return $result;
		*/
		$result = $this->badRequestResponce();

		if(isset($params['query_code'])){
			if ($this->request->isGet()) {
				$params = array_merge($params, $_GET);
				$result = $this->application->dbi->execConfigSearch($params['query_code'], $params);
				//$this->application->dbi->save_cache($result);
			}
		}
		@TXT_LOG("service", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "comService search end") ;
		return $result;
	}
	public function query($params){
		$values = $this->_auth_token();
		$is_auth = false;
		if($values["status"] ==="success") {
			$is_auth = true;
		}
		TXT_LOG("request", $_SERVER["REQUEST_URI"]);
		$result = $this->badRequestResponce();
		if(isset($params['query_code'])){
			if(isset($params['cache']) && $params['cache']==='use'){
				/*
				$result = $this->application->dbi->load_cache();
				if($result!==null) return $result;
				*/
			}
			$path = $params['query_code'];
			if (!empty($params["method"])) $params['query_code'] .= '_'.$params['method'];

			if ($this->request->isGet()) {
				$params = array_merge($params, $_GET);
			}
			else if ($this->request->isPost()) {
				$params = array_merge($params, $_POST);
			}
			$system = $this->system;
			$params['auth_system'] = $system;
			if(isset($params['system'])){
				$system = $params['system'];
			}
			$result = $this->application->dbi->execConfigQuery($params['query_code'], $params, $system);

/*
			if(isset($params['cache']) && $params['cache']==='use'){
				$this->application->dbi->save_cache($result);
			}
			if(isset($params['cache']) && $params['cache']==='clear'){
				$this->application->dbi->clear_cache($path);
			}
*/
		}
		return $result;
	}
	public function get_editpage($params){
		$result = $this->badRequestResponce();
		if ($this->request->isGet()) {
			$params = array_merge($params, $_GET);
		}
		$query_code = str_replace("_add", "_".$params["method"], $params["page_code"]);
		$params["query_code"] = "get_page";
		$params["system"] = "control";
		$params["method"] = "";
		$page = $this->query($params);
		if($page["status"]==="success"){
			$method = $params["method"];
			$params["query_code"] = $query_code;
			$params["system"] = $this->system;
			$data = $this->query($params);
			if($data["status"]==="success"){
				$result = $this->getResponce();
				$result["page"] = $page["data"];
				$result["data"] = $data["data"];
			}
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
		header('Location:/notfound');
		$this->exitProc();
	}
	private function _getfile($params, $is_inline){
		global $gPathList;

		$values = $this->application->dbi->load_cache();
		if($values===null){
			$values = $this->application->dbi->execConfigQuery("get_file", $params);
			$this->application->dbi->save_cache($values);
		}
		if($values["status"] =="success" && count($values["data"]) > 0){
			$save_file_path = $gPathList["upload"].$values["data"][0]["fileid"];
			$fileid = $values["data"][0]['fileid'];
			$filename = $values["data"][0]['filename'];
			$values = $this->downloadfile($fileid, $filename, $save_file_path, $is_inline);
			return true;
		}
		return false;
	}
	public function upload($params){
		global $gPathList;
		$result = $this->badRequestResponce();
		if ($this->request->isPost()) {
			$params = array_merge($params, $_POST);
			$todate = date("Ymd_His");
			$fileid = $todate."_".getSecureKey(6);
			$result = $this->uploadfile($params["formid"], $gPathList["upload"], $fileid);

			if($result["status"] ==="success"){
				$remark = "";
				if(!empty($params["remark"])) $remark = $params["remark"];
				$values = $this->application->dbi->addConfigFile($result["data"], $remark);
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
		$last_modified = filectime($savefile);
		$isStream = 0;
		$filename = ' filename*=UTF-8\'\''.rawurlencode($filename);
		if($is_inline === false) $filename = "attachment; ".$filename;
		$cache_time = 86400;
		///もしキャッシュ更新確認リクエストなら
		if(isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])){
			$str_time = $_SERVER["HTTP_IF_MODIFIED_SINCE"];
			$last_modified_since = strtotime($str_time);
			if($last_modified_since == $last_modified){
				//ファイル更新がなければ、キャッシュ有効を返す。
				header("HTTP/1.1 304 file not modified");
				header('Pragma: cache');
				header("Cache-Control: max-age=".($cache_time));
				return $values;
			}
		}
		else {
			header("Last-Modified: " . date('r', $last_modified));
		}
		header('Pragma: cache');
		header("Cache-Control: max-age=".($cache_time));

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
	public function auth($params){
		if ($this->request->isGet()) {
			return $this->_auth_token();
		}
		else if ($this->request->isPost()) {
			return $this->_auth($_POST);
		}
	}
	/**
	 * ログイン認証を行い認証結果を返却する
	 **/
	private function _auth($params){
		$status = "success";
		$description = "";
		$message = "";
		$data = "";
		$token = "";

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
			if(!isset($user) || $user["status"] !== "success"){
				$status = "failed";
				$message = "E_BAD_REQUEST";
				$description = "paramater error:".$user["status"];
			}
			else if(count($user["data"]) !== 1) {
				$status = "failed";
				$message = "E_AUTH_NOT_FOUND";
				$description = "";
			}
		}
		if($status === "success") {
			$data = $user["data"][0];
			$auth_id = $data["auth_id"];
			$token = $this->get_token($auth_id);
			$result = $this->application->dbi->execConfigQuery("user_token_update", array(
				'log_type' => 'login',
				'new_token' => $token,
				'auth_id' => $auth_id
			));
			if ($result["status"] !== "success") {
				$status = "failed";
				$message = "ERROR";
				$token = "";
			}
			else {
				session_regenerate_id(true) ;
				$_SESSION["access_time"] = time();
				$_SESSION["auth_token"] = $token;
				$_SESSION["auth_user_id"] = $data["ID"];
			}
		}
		$result = array(
			'status'      => $status,
			'message'     => $message,
			'token'     => $token,
			'data' => $data,
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
	public function _auth_token() {
		$http_header = getallheaders();
		$token = "";
		if(isset($http_header["api_token"])){
			$token = $http_header["api_token"];
		}
		if(empty($token) && isset($_SESSION["auth_token"])){
			$token = $_SESSION["auth_token"];
		}
		$response = array(
			'status'      => "success",
			'message'     => "",
			'description' => ""
		);
		$auth_check_time = 300;
		$diff_time = $auth_check_time+1;
		if(!empty($_SESSION["access_time"])) $diff_time = (time() - $_SESSION["access_time"]);
		if ($diff_time < $auth_check_time) {
			return $this->getResponce();
		}
		// validate
		if (empty($token)) {
			return $this->badRequestResponce();
		}

		$user = $this->application->dbi->execConfigQuery("login_by_token", array(
			'token' => $token
		));
		if ($user["status"] !== "success" || count($user["data"]) === 0) {
			return $this->notFoundResponce();
		}
		$auth_id = $user["data"][0]["auth_id"];
		$result = $this->application->dbi->execConfigQuery("user_token_update", array(
			'log_type' => 'auth_token',
			'auth_id' => $auth_id
		));
		if ($result["status"] !== "success") {
			return $this->errorResponce();
		}
		if (count($result["data"]) === 0) {
			return $this->notFoundResponce();
		}

		// Authenticate sceess
		$_SESSION["access_time"] = time(); // 最後にアクセスした時間をセット（タイムアウトに使用する）
		$result["data"] = $user["data"][0];
		return $result;
	}

	public function auth_clear(){
		$http_header = getallheaders();
		$token = "";
		if(isset($http_header["api_token"])){
			$token = $http_header["api_token"];
		}
		if(empty($token) && isset($_SESSION["auth_token"])){
			$token = $_SESSION["auth_token"];
		}
		$user = $this->application->dbi->execConfigQuery("login_by_token", array(
			'token' => $token
		));
		if ($user["status"] !== "success" || count($user["data"]) === 0) {
			return $this->notFoundResponce();
		}
		$auth_id = $user["data"][0]["auth_id"];

		$result = $this->application->dbi->execConfigQuery("user_token_update", array(
			'log_type' => 'auth_clear',
			'new_token' => '-',
			'auth_id' => $auth_id
		));
		if ($result["status"] !== "success") {
			return $this->errorResponce();
		}
		if ($result["data"]["result"][0]["count"] === 0) {
			return $this->notFoundResponce();
		}
		session_cache_limiter("nocache");
		session_cache_expire (0);
		session_regenerate_id( true ) ;

		$_SESSION = array() ;
		$sesName = session_name() ;
		if ( isset( $_COOKIE[$sesName] ) ) {
			$setExpTime = ( time() - ( 3600 * 24 * 365 ) ) ;
			setcookie( $sesName, "", $setExpTime, "/" ) ;
		}
		session_destroy();
		return $this->getResponce();
	}
	public function send_slack(){
		/*
		if(sendSlack("admin_school", "INFO", ":ghost", "test message", 'https://hooks.slack.com/services/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')){
			return $this->errorResponce();
		}
		*/
		$slackApiKey = 'xxxx-xxxxxxxxx-xxxx'; //上で作成したAPIキー
		$text = 'Hello!';
		$text = urlencode($text);
		$url = "https://slack.com/api/chat.postMessage?token=${slackApiKey}&channel=%23hogehoge&text=${text}&as_user=true";
		file_get_contents($url);
		return $this->getResponce();
	}
	public function send_mail(){
		mb_language("Japanese");
		mb_internal_encoding("UTF-8");
		$from = "programming.self.study@gmail.com<放課後プログラミング塾 Self-Study>";
		$to = "konto28go@yahoo.co.jp";
		$title = "お問い合わせ内容を送信いたしました。";
		$body = "お問い合わせ内容ありがとうございます。";

		if(sendMail($from, $to, $title, $body)===false){
			return $this->errorResponce();
		}
		return $this->getResponce();
	}
}
