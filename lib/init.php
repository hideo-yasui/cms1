<?php
//---------------------------------------------------------------------------
/*
　基本パス取得・セット（$gPathListのセット）
　環境データセット（env_set.php のinclude）
*/
//---------------------------------------------------------------------------

//---------------------------------------------------------------------------
// 基本となるパス情報のセット
//---------------------------------------------------------------------------
$gPathList = array() ;
//$gPathList["svroot"] = $_SERVER["DOCUMENT_ROOT"] ;
$gPathList["svroot"] = dirname(__FILE__)."/../webapps";

$gPathList["sv_top"] = substr( $gPathList["svroot"], 0, strrpos( $gPathList["svroot"], "/" ) ) ;
$gPathList["u_home"] = substr( $gPathList["sv_top"], 0, strrpos( $gPathList["sv_top"], "/" ) ) ;
$gPathList["curent"] = dirname( $_SERVER["PHP_SELF"] ) ;

// ディレクトリ構成
$gPathList["page"]       = $gPathList["svroot"] . '/page';
$gPathList["logs"]       = $gPathList["svroot"] . '/../../logs/';
$gPathList["lib"]       = $gPathList["svroot"] . '/../lib/';
$gPathList["upload"]       = $gPathList["svroot"] . '/../../upload/';
$gPathList["_cache"]       = $gPathList["svroot"] . '/../../_cache/';
// 一時フォルダが存在しない場合はフォルダ作成
if(!file_exists($gPathList["logs"])){
	mkdir($gPathList["logs"], '0755');
}
if(!file_exists($gPathList["upload"])){
	mkdir($gPathList["upload"], '0755');
}
if(!file_exists($gPathList["_cache"])){
	mkdir($gPathList["_cache"], '0755');
}
$gEnvList = array();
$gEnvList["version"] 	= '1.0.0.3' ;

// Slack WebHock End Point
$gEnvList["SLACK_endpoint"]		= "https://hooks.slack.com/services/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";
$gEnvList["SLACK_log_channel"]		= "admin_school";
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


require_once( $gPathList["lib"]."lib_web.php" ) ;
require_once( $gPathList["lib"]."lib_string.php" ) ;
require_once( $gPathList["lib"]."lib_mail.php" ) ;
require_once( $gPathList["lib"]."lib_file.php" ) ;
//s3見に行くと遅くなる
//require_once( $gPathList["lib"]."aws_s3.php" );

//---------------------------------------------------------------------------
// デフォルトパラメータのセット
//---------------------------------------------------------------------------

$ERROR = "";
session_cache_limiter("must-revalidate");
session_cache_expire (0);
//ここでセッション開始
session_start();


//---------------------------------------------------------------------------
// リファラ情報のセット
//---------------------------------------------------------------------------
if (isset($_SERVER["HTTP_REFERER"])){
	$_SESSION["HTTP_REFERER"] = $_SERVER["HTTP_REFERER"] ;
}


//---------------------------------------------------------------------------
// アタック対応：httpレスポンス分割攻撃
// sample：https://xxxxx.test.domain/index.html/%22;%0D%0A//--%3E%0D%0Aalert%28document.cookie%29;%0D%0A//%3C!--%20HTTP/1.0
//---------------------------------------------------------------------------
if (isset($_SERVER["REQUEST_URI"])) {
	$request_uri =$_SERVER["REQUEST_URI"] ;
	$chkAttackFlg = 0 ;
	if ( stripos( $request_uri, "%0d" ) || stripos( $request_uri, "%0a" ) ){
		header( "Location: /logouot" ) ;
		exit;
	}
}
