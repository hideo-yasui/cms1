<?php
require_once($_SERVER["DOCUMENT_ROOT"] . '/controller/comService.php');

class comView extends comService
{
	protected $auth_actions = array('');
	public function requireUrl($params){
		$path = $_SERVER["DOCUMENT_ROOT"] . '/'.$params["path"];
		require_once($path);
		$this->exitProc();
	}
	public function redirectUrl($params){
		$path = $params["path"];
		$queryString = $this->request->getQueryString();
		if(!empty($queryString)){
			if (false !== ($pos = strpos($controller_file, '?'))) {
				//クエリ文字列がついている場合は、&で後ろに連結
				$queryString = "&".$queryString;
			}
			else {
				$queryString = "?".$queryString;
			}
			$path .= $queryString;
		}
		header( "Location: ". $path ) ;
		$this->exitProc();
	}
	public function view($params){
		$this->showQueryView($params["contents"]);
	}
	private function showQueryView($page_code,  array $data=null){
		if(empty($page_code)) $this->showPageNotFound();
		$viewData = array();
		$values = $this->getViewData($page_code);
		$template = "";
		if($values["status"] ==="success"){
			$contents = $values["contents"];
			$template = $values["template"];
			$this->system = $values["system"];
			$viewData["title"] = $values["title"];
			$viewData["data"] = $values["data"];
		}
		else {
			$this->showPageNotFound();
		}
		if(isset($data) && is_array($data)){
			$viewData = array_merge($viewData, $data);
		}
		$this->showView($contents, $template, $viewData);
	}

