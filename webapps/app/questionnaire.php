<?php
include_once($_SERVER["DOCUMENT_ROOT"] . '/app/commonView.php');
/**
 * アンケート回答ページを表示する
 * @param dbconnect $DBCON
 **/
function showAddonPage($DBCON){
	$systemCode  = $GLOBALS['gEnvList']['system'];;
	$contents = "questionnaire_detail";
	$data = array();

	// validate
	if (!isValidKitId($DBCON)) {
		showPageForbidden();
		exitProc($DBCON);
	}

	/**
	* POST
	* リクエストの場合の処理
	*/
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	    //回答ロジックが入ります
	    $csrfCheck = isCorrectToken();
	    if($csrfCheck){
	      if( isset($_POST["page"]) && isset($_POST["display_order"]) && isset($_POST["kit_id_str"])){
	     	  @INS_SQL_LOG("auth", "answer del start", $contents);
		 	  delAnswer($DBCON,$_POST);
	     	  @INS_SQL_LOG("auth", "answer del end", $contents);
	     	  @INS_SQL_LOG("auth", "answer ins start", $contents);
	    	  insAnswer($DBCON,$_POST);
	     	  @INS_SQL_LOG("auth", "answer ins end", $contents);
	     	  //サンプル採取日を登録した際のみt_kitsのsampling_dateデータを更新する
	     	  if($_POST["page"] == "10" && $_POST["answer_display_order"] == "1"){
	         	  @INS_SQL_LOG("auth", "t_kits.sampling_date upd start", $contents);
			 	  updTkitsSamplingDate($DBCON,$_POST);
			 	  @INS_SQL_LOG("auth", "t_kits.sampling_date upd end", $contents);
	     	  }
	      }
	    }else{
	      @TXT_LOG("token", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__,"post=".$_POST["token"]."/session=".$_SESSION["token"]) ;
	    }

	  if($_POST["surveys_theme_id"] > 3 && $_POST["surveys_theme_id"] < 15 ){
	    //次ページへのリダイレクトを行います
	    if($_POST["display_order"]==1){
	        header('Location: /kitdetail?kit='.$_POST["kit_id_str"]."#enquete");
	    }else{
	        header('Location: /questionnaire?kit='.$_POST["kit_id_str"].'&surveys_theme_id='.$_POST["surveys_theme_id"].'&display_order='.$_POST["display_order"]);
	    }
	  }else{
	    //kitの詳細へリダイレクトします
	    header('Location: /kitdetail?kit='.$_POST["kit_id_str"]);
	  }
	  exit;
	}

	/**
	* GET
	* リクエストの場合の処理
	*/
	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	//  var_dump($_GET);
	//  var_dump($_SESSION);
	    if(!isset($_GET["kit"]) || $_GET["kit"] == ""){
		  //kit詳細へ戻す
	      header('Location: /kitdetail');
	      exit;
	    }

	    if(!isset($_GET["surveys_theme_id"]) || $_GET["surveys_theme_id"] == ""){
	      //リダイレクト
	      header('Location: /questionnaire?kit='.$_GET["kit"].'&surveys_theme_id=4&display_order=1');
	      exit;
	    }
	    if(!isset($_GET["display_order"]) || $_GET["display_order"] == ""){
	      //リダイレクト
	      header('Location: /questionnaire?kit='.$_GET["kit"].'&surveys_theme_id='.$_GET["surveys_theme_id"].'&display_order=1');
	      exit;
	    }

		$values = getViewData($DBCON, $systemCode, $contents);
		$template = "";
		if($values["status"] =="success"){
			$viewData["data"] = $values["data"];
			$viewData["title"] = $values["title"];
			$viewData["kit_id_str"] = $_GET["kit"];
	        $viewData["surveys_theme_id"] = $_GET["surveys_theme_id"];
	      	$viewData["display_order"] = $_GET["display_order"];
			$contents = $values["contents"];
			$template = $values["template"];
			$systemCode = $values["system"];
		} else {
			showPageNotFound();
		}

		showView($DBCON, $systemCode, $contents, $template, $viewData);

	//	exit;
	}
}
/**
 * Check ownership of the kit by kit_id
 * @param  Void
 * @return Boolean
 */
