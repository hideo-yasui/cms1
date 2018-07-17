<?PHP

$gEnvList = array();
$gEnvList["version"] 	= '1.0.0.1' ;

// Slack WebHock End Point
$gEnvList["SLACK_endpoint"]		= "";
$gEnvList["SLACK_log_channel"]		= "system_log";
$gEnvList["SLACK_template"]		= array();
$gEnvList["SLACK_template"]["INFO"] = array("name" => "INFO", "icon" => ":speech_balloon:");
$gEnvList["SLACK_template"]["WARNING"] = array("name" => "WARNING", "icon" => ":warning:");
$gEnvList["SLACK_template"]["ERROR"] = array("name" => "ERROR", "icon" => ":no_entry:");


//----------------------------------------------------------------------------------
//テキストログ出力/DBログ出力を無効にしたい場合に、query_codeを追加する
$gEnvList["ignorelog_query_code"] = array();
//$gEnvList["ignorelog_query_code"][] = "get_page";
$gEnvList["ignorelog_query_code"][] = "get_treemenu";
$gEnvList["ignorelog_query_code"][] = "get_group_code_enc";
$gEnvList["ignorelog_query_code"][] = "t_logs_ins";
$gEnvList["ignorelog_query_code"][] = "t_logs";

//次のpost変数は、秘匿する
$gEnvList["hidden_post_paramater"] = array();
$gEnvList["hidden_post_paramater"][] = "password_old";
$gEnvList["hidden_post_paramater"][] = "password";
$gEnvList["hidden_post_paramater"][] = "password_confirm";

//次のquery_codeは、認証処理を省略する
$gEnvList["authenticate_skip"] = array();
$gEnvList["authenticate_skip"][] = "get_group_code_enc";
$gEnvList["authenticate_skip"][] = "get_taxon_all";
$gEnvList["authenticate_skip"][] = "get_taxon_by_id";
$gEnvList["authenticate_skip"][] = "user_register_upd";
$gEnvList["authenticate_skip"][] = "t_temp_users_ins";
$gEnvList["authenticate_skip"][] = "check_age_is_mature";
$gEnvList["authenticate_skip"][] = "check_is_user_by_email";

//使用中のサブシステム
$gEnvList["system"] = getSystemNameByHost();

/**
 * ホストからシステムを判定する
 *
 * @param  Object ： DBコネクション
 * @return String
 */
function getSystemNameByHost() {
	$system = 'control';
	if(!isset($_SERVER) || !isset($_SERVER['HTTP_HOST'])){
		//バッチ処理などで呼ばれるケースを考慮
		return $system;
	}
	$tmp       = explode('.', $_SERVER['HTTP_HOST']);
	$subdomain = $tmp[0];

	return $system;
}
