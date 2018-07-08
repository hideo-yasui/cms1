<?php
require_once($_SERVER["DOCUMENT_ROOT"] . '/app/login.php');
require_once($_SERVER["DOCUMENT_ROOT"] . '/controller/api/v1/app.php');

/**
 * コーディング規約（Coach API 開発のみに適用）
 * ① public関数は、APIエンドポイントに対応する
 * ② 具体的な処理は、 private関数側で記述する
 */

/**
* API: coach
*/
class Coach extends App
{
  protected $auth_actions = array(
    'records',
    'records_feces',
    'records_feces_delete',
    'records_meals',
    'records_meals_delete',
    'records_conditions',
    'records_conditions_delete',
    'records_memos',
    'records_memos_delete',
    'records_steps',
    'records_steps_delete',
    'records_checks',
    'records_items',
    'logout',
  );



  // View
  /**
  * @param  array $params
  * @return json response
  */
  public function auth_email($params)
  {
    if ($this->request->isGet()){
      view('coach/index.php');
      exitProc();
    }

    if ($this->request->isPOST()){

      // get posted params
      $args = array(
        'email'      => $this->request->getPost('email'),
        'kit_id_str' => $this->request->getPost('kit_id'),
        'birth_y'    => $this->request->getPost('birth_y'),
        'birth_m'    => $this->request->getPost('birth_m'),
        'birth_d'    => $this->request->getPost('birth_d'),
      );

      // init
      $res = $this->getResponce();

      // check user exist by email
      $user = execConfigQuery($this->db, 'get_user_by_email_for_register', array('email' => $args['email']));
      if (isset($user['data']) && count($user['data']) == 1) {
        $user = $user['data'][0];

        // authenticating user
        if ($user['status'] == 0) {
          $tmp = $this->preRegister($args['email'], $user['user_id']);
          $this->sendJSONResponse($res);

        // authenticated user
        } else {
          $res['status']      = 'failed';
          $res['message']     = 'E_ALREADY_SAVED';
          $res['description'] = 'THIS ADRESS ALREADY EXISTS';
          $this->sendJSONResponse($res);
        }
      }

      // check user exist by kit_id and birthday
      if ($args['kit_id_str']) {
        $user = execConfigQuery($this->db, 'get_user_by_kit_id_and_birthday', $args);
        if (isset($user['data']) && count($user['data']) == 1) {
          $user = $user['data'][0];

          // authenticating pro user
          if ($user['status'] == 0) {
            $this->preRegister($args['email'], $user['user_id'], true);
            $this->sendJSONResponse($res);

          // authenticated pro user
          } else {
            $res['status']      = 'failed';
            $res['message']     = 'E_ALREADY_SAVED';
            $res['description'] = 'THIS ADRESS ALREADY EXISTS';
            $this->sendJSONResponse($res);
          }
        } else {
          $res['status']      = 'failed';
          $res['message']     = 'E_PERMISSION_ERROR';
          $res['description'] = 'INVALID PARAMS';
          $this->sendJSONResponse($res);
        }
      }

      // new user
      $this->preRegister($args['email']);
      $this->sendJSONResponse($res);
    }
  }

  private function preRegister($email, $user_id = null, $isPro = false) {
    // authコードの発行
    $auth_code = createAuthCode($email);
    $login_ip  = $_SERVER["REMOTE_ADDR"];
    // t_users への メアド登録
    if ($user_id) {
      // update
      $res = execConfigQuery($this->db, 'user_pre_upd_by_user_id', array(
        'user_id'        => $user_id,
        'email'          => $email,
        'auth_code'      => $auth_code,
        'login_ip'       => $_SERVER["REMOTE_ADDR"]
      ));
    } else {
      $res = execConfigQuery($this->db, 'user_pre_ins', array(
        'email'          => $email,
        'auth_code'      => $auth_code,
        'login_ip'       => $_SERVER["REMOTE_ADDR"],
      ));
    }

    // send pre register mail
    if ($isPro) {
      $res = execConfigQuery($this->db, 'api_send_mail_pre_register_for_pro_user', array(
        'email'    => $email,
        'auth_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/v1/coach/auth/email?token=' . $auth_code . '&email=' . $email,
      ));
    } else {
      $res = execConfigQuery($this->db, 'api_send_mail_pre_register', array(
        'email'    => $email,
        'auth_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/v1/coach/auth/email?token=' . $auth_code . '&email=' . $email,
      ));
    }

    execConfigMail($this->db);
  }

  /**
  * @param  array $params
  * @return json response
  */
  public function check_update($params)
  {
    if ($this->request->isGet()){

      // init
      $res = $this->getResponce();
      $res['data'] = array(
        'is_force_update' => false,
        'is_available_update' => false,
        'message'             => '' // ユーザに表示するメッセージ
      );

      // get current version info
      $tmp = execConfigQuery($this->db, 'get_coach_app_force_update_ver');
      $forceUpdateVer = isset($tmp['data'][0]['code_remark']) ? $tmp['data'][0]['code_remark'] : null;
      $tmp = execConfigQuery($this->db, 'get_coach_latest_app_ver');
      $availableVer = isset($tmp['data'][0]['code_remark']) ? $tmp['data'][0]['code_remark'] : null;
      $clientVer = $this->request->getGet('version');

      // validate
      if (!$forceUpdateVer || !$availableVer || !$clientVer) {
        $this->sendJSONResponse($this->badRequestResponce());
      }

      // check version for update
      // version format is [Major].[Minor].[Patch]
      $cV = explode('.', $clientVer);
      $fV = explode('.', $forceUpdateVer);
      $aV = explode('.', $availableVer);
      // check force update
      if (($cV[0] < $fV[0]) ||
          ($cV[0] == $fV[0] && $cV[1] < $fV[1]) ||
          ($cV[0] == $fV[0] && $cV[1] == $fV[1] && $cV[2] < $fV[2])) {
        $res['data']['is_force_update'] = true;
        $res['data']['is_available_update'] = true;
        $res['data']['message'] = 'アプリが新しくなりました。ストアでアプリを更新して下さい。';
      }
      // check available update
      elseif (($cV[0] < $aV[0]) ||
              ($cV[0] == $aV[0] && $cV[1] < $aV[1]) ||
              ($cV[0] == $aV[0] && $cV[1] == $aV[1] && $cV[2] < $aV[2])) {
        $res['data']['is_available_update'] = true;
        $res['data']['message'] = 'アプリが新しくなりました。ストアでアプリを更新して下さい。';
      }

      return $res;
    }
  }

