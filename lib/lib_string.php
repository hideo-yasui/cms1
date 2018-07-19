<?php
// ===========================================================
// 全角文字を半角文字に変換する（全角・半角の区別がわからないユーザにも対応する為）
// $strx：変換する文字列
// 戻り値：変換された文字列
//-----------------------------------------------------------
function STR_ZenToHan( $strx )
{
	$bsStrList = array( "０", "0", "１", "1", "２", "2", "３", "3", "４", "4", "５", "5", "６", "6", "７", "7", "８", "8", "９", "9", "ａ", "a", "Ａ", "A", "ｂ", "b", "Ｂ", "B", "ｃ", "c", "Ｃ", "C", "ｄ", "d", "Ｄ", "D", "ｅ", "e", "Ｅ", "E", "ｆ", "f", "Ｆ", "F", "ｇ", "g", "Ｇ", "G", "ｈ", "h", "Ｈ", "H", "ｉ", "i", "Ｉ", "I", "ｊ", "j", "Ｊ", "J", "ｋ", "k", "Ｋ", "K", "ｌ", "l", "Ｌ", "L", "ｍ", "m", "Ｍ", "M", "ｎ", "n", "Ｎ", "N", "ｏ", "o", "Ｏ", "O", "ｐ", "p", "Ｐ", "P", "ｑ", "q", "Ｑ", "Q", "ｒ", "r", "Ｒ", "R", "ｓ", "s", "Ｓ", "S", "ｔ", "t", "Ｔ", "T", "ｕ", "u", "Ｕ", "U", "ｖ", "v", "Ｖ", "V", "ｗ", "w", "Ｗ", "W", "ｘ", "x", "Ｘ", "X", "ｙ", "y", "Ｙ", "Y", "ｚ", "z", "Ｚ", "Z", "＠", "@", "ー", "-", "?", "-", "＿", "_", "。", ".", "．", ".", "・", ".", "　", " " ) ;
	$chkStr = is_string( $strx ) ;
	if ( !$chkStr ) {
		$strx = (string)$strx ;
	}
	for ( $cnt=0; $cnt < count( $bsStrList ); $cnt+=2 ) {
		$strx = str_replace( $bsStrList[$cnt], $bsStrList[$cnt+1], $strx );
	}
	return($strx);
}
// ===========================================================
// メールアドレスとして適切な文字のみに変換する
// $strx：変換する文字列（E-Mail）
// 戻り値：変換された文字列（E-Mail）
//-----------------------------------------------------------
function STR_emailCnvert( $strx )
{
	// オリジナルのファンクションではなく、PHPの標準コマンドに変更する
	//$strx = STR_ZenToHan( $strx ) ;
	// 記号を含む英数字の全角→半角の変換は、エスケープが必要な文字まで変換するので、使用しない
	//$strx = mb_convert_kana( $strx, "as" ) ; // a：全角英数字（記号含む）→半角英数字（記号含む） / s：全角スペース→半角スペース
	// 2011.03.30 お客様のご指示により、全角→半角の変換から、英数字は省く
	//$strx = mb_convert_kana( $strx, "rns" ) ; // r：全角英字→半角英字 / n：全角数字→半角数字 / s：全角スペース→半角スペース
	$strx = mb_convert_kana( $strx, "s" ) ; // s：全角スペース→半角スペース

	$strx = str_replace( "＠", "@", $strx ); // 全角→半角
	$strx = str_replace( "ー", "-", $strx ); // 全角→半角
	$strx = str_replace( "－", "-", $strx ); // 全角→半角
	$strx = str_replace( "＿", "_", $strx ); // 全角→半角
	//$strx = str_replace( "・", ".", $strx ); // 全角→半角
	$strx = str_replace( "。", ".", $strx ); // 全角→半角
	$strx = str_replace( "．", ".", $strx ); // 全角→半角

	$strx= str_replace("&quot;","",$strx); // htmlspecialchars変換文字の削除「"」
	$strx = str_replace( "&gt;", "", $strx); // htmlspecialchars変換文字の削除「>」
	$strx = str_replace( "&lt;", "", $strx); // htmlspecialchars変換文字の削除「<」
	$strx = str_replace( "&#039;", "", $strx); // htmlspecialchars変換文字の削除「'」
	$strx = str_replace( "&amp;", "", $strx); // htmlspecialchars変換文字の削除「&」

	$strx = str_replace( " ", "", $strx ); // 半角スペースの削除
	$strx = str_replace( "\\", "", $strx ); // 改行（\r）の削除
	$strx = str_replace( "\r", "", $strx ); // 改行（\r）の削除
	$strx = str_replace( "\n", "", $strx ); // 改行（\n）の削除
	$strx = str_replace( "\t", "", $strx ); // タブの削除

	return($strx);
}
// ===========================================================
// 日本語対応文字列チェック
// $chkstr：判断基準、もしくは判断対象となる文字列
// $str：判定する文字列
// 戻り値：0：OK／-1：NG
//-----------------------------------------------------------
function STR_StringCheck( $chkstr, $str )
{
	$phpV = (int)substr( PHP_VERSION, 0, 1 ) ;

	switch ( $chkstr ) {
		case "NUMINT" :
			$okstr = sprintf( "0123456789" ) ;
			break ;
		case "NUMBER" :
			$okstr = sprintf( "0123456789." ) ;
			break ;
		case "MAIL" :
			$okstr = sprintf( "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_.@" ) ;
			break ;
		case "ID" :
		case "PASSWD" :
			$okstr = sprintf( "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_.@+()[]!#=^" ) ;
			break ;
		case "ABC-XYZ" :
			$okstr = sprintf( "ABCDEFGHIJKLMNOPQRSTUVWXYZ" ) ;
			break ;
		case "abc-ABC" :
			$okstr = sprintf( "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" ) ;
			break ;
		case "ABC_NUMBER" :
			$okstr = sprintf( "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_" ) ;
			break ;
		case "ZIP" :
		case "TEL" :
			$okstr = sprintf( "0123456789-" ) ;
			break ;
		case "KATAKANA" :
			$okstr = sprintf( "ァアィイゥウェエォオカガキギクグケゲコゴサザシジスズセゼソゾタダチヂッツヅテデトドナニヌネノハバパヒビピフブプヘベペホボポマミムメモャヤュユョヨラリルレロヮワヰヱヲンヴヵヶー" ) ;
			break ;
		case "HIRAGANA" :
			$okstr = sprintf( "ぁあぃいぅうぇえぉおかがきぎくぐけげこごさざしじすずせぜそぞただちぢつづてでとどなにぬねのはばぱひびぴふぶぷへべぺほぼぽまみむめもゃやゅゆょよらりるれろゎわゐゑをんー" ) ;
			break ;
		default :
			$okstr = $chkstr ;
	}

	/***
	$inCode = mb_internal_encoding() ;
	if ( $inCode != "SJIS" ) {
		$str = mb_convert_encoding($str,"SJIS", $inCode);
		$okstr = mb_convert_encoding($okstr,"SJIS",$inCode);
	}
	***/

	$retVal = 0 ;
	$maxLenght = strlen($str);
	$chkLength = strlen($okstr);
	for ( $strCnt=0 ; $strCnt<$maxLenght ; $strCnt++ ){
		$strChk = ord( substr( $str, $strCnt, 1 ) ) ;
		$len = 1 ;
		if ( ( $strChk>=0x80 ) && ( $strChk<=0x9F ) ) $len = 2 ;
		if ( ( $strChk>=0xE0 ) && ( $strChk<=0xFF ) ) $len = 2 ;

		for ( $okCnt=0 ; $okCnt<$chkLength ; $okCnt++ ){
			$okChk = ord( substr( $okstr, $strCnt, 1 ) ) ;
			$oklen = 1 ;
			if ( ( $okChk>=0x80 ) && ( $okChk<=0x9F ) ) $oklen=2;
			if ( ( $okChk>=0xE0 ) && ( $okChk<=0xFF ) ) $oklen=2;

			if ( substr( $str, $strCnt, $len ) == substr( $okstr, $okCnt, $oklen ) ) break;
			if ( $oklen==2 ) $okCnt++;
		}
		if ( $len == 2 ) $strCnt++;

		if ( $okCnt == $chkLength ) {
			$retVal = -1 ;
			break ;
		}
	}
	return( $retVal );
}
/* ---------------------------------------------------------------------------------------
 getSecureKey
*/
function getSecureKey($length) {
  $length_bytes = (intval($length)+1)/2 ; // 生成数が Bytes 単位なので +1 して /2 する
  $bytes = openssl_random_pseudo_bytes( $length_bytes );
  return substr(bin2hex($bytes), 0, $length );
}

