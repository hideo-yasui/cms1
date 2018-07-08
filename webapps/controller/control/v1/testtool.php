<?php
require_once($_SERVER["DOCUMENT_ROOT"] . '/controller/admin/v1/admin.php');

/**
* API: Testtool
*/
class Testtool extends Admin
{
    protected $auth_actions = array('');
    //いったん固定値にする（jenkins側の変数）
    protected $jenkins_url = 'ci.mykinso.com:8080/job/';
    protected $jenkins_user = 'cykadmin';
    protected $jenkins_token = '5defcf8f7b26f80aa7f5ff9190558df8';
    /**
    * POST : シナリオグループテストの実行
    * @param  array $params
    * @return json response
    */
    public function scenario_group_start($params)
    {
        if ($this->request->isPost()) {
            $result = $this->start_scenario_group_job($params['scenario_group']);
            return $result;
        }
    }
    /**
    * POST : シナリオテストの実行
    * @param  array $params
    * @return json response
    */
    public function scenario_start($params)
    {
        if ($this->request->isPost()) {
            $result = $this->start_scenario_job($params['scenario']);
            return $result;
        }
    }
    /**
    * GET : シナリオグループ取得
    * @param  array $params
    * @return json response
    */
    public function scenario_group($params)
    {
        if ($this->request->isGet()) {
            $result = $this->get_scenario_group($params['scenario_group']);
        }
        return $result;
    }
    /**
    * POST : シナリオグループテスト登録
    * @param  array $params
    * @return json response
    */
    public function scenario_group_test($params)
    {
        if ($this->request->isPost()) {
            $result = $this->post_scenario_group_test($params['scenario_group']);
        }
        return $result;
    }

    /**
    * POST : シナリオ登録
    * @param  array $params
    * @return json response
    */
    public function scenario($params)
    {
        if ($this->request->isPost()) {
            $result = $this->post_scenario($params['scenario'], $_POST);
        }
        return $result;
    }
    /**
    * POST : シナリオテスト操作イベントの登録
    * @param  array $params
    * @return json response
    */
    public function scenario_event($params)
    {
        if ($this->request->isGet()) {
            $result = $this->get_scenario_event($params['scenario']);
        }
        if ($this->request->isPost()) {
            $result = $this->post_scenario_event($params['scenario'], $_POST);
        }
        return $result;
    }

    /**
    * GET : シナリオテスト実行履歴（直近）の取得
    * POST : シナリオテスト実行履歴の登録
    * @param  array $params
    * @return json response
    */
    public function scenario_test($params)
    {
        if ($this->request->isGet()) {
            $result = $this->get_scenario_test($params['scenario']);
        }
        if ($this->request->isPost()) {
            $result = $this->post_scenario_test($params['scenario'], $_POST);
        }
        return $result;
    }
    /**
    * GET : シナリオテスト実行履歴詳細の取得
    * POST : シナリオテスト実行履歴詳細の登録
    * @param  array $params
    * @return json response
    */
    public function scenario_test_detail($params)
    {
        if ($this->request->isGet()) {
          $result = $this->get_scenario_test_detail_by_id($params['test_id']);
        }
        if ($this->request->isPost()) {
          $result = $this->post_scenario_test_event($params['test_id'], $params['event_id']);
        }

        return $result;
    }
    /**
    * POST : シナリオテストステータス更新
    * @param  array $params
    * @return json response
    */
    public function scenario_test_status($params)
    {
        if ($this->request->isPost()) {
          $result = $this->post_scenario_test_status($params['test_id'], $_POST);
        }
        return $result;
    }