  /**
  * @param  array $params
  * @return json response
  */
  public function teachers_advices($params)
  {
    if ($this->request->isGet()){
      // init
      $res = $this->getResponce();
      $res['data'] = array(
        "食事をしたら、その後のお腹の調子も観察しよう！",
        "食事とお腹の調子の関係性を観察してみてね。",
        "まずは2週間、食事を記録してみよう！",
        "記録は順調かな？たまに記録を忘れちゃっても大丈夫！また再開してね。",
        "最近のお腹の調子はどう？",
        "朝起きたあとは、お腹のすき具合や疲れがとれいてるかもチェックしてみよう！",
        "君のお腹の調子が良くなると先生も嬉しいなー！",
        "脳を持たない動物はいても、腸を持たない動物はいないんだ！僕達クラゲもそんな動物の一種なんだー♪",
        "腸は脳からの指令がなくても独立して活動できるんだ。だから『腸は第2の脳』とも呼ばれているよ。",
        "腸が整えるには、自律神経もとても大切！規則的な食事、運動、睡眠を心がけよう。",
        "ストレスは腸の大敵！ストイックになりすぎずに、たまには息抜きもしてね。",
        "トイレにいったら必ず振り返って観察してみよう。見返り美人は腸美人♪",
        "僕達クラゲは、脳がないからストレスフリー！口と腸があるから生きていけるんだ！",
        "ストレスを感じた時は、深呼吸して肩の力を抜いてみよう！",
        "お腹の調子を整えるためには、ストレスを溜めすぎないことも大切！",
        "腸が元気になると、睡眠の質も良くなるかも…!?",
        "お腹の調子を良くするためには、頑張りすぎないことも大切だよ！",
      );
      return $res;
    }
  }


  /**
  * @param  array $params
  * @return json response
  */
  public function login($params)
  {
    if ($this->request->isPost()){
      return $this->login_token_update(array(
        'email' => $this->request->getPost('email'),
        'password' => $this->request->getPost('password'),
        'device_id' => $this->request->getPost('device_id'),
      ));
    }
  }

  /**
  * @param  array $params
  * @return json response
  */
  public function users($params)
  {
    if ($this->request->isGet()) {
      // validate
      if (!isset($this->token_user)) {
        return $this->forbiddenResponce();
      }
      $result = $this->get_user_by_token();
      $result['data'] = $this->appendLogsToUser($result['data']);
      return $result;
    }

    if ($this->request->isPost()) {

      // validate
      $args = array(
        'auth_code'  => $this->request->getPost('token'), // email authentificated code
        'device_id'  => $this->request->getPost('device_id'),
        'name_last'  => $this->request->getPost('name_last'),
        'name_first' => $this->request->getPost('name_first'),
        'kana_last'  => $this->request->getPost('kana_last'),
        'kana_first' => $this->request->getPost('kana_first'),
        'gender'     => $this->request->getPost('gender'),
        'birth_y'    => $this->request->getPost('birth_y'),
        'birth_m'    => $this->request->getPost('birth_m'),
        'birth_d'    => $this->request->getPost('birth_d'),
        'height'     => $this->request->getPost('height'),
        'weight'     => $this->request->getPost('weight'),
        'password'   => $this->request->getPost('password'),
      );

      // update
      if (isset($params['id'])) {
        if (!isset($this->token_user['user_id']) || $params['id'] != $this->token_user['user_id']) {
          return $this->forbiddenResponce();
        }
        return $this->updateUser($this->token_user['user_id'], $args);
      }
      // create
      else {
        return $this->updateUserByAuthCode($args);
      }
    }
  }

  /**
  * @param  array $params
  * @return json response
  */
  public function users_preregisters($params)
  {
    if ($this->request->isGet()) {
      // init
      $token = $this->request->getGet('token');
      if (empty($token)) {
        return $this->badRequestResponce('E_INVALID_TOKEN');
      }

      // get user by token
      $tmp = execConfigQuery($this->db, 'get_user_by_auth_code', array(
        'auth_code' => $token,
      ));
      if (count($tmp['data']) !== 1 || !isset($tmp['data'][0])) {
        return $this->badRequestResponce('E_INVALID_TOKEN');
      }

      $res = $this->getResponce();
      $u = $tmp['data'][0];
      $res['data'] = array(
        'name_last'  => !empty($u['name_last'])  ? $u['name_last']  : null,
        'name_first' => !empty($u['name_first']) ? $u['name_first'] : null,
        'kana_last'  => !empty($u['kana_last'])  ? $u['kana_last']  : null,
        'kana_first' => !empty($u['kana_first']) ? $u['kana_first'] : null,
        'gender'     => !empty($u['gender'])     ? $u['gender']     : null,
        'birth_y'    => !empty($u['birth_y'])    ? $u['birth_y']    : null,
        'birth_m'    => !empty($u['birth_m'])    ? $u['birth_m']    : null,
        'birth_d'    => !empty($u['birth_d'])    ? $u['birth_d']    : null,
        'height'     => !empty($u['height'])     ? $u['height']     : null,
        'weight'     => !empty($u['weight'])     ? $u['weight']     : null,
      );
      return $res;
    }
  }

  /**
  * @param  array $params
  * @return json response
  */
  public function records($params)
  {
    if ($this->request->isPost()){
      // α版未実装
    }
    elseif ($this->request->isGet()){
      return $this->getRecords($this->token_user['user_id'], $this->request->getGet('date'));

    }
    $this->sendJSONResponse($this->badRequestResponce());
  }

  /**
  * @param  array $params
  * @return json response
  */
  public function records_feces($params)
  {
    if ($this->request->isGet()){
      // validate
      if (!isset($params["id"]) || !is_numeric($params["id"])) {
        $this->sendJSONResponse($this->badRequestResponce());
      }

      $result = $this->getResponce();
      $data = $this->getFeceLogByIdAndUserId($params["id"], $this->token_user['user_id']);

      // check data exits
      if (!isset($data[0]) || empty($data[0])) {
        $this->sendJSONResponse($this->forbiddenResponce());
      }
      $result["data"] = $data[0];
      $this->sendJSONResponse($result);
    }
    elseif ($this->request->isPost()){

      // validate
      if (!isset($_POST['color']) || !is_numeric($_POST['color']) ||
      !isset($_POST['shape']) || !is_numeric($_POST['shape']) ||
      !isset($_POST['amount'])   || !is_numeric($_POST['amount']) ||
      !isset($_POST['datetime']) ||
      $_POST['datetime'] !== date("Y-m-d H:i:s", strtotime($_POST['datetime']))) {
        $this->sendJSONResponse($this->badRequestResponce());
      }

      // update
      if (isset($params['id']) && is_numeric($params['id'])) {
        $values = $_POST;
        $values['id']   = $params['id'];
        $values['sampling_date'] = $values['datetime'];
        $data = $this->updateFeceLog($values);

        if ($data['result'][0]['error'] != 0) {
          $this->sendJSONResponse($this->badRequestResponce());
        }
        $this->sendJSONResponse($this->getResponce());
      }
      // create
      else {
        $values = $_POST;
        $values['sampling_date'] = $values['datetime'];
        $data = $this->createFeceLog($values);
        $result = $this->getResponce();
        $result["data"] = array("id" => $data["result"][0]["insert_id"]);
        $this->sendJSONResponse($result);
      }
    }
    $this->sendJSONResponse($this->badRequestResponce());
  }