function isValidKitId($DBCON) {
	$kit_id_str = 0;
	if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET["kit"])) {
		$kit_id_str = $_GET["kit"];
	}
	elseif ($_SERVER['REQUEST_METHOD'] == 'POST' &&isset($_POST["kit_id_str"])) {
		$kit_id_str = $_POST["kit_id_str"];
	}
	$user = execConfigQuery($DBCON, 'get_user_by_kit_id', array('kit_id_str' => $kit_id_str));
	if (isset($user['data'][0]['rid']) && $user['data'][0]['rid'] == $_SESSION['sUsrID']) {
		return true;
	}
	return false;
}

function delAnswer($DBCON,$data){
    $kit_id_str = $data["kit_id_str"];
    //まずdeleteから実行
    foreach ($data as $key => $value) {
      if($key == "kit_id_str"){
        $kit_id_str = $data["kit_id_str"];
      }else if($key == "page"){
        $survey_themes_id = $value;
      }else if($key == "answer_display_order"){
        $display_order = $value;
      }
    }
    execConfigQuery($DBCON, 'del_answer',  array( 'kit_id_str' => $kit_id_str , 'surveysThemeId' => $survey_themes_id , 'displayOrder' => $display_order ));
}

function insAnswer($DBCON,$data){
    $answer = array();
    foreach ($data as $key => $value) {
        switch ($key){
        case "user_rid":
        case "page":
        case "display_order":
        case "answer_display_order":
        case "kit_id_str":
        case "surveys_theme_id":
        case "token":
          break;
        default:
            $keys = explode("_", $key);
            $answer[$keys[0]."_".$keys[1]."_".$keys[2]][$keys[3]] = $value;
        }
    }

    foreach ($answer as $key_1 => $value_1) {
        $arykey = explode("_", $key_1);
        $jobs_surveys_questions_id = $arykey[0];
        $surveys_questions_offered_answer_id = "";
        $other_text = "";
        $text_answer = "";
        $numeric_answer = "";
        $time_answer = "";
        $date_answer = "";
        if(isset($answer[$key_1][1]) && $answer[$key_1][1] != ""){
          $surveys_questions_offered_answer_id = $answer[$key_1][1];
        }
        if(isset($answer[$key_1][2]) && $answer[$key_1][2] != ""){
          $other_text = $answer[$key_1][2];
        }
        if(isset($answer[$key_1][3]) && $answer[$key_1][3] != ""){
          $text_answer = $answer[$key_1][3];
        }
        if(isset($answer[$key_1][4]) && $answer[$key_1][4] != ""){
          $numeric_answer = $answer[$key_1][4];
        }
        if(isset($answer[$key_1][5]) && $answer[$key_1][5] != ""){
          $time_answer = $answer[$key_1][5];
        }
        if(isset($answer[$key_1][6]) && $answer[$key_1][6] != ""){
          $date_answer = $answer[$key_1][6];
        }
        $aryAnswer[] = array( $jobs_surveys_questions_id, $surveys_questions_offered_answer_id, $other_text , $text_answer , $numeric_answer , $time_answer , $date_answer );
    }
    execConfigQuery($DBCON, 'ins_answer',   array("answers"=>$aryAnswer));
}

function updTkitsSamplingDate($DBCON,$data){
    $kit_id_str = $data["kit_id_str"];
	if(empty($kit_id_str)) return false;
	//アンケートの採取日を取得
	$get_sampling_date = execConfigQuery($DBCON, 'get_survey_sampling_date',  array('kit_id_str' => $kit_id_str));
	if($get_sampling_date["status"] !== "success"){
		return false;
	}
	//採取日存在チェック
	if(count($get_sampling_date["data"]) < 1
		|| !isset($get_sampling_date["data"][0]["sampling_date_year"])
		|| !isset($get_sampling_date["data"][0]["sampling_date_month"])
		|| !isset($get_sampling_date["data"][0]["sampling_date_day"])){
		return false;
	}
	$sampling_date_year = str_replace("年", "",  $get_sampling_date["data"][0]["sampling_date_year"]);
	$sampling_date_month = str_replace("月", "",  $get_sampling_date["data"][0]["sampling_date_month"]);
	$sampling_date_day = str_replace("日", "",  $get_sampling_date["data"][0]["sampling_date_day"]);

	//採取日の値チェック
	if(!is_numeric($sampling_date_year)
		|| !is_numeric($sampling_date_month)
		|| !is_numeric($sampling_date_day)){
		return false;
	}
	$sampling_date = date('Y/m/d', strtotime($sampling_date_year.'-'.$sampling_date_month.'-'.$sampling_date_day));
	execConfigQuery($DBCON, 'kit_sampling_date_upd',  array('kit_id_str' => $kit_id_str, 'sampling_date' => $sampling_date));
	return true;
}
