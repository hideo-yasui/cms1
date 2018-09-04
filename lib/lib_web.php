<?php
// ===========================================================
// ユーザが入力したテキストデータから、htmlタグ、コード、及び改行を変更して変換する
// $str：変換するテキスト
// 戻り値：変換されたテキスト
//-----------------------------------------------------------
function __WEBIO_in( $str ,$brmode=false)
{
	// 下で htmlspecialchars 変換するので、その前に戻す
	// 理由：変換されたコード自体に変換対象となるコードが含まれる為　& とか...
	$str= str_replace("\0", "", $str);
	$str= str_replace("&quot;","\"",$str);
	$str = str_replace( "&gt;", ">", $str);
	$str = str_replace( "&lt;", "<", $str);
	$str = str_replace( "&#039;", "'", $str);
	$str = str_replace( "&amp;", "&", $str);

	/***
	$str = str_replace( "<BR>", "###BRCODE###", $str ) ;
	$str = str_replace( "<BR />", "###BRCODE###", $str ) ;
	$str = str_replace( "<br>", "###BRCODE###", $str ) ;
	$str = str_replace( "<br />", "###BRCODE###", $str ) ;
	***/
	$brCodeList = array( "<BR>", "<BR />", "<br>", "<br />" ) ;
	$str = str_replace( $brCodeList, "###BRCODE###", $str ) ;
	//$str = str_replace( "\\", "###YEN###", $str ) ;

	// "[ダブルクォーテーション]&quot;／'[シングルクォーテーション]
	// &#039;／>[大なり（開始タグ）]&gt;／<[小なり（閉じタグ）]&lt;／&[アンパサンド]&amp;
	$str = htmlspecialchars( $str, ENT_QUOTES ) ;

//	if (!$brmode) {
//		$str = str_replace( "\r\n", "<br />", $str ) ;
//		$str = str_replace( "\n", "<br />", $str ) ;
//		$str = str_replace( "\r", "<br />", $str ) ;
//	}
	//$str = str_replace( "\t", "&nbsp;&nbsp;", $str ) ;
	// $str = str_replace( "\t", " ", $str ) ; // by SSFF ===> 2015.06.08

	//$str = str_replace( "###YEN###", "\\", $str ) ;
	$str = str_replace( "###BRCODE###", "<br />", $str ) ;

	//$str = mysqli_escape_string($str) ;
	return( $str ) ;
}
// ===========================================================
// 無変換で渡されたパラメータをhiddenで渡す際の文字列に変換する
// $str：変換するテキスト
// 戻り値：変換されたテキスト
//-----------------------------------------------------------
function __WEBIO_prm( $str )
{
	$str= str_replace("'","\'",$str);
	$str= str_replace("\"","\\\"",$str);
	//$str= str_replace("<","\<",$str);
	//$str= str_replace(">","\>",$str);
	return( $str ) ;
}
// ===========================================================
// __WEBIO_in にて変換されたデータを改行を変更して変換する
// $str：変換するテキスト
// 戻り値：変換されたテキスト
//-----------------------------------------------------------
function __WEBIO_out( $str )
{
	$str = str_replace( "<BR>", "\n", $str ) ;
	$str = str_replace( "<br>", "\n", $str ) ;
	$str = str_replace( "<BR />", "\n", $str ) ;
	$str = str_replace( "<br />", "\n", $str ) ;
	return( $str ) ;
}
// ===========================================================
// __WEBIO_in にて変換されたデータを改行を変更して変換する
// $str：変換するテキスト
// 戻り値：変換されたテキスト
//-----------------------------------------------------------
function __WEBIO_disp( $str )
{
	$str = str_replace( "\r\n", "<br />", $str ) ;
	$str = str_replace( "\r", "<br />", $str ) ;
	$str = str_replace( "\n", "<br />", $str ) ;
	return( $str ) ;
}
// ===========================================================
// __WEBIO_in にて変換されたデータを元のテキストに変換する
// $str：変換するテキスト
// 戻り値：変換されたテキスト
//-----------------------------------------------------------
function __WEBIO_html( $str )
{
	$str= str_replace("&quot;","\"",$str);
	$str = str_replace( "&gt;", ">", $str);
	$str = str_replace( "&lt;", "<", $str);
	$str = str_replace( "&#039;", "'", $str);
	$str = str_replace( "&amp;", "&", $str);
	return( $str ) ;
}
// ===========================================================
// __WEBIO_in にて変換されたデータを元のテキストに変換する
// $str：変換するテキスト
// 戻り値：変換されたテキスト
//-----------------------------------------------------------
function __WEBIO_return( $str )
{
	$str = __WEBIO_out( $str ) ;
	$str = __WEBIO_html( $str ) ;
	return( $str ) ;
}
// ===========================================================
// __WEBIO_in にて変換されたデータを改行を変更して変換する
// $str：変換するテキスト
// 戻り値：変換されたテキスト
//-----------------------------------------------------------
function __WEBIO_schKey( $str )
{
	$str = __WEBIO_return( $str ) ;
	$str = mysqli_escape_string($str) ;
	return( $str ) ;
}
// ===========================================================
// スーパーグローバルにて受け渡されたパラメータを適切に変換する
// &$data：変換対象のキーリストのポインタ
// 戻り値：無し（直接変換する）
//-----------------------------------------------------------
function __WEBIO_PrmIN( &$data )
{
	foreach ( $data as $pKey => $pVal ) {
		if ( is_array( $pVal ) ) {
			$data[$pKey] = __WEBIO_PrmIN( $pVal );
		} else {
			$data[$pKey] = __WEBIO_in( $pVal ) ;
		}
	}
	return( $data ) ;
}