  /**
  * @param  array $params
  * @return json response
  */
  public function records_feces_delete($params)
  {
    if ($this->request->isPost()) {
      $id = $params['id'];
      $user_id = $this->token_user['user_id'];

      // is exsit
      $feces = $this->getFeceLogByIdAndUserId($id, $user_id);
      if (!isset($feces[0]['id'])) {
        return $this->badRequestResponce('DATA NOT EXIST');
      }

      // delete
      $res = execConfigQuery($this->db, 'coach_user_fece_log_del', array(
        'id' => $id,
        'user_id' => $user_id,
      ));
      if ($res['status'] !== 'success') {
        return $this->badRequestResponce('DATA NOT EXIST');
      }
      return $this->getResponce();
    }
  }

  /**
  * @param  array $params
  * @return json response
  */
  public function records_meals($params)
  {
    if ($this->request->isPost()){
      // validate
      if (!isset($_POST['items']) ||
          !isset($_POST['segment']) ||
          !isset($_POST['date']) ||
          $_POST['date'] !== date("Y-m-d", strtotime($_POST['date']))) {
        $this->sendJSONResponse($this->badRequestResponce());
      }

      // create response
      // update
      if (isset($params['id'])) {
        return $this->updateMealLog( // records_meals_update
          $params['id'],
          $this->token_user['user_id'],
          $this->request->getPost('items'),
          $this->request->getPost('date'),
          $this->request->getPost('segment'),
          $this->request->getPost('pic_urls') // カンマ区切りでフルパス
        );
      }
      // create
      else {
        $tmp = $_POST;
        $tmp['eating_date'] = $this->request->getPost('date');
        $tmp['user_id'] = $this->token_user['user_id'];
        $data = $this->createMealLog($tmp);
        $result = $this->getResponce();
        if (!isset($data["result"][0]["insert_id"])) {
          // TODO 重複したかどうか判定して返却する
          $this->sendJSONResponse($this->badRequestResponce());
        }
        $result["data"] = array("id" => $data["result"][0]["insert_id"]);
        $this->sendJSONResponse($result);
      }
    }
    elseif ($this->request->isGet()){
      if (isset($params['id'])) {

        // check data
        $data = $this->getMealLogByIdAndUserId($params['id'], $this->token_user['user_id']);
        if (empty($data)) {
          $this->sendJSONResponse($this->forbiddenResponce());
        }

        $result = $this->getResponce();
        $result["data"] = $data;
        $this->sendJSONResponse($result);
      }
    }
    $this->sendJSONResponse($this->badRequestResponce());
  }

  /**
  * @param  array $params
  * @return json response
  */
  public function records_meals_delete($params)
  {
    if ($this->request->isPost()) {
      $id = $params['id'];
      $user_id = $this->token_user['user_id'];

      // is exsit
      $meal = $this->getMealLogByIdAndUserId($id, $user_id);
      if (!isset($meal['user_id'])) {
        return $this->badRequestResponce('DATA NOT EXIST');
      }

      // delete
      $res = execConfigQuery($this->db, 'coach_user_meal_log_del', array(
        'id' => $id,
        'user_id' => $user_id,
      ));
      // delete subordinate
      execConfigQuery($this->db, 'coach_user_meal_item_log_del', array(
        'meal_log_id' => $id,
      ));
      execConfigQuery($this->db, 'coach_user_meal_pic_log_del', array(
        'meal_log_id' => $id,
      ));

      if ($res['status'] !== 'success') {
        return $this->badRequestResponce('DATA NOT EXIST');
      }
      return $this->getResponce();
    }
  }

  /**
  * @param  array $params
  * @return json response
  */
  public function records_conditions($params)
  {
    if ($this->request->isGet()){
      // validate
      if (!isset($params["id"]) || !is_numeric($params["id"])) {
        $this->sendJSONResponse($this->badRequestResponce());
      }

      $result = $this->getResponce();
      $data = $this->getConditionLogByIdAndUserId($params["id"], $this->token_user['user_id']);

      // check data exits
      if (!isset($data[0]) || empty($data[0])) {
        $this->sendJSONResponse($this->forbiddenResponce());
      }
      $result["data"] = $data[0];
      $this->sendJSONResponse($result);
    }

    if ($this->request->isPost()){
      $args = array(
        'ache'    => $this->request->getPost('ache'),
        'strain'  => $this->request->getPost('strain'),
        'fart'    => $this->request->getPost('fart'),
        'stress'  => $this->request->getPost('stress'),
        'wakeup'  => $this->request->getPost('wakeup'),
        'hungry'  => $this->request->getPost('hungry'),
        'date'    => $this->request->getPost('date'),
        'segment' => $this->request->getPost('segment'),
      );

      // validate
      if ($args['ache']    == null ||
          $args['strain']  == null ||
          $args['date']    == null ||
          $args['segment'] == null ||
          $args['date']    !== date("Y-m-d", strtotime($args['date']))) {
          $this->sendJSONResponse($this->badRequestResponce());
      }

      // update
      if (isset($params['id']) && is_numeric($params['id'])) {
        $args['id'] = $params['id'];
        // TODO dateが重複した場合、更新処理が走らないバグがある！
        $data = $this->updateConditionLog($args);
        $this->sendJSONResponse($this->getResponce());
      }
      // create
      else {
        $data = $this->createConditionLog($args);
        if(!isset($data["result"])) {
          $this->sendJSONResponse($this->badRequestResponce('DATA IS DUPLICATE'));
        }
        $result = $this->getResponce();
        $result["data"] = array("id" => $data["result"][0]["insert_id"]);
        $this->sendJSONResponse($result);
      }
    }
    $this->sendJSONResponse($this->badRequestResponce());
  }

