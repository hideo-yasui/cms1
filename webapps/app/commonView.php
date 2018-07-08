<?php
/**
 * 指定されたアクションを実行し、
 * 結果のviewを表示する
 * @param dbconnect $DBCON
 * @param string $action
 * @param array $params
 **/
function commonView($DBCON, $action, $params){
	$systemCode = $GLOBALS['gEnvList']['system'];
	if(!isset($params["template"]) || empty($params["template"])) $params["template"]="";
	if(!isset($params["contents"]) || empty($params["contents"])) $params["contents"]="";
	if(isset($params["contents"]) && !empty($params["contents"])){
		$systemCode = $GLOBALS['gEnvList']['system'];
		if(!isset($params["template"]) || empty($params["template"])) $params["template"]="";
		if(isset($action) && $action==="query") {
			showQueryView($DBCON, $systemCode,  $params["contents"]);
		}
		else {
			showView($DBCON, $systemCode, $params["contents"], $params["template"]);
		}
	}
	exitProc($DBCON);
}
//if(isset($type) && $type=="query"){
//showQueryView：getViewData+showViewを行う
function showQueryView($DBCON, $systemCode, $contents,  array $data=null){
	if(!isset($contents) || empty($contents)) showPageForbidden();
	$viewData = array();
	$values = getViewData($DBCON, $systemCode, $contents);
	$template = "";
	if($values["status"] =="success"){
		$contents = $values["contents"];
		$template = $values["template"];
		$systemCode = $values["system"];
		$viewData["title"] = $values["title"];
		$viewData["data"] = $values["data"];
	}
	else {
		showPageNotFound();
	}
	if(isset($data) && is_array($data)){
		$viewData = array_merge($viewData, $data);
	}
	showView($DBCON, $systemCode, $contents, $template, $viewData);
}

//getViewData : view用のパラメータをm_pageより取得する
function getViewData($DBCON, $systemCode, $contents){
	$gPathList = $GLOBALS['gPathList'];
	$pageData = array(
		'SYSTEM_CODE' => $systemCode,
		'PAGE_TYPE' => 'commonView',
		'page_code' => $contents
	);
	$ret = array(
		'title' => "",
		'template' => "",
		'system' => $systemCode,
		'contents' => $contents,
		'data' => "",
		'status'      => "success",
		'message'     => "",
		'description' => ""
	);

	if(!isset($systemCode) || empty($systemCode)) return $ret;
	if(!isset($contents) || empty($contents)) return $ret;

	$values = execConfigQuery($DBCON , "get_page", $pageData);
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
			$ret["description"] ="system=$systemCode,contents=$contents";
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
			$values = getAuthenticateResponse($DBCON);
			if($values["status"] ==="success") {
				header( "Location: /".$option["autologin"] ) ;
			    exitProc($DBCON);
			}
		}

		if(!isset($option["auth"]) || empty($option["auth"]) || $option["auth"]!="false"){
			//デフォルトで認証が必要、auth=falseの場合のみ認証を無視できる
			$values = getAuthenticateResponse($DBCON);
			if($values["status"] !="success") {
				if($values["message"] === "E_AUTH_TIMEOUT"){
					showPageSessionTimeout();
					exitProc($DBCON);
				}
				showPageForbidden();
				exitProc($DBCON);
			}
		}

		if(isset($option["query"]) && !empty($option["query"])){
			//ページ表示用データを取得する
			if(!is_array($option["query"])) $query = array($option["query"]);
			else $query = $option["query"];
			for($i=0;$i<count($query);$i++){
				$values = execConfigQuery($DBCON , $query[$i], $pageData);
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
function showView($DBCON, $systemCode, $contents, $template, array $data=null){
	@INS_SQL_LOG("service", "showView:".$systemCode."/".$contents."/".$template );
	if(!isset($systemCode) || empty($systemCode)){
		showPageNotFound();
		exitProc($DBCON);
	}
	if(!isset($contents) || empty($contents)){
		showPageNotFound();
		exitProc($DBCON);
	}
	if(!isset($data) || empty($data)) $data = array();
	if(!isset($data["title"]) || empty($data["title"])) $data["title"] = "no title";

	//拡張子がない場合は、.phpを使う
	if(!strrpos($contents, '.')) $contents.='.php';

	//m_code.GROUP_VAL=SYS_CONFIGのCODE_VAL、CODE_NAMEを取得し、表示用変数として利用
	$values = execConfigQuery($DBCON , "get_config");
	$ret["status"] = $values["status"];
	$ret["message"] = $values["message"];
	$ret["description"] = $values["description"];
	if($values["status"] !="success"){
		@INS_SQL_LOG("service", "not found/get_config error");
		showPageNotFound();
		exitProc($DBCON);
	}
	//CSRF対策用token
	$data["token"] = getToken();
	$_SESSION["token"] = $data["token"];

	//ページ利用するフォルダ定義
	$data["_directory"] = array( 'system' => $systemCode,
		'partial' => $systemCode."/partial/",
		'template' => $systemCode."/template/",
		'contents' => $systemCode."/template/contents/",
		'meta' => $systemCode."/template/meta/"
	);

	if(isset($values["data"]) && !empty($values["data"])){
		$data["config"] = array();
		for($i=0;$i<count($values["data"]);$i++){
			$data["config"][$values["data"][$i]["CODE_VAL"]] =$values["data"][$i]["CODE_NAME"];
		}
	}

	if(isset($template) && !empty($template)) {
		//template利用型
		//拡張子がない場合は、.phpを使う
		if(!strrpos($template, '.')) $template.='.php';
		$data['contents'] = $data["_directory"]["contents"].$contents;
		if(view($data["_directory"]["template"].$template, $data)){
			exitProc($DBCON);
		}
	}
	else{
		//template利用しない
		if(!strrpos($contents, '.')) $contents.='.php';
		if(view($data["_directory"]["system"]."/".$contents, $data)){
			exitProc($DBCON);
		}
	}
	@INS_SQL_LOG("err", "showView paramater error:".$systemCode."/".$contents."/".$template );
	showPageNotFound();
	exitProc($DBCON);
}
//404相当ページの表示
function showPageNotFound(){
	view("notfound.html", array());
}
//403相当ページの表示
function showPageForbidden(){
	view("forbidden.html", array());
}
//セッションタイムアウトページの表示
function showPageSessionTimeout(){
	header("Location:logout?error=1");
}
//CSRF向け tokenチェック
function isCorrectToken(){
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
?>