    /**
    * GET : シナリオテスト実行時キャプチャ取得
    * POST : シナリオテスト実行時キャプチャの登録
    * @param  array $params
    * @return json response
    */
    public function scenario_test_capture($params)
    {

        if ($this->request->isGet()) {
          $result = $this->get_scenario_test_capture_by_id($params['test_id'],$params['event_id']);
          return $result;
        }
        if ($this->request->isPost()) {
            $result = $this->post_scenario_test_capture($params['test_id'],$params['event_id'],$_POST);
        }

        return $result;
    }
    /**
    * POST : シナリオテスト実行差分キャプチャの更新
    * @param  array $params
    * @return json response
    */
    public function scenario_test_capture_diff($params)
    {
        if ($this->request->isPost()) {
            $result = $this->upd_scenario_test_diff($params['test_id'], $params['event_id'],$_POST);
        }
        return $result;
    }
    /**
    * POST : シナリオテスト実行エラーメッセージの登録
    * @param  array $params
    * @return json response
    */
    public function scenario_test_error($params)
    {
        if ($this->request->isPost()) {
            $result = $this->post_scenario_test_error($params['test_id'], $params['event_id'], $_POST);
        }
        return $result;
    }
    // Services ==================================================================
    /**
    * シナリオグループ定義の取得
    * @access private
    * @param string $scenario_group
    * @return json response
    */
    private function get_scenario_group($scenario_group)
    {
        $result = $this->badRequestResponce();
        if(empty($scenario_group)){
            $result["description"] = "scenario_group none";
            return $result;
        }
        $res = array();
        $result = execConfigQuery($this->db, 'get_scenario_by_group',array('scenario_group'=>$scenario_group));
        if($result["status"] === "success"){
            $res["scenarios"] = $result["data"];
            $param = execConfigQuery($this->db, 'get_scenario_group_param_by_group',array('scenario_group'=>$scenario_group));
            $res["params"] = $param["data"];
            $result["data"] = $res;
        }
        return $result;
    }
    /**
    * シナリオグループテストの登録
    * @access private
    * @param string $scenario_group
    * @return json response
    */
    private function post_scenario_group_test($scenario_group)
    {
        $result = $this->badRequestResponce();
        if(empty($scenario_group)){
            $result["description"] = "scenario_group none";
            return $result;
        }
        $result = execConfigQuery($this->db, 'post_scenario_group_test_by_name',array('scenario_group'=>$scenario_group));
        if($result["status"] === "success"){
            $result = $this->get_scenario_group_test($scenario_group);
        }
        return $result;
    }

    /**
    * シナリオイベント定義の取得
    * @access private
    * @param string $scenario_name
    * @return json response
    */
    private function get_scenario_event($scenario_name)
    {
        $result = $this->badRequestResponce();
        if(empty($scenario_name)){
            $result["description"] = "scenario_name none";
            return $result;
        }
        $result = execConfigQuery($this->db, 'get_scenario_event',array('scenario_name'=>$scenario_name));
        return $result;
    }
    /**
    * シナリオグループテスト直近の情報を取得
    * @access private
    * @param string $scenario_group
    * @return json response
    */
    private function get_scenario_group_test($scenario_group)
    {
        $result = $this->badRequestResponce();
        //validate
        if(empty($scenario_group)){
            $result["description"] = "scenario_group none";
            return $result;
        }
        $result = execConfigQuery($this->db, 'get_scenario_group_test_by_name',array('scenario_group'=>$scenario_group));
        if($result["status"] === "success" && !empty($result["data"]) && count($result["data"])==1){
            $result["data"] = $result["data"][0];
        }
        else {
            $result = $this->notExistResponce();
        }
        return $result;
    }
    /**
    * シナリオテスト直近の情報を取得
    * @access private
    * @param string $scenario_name
    * @return json response
    */
    private function get_scenario_test($scenario_name)
    {
        $result = $this->badRequestResponce();
        //validate
        if(empty($scenario_name)){
            $result["description"] = "scenario_name none";
            return $result;
        }
        $result = execConfigQuery($this->db, 'get_scenario_test_by_name',array('scenario_name'=>$scenario_name));
        if($result["status"] === "success" && !empty($result["data"]) && count($result["data"])==1){
            $result["data"] = $result["data"][0];
        }
        else {
            $result = $this->notExistResponce();
        }
        return $result;
    }
    /**
    * シナリオテスト実施詳細の取得（test_id指定）
    * @access private
    * @param int $test_id
    * @return json response
    */
    private function get_scenario_test_detail_by_id($test_id)
    {
        $result = $this->badRequestResponce();
        //validate
        if(!is_numeric($test_id)){
            $result["description"] = "test_id none";
            return $result;
        }
        $result = execConfigQuery($this->db, 'get_scenario_test_detail_by_id',array('test_id'=>$test_id));
        return $result;
    }
    /**
    * シナリオテスト実行履歴情報を取得
    * @access private
    * @param int $test_id
    * @return json response
    */
    private function get_scenario_test_capture_by_id($test_id, $event_id)
    {
        $result = $this->badRequestResponce();
        //validate
        if(!is_numeric($test_id)){
            $result["description"] = "test_id none";
            return $result;
        }
        $result = execConfigQuery($this->db, 'get_scenario_test_capture_by_id',array(
            'test_id'=>$test_id,
            'scenario_event_id' =>$event_id
        ));
        if($result["status"] === "success" && !empty($result["data"]) && count($result["data"])==1){
            $result["data"] = $result["data"][0];
        }
        else {
            $result = $this->notExistResponce();
        }
        return $result;
    }