  /**
  * @param  array $params
  * @return json response
  */
  public function records_conditions_delete($params)
  {
    if ($this->request->isPost()) {
      $id = $params['id'];
      $user_id = $this->token_user['user_id'];

      // is exsit
      $log = $this->getConditionLogByIdAndUserId($id, $user_id);
      if (!isset($log[0]['id'])) {
        return $this->badRequestResponce('DATA NOT EXIST');
      }

      // delete
      $res = execConfigQuery($this->db, 'coach_user_condition_log_del', array(
        'id' => $id,
        'user_id' => $user_id,
      ));
      if ($res['status'] !== 'success') {
        return $this->badRequestResponce('DATA NOT EXIST');
      }
      return $this->getResponce();
    }
  }

  /**
  * カスタム料理名の登録・取得
  * @param  array $params
  * @return json response
  */
  public function records_items($params)
  {
    if ($this->request->isPost()){
      $name = $this->request->getPost('name');
      $user = $this->token_user;

      if (empty($name)) {
        $this->sendJSONResponse($this->badRequestResponce());
      }

      $tmp = execConfigQuery($this->db, 'coach_user_meal_item_ins', array(
        'user_id' => $user['user_id'],
        'name'    => $name,
      ));

      if ($tmp['status'] === 'failed') {
        $res = $this->badRequestResponce();
        $res['description'] = 'DUPLICATE ENTRY';
        $res['message']     = 'E_ALREADY_SAVED';
        $this->sendJSONResponse($res);
      }

      $res = $this->getResponce();
      $res['data'] = array(
        'id' => $tmp['data']['result'][0]['insert_id'],
      );
      return $res;
    }

    if ($this->request->isGet()){
      if ($this->request->getGet('filter') !== 'custom') {
        // 現状は機能をユーザが登録したカスタムitemに絞っている
        $this->sendJSONResponse($this->badRequestResponce());
      }
      $user = $this->token_user;

      $tmp = execConfigQuery($this->db, 'get_coach_user_meal_items', array(
        'user_id' => $user['user_id'],
      ));
      $res = $this->getResponce();
      $res['data'] = $tmp['data'];
      return $res;
    }
  }

  /**
  * ユーザ1日あたりの歩数
  * @param  array $params
  * @return json response
  */
  public function records_steps($params)
  {
    if ($this->request->isGet()){
      // get one by id
      if(isset($params['id']) && is_numeric($params['id'])) {
        $data = $this->getStepByIdAndUserId($params['id'], $this->token_user['user_id']);
      } else {
      // get all
        $data = $this->getStepsByUserId($this->token_user['user_id']);
      }

      // set response
      $result = $this->getResponce();
      $result["data"] = isset($params['id']) ? $data[0] : $data;
      $this->sendJSONResponse($result);
    }

    if ($this->request->isPost()){
      $args = array(
        'date'  => $this->request->getPost('date'),
        'step'  => $this->request->getPost('step')
      );

      // validate
      if ($args['step']    == null ||
          $args['date']    == null ||
          $args['date']    !== date("Y-m-d", strtotime($args['date']))) {
          $this->sendJSONResponse($this->badRequestResponce());
      }

      // update

      if (isset($params['id']) && is_numeric($params['id'])) {
        $args['id'] = $params['id'];
        // TODO dateが重複した場合、更新処理が走らないバグがある！
        $data = $this->updateStep($args);

        if ($data['result'][0]['error'] != 0) {
          $this->sendJSONResponse($this->badRequestResponce());
        }
        $this->sendJSONResponse($this->getResponce());
      }
      // create
      else {
        // CHECK DUPLICATE
        $tmp = $this->getStepsByUserIdAndData($this->token_user['user_id'], $args['date']);
        if (!empty($tmp)) {
          $this->sendJSONResponse($this->badRequestResponce('DATA IS DUPLICATE'));
        }

        $data = $this->createUserStep($args);
        $result = $this->getResponce();
        $result["data"] = array("id" => $data["result"][0]["insert_id"]);
        $this->sendJSONResponse($result);
      }
    }
    $this->sendJSONResponse($this->badRequestResponce());
  }

  /**
  * ユーザ1日あたりの歩数 削除
  * @param  array $params
  * @return json response
  */
  public function records_steps_delete($params)
  {
    if ($this->request->isPost()) {
      $id = $params['id'];
      $user_id = $this->token_user['user_id'];

      // is exsit
      $memo = $this->getStepByIdAndUserId($id, $user_id);
      if (!isset($memo[0]['id'])) {
        return $this->badRequestResponce('DATA NOT EXIST');
      }

      // delete
      $res = execConfigQuery($this->db, 'coach_user_step_del', array(
        'id' => $id,
        'user_id' => $user_id,
      ));
      if ($res['status'] !== 'success') {
        return $this->badRequestResponce('DATA NOT EXIST');
      }
      return $this->getResponce();
    }
  }

  /**
  * @param  array $params
  * @return json response
  */
  public function records_memos($params)
  {
    if ($this->request->isGet()){
      // get one by id
      if(isset($params['id']) && is_numeric($params['id'])) {
        $data = $this->getMemoByIdAndUserId($params['id'], $this->token_user['user_id']);
      } else {
      // get all
        $data = $this->getMemosByUserId($this->token_user['user_id']);
      }

      // set response
      $result = $this->getResponce();
      $result["data"] = isset($params['id']) ? $data[0] : $data;
      $this->sendJSONResponse($result);
    }

    if ($this->request->isPost()){
      $args = array(
        'date'  => $this->request->getPost('date'),
        'memo'  => $this->request->getPost('memo')
      );

      // validate
      if ($args['memo']    == null ||
          $args['date']    == null ||
          $args['date']    !== date("Y-m-d", strtotime($args['date']))) {
          $this->sendJSONResponse($this->badRequestResponce());
      }

      // update
      if (isset($params['id']) && is_numeric($params['id'])) {
        $args['id'] = $params['id'];
        // TODO dateが重複した場合、更新処理が走らないバグがある！
        $data = $this->updateMemo($args);

        if ($data['result'][0]['error'] != 0) {
          $this->sendJSONResponse($this->badRequestResponce());
        }
        $this->sendJSONResponse($this->getResponce());
      }
      // create
      else {
        $data = $this->createUserMemo($args);
        if(!isset($data["result"])) {
          $this->sendJSONResponse($this->badRequestResponce('DATA IS DUPLICATE'));
        }
        $result = $this->getResponce();
        $result["data"] = array("id" => $data["result"][0]["insert_id"]);
        $this->sendJSONResponse($result);
      }
    }
    $this->sendJSONResponse($this->badRequestResponce());
  }

