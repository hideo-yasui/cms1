var formPageid = "page1";
//on ready statement
$(function(){
	//画面縮小時のみ再度ナビトグル
    $("#snav").on({
        "click": function() {
            $("#snav,#main").toggleClass("is--active")
        }
    })

	base.init();
});

//ユーザー情報設定
function setUserInfo(){
	var userInfo = util.getLocalData("userinfo");
	if(util.isEmpty(userInfo)){
		getData("get_userinfo_by_session_enc", {},
			function(data, st, xhr) {
				if(data.length>0){
					_setUserInfo(data[0]);
					util.setLocalData("userinfo", data[0]);
				}
			},
			function(xhr, st, err) {
			}
		);
	}
	else {
		_setUserInfo(userInfo);
	}
}
//ユーザー情報設定（DOM設定）
function _setUserInfo(userInfo){
	for(key in userInfo){
		if(!util.isEmpty(userInfo[key])){
			var _val = service.getDecodeString(userInfo[key]);
			$("#user_"+key).html(_val);
		}
	}
	//70分経過でセッション有効チェック
	setTimeout(sessionCheck,  4200000);
}
//セッション有効チェック
function sessionCheck(){
	util.setLocalData("userinfo", "");
	setUserInfo();
}
function getPermission(callback){
	var permission = util.getLocalData("permission");
	if(util.isEmpty(permission)){
		service.getAjax(true, "/getpermission", {},
			function(result, st, xhr) {
				if(result["status"]=="success"){
					util.setLocalData("permission", result["data"]);
					if(util.isFunction(callback)) callback(result["data"]);
				}
			},
			function(xhr, st, err) {
			}
		);
	}
	else {
		callback(permission);
	}
}
//データ取得
function getData(query, req, callback){
	service.getAjax(false, "/get/"+query, req,
		function(result, st, xhr) {
			if(result["status"]=="success"){
				if(util.isFunction(callback)) callback(result["data"]);
			}
		},
		function(xhr, st, err) {
		}, true
	);
}

function logout(){
	service.logout();
}
function passwordEdit(){
	base.showEditPage("page2", "password_edit","", {}, true);
}
function passwordEditSave(){
	base.saveProc("upd", "password_edit", function(data) {
		if(data["status"]=="success" && data["data"]["result"].length>0 && data["data"]["result"][0]["count"]>0){
			service.alert("I_PASSWORD","",  function(){});
		}
		else {
			service.alert("ERROR","",  function(){});
		}
	});
}
function userSettingEdit(){
	base.showEditPage("page2", "usersetting","", util.getLocalData("userSetting"), true);
}
function userSettingSave(){
	if(base.userSetting()){
		service.alert("I_USERSETTING","",  function(){});
	}
}

function accountEditSave(){
	base.saveProc("upd", "accountedit", function(result, st, xhr) {
		var isUpdate=result["data"]["result"][0]["count"];
		if(isUpdate|0>0){
			service.alert("I_ACCOUNT","",  function(){});
		}
		else {

		}
	});
}
function pageSetting(pagecode, callback){
	if(!pagecode) return;
	base.showPage(formPageid, pagecode, "", function(option){
		$(".nav--header__menu ul li").hide();
		if(!util.isEmpty(option["title"])) base.setSubTitle(option["title"]);
		for (var i=0;i<option["nav_header"].length;i++){
			if(util.isEmpty(option["nav_header"][i])) continue;
			$(".nav--header__menu ul li#"+option["nav_header"][i]).show();
			if(util.isFunction(callback)) callback();
		}
	});
}

/**
 * セレクトボックスに生年月日入力用のオプションをセットする
 * @param Int   id_year    id of select element
 * @param Int   id_month   id of select element
 * @param Int   id_day     id of select element
 * TODO 未成年となる年齢を選べないような処理を入れたい
 */
function setOptionOfBirthDay(id_year, id_month, id_day) {
  // set Options of Common Era
  var today = new Date();
  var this_year  = today.getFullYear();
  var start_year = this_year - 120; // max: 120
  var end_year   = this_year - 16;  // min: 16
  setOptionOfCommonEra(id_year, id_month, id_day, start_year, end_year);
}

/**
 * セレクトボックスに西暦（年、月、日）のオプションをセットする
 * @param Int   id_year    id of select element
 * @param Int   id_month   id of select element
 * @param Int   id_day     id of select element
 * @param Int   start_year
 * @param Int   end_year
 * TODO 実在しない組み合わせ（2/31等）を作成できないようにしたい
 */
function setOptionOfCommonEra(id_year, id_month, id_day, start_year, end_year) {
  optionLoop(start_year, end_year, id_year, '年', 'DESC');
  optionLoop(1, 12, id_month, '月');
  optionLoop(1, 31, id_day, '日');
}

/** セレクトボックスに年、月、日などのオプションをセットする
 * @param  Int      start
 * @param  Int      end
 * @param  Int      id    id of select element
 * @param  String   unit  年、月、日 ... など
 * @param  String   sort  'ASC' | 'DESC'
 * @return String         HTML(option elements)
 */
function optionLoop(start, end, id, unit, sort) {
  var i, opt;
  opt = null;
  opt += '<option value="">未選択</option>';

  if (sort !== 'DESC') {
    for (i = start; i <= end ; i++) {
      opt += "<option value='" + i + "'>" + i + unit + "</option>";
    }
  } else {
    for (i = end; i >= start ; i--) {
      opt += "<option value='" + i + "'>" + i + unit + "</option>";
    }
  }
  return document.getElementById(id).innerHTML = opt;
};
