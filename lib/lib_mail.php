<?php

// ===========================================================
/**
* parseWords
* テキストテンプレートの置換文字を差し替える
* @param  String
* @param  Array
* @return String
*/
function parseWords($txt, Array $words) {
	foreach ($words as $key => $value) {
		$txt = str_replace('===' . $key . '===', $value, $txt);
	}
	return $txt;
}
// ===========================================================
/**
	* notification
	* system_logチャンネルにメッセージをポストする
	* @param  String メッセ―ジタイプ（ユーザーとアイコンの設定）
	* @param  String メッセ―ジ(本文）
	* @return Boolean  成功：true / 失敗：false
	*/
function notification($messageType, $message) {
	$gEnvList  = $GLOBALS['gEnvList'];
	$channel =  $gEnvList["SLACK_log_channel"];
	return remindSlack($channel,  $messageType, $message);
}
// ===========================================================
/**
	* remindSlack
	*
	* @param  String チャンネルタイプ（エイリアス）
	* @param  String メッセ―ジタイプ（ユーザーとアイコンの設定）
	* @param  String メッセ―ジ(本文）
	* @param  String Slack WebHooks EndPoint
	* @return Boolean  成功：true / 失敗：false
	*/
function remindSlack($channel, $messageType, $message, $url=null) {
	$gEnvList  = $GLOBALS['gEnvList'];

	$_url = $gEnvList["SLACK_endpoint"];
	$templates = $gEnvList["SLACK_template"];
	if (isset($url)) $_url = $url;
	if (!isset($channel) || empty($channel)) return false;

	if (!isset($templates[$messageType])) return false;
	$_template = $templates[$messageType];
	$env = getenv("Env");
	return sendSlack($channel,"【".$env."】".$_template["name"], $_template["icon"], $message, $_url);
}
// ===========================================================
/**
	* sendSlack
	*
	* @param  String チャンネル名
	* @param  String ユーザー名
	* @param  String アイコンエイリアス
	* @param  String メッセ―ジ
	* @param  String Slack WebHooks EndPoint
	* @return Boolean  成功：true / 失敗：false
	*/
function sendSlack($channel, $username, $icon, $message, $url) {
	@TXT_LOG("slack", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "sendSlack", "$channel, $username, $icon, $message, $url") ;

	$_request = array(
	"channel" =>  $channel,
	'username' => $username,
	'text' => $message,
	"icon_emoji" =>  $icon
	);
	$options = array(
	'http' => array(
		"protocol_version" => "1.1",
		'method' => 'POST',
		'header' => 'Content-Type: application/json',
		'content' => json_encode($_request),
	),
	'ssl' => array(
			'verify_peer' => false,
			'verify_peer_name' => false
		)
	);
	$response = file_get_contents($url, false, stream_context_create($options));
	if($response === 'ok') return true;
	return false;
}
// ===========================================================
/**
	* sendMail
	*
	* @param  String Fromアドレス
	* @param  String Toアドレス
	* @param  String 件名
	* @param  String 本文
	* @param  String Fromアドレス別名
	* @param  String CCアドレス
	* @param  String BCCアドレス
	* @return Boolean  成功：true / 失敗：false
	*/
function sendMail($mail_from, $mail_to, $mail_title, $mail_body, $mail_cc='', $mail_bcc=''){
	if(!isset($mail_from) || empty($mail_from)) return false;
	if(!isset($mail_to) || empty($mail_to)) return false;
	if(!isset($mail_title) || empty($mail_title)) return false;
	if(!isset($mail_body) || empty($mail_body)) return false;
	$mail_title= str_replace("\n", "", $mail_title);
	$mail_to= str_replace("\n", "", $mail_to);
	$mail_title= str_replace("\n", "", $mail_title);

	$crlf = "\n"; // 現在の改行コード
	// UTF-8 => JIS
	$org_encode = mb_internal_encoding();
/*
	$mail_title = mb_convert_encoding( $mail_title, "ISO-2022-JP", "UTF-8" );
	mb_internal_encoding( "ISO-2022-JP" );
	$mail_title = mb_encode_mimeheader( $mail_title, "ISO-2022-JP" );
*/
	mb_internal_encoding($org_encode);
	$mail_body = mb_convert_encoding( $mail_body, "ISO-2022-JP", "UTF-8" );

	$mail_to = addressEncoding($mail_to, $org_encode);
	$mail_from = addressEncoding($mail_from, $org_encode);

	$header = "MIME-Version: 1.0\n";
	$header .= "Content-Transfer-Encoding: 7bit\n";
	$header .= "Content-Type: text/plain; charset=ISO-2022-JP\n";

	// ヘッダー情報 Sender不要？
	if(isset($mail_cc) && !empty($mail_cc)){
		$mail_cc= str_replace("\n", "", $mail_cc);
		$mail_cc = addressEncoding($mail_cc, $org_encode);
		$header .= "cc: ".$mail_cc."\n";
	}
	if(isset($mail_bcc) && !empty($mail_bcc)){
		$mail_bcc= str_replace("\n", "", $mail_bcc);
		$mail_bcc = addressEncoding($mail_bcc, $org_encode);
		$header .= "bcc: ".$mail_bcc."\n";
	}
	$header .= "from: ".$mail_from;
	@TXT_LOG("mail", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, $mail_to, $mail_title, $mail_body, $header);
	return mb_send_mail($mail_to, $mail_title, $mail_body, $header);
	//return true;
}
// ===========================================================
/**
* addressEncoding
* 1.セミコロン区切りのアドレスを配列化
* 2.別名 <アドレス>形式の別名部分のエンコード
* @param  String メールアドレス
* @param  String デフォルトエンコード
* @return String name-addrをEncodeしたメールアドレス
*/
function addressEncoding($address, $org_encode){
	//セミコロン区切りでをアドレス１つ単位に配列化
	$addressArray  = explode(';', $address.';');
	$_addressParam = array();
	mb_internal_encoding($org_encode);
	for($i=0;$i<count($addressArray);$i++){
		if(empty($addressArray[$i])) continue;

		preg_match('/<(.+)>/', $addressArray[$i], $_match);
		if(count($_match) < 1){
			//別名指定がない場合
			$_addressParam[] =array("address" => $addressArray[$i], "alias" => "");
			continue;
		}
		$_alias = str_replace($_match[0], '', $addressArray[$i]);
		$_address = str_replace('<', '', $_match[0]);
		$_address = str_replace('>', '', $_address);
		//別名指定がる場合は、別名部分のエンコード
		$_alias = mb_convert_encoding( $_alias, "ISO-2022-JP", "UTF-8" );
		mb_internal_encoding( "ISO-2022-JP" );
		$_alias = mb_encode_mimeheader( $_alias, "ISO-2022-JP" );
		$_addressParam[] =array("address" => $_address, "alias" => $_alias);
	}
	mb_internal_encoding($org_encode);
	$result = "";
	for($i=0;$i<count($_addressParam);$i++){
		if(empty($_addressParam[$i]["alias"])){
			$result .= $_addressParam[$i]["address"].';';
		}
		else {
			$result .= $_addressParam[$i]["alias"].' <'.$_addressParam[$i]["address"].'>;';
		}
	}
	$result = trim($result,";");

	return $result;
}
?>