  /**
  * @param  array $params
  * @return json response
  */
  public function records_memos_delete($params)
  {
    if ($this->request->isPost()) {
      $id = $params['id'];
      $user_id = $this->token_user['user_id'];

      // is exsit
      $memo = $this->getMemoByIdAndUserId($id, $user_id);
      if (!isset($memo[0]['id'])) {
        return $this->badRequestResponce('DATA NOT EXIST');
      }

      // delete
      $res = execConfigQuery($this->db, 'coach_user_memo_del', array(
        'id' => $id,
        'user_id' => $user_id,
      ));
      if ($res['status'] !== 'success') {
        return $this->badRequestResponce('DATA NOT EXIST');
      }
      return $this->getResponce();
    }
  }

  /**
   *  @param user_id        ユーザID
   *  @param choices Strign 設問の回答が、カンマ区切りの文字列で送られてくる
   */
  public function records_checks($params)
  {
    if ($this->request->isPOST()) {
      // init
      $args = array(
        'choices' => $this->request->getPost('choices'),
      );
      $choices = explode(',', $args['choices']);
      $user    = $this->token_user;

      // validate
      // MEMO 設問数が決め打ちなので、設問数分回答があるかチェックする
      if (count($choices) < 4) {
        $this->sendJSONResponse($this->badRequestResponce());
      }

      $tmp = execConfigQuery($this->db ,'coach_user_check_logs_ins',array(
        'user_id' => $user['user_id'],
        'q01' => $choices[0],
        'q02' => $choices[1],
        'q03' => $choices[2],
        'q04' => $choices[3],
      ));

      $res = $this->getResponce();
      $this->sendJSONResponse($res);
    }

    if ($this->request->isGet()) {
      // init
      $args = array(
        'filter' => $this->request->getGet('filter'),
      );
      $user = $this->token_user;

      if ($args['filter'] != 'latest') {
        $this->sendJSONResponse($this->badRequestResponce());
      }

      $tmp = execConfigQuery($this->db ,'get_coach_user_check_logs_latest',array(
        'user_id' => $user['user_id']
      ));

      // nodata
      $res = $this->getResponce();
      if (!isset($tmp['data'][0])) {
        $res['data'] = array(
          'choices' => array(),
          'result'  => (object)array(),
        );
        $this->sendJSONResponse($res);
      }

      $data = $this->getCheckResultOfIBS($tmp['data'][0]);
      $res['data'] = $data;
      $this->sendJSONResponse($res);
    }
  }

  // Services ==================================================================

  /**
   *  IBS判定処理
   */
  private function getCheckResultOfIBS(Array $p) {
    $res = array(
      'choices' => array(
        $p['q01'],
        $p['q02'],
        $p['q03'],
        $p['q04']
      ),
      'result' => array(),
    );

    // see https://docs.google.com/spreadsheets/d/18kwPwri5p3qc9pKKe5LpxzulF632m4XkWsFdRbZBn4k/edit#gid=1741543608
    $count = 0;
    // IBSに関する質問項目の値によって、点数を加算
    if ($p['q01'] > 1)  $count++;
    if ($p['q02'] == 1) $count++;
    if ($p['q03'] == 1) $count++;
    if ($p['q04'] == 1) $count++;

    if ($count === 0) {
      $res['result']['summaryPrefix'] = "お腹の調子は";
      $res['result']['summary'] = "好調です！";
      $res['result']['detail'] = "今は良好でも、実は自分のお腹に合わない食材が隠れているかもしれません。食事とお腹の調子を記録して、その関係性を観察してみましょう！\n更に快”腸”を目指しましょう♪";
      $res['result']['rank'] = 'A';
    } elseif($count <= 2) {
      $res['result']['summaryPrefix'] = "お腹の調子は";
      $res['result']['summary'] = "少し不安定です。";
      $res['result']['detail'] = "実は、自分のお腹に合わない食材を日頃食べているのかも… 食事とお腹の調子を記録して、その関係性を観察してみましょう！\n3ヶ月後を目安にもう一度チェックをしてみましょう。";
      $res['result']['rank'] = 'B';
    } else {
      $res['result']['summaryPrefix'] = "お腹の調子は";
      $res['result']['summary'] = "不安定です。";
      $res['result']['detail'] = "一度、医療機関の受診を考えても良いかもしれません。自分のお腹に合わない食材が見つかれば、お腹の調子も安定してくるかもしれません。\n3ヶ月後を目安にもう一度チェックをしてみましょう。";
      $res['result']['rank'] = 'C';
    }
    return $res;
  }

  /**
  * 排便ログを保存
  * @param  array(
  *           "color"     : int
  *           "shape"     : int
  *           "amount"       : int
  *           "sampling_date": date
  *         )
  * @return json response
  */
  private function createFeceLog(Array $params){
    $params['user_id'] = $this->token_user['user_id'];
    $res = execConfigQuery($this->db, 'coach_user_fece_log_ins', $params);
    return $res["data"];
  }

  /**
  * 排便ログを取得
  * @param  (int)fece_id
  * @return json response
  */
  private function getFeceLogByIdAndUserId($fece_id, $user_id){
    $res = execConfigQuery(
      $this->db,
      'get_coach_user_fece_log_by_id',
      array(
        'id' => $fece_id,
        'user_id' =>  $user_id,
      )
    );
    return $res["data"];
  }

  /**
  * 排便ログを取得（ALL）
  * @param  (int)fece_id
  * @return json response
  */
  private function getFeceLogByUserId($user_id){
    $logs = execConfigQuery(
      $this->db,
      'get_coach_user_fece_log_by_user_id',
      array('user_id' => $user_id)
    );

    // 日付毎に排便回数(times)を付与
    $res = array();
    $times = 1;
    $preDate = 0;
    foreach ($logs['data'] as $key => $log) {
      $log['date'] = substr($log['datetime'], 0, 10);
      if ($preDate != $log['date']) {
        $times = 1;
      } else {
        $times++;
      }
      $log['times'] = $times;
      $preDate = $log['date'];

      // レスポンス形式を整形
      $res[] = array(
        'fece_id'   => $log['fece_id'],
        'type'      => $log['type'],
        'color'     => $log['color'],
        'datetime'  => $log['datetime'],
        'shape'     => $log['shape'],
        'amount'    => $log['amount'],
        'date'      => $log['date'],
        'times'     => $log['times'],
      );
    }
    return $res;
  }