function _esn_sq ( $str, $numflg=false  ) {
	if ( is_null($str) || ($str === "") ) {
		return "NULL";
	}
	else if ($str === 0 && $numflg) {
		return "NULL";
	}

	$str = str_replace(array("\r\n", "\r", "\n"), "<BR>", $str);
	$str = str_replace("\t", " ", $str);

	global $DBCON;
	return "'".mysqli_real_escape_string( $DBCON, $str )."'";
}

// ===========================================================
/**
 * 指定したviewを読み込む
 * @param  String $view     : 表示したいViewファイル（pageディレクトリ以下）
 * @param  Array  $data     : Viewに渡したい文字
 * @return boolean
 */
function view($view ,array $data = array()) {
	$gPathList = $GLOBALS['gPathList'];
	if(file_exists($gPathList["page"].'/'.$view)){
		include_once($gPathList["page"].'/'.$view);
		return true;
	}
	return false;
}

// ===========================================================
/**
 * sendJSONResponse
 * JSON形式でレスポンスをechoする
 * @param  JSONの元になる配列
 * @param  cross originを許可する場合 true
 * @return void
 */
function sendJSONResponse(array $values, $isArrowOrigin=false, $opt=null) {
  if($isArrowOrigin === true){
	//認証省略するAPIは、CORS許可
    header("Access-Control-Allow-Origin: *");
  }
  header("Content-Type: application/json; charset=utf-8");
  header('X-XSS-Protection:1; mode=block');
  header('X-Frame-Options:SAMEORIGIN');
  header('X-Frame-Options:ALLOW-FROM uri');
  header('X-Content-Type-Options:nosniff');
  header("Expires: Wed, 01 Dec 1999 16:00:00 GMT");
  header("Last-Modified: ". gmdate("D, d M Y H:i:s"). " GMT");
  header("Cache-Control: no-cache, must-revalidate");
  header("Cache-Control: post-check=0, pre-check=0", false);
  header("Pragma: no-cache");
  if ($opt === null) {
    echo json_encode($values);
  } else {
    echo json_encode($values, $opt);
  }
}

// ===========================================================
/**
 * isOriginHeader
 * http://d.hatena.ne.jp/hasegawayosuke/20130302/p1
 * REST向けCSRF対策として、HTTP独自ヘッダーチェックをする
 * @return boolean
 */
function isOriginHeader(){
	foreach (getallheaders() as $name => $value) {
		if($name === "X-Requested-With" && $value === "XMLHttpRequest"){
			return true;
		}
	}
	return false;
}
// ===========================================================
/**
 * getToken
 * FORM/SUBMIT向けCSRF対策として、埋め込み用トークンを生成する
 * @return string
 */
function getToken(){
	if(!isset($_SESSION)) {
		//SESSIONが開始の場合
		$baseString = session_id().date("Ymd-H:i:s:u");
	}
	else {
		//SESSIONが開始していない場合
		$baseString = $_SERVER["HTTP_HOST"].date("Ymd-H");
	}
	return md5($baseString);
}
// ===========================================================
/**
 * exitProc
 * webAPI(view含む）をexitで終了する場合に使う
 * @param  mysqli_conncect 変数
 * @return void
 */

function exitProc($dbconnection=NULL){
	if ((isset($dbconnection )) && (is_object($dbconnection))) {
		$status = @mysqli_close($dbconnection);
		unset($dbconnection) ;
	}
	else if((isset( $DBCON )) && (is_object($DBCON))) {
		$status = @mysqli_close($DBCON);
		unset($DBCON) ;
	}

	exit;
}
function TXT_LOG(){
	$gPathList = $GLOBALS['gPathList'];
	$now = date("Ym");
	$message = "";
	$argc = func_num_args();
	for($i=0;$i<$argc;$i++){
		$arg = func_get_arg($i);
		if($i==0) $filepath = $gPathList["logs"].$now."_".$arg.".log";
		else $message = $message.$arg."\t";
	}
	$message = str_replace("\r\n"," ",$message);
	$message = str_replace("\r"," ",$message);
	$message = str_replace("\n"," ",$message);

	@logWrite($filepath, $message);
}
function logWrite($filepath, $msg){
	$arrTime = explode('.',microtime(true));
	$now = date('Y-m-d H:i:s', $arrTime[0]) . '.' .$arrTime[1];
	$dat = $now.":".$msg."\n";
	chmod($filepath, 0666);
	file_put_contents($filepath, $dat, FILE_APPEND);
}