    /**
    * シナリオ定義の登録
    * @access private
    * @param string $scenario_name
    * @param array $params
    * @return json response
    */
    private function post_scenario($scenario_name, Array $params)
    {
        $result = $this->badRequestResponce();
        //validate
        if(empty($scenario_name)){
            $result["description"] = "scenario_name none";
            return $result;
        }
        $result = execConfigQuery($this->db, 'post_scenario',array('scenario_name'=>$scenario_name));
        return $result;
    }

    /**
    * シナリオイベントの登録
    * @access private
    * @param string $scenario_name\
    * @param array $params
    * @return json response
    */
    private function post_scenario_event($scenario_name, Array $params)
    {
        $result = $this->badRequestResponce();
        //validate
        if(empty($scenario_name)){
            $result["description"] = "scenario_name none";
            return $result;
        }
        $__param = $this->set_scenario_event($scenario_name, $params);
        $result = execConfigQuery($this->db, 'post_scenario_event',$__param);
        return $result;
    }
    /**
    * シナリオテスト実施履歴の登録
    * @access private
    * @param string $scenario_name
    * @param array $params
    * @return json response
    */
    private function post_scenario_test($scenario_name, $params)
    {
        $result = $this->badRequestResponce();
        //validate
        if(empty($scenario_name)){
            $result["description"] = "scenario_name none";
            return $result;
        }
        $_params = array('scenario_name'=>$scenario_name, 'group_test_id' => 0);
        if(isset($params["group_test_id"])){
            if(!is_numeric($params["group_test_id"])){
                $result["description"] = "group_test_id not int";
                return $result;
            }
            $_params["group_test_id"] = $params["group_test_id"];
        }

        $result = execConfigQuery($this->db, 'post_scenario_test_by_name', $_params);
        if($result["status"] === "success"){
            $result = $this->get_scenario_test($scenario_name);
        }
        return $result;
    }
    /**
    * シナリオテスト実施エラーメッセージの登録
    * 対象：test_id && event_id
    * @access private
    * @param int $test_id
    * @param int $event_id
    * @param array $params
    * @return json response
    */
    private function post_scenario_test_error($test_id, $event_id, Array $params)
    {
        $result = $this->badRequestResponce();
        //validate
        if(!is_numeric($test_id)){
            $result["description"] = "test_id none";
            return $result;
        }
        if(!is_numeric($event_id)){
            $result["description"] = "scenario_event_id none";
            return $result;
        }
        if(!isset($params["error_message"]) || empty($params["error_message"])){
            $result["description"] = "error_message none";
            return $result;
        }
        $result = execConfigQuery($this->db, 'post_scenario_test_error',array(
            'test_id'=>$test_id,
            'scenario_event_id' =>$event_id,
            'error_message' =>$params["error_message"])
        );
        return $result;
    }
    /**
    * シナリオテストステータス更新
    * 対象：test_id
    * @access private
    * @param int $test_id
    * @param array $params
    * @return json response
    */
    private function post_scenario_test_status($test_id, Array $params)
    {
        $result = $this->badRequestResponce();
        //validate
        if(!is_numeric($test_id)){
            $result["description"] = "test_id none";
            return $result;
        }
        if(!isset($params["status"]) || !is_numeric($params["status"])){
            $result["description"] = "status none";
            return $result;
        }
        $result = execConfigQuery($this->db, 'post_scenario_test_status',array(
            'test_id'=>$test_id,
            'status' =>$params["status"]
        ));
        return $result;
    }
    /**
    * シナリオテスト実施履歴の登録
    * 対象：test_id && event_id
    * @access private
    * @param int $test_id
    * @param int $event_id
    * @return json response
    */
    private function post_scenario_test_event($test_id, $event_id)
    {

        $result = $this->badRequestResponce();
        //validate
        if(!is_numeric($test_id)){
            $result["description"] = "test_id none";
            return $result;
        }
        if(!is_numeric($event_id)){
            $result["description"] = "scenario_event_id none";
            return $result;
        }
        $result = execConfigQuery($this->db, 'post_scenario_test_event',array(
            'test_id'=>$test_id,
            'scenario_event_id' =>$event_id
        ));
        return $result;
    }
    /**
    * シナリオテスト実施キャプチャの更新
    * 対象：test_id && event_id
    * @access private
    * @param int $test_id
    * @param int $event_id
    * @param array $params
    * @return json response
    */
    private function post_scenario_test_capture($test_id, $event_id, Array $params)
    {

        $result = $this->badRequestResponce();
        //validate
        if(!is_numeric($test_id)){
            $result["description"] = "test_id none";
            return $result;
        }
        if(!is_numeric($event_id)){
            $result["description"] = "scenario_event_id none";
            return $result;
        }
        if(!isset($params["capture"]) || empty($params["capture"])){
            $result["description"] = "capture none";
            return $result;
        }
        $result = execConfigQuery($this->db, 'post_scenario_test_capture',array(
            'test_id'=>$test_id,
            'scenario_event_id' =>$event_id,
            'capture' =>$params["capture"]
        ));
        return $result;
    }
    /**
    * シナリオテスト実施キャプチャ・前回キャプチャ差分の登録
    * 対象：シナリオ名-イベントID-キャプチャID
    * @access private
    * @param int $test_id
    * @param int $event_id
    * @param array $params
    * @return json response
    */
    private function upd_scenario_test_diff($test_id, $event_id, $params)
    {
        $result = $this->badRequestResponce();
        //validate
        if(!is_numeric($test_id)){
            $result["description"] = "test_id none";
            return $result;
        }
        if(!is_numeric($event_id)){
            $result["description"] = "scenario_event_id none";
            return $result;
        }
        if(!isset($params["diff_capture"]) || empty($params["diff_capture"])){
            $result["description"] = "diff_capture none";
            return $result;
        }
        if(!isset($params["diff_capture_val"])){
            $result["description"] = "diff_capture_val none";
            return $result;
        }
        $result = execConfigQuery($this->db, 'upd_scenario_test_diff',array(
            'test_id'=>$test_id,
            'scenario_event_id' =>$event_id,
            'diff_capture_val' =>$params["diff_capture_val"],
            'diff_capture' =>$params["diff_capture"])
        );
        return $result;
    }