  /**
  * 排便ログを更新
  * @param  array(
  *           "id"           : int
  *           "color"     : int
  *           "shape"     : int
  *           "amount"       : int
  *           "sampling_date": date
  *         )
  * @return json response
  */
  private function updateFeceLog(Array $params){
    $params['user_id'] = $this->token_user['user_id'];
    $res = execConfigQuery($this->db, 'coach_user_fece_log_upd', $params);
    return $res["data"];
  }

  /**
  * 体調ログを保存
  * @param  array
  * @return json response
  */
  private function createConditionLog(Array $params){
    $params['user_id'] = $this->token_user['user_id'];
    $res = execConfigQuery($this->db, 'coach_user_condition_log_ins', $params);
    return $res["data"];
  }

  /**
  * 体調ログを取得
  * @param  (int) id
  * @param  (int) user_id
  * @return json response
  */
  private function getConditionLogByIdAndUserId($id, $user_id){
    $res = execConfigQuery(
      $this->db,
      'get_coach_user_condition_log_by_id',
      array(
        'id' => $id,
        'user_id' =>  $user_id,
      )
    );
    return $res["data"];
  }

  /**
  * 体調ログを取得（ALL）
  * @param  (int) id
  * @return json response
  */
  private function getConditionLogByUserId($user_id){
    $res = execConfigQuery(
      $this->db,
      'get_coach_user_condition_log_by_user_id',
      array('user_id' => $user_id)
    );
    return $res["data"];
  }

  /**
  * 体調ログを更新
  * @param  array
  * @return json response
  */
  private function updateConditionLog(Array $params){
    $params['user_id'] = $this->token_user['user_id'];
    $res = execConfigQuery($this->db, 'coach_user_condition_log_upd', $params);
    return $res["data"];
  }

//===============
/**
* メモを保存
* @param  array
* @return json response
*/
private function createUserMemo(Array $params){
  $params['user_id'] = $this->token_user['user_id'];
  $res = execConfigQuery($this->db, 'coach_user_memo_ins', $params);
  return $res["data"];
}

/**
* メモを取得
* @param  (int) id
* @param  (int) user_id
* @return json response
*/
private function getMemoByIdAndUserId($id, $user_id){
  $res = execConfigQuery(
    $this->db,
    'get_coach_user_memo_by_id_and_user_id',
    array(
      'id' => $id,
      'user_id' =>  $user_id
    )
  );
  return $res["data"];
}

/**
* メモを取得
* @param  (int) user_id
* @return json response
*/
private function getMemosByUserId($user_id){
  $res = execConfigQuery(
    $this->db,
    'get_coach_user_memos_by_user_id',
    array(
      'user_id' =>  $user_id
    )
  );
  return $res["data"];
}

/**
* メモを更新
* @param  array
* @return json response
*/
private function updateMemo(Array $params){
  $params['user_id'] = $this->token_user['user_id'];
  $res = execConfigQuery($this->db, 'coach_user_memo_upd', $params);
  return $res["data"];
}

/**
* 歩数を保存
* @param  array
* @return json response
*/
private function createUserStep(Array $params){
  $params['user_id'] = $this->token_user['user_id'];
  $res = execConfigQuery($this->db, 'coach_user_step_ins', $params);
  return $res["data"];
}

/**
* 歩数を取得
* @param  (int) id
* @param  (int) user_id
* @return json response
*/
private function getStepByIdAndUserId($id, $user_id){
  $res = execConfigQuery(
    $this->db,
    'get_coach_user_step_by_id_and_user_id',
    array(
      'id' => $id,
      'user_id' =>  $user_id
    )
  );
  return $res["data"];
}

/**
* 歩数を取得
* @param  (int) user_id
* @return json response
*/
private function getStepsByUserId($user_id){
  $res = execConfigQuery(
    $this->db,
    'get_coach_user_steps_by_user_id',
    array(
      'user_id' =>  $user_id
    )
  );
  return $res["data"];
}

/**
* 歩数を取得
* @param  (int) user_id
* @param  (int) date
* @return json response
*/
private function getStepsByUserIdAndData($user_id, $date){
  $res = execConfigQuery(
    $this->db,
    'get_coach_user_steps_by_user_id_and_date',
    array(
      'user_id' =>  $user_id,
      'date'    =>  $date,
    )
  );
  return $res["data"];
}

/**
* メモを更新
* @param  array
* @return json response
*/
private function updateStep(Array $params){
  $params['user_id'] = $this->token_user['user_id'];
  $res = execConfigQuery($this->db, 'coach_user_step_upd', $params);
  return $res["data"];
}

  /**
  * 食事ログを取得
  * @param  (int)meal_id
  * @return json response
  */
  private function getMealLogByIdAndUserId($id, $user_id) {
    $res = execConfigQuery(
      $this->db,
      'get_coach_user_meal_log_by_id',
      array(
        'id' => $id,
        'user_id' => $user_id,
      )
    );

    if (count($res['data']) < 1) {
      return array();
    }

    // get pictures
    $pic_logs = execConfigQuery(
      $this->db,
      'get_coach_user_meal_pic_logs',
      array(
        'coach_user_meal_log_id' => $id
      )
    );
    $pic_urls = array();
    foreach ($pic_logs['data'] as $key => $value) {
      $pic_urls[] = $value['pic_url'];
    }

    // TODO 基本形とAppendに分離する
    $logs = array(
      'id' => $res['data'][0]['id'],
      'user_id' => $res['data'][0]['user_id'],
      'date' => $res['data'][0]['date'],
      'segment' => $res['data'][0]['segment'],
      'pic_urls' => $pic_urls,
      'items' => array()
    );

    foreach ($res['data'] as $key => $append) {
      $logs['items'][] = $append['item_id'];
    }

    return $logs;
  }

  /**
  * 食事ログ取得
  * @param
  * @return json response
  */
  private function getMealLogByUserId($user_id) {
    // get user logs
    $res = execConfigQuery(
      $this->db,
      'get_coach_user_meal_log_all_by_user_id',
      array('user_id' => $user_id)
    );
    // validate
    if (!isset($res['data']) || count($res['data']) < 1) {
      return array();
    }
    $logs = $res['data'];
    // get meals data from logs
    $meals = array();
    $preId = 0;
    foreach ($logs as $key => $log) {
      if ($log['id'] !== $preId) {
        $meals[]    = array(
          'id'      => $log['id'],
          'date'    => $log['date'],
          'segment' => $log['segment'],
          'items'   => array(),
          'pic_urls' => array(),
        );
        $preId = $log['id'];
      }
    }
    // set items to the meal
    $return = $meals;
    foreach ($meals as $mk => $meal) {
      foreach ($logs as $key => $log) {
        if ($return[$mk]['id'] == $log['id']) {
          $tmp = array('id' => $log['item_id']);
          // カスタム料理のnameが取得できる場合のみ、パラメーターをセット
          if (isset($log['name'])) {
            $tmp['name'] = $log['name'];
          }
          $return[$mk]['items'][] = $tmp;
        }
      }
    }

    // set pic_urls to the meal
    $logs = execConfigQuery(
      $this->db,
      'get_coach_user_meal_pic_logs_by_user_id',
      array('user_id' => $user_id)
    );
    $logs = $logs['data'];

    $meals = $return;
    foreach ($meals as $mk => $meal) {
      foreach ($logs as $key => $log) {
        if ($return[$mk]['id'] == $log['coach_user_meal_log_id']) {
          $return[$mk]['pic_urls'][] = $log['pic_url'];
        }
      }
    }

    return $return;
  }