/**
 * 認証用の認証コードを生成
 * @param  String $login_id
 * @return String AuthCode
 */
function createAuthCode($login_id) {
	return sha1($login_id . COM_getRandomKey(12));
}


// ===========================================================
// 日本語対応文字列チェック
// $chkstr：判断基準、もしくは判断対象となる文字列
// $str：判定する文字列
// 戻り値：0：OK／-1：NG
//-----------------------------------------------------------

// ===========================================================
/**
 * STR_wordFormatter
 * 前後のホワイトスペースを削り、全角の英数文字を半角にする
 *
 *  @param  String $word : 文字列
 *  @return String
 */
function STR_wordFormatter($word) {
  if (!isset($word)) $word = "";
  $word = STR_ZenToHan($word);
  $word = trim($word);
  return $word;
}

// ===========================================================
/**
 * STR_passwordValidator
 * パスワードの形式チェック
 *
 * @param  String $pw : パスワードの文字列
 * @return Boolean
 */
function STR_passwordValidator($pw) {
  // 8文字以上 32文字以内
  if (strlen($pw) < 8) {
    return false;
  } elseif (strlen($pw) > 32) {
    return false;
  }
  // 英数半角アルファベット、ハイフン, アンダースコア のみ
  $result = preg_match("/[0-9A-Za-z_\-]+/", $pw, $match1);
  if ($result !== 1 || $match1[0] !== $pw) {
    return false;
  }
  return true;
}

/**
* 配列データ出力テキスト変換
* @param array $input_array 対象配列データ
* @param string $delimita 区切り文字
* @param string $encode 出力文字コード
* @return array
*/
function conv_array_to_text($input_array, $delimita, $encode='UTF-8'){
    $textdata  = "";
	if( $input_array==null || count($input_array)<1){
		return null;
	}
    $linedata = "";
	$headerText = "";
	$strdata = "";
	$delimita=",";
	for($i=0;$i<count($input_array);$i++){
		foreach ($input_array[$i] as $key => $val){
			$strdata = $val;
			$strdata = str_replace(array("\r\n","\r"), "\n", $strdata);
			$strdata = str_replace("\n", '\n', $strdata);
			$strdata = str_replace("\t", '\t', $strdata);
			if($encode!="UTF-8") {
                $strdata = mb_convert_encoding($strdata, $encode, 'UTF-8');
                $key = mb_convert_encoding($key, $encode, 'UTF-8');
            }
			$linedata =$linedata.$strdata.$delimita;
			if($i==0) $headerText = $headerText.$key.$delimita;
		}
		$linedata = trim($linedata,$delimita);
		if($i < count($input_array)-1){
			$linedata =$linedata."\r\n";
		}
	}
	$textdata = $headerText."\r\n".$linedata;
	return $textdata;
}

?>
