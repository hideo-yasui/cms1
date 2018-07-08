<?php
require_once($_SERVER["DOCUMENT_ROOT"] . '/app/login.php');
require_once($_SERVER["DOCUMENT_ROOT"] . '/controller/api/v1/app.php');

/**
* API: Pro_result
*/
class Oem extends App
{
    protected $auth_actions = array('');

    // API Endpoints
    /**
    * @param  array $params
    * @return json responce
    */
    public function login($params)
    {
        if ($this->request->isPost()){
            return $this->login_token_update($_POST);
        }
    }

    /**
    * @param  array $params
    * @return json responce
    */
    public function results($params)
    {
        @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "results:start") ;

        if (!isset($this->token_user)) {
            return $this->forbiddenResponce();
        }
        if ($this->request->isGet()) {
            if(!isset($_GET["kit_id"]) || !is_numeric($_GET["kit_id"])){
                return $this->badRequestResponce("not kit_id");
            }
            $result = $this->getResponce();
            $kit_id_str = $_GET["kit_id"];
            $results_res = $this->get_results($this->token_user["hospital_id"], $kit_id_str);
            if($results_res["status"] !== "success" || !isset($results_res["data"]) || count($results_res["data"])<1){
                return $this->notFoundResponce($results_res["description"]);
            }
            $result["data"] = $this->get_result_responce($results_res["data"]["result"],
                                                           $results_res["data"]["average"],
                                                           $results_res["data"]["bacterias"],
                                                           $results_res["data"]["related_bacterias"]);
           @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "results:end") ;
            return $result;
        }
    }


  // Services ==================================================================

    /**
    * 応答用検査結果データ取得
    * @access private
    * @param int $average_id
    * @return json responce
    */
    private function get_result_responce($result_data, $average_data, $bacterias, $related_bacterias)
    {
        //応答データの仕様に応じて、格納状態を調整する
        $result_item = array();
        foreach (array_merge($result_data, $average_data) as $key => $value) {
            if($key==="result_id") continue;
            $keys = explode("__", $key);
            if(!isset($result_item[$keys[0]])){
                $result_item[$keys[0]] = array();
            }
            if(!isset($result_item[$keys[0]][$keys[1]])){
                $result_item[$keys[0]][$keys[1]] = array();
            }
            if(count($keys)>2 && !isset($result_item[$keys[0]][$keys[1]][$keys[2]])){
                $result_item[$keys[0]][$keys[1]][$keys[2]] = array();
            }
            if(count($keys)===2){
                $result_item[$keys[0]][$keys[1]] = $value;
            }
            if(count($keys)===3){
                $result_item[$keys[0]][$keys[1]][$keys[2]] = $value;
                if ($keys[2]==="segment") {
                    unset($result_item[$keys[0]][$keys[1]][$keys[2]]);
                    $json_array = json_decode(htmlspecialchars_decode($value));
                    if ($keys[0]==="histogram") {
                        foreach ($json_array as $k => $v) {
                            if ($k+1 > 6) continue;
                            $result_item[$keys[0]][$keys[1]][sprintf("segment%s", $k+1)] = $v->val;
                        }
                        foreach ($json_array as $k => $v) {
                            $result_item[$keys[0]][$keys[1]][sprintf("segment_ratio%s", $k+1)] = $v->ratio;
                        }
                    } else if ($keys[0]==="criteria") {
                        foreach ($json_array as $k => $v) {
                            $result_item[$keys[0]][$keys[1]][sprintf("segment%s", $k+1)] = $v;
                        }
                        foreach ($json_array as $k => $v) {
                            $result_item[$keys[0]][$keys[1]][sprintf("segment_ratio%s", $k+1)] = $v;
                        }
                    }
                }
            }
        }
        $result_item["bacterias"] = array();
        foreach ($bacterias as $row => $bacteria) {
            $result_item["bacterias"][] = array(
                "name" => $bacteria["name"],
                "ratio" => $bacteria["ratio"],
                "average" => $bacteria["average"]
            );
        }
        $result_item["related_bacterias"] = array();
        foreach ($related_bacterias as $row => $related_bacteria) {
            $keys = explode("_",  $related_bacteria["data_type"]);
            $type = $keys[0];
            $attr = $keys[1];
            if(!isset($result_item["related_bacterias"][$type])){
                $result_item["related_bacterias"][$type] = array();
            }
            if(!isset($result_item["related_bacterias"][$type][$attr])){
                $result_item["related_bacterias"][$type][$attr] = array();
            }
            $result_item["related_bacterias"][$type][$attr][] = array(
                "name" => $related_bacteria["name"],
                "name_jpn" => $related_bacteria["name_jpn"],
                "name_eng" => $related_bacteria["name_eng"],
                "ratio" => $related_bacteria["ratio"],
                "average" => $related_bacteria["average"],
                "review" => $related_bacteria["review"],
                "description" => $related_bacteria["description"]
            );
        }
        return $result_item;
    }
    /**
    * 検査結果データの取得
    * @access private
    * @param int $hospital_id
    * @param string $kit_id_str
    * @return json responce
    */
    private function get_results($hospital_id, $kit_id_str)
    {
        $result = $this->badRequestResponce();
        if(!is_numeric($hospital_id)){
            //医療機関IDの指定がない
            $result["description"] = "not hospital_id";
            return $result;
        }
        if(!is_numeric($kit_id_str)){
            //キットIDの指定がない
            $result["description"] = "not kit_id";
            return $result;
        }
        $__param = array(
            "kit_id_str" => $kit_id_str,
            "hospital_id" => $hospital_id
        );
        @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "get_results:start") ;
        $results_res = execConfigQuery($this->db, 'get_results', $__param);
        if($results_res["status"] !== "success" || !isset($results_res["data"]) || count($results_res["data"])<1){
            $result["description"] = "kit not found";
            return $result;
        }
        if(!is_numeric($results_res["data"][0]["result_id"])){
            $result["description"] = "waiting for result data";
            return $result;
        }
        @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "get_average: success") ;
        $average_res = execConfigQuery($this->db, 'get_average', $__param);
        @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "get_results: success") ;
        $bacterias_res = execConfigQuery($this->db, 'get_bacterias', $__param);
        @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "get_bacterias: success") ;
        $related_bacterias_res = execConfigQuery($this->db, 'get_related_bacterias', $__param);
        @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "get_related_bacterias: success") ;

        $result = $this->getResponce();
        $result["data"] = array("result" => $results_res["data"][0],
                                "average" => $average_res["data"][0],
                                "bacterias" => $bacterias_res["data"],
                                "related_bacterias" => $related_bacterias_res["data"]);
        @TXT_LOG("api", $_SERVER['SCRIPT_NAME'], basename(__FILE__),__LINE__, "get_results:end") ;
        return $result;
    }

}