  /**
  * ログを一括取得
  * @param INT  $user_id
  * @param DATE $targetDate OPTION: 指定日のデータのみ取得する
  */
  private function getRecords($user_id, $targetDate = null) {
    $mealLogs = $this->getMealLogByUserId($user_id);
    $feceLogs = $this->getFeceLogByUserId($user_id);
    $condLogs = $this->getConditionLogByUserId($user_id);
    $memos    = $this->getMemosByUserId($user_id);
    $steps    = $this->getStepsByUserId($user_id);

    if (empty($mealLogs) && empty($feceLogs) && empty($condLogs) && empty($memos) && empty($steps)) {
      if ($targetDate) {
        // レスポンス形式を統一
        $res = $this->getResponce();
        $res['data'] = array(
          'date'       => $targetDate,
          'meals'      => array(),
          'feces'      => array(),
          'conditions' => array(),
          'memo'      => null,
          'step'      => null,
        );
        return $res;
      }
      return $this->getResponce('success', 'this user have not records');
    }

    // TODO datetime $k が重複したケースを考慮して書き直すこと！
    // 要BUGFIX
    // 日付毎にデータを集計
    $records = array();
    $preFeceLogDate = 0;
    foreach ($feceLogs as $key => $log) {
      $k = strtotime($log['datetime']);
      if ($preFeceLogDate == $k) {
        $k += 1; // feceLog は 同じ登録時刻に複数データ登録できるのでその調整をする
      }
      $log['type'] = 'feces';
      $records[$k] = $log;
      $preFeceLogDate = $k;
    }
    foreach ($mealLogs as $key => $log) {
      $k = strtotime($log['date']) +  $log['segment'];
      $log['type'] = 'meals';
      $log['date'] = substr($log['date'], 0, 10);
      $records[$k] = $log;
    }
    foreach ($condLogs as $key => $log) {
      $k = strtotime($log['date']) +  ($log['segment'] * 10); // mealsとキーをダブらないようにする
      $log['type'] = 'conditions';
      $log['date'] = substr($log['date'], 0, 10);
      $records[$k] = $log;
    }
    foreach ($memos as $key => $log) {
      $k = strtotime($log['date']) +  1000; // meals, feces とキーをダブらないようにする
      $log['type'] = 'memos';
      $log['date'] = substr($log['date'], 0, 10);
      $records[$k] = $log;
    }
    foreach ($steps as $key => $log) {
      $k = strtotime($log['date']) +  10000; // meals, feces, memos とキーをダブらないようにする
      $log['type'] = 'steps';
      $log['date'] = substr($log['date'], 0, 10);
      $records[$k] = $log;
    }
    ksort($records);

    // 日付毎の容器を作る
    $tmp;
    $firstRecord = reset($records);
    $date = null;
    foreach ($records as $key => $record) {
      if ($date !== $record['date']) {
        // create default format
        $date = $record['date'];
        $tmp[$date] = array(
          'date' => $date,
          'meals' => array(),
          'feces' => array(),
          'conditions' => array(),
          'memos' => array(),
          'steps' => array(),
        );
      }
      // set date
      $tmp[$record['date']][$record['type']][] = $record;
    }

    // キーから日付を外す
    $res = $this->getResponce();
    foreach ($tmp as $key => $record) {
      $res['data'][] = $record;
    }

    // TODO DB取得時点でデータを絞っておくこと！！ 絞込処理
    // ========================================

    if ($targetDate) {
      $tmp = $res;
      $res['data'] = array(
        'date'       => $targetDate,
        'meals'      => array(),
        'feces'      => array(),
        'conditions' => array(),
        'memo' => null,
        'step' => null,
      );
      foreach ($tmp['data'] as $key => $value) {
        if ($value['date'] === $targetDate) {
          $res['data']['meals']      = $value['meals'];
          $res['data']['feces']      = $value['feces'];
          $res['data']['conditions'] = $value['conditions'];
          if (!empty($value['memos'][0])) {
            $res['data']['memo']       = $value['memos'][0];
          }
          if (!empty($value['steps'][0])) {
            $res['data']['step']       = $value['steps'][0];
          }
          break;
        }
      }
    }
    // ========================================
    return $res;
  }

  /**
  * 食事ログを保存
  * @param  array(
  *           "items"       : array
  *           "pic_urls"       : array
  *           "date" : timestamp
  *         )
  * @return json response
  */
  private function createMealLog(Array $params){
    $res = execConfigQuery($this->db, 'coach_user_meal_log_ins', $params);
    $res = $res["data"];
    $insert_id = $res["result"][0]["insert_id"];
    // TODO: 本来トランザクションがあると良い処理だが、FWの都合でトランザクションが張れない
    $this->createMealItemLogs($insert_id, $params['items']);
    if (isset($params['pic_urls'])){
      $this->createMealPicLogs($insert_id, $params['pic_urls']);
    }
    return $res;
  }

  /**
  * 食事ログの写真を保存
  * @param  array $files
  * @return json response
  */
  private function createMealPicLogs($meal_id, $files) {
    $files = explode(',', $files);
    foreach ($files as $key => $file) {
      $tmp = execConfigQuery(
        $this->db,
        'coach_user_meal_pic_log_ins',
        array(
          'coach_user_meal_log_id' => $meal_id,
          'pic_url' => $file,
        )
      );
    }
  }

  /**
  * 食事ログの食品を保存
  * @param  array $items
  * @return json response
  */
  private function createMealItemLogs($meal_id, $items) {
    $items = explode(',', $items);
    foreach ($items as $key => $item) {
      $tmp = execConfigQuery(
        $this->db,
        'coach_user_meal_item_log_ins',
        array(
          'coach_user_meal_log_id' => $meal_id,
          'coach_meal_item_id' => $item,
        )
      );
    }
  }