	//getViewData : view用のパラメータをm_pageより取得する
	private function getViewData($page_code){
		$ret = array(
			'title' => "",
			'template' => "",
			'system' => $this->system,
			'contents' => $page_code,
			'data' => "",
			'status'      => "success",
			'message'     => "",
			'description' => ""
		);

		if(!isset($this->system) || empty($this->system)) return $ret;
		if(!isset($page_code) || empty($page_code)) return $ret;

		$pageData = array(
			'SYSTEM_CODE' => $this->system,
			'PAGE_TYPE' => 'commonView',
			'page_code' => $page_code
		);
		$values = $this->application->dbi->execConfigQuery('get_page', $pageData);
		if($values["status"] =="success"){
			$data = $values["data"];
			$option = array();
			$isAuth = "";
			if(count($data)>0){
				$data = $data[0];
			}
			else {
				$ret["status"] ="error";
				$ret["message"] ="data nothing";
				$ret["description"] ="system=".$this->system.",page_code=".$page_code;
				return $ret;
			}
			//m_page.NAMEからtitle
			if(isset($data["NAME"]) && !empty($data["NAME"])) $ret["title"] = $data["NAME"];
			if(isset($data["OPTION_STRING"]) && !empty($data["OPTION_STRING"])){
				$option = trim('{'.str_replace('&quot;', '"', $data["OPTION_STRING"]).'}');
				$option = json_decode($option, true);
				//m_page.OPTION_STRINGからtemplateとcontentsを取得
				if(isset($option["title"]) && !empty($option["title"])) $ret["title"] = $option["title"];
				if(isset($option["template"]) && !empty($option["template"])) $ret["template"] = $option["template"];
				if(isset($option["contents"]) && !empty($option["contents"])) $ret["contents"] = $option["contents"];
				if(isset($option["system"]) && !empty($option["system"])) $ret["system"] = $option["system"];
			}
			if(isset($option["autologin"]) && !empty($option["autologin"]) ){
				//自動ログイン、autologinにログイン後の遷移先を設定する
				$values = getAuthenticateResponse($this->db);
				if($values["status"] ==="success") {
					header( "Location: /".$option["autologin"] ) ;
					$this->exitProc();
				}
			}

			if(!isset($option["auth"]) || empty($option["auth"]) || $option["auth"]!="false"){
				//デフォルトで認証が必要、auth=falseの場合のみ認証を無視できる
				$values = getAuthenticateResponse($this->db);
				if($values["status"] !=="success") {
					if($values["message"] === "E_AUTH_TIMEOUT"){
						$this->showPageSessionTimeout();
					}
					$this->showPageForbidden();
				}
			}

			if(isset($option["query"]) && !empty($option["query"])){
				//ページ表示用データを取得する
				if(!is_array($option["query"])) $query = array($option["query"]);
				else $query = $option["query"];
				for($i=0;$i<count($query);$i++){
					$values = $this->application->dbi->execConfigQuery($query[$i], $pageData);
					$ret["status"] = $values["status"];
					$ret["message"] = $values["message"];
					$ret["description"] = $values["description"];
					if($values["status"] !="success") break;
					if(isset($values["data"]) && !empty($values["data"])) $ret["data"][$query[$i]] = $values["data"];
				}
			}

		}
		return $ret;
	}
	//showView : viewを表示する
	private function showView($contents, $template, array $data=null){
		if(empty($this->system) || empty($contents)){
			$this->showPageNotFound();
		}
		if(!isset($data)) $data = array();
		if(empty($data["title"])) $data["title"] = "no title";

		//拡張子がない場合は、.phpを使う
		if(!strrpos($contents, '.')) $contents.='.php';

		//m_code.GROUP_VAL=SYS_CONFIGのCODE_VAL、CODE_NAMEを取得し、表示用変数として利用
		$values = $this->application->dbi->execConfigQuery("get_config");
		$ret["status"] = $values["status"];
		$ret["message"] = $values["message"];
		$ret["description"] = $values["description"];
		if($values["status"] !="success"){
			$this->application->dbi->addConfigLog("service", "not found/get_config error");
			$this->showPageNotFound();
		}
		//CSRF対策用token
		$data["token"] = getToken();
		$_SESSION["token"] = $data["token"];
		//ページ利用するフォルダ定義
		$data["_directory"] = array( 'system' => $this->system,
			'partial' => $this->system."/partial/",
			'template' => $this->system."/template/",
			'contents' => $this->system."/template/contents/",
			'meta' => $this->system."/template/meta/"
		);

		if(isset($values["data"])){
			$data["config"] = array();
			for($i=0;$i<count($values["data"]);$i++){
				$data["config"][$values["data"][$i]["CODE_VAL"]] =$values["data"][$i]["CODE_NAME"];
			}
		}

		if(!empty($template)) {
			//template利用型
			//拡張子がない場合は、.phpを使う
			if(!strrpos($template, '.')) $template.='.php';
			$data['contents'] = $data["_directory"]["contents"].$contents;
			if(view($data["_directory"]["template"].$template, $data)){
				$this->exitProc();
			}
		}
		else{
			//template利用しない
			if(!strrpos($contents, '.')) $contents.='.php';
			if(view($data["_directory"]["system"]."/".$contents, $data)){
				$this->exitProc();
			}
		}
		$this->application->dbi->addConfigLog("err", "showView paramater error:".$this->system."/".$contents."/".$template );
		$this->showPageNotFound();
	}

	//404相当ページの表示
	private function showPageNotFound(){
		view("notfound.html", array());
		$this->exitProc();
	}
	//403相当ページの表示
	private function showPageForbidden(){
		view("forbidden.html", array());
		$this->exitProc();
	}
	//セッションタイムアウトページの表示
	private function showPageSessionTimeout(){
		header("Location:logout?error=1");
		$this->exitProc();
	}
	//CSRF向け tokenチェック
	private function isCorrectToken(){
		if( isset($_SESSION) ) {
			if(isset($_POST['token']) && isset($_SESSION['token']) && $_POST['token'] === $_SESSION['token']){
				//SESSIONが開始の場合
				return true;
			}
		}
		else {
			if (isset($_POST['token']) // tokenは下記ロジックで発行される。有効時間は1時間
	        && ($_POST['token'] === md5($_SERVER["HTTP_HOST"].date("Ymd-H", strtotime()))
	          || $_POST['token'] === md5($_SERVER["HTTP_HOST"].date("Ymd-H", strtotime('- 1 hour'))))) {
				//SESSIONが開始していない場合
				return true;
			}
		}
		return false;
	}

}
