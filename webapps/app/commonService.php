<?php
/**
 * 指定されたアクションを実行し、
 * 結果をhttpResponceとしてjsonで返却する
 * @param dbconnect $DBCON
 * @param string $action
 * @param array $params
 **/
function commonService($DBCON, $action, $params){
	global $gEnvList ;
	global $gPathList;
	$query_code = "";
	if(isset($params["query_code"]) && !empty($params["query_code"])) $query_code = $params["query_code"];
	if(isset($params["method"]) && !empty($params["method"])) $query_code = $query_code."_".$params["method"];
    $target = $action;

	$values = array(
	  'status'      => "success",
	  'message'     => "",
	  'description' => ""
	);

	$isAuth = true;
	//認証省略するサービス判定
	if (isset($query_code) && !empty($query_code)) {
		for($i=0;$i<count($gEnvList["authenticate_skip"]);$i++){
			if($query_code===$gEnvList["authenticate_skip"][$i]) {
				$isAuth = false;
				break;
			}
		}
	}


	if ($isAuth) {
		$values = getAuthenticateResponse($DBCON);
		if($values["status"] !== "success"){
			authenticate_error($values["message"], $values["description"]);
			sendJSONResponse($values);
			exitProc($DBCON);
		}
	}

	$logging = true;
	if($values["status"] =="success"){
		$logRemark = "-";
		//ログ出力省略するサービス判定
		if (isset($query_code) && !empty($query_code)) {
			$logRemark = $query_code;
			for($i=0;$i<count($gEnvList["ignorelog_query_code"]);$i++){
				if($query_code===$gEnvList["ignorelog_query_code"][$i]) {
					$logging = false;
					break;
				}
			}
		}

		if (isset($target) && !empty($target)) {
			if($logging) @INS_SQL_LOG("service", $target." start", $logRemark);
		}
		else {
			@INS_SQL_LOG("service", "No Target", "-");
		}
		$isOrigin = isOriginHeader();
		@TXT_LOG("service", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "isOriginHeader",$isOrigin, $_SESSION["sLoginSystem"]) ;
		//リクエストヘッダーの判定 （利用システムが、adminとcontrolは除く）
		if($isAuth && $_SESSION["sLoginSystem"] !== "admin" && $_SESSION["sLoginSystem"] !== "control" && $isOrigin === false ){
	        @INS_SQL_LOG("err", $target." isOriginHeader error", "st=[".$values["status"]."]\nmsg=[".$values["message"]."]\n".$values["description"]);
			$values["status"] = "error";
			sendJSONResponse($values);
			exitProc($DBCON);
		}

		switch($target){
	        case "getpermission":
				$values["data"] = $_SESSION["sPermission"];
				break;
			case "search":
				$values = execConfigSearch($DBCON , $query_code, $params);
				break;
			case "query":
				$values = execConfigQuery($DBCON , $query_code, $params);
				break;
			case "export":
				$outputfile = "csv";
				if (!empty($params["outputfile"])){
					$outputfile = $params["outputfile"];
				}
				$encode = "";
				if (!empty($params["encode"])){
					$encode = $params["encode"];
				}
				$values = export_file($DBCON, $query_code, $params, $outputfile, $encode);
				break;
			case "download":
			case "getfile":
				$values = execConfigQuery($DBCON , "get_file", $params);
				$is_inline = false;
				if($target === "getfile") $is_inline = true;
				if($values["status"] =="success" && count($values["data"]) > 0){
					$save_file_path = $gPathList["upload"].$values["data"][0]["fileid"];
					$fileid = $values["data"][0]['fileid'];
					$filename = $values["data"][0]['filename'];
					$values = downloadfile($fileid, $filename, $save_file_path, $is_inline);
					if($values["status"] =="success"){
						exitProc($DBCON);
					}
				}
				if($is_inline === true){
					view("notfound.html", array());
					exitProc($DBCON);
				}
				break;
			case "upload":
				$todate = date("Ymd_His");
				$fileid = $todate."_".getSecureKey(6);
				$values = uploadfile($_POST["formid"], $gPathList["upload"], $fileid);
				if($values["status"] =="success"){
					$values2 = INS_FILE($values["data"], $_POST["remark"]);
					$values["status"] = $values2["status"];
					$values["message"] = $values2["message"];
					$values["description"] = $values2["description"];
				}
				break;
			case "import":
				$todate = date("Ymd_His");
				$fileid = $todate."_".getSecureKey(6);
				$values = uploadfile($_POST["formid"], $gPathList["upload"], $fileid);
				if($values["status"] =="success"){
					$values2 = INS_FILE($values["data"], $query_code);
					$values["status"] = $values2["status"];
					$values["message"] = $values2["message"];
					$values["description"] = $values2["description"];
					if($values2["status"] =="success"){
						$path = $gPathList["upload"].$fileid;
						$values3 = import_file($DBCON, $query_code, $path, $_POST);
						$values["status"] = $values3["status"];
						$values["message"] = $values3["message"];
						$values["description"] = $values3["description"];
					}
				}
				break;
	        case "sendmail":
				if(execConfigMail($DBCON) === false){
	                $values["status"] = "failed";
	                $values["message"] = "ERROR";
	                $values["description"] = $query_code;
	            }
				break;
		}
	}
	if (isset($target) && !empty($target)) {
		if($logging && $values["status"] != "success") @INS_SQL_LOG("err", $target." err", "st=[".$values["status"]."]\nmsg=[".$values["message"]."]\n".$values["description"]);
	}
	sendJSONResponse($values, !$isAuth);
	@TXT_LOG("service", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "end") ;
	exitProc($DBCON);
}

/**
* ファイルダウンロード
* @param string $fileid s3ファイルＩＤ
* @param string $filename ファイル名称
* @param boolean $is_inline trueの場合inlineで表示
* @return array
*/
function downloadfile($fileid, $filename, $savefile, $is_inline=false){
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
/**
* ファイルアップロード
* @param string $formid fileフォームのid
* @param string $folder 保存フォルダ（uploadフォルダ）
* @param string $fileid 保存ファイルID
* @return array
*/
function uploadfile($formid,$folder,$fileid){
	$result = save_uploadfile($formid,$folder,$fileid);
	if($result["status"]!=="success"){
		@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "save_uploadfile",$result["message"], $result["description"]) ;
		return $result;
	}
	return $result;
}
/**
* アップロードファイル保存
* @param string $formid fileフォームのid
* @param string $folder 保存フォルダ（uploadフォルダ）
* @param string $fileid 保存ファイルID
* @return array
*/
function save_uploadfile($formid,$folder,$fileid){
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