  /**
  * 食事ログを更新
  * @param  array(
  *           "items"       : array
  *           "pic_urls"    : array
  *           "date"        : date
  *         )
  * @return $this->getResponce()
  */
  private function updateMealLog($id, $userId, $itemIds, $eatingDate, $segment, $picUrls = null) {
    // validate
    if (!is_numeric($id) || !is_numeric($userId) || !isset($itemIds)
    || $eatingDate !== date("Y-m-d", strtotime($eatingDate))) {
      $this->sendJSONResponse($this->badRequestResponce());
    }

    // check is own user
    $res = execConfigQuery($this->db, 'get_coach_user_meal_log_by_id', array(
        'id' => $id,
        'user_id' => $userId,
      )
    );
    if (empty($res['data'])) {
      return $this->forbiddenResponce();
    }

    // MEMO: 本来トランザクションが望ましい処理だが、FWの都合で無し
    $res = execConfigQuery($this->db, 'coach_user_meal_log_upd', array(
      'id' => $id,
      'user_id' => $userId,
      'eating_date' => $eatingDate,
      'segment' => $segment,
    ));
    if ($res['status'] !== 'success') {
      return $this->badRequestResponce();
    }

    // delete old logs
    execConfigQuery($this->db, 'coach_user_meal_item_log_del', array(
      'meal_log_id' => $id,
    ));
    execConfigQuery($this->db, 'coach_user_meal_pic_log_del', array(
      'meal_log_id' => $id,
    ));
    // insert new logs
    $this->createMealItemLogs($id /*meal_id*/, $itemIds);
    // optional insert
    if ($picUrls) {
      $this->createMealPicLogs($id /*meal_id*/, $picUrls);
    }
    return $this->getResponce();
  }

  /**
  *
  * @return json response
  */
  private function updateUserByAuthCode(Array $params) {
    // validate
    if ( empty($params['auth_code'])
      //  || empty($params['device_id']) device_id は nullable
      || empty($params['name_last'])
      || empty($params['name_first'])
      || empty($params['kana_last'])
      || empty($params['kana_first'])
      || empty($params['gender'])
      || empty($params['birth_y'])
      || empty($params['birth_m'])
      || empty($params['birth_d'])
      || empty($params['height'])
      || empty($params['weight'])
      || empty($params['password'])) {
      return $this->badRequestResponce();
    }

    // check user exist by auth_code
    $tmp = execConfigQuery($this->db, 'get_user_by_auth_code', array(
      'auth_code' => $params['auth_code'],
    ));

    // validate checking
    if (!isset($tmp['data']) || count($tmp["data"]) !== 1) {
      return $this->getResponce("failed", "E_PERMISSION_ERROR");
    }
    $user = $tmp['data'][0];

    // check coach user exists
    if ($user['usrtype'] === 2) {
      return $this->getResponce("failed", "E_ALREADY_SAVED");
    }

    // update user
    $params['usrtype'] = 2; // Coach user = 2
    $params['status'] = 1;  // Authetificated = 1
    $tmp = $this->updateUser($user['rid'], $params);

    // register device_id
    if (!empty($params['device_id'])) {
      $tmp = execConfigQuery($this->db, 'coach_user_device_id_ins', array(
        'user_id' => $user['rid'],
        'device_id' => $params['device_id'],
      ));
    }

    $result = $this->getResponce();
    $result['data'] = array('id' => $user['rid']);
    return $result;
  }

  /**
  *
  * @return json response
  */
  private function updateUser($userId, Array $params) {
    // adapt params
    $params['birth'] = null;
    if ($params['birth_y'] && $params['birth_m'] && $params['birth_d']) {
      $params['birth'] = $params['birth_y'];
      $params['birth'] .= '-' . $params['birth_m'];
      $params['birth'] .= '-' . $params['birth_d'];
    }
    $params['cipher_pw'] = null;
    if ($params['password']) {
      $params['cipher_pw'] = LC_COM_getPWencode($params['password']);
    }
    // adapt option params
    $params['usrtype'] = isset($params['usrtype']) ? $params['usrtype'] : null;
    $params['status']  = isset($params['status']) ? $params['status'] : null;

    // set update params
    $res = execConfigQuery($this->db, 'get_user_by_id', array(
      'id' => $userId)
    );
    $user = $res['data'][0];

    $res = execConfigQuery($this->db, 'coach_user_upd', array(
      'user_id'    => $userId,
      'name_last'  => $params['name_last'] ? $params['name_last'] : $user['name_last'],
      'name_first' => $params['name_first'] ? $params['name_first'] : $user['name_first'],
      'kana_last'  => $params['kana_last'] ? $params['kana_last'] : $user['kana_last'],
      'kana_first' => $params['kana_first'] ? $params['kana_first'] : $user['kana_first'],
      'gender'     => $params['gender'] ? $params['gender'] : $user['gender'],
      'birth'      => $params['birth'] ? $params['birth'] : $user['birth'],
      'birth_y'    => $params['birth_y'] ? $params['birth_y'] : $user['birth_y'],
      'birth_m'    => $params['birth_m'] ? $params['birth_m'] : $user['birth_m'],
      'birth_d'    => $params['birth_d'] ? $params['birth_d'] : $user['birth_d'],
      'cipher_pw'  => $params['cipher_pw'] ? $params['cipher_pw'] : $user['cipher_pw'],
      'usrtype'    => $params['usrtype'] ? $params['usrtype'] : $user['usrtype'],
      'status'     => $params['status'] ? $params['status'] : $user['status'],
    ));

    if ($res['status'] !== 'success') {
      $this->badRequestResponce();
    }

    if ($params['weight'] || $params['height']) {
      execConfigQuery($this->db, 'coach_user_daily_log_ins', array(
        'user_id' => $userId,
        'weight'  => $params['weight'],
        'height'  => $params['height'],
      ));
    }

    return $this->getResponce();
  }

  /**
  * getUser系のレスポンスデータに 体重、身長 などの情報を付与する
  * @return json response
  */
  private function appendLogsToUser(Array $user) {
    // set default
    $user['weight']     = null;
    $user['height']     = null;

    // set data
    $weight = execConfigQuery($this->db, 'get_user_latest_weight', $user);
    if (isset($weight['data'][0]) && count($weight['data']) >= 1) {
      $user['weight']    = $weight['data'][0]['weight'];
    }
    $height = execConfigQuery($this->db, 'get_user_latest_height', $user);
    if (isset($height['data'][0]) && count($height['data']) >= 1) {
      $user['height']    = $height['data'][0]['height'];
    }

    return $user;
  }
}
