<?php
require_once($_SERVER["DOCUMENT_ROOT"] . '/controller/comService.php');

class comMail extends comService
{
	protected $auth_actions = array('');
	public function send($params){
		$result = $this->badRequestResponce();
		if(isset($params['cache']) && $params['cache']==='use'){
			/*
			$result = $this->application->dbi->load_cache();
			if($result!==null) return $result;
			*/
		}
		if ($this->request->isGet()) {
			$params = array_merge($params, $_GET);
		}
		else if ($this->request->isPost()) {
			$params = array_merge($params, $_POST);
		}
		$result = $this->application->dbi->execConfigQuery('get_unsentmail', null, "master");
		if(count($result["data"]) === 0) {
			//送信対象なし
			return $this->getResponce();
		}

		for($i=0;$i<count($result["data"]);$i++){
			$mailids[] = $result["data"][$i]["ID"];
		}

		//送信対象メールのチェック
		$result = $this->application->dbi->execConfigQuery("unsentmail_upd", array("IDS" => $mailids), "master");
		if($result["status"] !== "success"){
			@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "sendMail", "unsentmail_upd/status=".$result["status"]) ;
			return $this->errorResponce("unsentmail_upd");
		}
		$result = $this->application->dbi->execConfigQuery("get_mailparam", null, $this->application->system_info["DB_NAME"]);
		$mail_param = array();
		if($result["status"] !== "success" || count($result["data"]) === 0){
			@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "sendMail", "get_mailparam/status=".$result["status"]."/count=".count($result["data"])) ;
			return $this->errorResponce("get_mailparam");
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
		$result = $this->application->dbi->execConfigQuery("get_sendingmail", null, "master");
		if($result["status"] !== "success" || count($result["data"]) === 0){
			//送信対象がない＝全部エラーになった
			@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "sendMail", "get_sendingmail/status=".$result["status"]."/count=".count($result["data"])) ;
			return $this->errorResponce("get_sendingmail");
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
			$result = $this->application->dbi->execConfigQuery("sendingmail_upd", array("IDS" => $success_mailids, "SEND_STATUS" => "send"), "master");
			if($result["status"] !== "success"){
				@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "sendMail", "sendingmail_upd(send)/status=".$result["status"]."/count=".count($success_mailids)) ;
				return $this->errorResponce("sendingmail_upd");
			}
		}
		if(count($error_mailids) > 0){
			//対象メールエラー更新
			$result = $this->application->dbi->execConfigQuery("sendingmail_upd", array("IDS" => $error_mailids, "SEND_STATUS" => "error"), "master");
			if($result["status"] !== "success"){
				@TXT_LOG("error", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "sendMail", "sendingmail_upd(error)/status=".$result["status"]."/count=".count($error_mailids)) ;
				return $this->errorResponce("sendingmail_upd");
			}
		}
		@TXT_LOG("mail", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "sendMail", "end/success=".count($success_mailids)."/error=".count($error_mailids)) ;
		return $this->getResponce(null, "", "end/success=".count($success_mailids)."/error=".count($error_mailids)  );
	}
}
