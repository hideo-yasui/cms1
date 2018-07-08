<?php
require_once($gPathList["lib"].'lib_mail.php');

/**
* DB.config.t_mailより送信対象メールデータを取得し、送信する
* @param  Object : DB Connection
* @return boolean
*/
function execConfigMail($DBCON) {
	//未送信メールの取得
	$result = execConfigQuery($DBCON, "get_unsentmail");
	if($result["status"] !== "success"){
		@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "sendMail", "get_unsentmail/status=".$result["status"]) ;
		return false;
	}
	if(count($result["data"]) === 0) {
		//送信対象なし
		return true;
	}
	@TXT_LOG("mail", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "sendMail", "start") ;
	$mailids = array();
	for($i=0;$i<count($result["data"]);$i++){
		$mailids[] = $result["data"][$i]["ID"];
	}
	//送信対象メールのチェック
	$result = execConfigQuery($DBCON, "unsentmail_upd", array("IDS" => $mailids));
	if($result["status"] !== "success"){
		@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "sendMail", "unsentmail_upd/status=".$result["status"]) ;
		return false;
	}
	//メール用パラメータの取得m_code/GEOUP_VAL = SYS_CONFIG or MAIL_PARAM
	$result = execConfigQuery($DBCON, "get_mailparam");
	$mail_param = array();
	if($result["status"] !== "success" || count($result["data"]) === 0){
		@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "sendMail", "get_mailparam/status=".$result["status"]."/count=".count($result["data"])) ;
		return false;
	}
	for($i=0;$i<count($result["data"]);$i++){
		if($result["data"][$i]["GROUP_VAL"] === "SYS_CONFIG"){
			$mail_param[$result["data"][$i]["CODE_VAL"]] = $result["data"][$i]["CODE_NAME"];
		}
		else if($result["data"][$i]["GROUP_VAL"] === "MAIL_PARAM"){
			$mail_param[$result["data"][$i]["CODE_VAL"]] = $result["data"][$i]["CODE_REMARK"];
		}
	}
	//送信対象メールの取得（送信可能なものだけ取得する）
	$result = execConfigQuery($DBCON, "get_sendingmail");
	if($result["status"] !== "success" || count($result["data"]) === 0){
		//送信対象がない＝全部エラーになった
		@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "sendMail", "get_sendingmail/status=".$result["status"]."/count=".count($result["data"])) ;
		return false;
	}
	$success_mailids = array();
	$error_mailids = array();

	for($i=0;$i<count($result["data"]);$i++){
		$sended = false;
		$body = parseWords(__WEBIO_return($result["data"][$i]["BODY"]), $mail_param);
		if($result["data"][$i]["MAIL_TYPE"]=='slack'){
			//slack通知
			$sended = remindSlack($result["data"][$i]["TO_ADDRESS"],$result["data"][$i]["TITLE"],$body);
		}
		else if($result["data"][$i]["MAIL_TYPE"]=='text'){
			//メール送信
			$sended = sendMail(__WEBIO_return($result["data"][$i]["FROM_ADDRESS"]),
			__WEBIO_return($result["data"][$i]["TO_ADDRESS"]),
			__WEBIO_return($result["data"][$i]["TITLE"]),$body,
			__WEBIO_return($result["data"][$i]["CC_ADDRESS"]),
			__WEBIO_return($result["data"][$i]["BCC_ADDRESS"]));
		}
		if($sended === true){
			$success_mailids[] = $result["data"][$i]["ID"];
		}
		else {
			$error_mailids[] = $result["data"][$i]["ID"];
		}
	}
	if(count($success_mailids) > 0){
		//対象メール送信済み更新
		$result = execConfigQuery($DBCON, "sendingmail_upd", array("IDS" => $success_mailids, "SEND_STATUS" => "send"));
		if($result["status"] !== "success"){
			@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "sendMail", "sendingmail_upd(send)/status=".$result["status"]."/count=".count($success_mailids)) ;
			return false;
		}
	}
	if(count($error_mailids) > 0){
		//対象メールエラー更新
		$result = execConfigQuery($DBCON, "sendingmail_upd", array("IDS" => $error_mailids, "SEND_STATUS" => "error"));
		if($result["status"] !== "success"){
			@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "sendMail", "sendingmail_upd(error)/status=".$result["status"]."/count=".count($error_mailids)) ;
			return false;
		}
	}
	@TXT_LOG("mail", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "sendMail", "end/success=".count($success_mailids)."/error=".count($error_mailids)) ;
	return true;
}
?>