    /**
    * シナリオグループテスト実行
    * @access private
    * @param string $scenario_name
    * @return json response
    */
    private function start_scenario_group_job($scenario_group)
    {
        $result = $this->badRequestResponce();
        if(empty($scenario_group)){
            $result["description"] = "scenario_group none";
            return $result;
        }
        return $this->exec_jenkins_job('scenario_group_test', array(
            'scenario_group' => $scenario_group,
            'api_domain' => $_SERVER['HTTP_HOST']
        ));
    }
    /**
    * シナリオテスト実行
    * @access private
    * @param string $scenario_name
    * @return json response
    */
    private function start_scenario_job($scenario_name)
    {
        $result = $this->badRequestResponce();
        if(empty($scenario_name)){
            $result["description"] = "scenario none";
            return $result;
        }
        return $this->exec_jenkins_job('scenario_test', array(
            'scenario' => $scenario_name,
            'api_domain' => $_SERVER['HTTP_HOST']
        ));
    }
    /**
    * jenkins ジョブ実行
    * @access private
    * @param string $job_name
    * @param array $params
    * @return json response
    */
    private function exec_jenkins_job($job_name, $params)
    {
        $result = $this->badRequestResponce();
        //validate
        if(empty($job_name)){
            $result["description"] = "job none";
            return $result;
        }
        $url = 'http://'.$this->jenkins_user.':'.$this->jenkins_token.'@';
        $url .= $this->jenkins_url.$job_name.'/buildWithParameters';
        $_param = "";
        foreach ($params as $key => $value) {
            $_param .= $key.'='.$value.'&';
        }
        $url .= '?'.trim($_param, '&');
        $options = array(
            'http'=>array(
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n"
                . "User-Agent: php.scenario_start"
            )
        );
        $context = stream_context_create($options);
        //jenkinsのjob実行用のリクエスト発行
        $data = file_get_contents($url, false , $context);
        if($data===false){
            //実行失敗
            return $result;
        }
        //成功
        $result = $this->getResponce("sccess", $url, $data);
        return $result;
    }
    /**
    * イベントデータ設定
    * @access private
    * @param string $scenario_name
    * @param array $params
    * @return json response
    */
    private function set_scenario_event($scenario_name, Array $params)
    {
        $__param = array();
        //validate
        if(empty($scenario_name)){
            return $params;
        }
        $__param['scenario_name'] = $scenario_name;
        $columns = array("key", "attribute", "capture", "wait", "value", "comment", "event");
        $option = '';
        foreach ($params as $key => $value) {
            $find = false;
            for($t=0;$t<count($columns);$t++){
                if($key === $columns[$t]){
                    $find=true;
                    break;
                }
            }
            if($find===true){
                switch($key){
                    case "name" :
                        $__param["_name"] = $params[$key];
                        break;
                    case "comment" :
                        $__param["name"] = $params[$key];
                        break;
                    default:
                        $__param[$key] = $params[$key];
                }
                continue;
            }
            $option .= '"'+$key+'": "'+$params[$key];+'",';
        }
        $__param["option"] = trim($option,',');
        return $__param;
    }
}
