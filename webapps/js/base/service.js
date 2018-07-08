/**
* web-apiプラグイン
* サーバーサイドと通信するロジックをまとめる
* domを利用
* @namespace
* @class service
**/
;(function(root, undefined) {
	"use strict";
	var _loadTimer = null;
	var _loading = null;
	var _requestCache = [];
	var _cache = {};
	//list_table用のIF
	var _isDebug = false;
	var public_method = {
		//ページコード=URL
		setQueryParam : setQueryParam,
		getQueryParam : getQueryParam,
		getGroupData : getGroupData,
		getCodeData : getCodeData,
		getMessage  : getMessage ,
		getEncodeString : getEncodeString,
		getDecodeString : getDecodeString,
		logout : _logout,
		getAjax  : getAjax,
		postAjax : postAjax,
		uploadAjax : uploadAjax,
        downloadAjax : downloadAjax,
		fileDownload : fileDownload,
		extendRequestJson  : extendRequestJson,
		error : errorMessage,
		alert : alertMessage,
		confirm : confirmMessage,
		sendMail : sendMail
	};
	startProc();
	//クロージャー
	/**
	* 初期処理 :
	* リクエスト処理用のパラメータの読み込み、groupcodeをキャッシュ
    * @private
    * @method startProc
    * @return {void} return nothing
    */
	function startProc(){
		var data = util.getLocalData("userSetting");
		_cache["userSetting"] = {
			"loadingStart" : 300,
			"requestCacheTime" : 300000,
			"requestCacheSize" : 30
		};
		if(!util.isEmpty(data)){
			for(var key in _cache["userSetting"]){
				if(!util.isEmpty(data[key])) continue;
				_cache["userSetting"][key] = data[key];
			}
		}
		setQueryParam();
		setGroupCode();
	}
	/**
	* ローディング開始
	* loadingStart 経過時、loadOpenを実行
    * @private
    * @method loadStart
    * @return {void} return nothing
    */
	function loadStart (){
		if(_loadTimer!=null){
			clearTimeout(_loadTimer);
		}
		_loadTimer = setTimeout(loadOpen, _cache["userSetting"]["loadingStart"]);
	}
	/**
	* ローディング終了
	* loadingStart 経過時、loadCloseを実行
    * @private
    * @method loadStop
    * @return {void} return nothing
    */
	function loadStop (){
		if(_loadTimer!=null){
			clearTimeout(_loadTimer);
			_loadTimer = null;
		}
		_loadTimer = setTimeout(loadClose, _cache["userSetting"]["loadingStart"]);
	}
	/**
	* ローディングダイアログを閉じる
    * @private
    * @method loadClose
    * @return {void} return nothing
    */
	function loadClose (){
		if(util.isEmpty(_loading)) return;
		_loading.dialog("close");
	}
	/**
	* ローディングダイアログを表示する
    * @private
    * @method loadOpen
    * @return {void} return nothing
    */
	function loadOpen (){
		if(util.isEmpty(_loading)) {
			if($("#loading")[0] && _loading==null){
				_loading = $("#loading").dialog({
					title : "Now Loading",
					draggable : true,
					resizable : false,
					stack : true,
					width: "300px",
					height: "auto",
					autoOpen : false,
					open:function(event, ui){
					},
					modal : true,
					zIndex : 2000
				});
				$(".ui-dialog-titlebar-close").hide();
			}
		}
		_loading.dialog("open");
	}

	/**
    * ログアウト処理
	*  ブラウザストレージをクリアし、logoutに遷移
    * @method logout
    * @return {void} return nothing
    */
	function _logout(){
		if(!_isDebug) {
			util.clearLocalData();
			location.href = "/logout";
		}
	}
	/**
    * URLパラメータ設定
	* https://domain?p1=a&p2=b&p3=c
	* cache = { "p1": "a", "p2" : "b", "p3" : "c"}
    * @method setQueryParam
    * @return {void} return nothing
    */
	function setQueryParam(){
		var _urls = (document.URL+"").split('/');
		var _pageCode = _urls[_urls.length-1].replace("#","");
		//urlからページコード取得
		_cache["_query_"] = {};
		var queryParam = ($(location).attr("search")+"&").split("&");
		_pageCode = _pageCode.replace('"',"");
		_pageCode = _pageCode.replace(/\?.*$/,"");
		for(var i=0,n=queryParam.length;i<n;i++){
			if(queryParam[i].indexOf("=")<0) continue;
			var q = queryParam[i].split("=");
			if(q.length!=2) continue;
			if(q[0].substring(0,1)=="?") q[0] = q[0].substring(1, q[0].length);
			_cache["_query_"][q[0]]=q[1];
		}
		_cache["page"] = _pageCode;
		var h = getQueryParam("h");
		if(!util.isEmpty(h)) _cache["h0"] = h;
		return {"page" : _cache["page"], "h" : h, "query" : _cache["_query_"]};
	}
	/**
    * URLパラメータ取得
	* キャッシュしたURLパラメータより値を取得
    * @method getQueryParam
	* @param name {String}
    * @return {String} _cache["_query"_][name]
    */
	function getQueryParam(name){
		return _cache["_query_"][name];
	}

	/**
	* base64デコード
	* base64文字列をutf8にデコード
    * @method getDecodeString
	* @param str {String} (base64)
    * @return (utf8)
    */
	function getDecodeString(str){
		if(util.isEmpty(str)) return "";
		var res = str.replaceAll( ":eq:", "=");
		res = res.replaceAll(":amp:", "&");
		res = res.replaceAll(":sl:", "/");
		res = res.replaceAll(":hash:", "#");
		res = base64decode(res);
		res = utf8to16(res);
		return res;
	}
	/**
	* base64エンコード
	* utf8文字列をbse64形式にエンコード
    * @method getEncodeString
	* @param str {String} (utf8)
    * @return {String} (base64)
    */
	function getEncodeString(str){
		if(util.isEmpty(str)) return "";
		var res = utf16to8(str);
		res = base64encode(res);
		res = res.replaceAll("=", ":eq:");
		res = res.replaceAll("&", ":amp:");
		res = res.replaceAll("/", ":sl:");
		res = res.replaceAll("#", ":hash:");
		return res;
	}
	/**
	* 汎用グループ・汎用コード設定
	* システムで共通利用する汎用グループ・コードを取得
	* JSON形式でクロージャにキャッシュ
	*またブラウザストレージにキャッシュしリクエストを削減する
    * @method setGroupCode
    * @return {void} return nothing
    */
	function setGroupCode(){
		_cache["GROUP"] = util.getLocalData("GROUP");
		_cache["GROUP_CODE"] = util.getLocalData("GROUP_CODE");
		if(util.isEmpty(_cache["GROUP_CODE"]) || util.isEmpty(_cache["GROUP"])){
			var ret = getAjax(false, "/getgroupcode", {},
				function(result, st, xhr) {
					_cache["GROUP"] = [];
					_cache["GROUP_CODE"] = {};
					var data =  result["data"];
					if(data && data.length >=0){
						var count = data.length;
						for(var i=0;i<count;i++){
							var group = data[i]["GROUP_VAL"];
							var gname = data[i]["GROUP_NAME_ENC"];
							if(_cache["GROUP"].length<1 || _cache["GROUP"][_cache["GROUP"].length-1][0]!=group){
								_cache["GROUP"].push([group, gname]);
							}
							var code = data[i]["CODE_VAL"];
							var cname = data[i]["CODE_NAME_ENC"];
							var cremark = data[i]["CODE_REMARK_ENC"];
							var pgroup = data[i]["PARENT_GROUP"];
							var pcode = data[i]["PARENT_CODE"];
							if(!_cache["GROUP_CODE"][group]) {
								_cache["GROUP_CODE"][group] = {};
								_cache["GROUP_CODE"][group]["name"] = gname;
								_cache["GROUP_CODE"][group]["code"] = [];
							}
							_cache["GROUP_CODE"][group]["code"].push([code, cname, cremark, pgroup,pcode]);
						}
						util.setLocalData("GROUP", _cache["GROUP"]);
						util.setLocalData("GROUP_CODE", _cache["GROUP_CODE"]);
					}
				},
				function(xhr, st, err) {
					alert("getGroupCode\n"+err.message+"\n"+xhr.responseText);
				}, true
			);
		}
	}
	/**
	* 汎用グループ取得
	* [["group_code", "groupname"],
	* ["group_code", "groupname"],
	* ["group_code", "groupname"]]
    * @method getGroupData
    * @return {Array}
    */
	function getGroupData(){
		var _result = [];
		for(var i=0, n=_cache["GROUP"].length;i<n;i++){
			_result.push([_cache["GROUP"][i][0], getDecodeString(_cache["GROUP"][i][1])]);
		}
		return _result;
	}
	/**
	* 汎用コード取得
	* グループ配下のコードをすべて取得
    * @method getCodeData
	* @param groupval {String}
    * @return {Array} ["code_val", "code_name", "code_remark"],["code_val", "code_name", "code_remark"]..
    */
	/**
	* 汎用コード取得
	* コード（単一）の情報を取得
    * @method getCodeData
	* @param groupval {String}
	* @param codeval {String}
    * @return {Array} ["code_val", "code_name", "code_remark"]
    */
	function getCodeData(groupval, codeval){
		if(util.isEmpty(groupval)) return null;
		if(!_cache["GROUP_CODE"][groupval]) return null;
		var _result = [];

		if(util.isEmpty(codeval)){
			//return _cache["GROUP_CODE"][groupval]["code"];
			for(var i=0,n=_cache["GROUP_CODE"][groupval]["code"].length;i<n;i++){
				var _codeval = _cache["GROUP_CODE"][groupval]["code"][i][0];
				_result.push(getCodeData(groupval,_codeval));
			}
			return _result;
		}
		else {
			for(var i=0,n=_cache["GROUP_CODE"][groupval]["code"].length;i<n;i++){
				if(codeval == _cache["GROUP_CODE"][groupval]["code"][i][0]){
					for(var j=0, m=_cache["GROUP_CODE"][groupval]["code"][i].length;j<m;j++){
						var _val = _cache["GROUP_CODE"][groupval]["code"][i][j];
						if(j==1 || j==2) {
							//CODE_NAME , CODE_REMARKの場合
							_val = getDecodeString(_val);
						}
						_result.push(_val);
					}
					return _result;
				}
			}
		}
		return null;

	}
	/**
	* メッセージデータ取得
	* 汎用グループ[SYS_MSG]からmsgCodeよりタイトル、本文を取得する
	* 本文はparamを埋め込む
    * @method getMessage
	* @param msgCode {String}
	* @param [param] {String}
    * @return {JSON} {"title" : "messageTitle", "body" : "messageBody"}
    */
	function getMessage(msgCode, param){
		var p = param;
		//paramが文字列 p0|p1|p2の場合 、[p0.p1.p2]とする
		if(!util.isEmpty(param) && typeof param == "string") p = (param+"|").split("|");
		var title = "";
		var body = "";
		if(util.isHankaku(msgCode)){
			//半角の場合、メッセージコードとして扱う
			if(!_cache["GROUP_CODE"]) return null;
			if(!_cache["GROUP_CODE"]["SYS_MSG"]) return null;
			if(!_cache["GROUP_CODE"]["SYS_MSG"]["code"]) return null;
			var sysmsg = getCodeData("SYS_MSG");
			if(util.isEmpty(param)) param="";
			for(var i=0,n=sysmsg.length;i<n;i++){
				if(sysmsg[i][0]==msgCode){
					title = sysmsg[i][1];
					body = sysmsg[i][2];
					break;
				}
			}
		}
		else {
			//メッセージコードではない場合
			body = msgCode;
			if(util.isZenkaku(param)) title = param;
		}
		for(var i=0,n=p.length;i<n;i++){
			if(body.indexOf("{%")<0) break;
			body = body.replace("{%"+i+"}", p[i]);
		}
		return {"title":title, "body": body};
	}

	//---------------------------------------------------
	//ajax処理系～
	//---------------------------------------------------
	/**
	* Ajax GET Method
	* 実行途中はloadingを表示する
	* リクエストに応じて結果をキャッシュする
    * @method getAjax
	* @param [async] {Boolean}  同期処理とする場合:True
	* @param url {String}
	* @param request {JSON}
	* @param success {Function}  成功時コールバック関数
	* @param error {Function}  エラー時コールバック関数
	* @param [cacheReset] {Boolean}
    * @return {Object} defferd
    */
	function getAjax(async,url, request, success, error, cacheReset){
		var _request = requestDataParse(request);
		var key = url+JSON.stringify(_request);
		var now = +new Date();
		var passtime = _cache["userSetting"]["requestCacheTime"];

		if(cacheReset){
			_requestCache = [];
		}
		else {
			for(var i=_requestCache.length-1;i>=0;i--){
				if(!_requestCache[i]["key"]) continue;
				if(_requestCache[i]["key"]==key){
					if(now-_requestCache[i]["time"] > passtime || cacheReset){
						_requestCache.splice(i,1);
						break;
					}
					else {
						if(util.isFunction(success)) success(_requestCache[i]["result"], null,null);
						return true;
					}
				}
			}
		}
		loadStart();
		//console.log("ajax exec:"+key);
		var ret = $.ajax({
			headers: {'X-Requested-With': 'XMLHttpRequest'},
			type: "GET",
			async: async,
			cache: false,
			dataType: "JSON",
			url: (url),
			data: (_request)
		}).done(
			function(result, st, xhr) {
				loadStop();
				if(result["status"]=="success"){
					if(util.isFunction(success)) success(result, st, xhr);
					var cache = {"key" : key , "time":now, "result" : result, "request" : request}
					if(_requestCache.length >_cache["userSetting"]["requestCacheSize"]) _requestCache.splice(0,10);
					_requestCache.push(cache);
				}
				else {
					alertMessage(result["message"], result["description"], _logout);
					//10秒経過で自動ログアウト
					setTimeout(_logout, 10000);
				}
			}
		).
		fail(
			function(xhr, st, err) {
				loadStop();
				if(util.isFunction(error)) error(xhr, st, err);
			}
		);
		return ret;
	}
	/**
	* Ajax POST Method
	* 実行途中はloadingを表示する
    * @method postAjax
	* @param url {String}
	* @param request {JSON}
	* @param success {Function}  成功時コールバック関数
	* @param error {Function}  エラー時コールバック関数
    * @return {Object} defferd
    */
		function postAjax(url, request, success, error){
		loadStart();
		//console.log("ajax exec:"+url);
		var ret = $.ajax({
			headers: {'X-Requested-With': 'XMLHttpRequest'},
			type: "POST",
			cache: false,
			dataType: "JSON",
			url: (url),
			data: (request)
		}).done(
			function(result, st, xhr) {
				loadStop();
				_requestCache = [];
				if(util.isFunction(success)) success(result, st, xhr);
			}
		).
		fail(
			function(xhr, st, err) {
				loadStop();
				if(util.isFunction(error)) error(xhr, st, err);
			}
		);
		return ret;
	}
	/**
	* Ajax FileUpload Method
	* 実行途中はloadingを表示する
    * @method uploadAjax
	* @param url {String}
	* @param request {JSON}
	* @param success {Function}  成功時コールバック関数
	* @param error {Function}  エラー時コールバック関数
	* @param loading {Function}  アップロード中コールバック関数
    * @return {void} return nothing
    */
	function uploadAjax(url, request,  success, error, loading){
		loadStart();
		var ret=$.ajax({
			url: (url),
			data : request,
			type: "POST",
			contentType:false,
			processData: false,
			cache: false,
			xhr: function() {
				var xhrobj = $.ajaxSettings.xhr();
				if (xhrobj.upload) {
						xhrobj.upload.addEventListener('progress', function(event) {
							if(util.isFunction(loading)) loading(event);
						}, false);
				}
				return xhrobj;
			},
			success: function(result, st, xhr) {
				loadStop();
				_cache["_uploadFiles"] = {};
				if(util.isFunction(success)) success(result, st, xhr);
			},
			error: function(xhr, st, err) {
				loadStop();
				_cache["_uploadFiles"] = {};
				if(util.isFunction(error)) error(xhr, st, err);
			}
		});

	}
	//ajax file download
	/**
	* Ajax FileDownload Method
	* 実行途中はloadingを表示する
    * @method downloadAjax
	* @param url {String}
	* @param request {JSON}
	* @param success {Function}  成功時コールバック関数
	* @param error {Function}  エラー時コールバック関数
    * @return {void} return nothing
    */
	function downloadAjax(url, request,  success, error){
		var _request = requestDataParse(request);
		loadStart();
		var ret = $.ajax({
			headers: {'X-Requested-With': 'XMLHttpRequest'},
			secureuri:false,
			type: "GET",
			async: false,
			cache: false,
			url: url,
			dataType: "JSON",
			data: (_request)
		}).done(
			function(result, st, xhr) {
				loadStop();
				if(result["status"] =="success"){
					if(!util.isEmpty(result.url)){
						if(util.isFunction(success)) success(result, st, xhr);
					}
					else {
						alertMessage(result["message"], result["description"], null);
					}
				}
				else {
					alertMessage(result["message"], result["description"], null);
				}
			}
		).
		fail(
			function(xhr, st, err) {
				loadStop();
				if(util.isFunction(error)) error(xhr, st, err);
			}
		);
	}

	//filedownload usage hidden frame
	/**
	* fileDownload Method
	* hidden frameを利用し、POSTでファイルダウンロードする
	*
    * @method fileDownload
	* @param url {String}
    * @return {void} return nothing
    */
	function fileDownload(url) {
		var fileDownloadFrame = util.getFileName(url)+'frame',
		iframe = document.getElementById(fileDownloadFrame);
		if (iframe === null) {
			iframe = document.createElement('iframe');
			iframe.id = fileDownloadFrame;
			iframe.style.display = 'none';
			document.body.appendChild(iframe);
		}
		iframe.src = url;
	};

	/**
	*  JSONパラメータ拡張
	*  JSONパラメータa にbの要素を追加する
    * @method extendRequestJson
	* @param a {JSON}
	* @param b {JSON}
	* @param [overwrite] {Boolean}  trueの場合、必ずbを設定する
    * @return {JSON}
    */
	function extendRequestJson(a, b, overwrite){
		if(a && b){
			for(var key in b){
				if(!overwrite && util.isEmpty(b[key])) continue;
				if(!overwrite && typeof b[key] == "object" && (util.isEmpty(b[key]) || !b[key][0])) {
					//配列要素が存在しない場合、設定しない
					continue;
				}
				a[key] = b[key];
			}
			return a;
		}
		return {};
	}
	/**
	* GETメソッド向けリクエストパラメータを調整
	* 要素のない配列、改行文字を含む文字列を除去
    * @method requestDataParse
	* @param a {JSON}
    * @return {JSON} adjust a
    */
	function requestDataParse(a){
		var _ret = {};
		for(var key in a){
			if(typeof a[key] == "object" && (util.isEmpty(a[key]) || !a[key][0])) {
				//配列要素が存在しない場合、設定しない
				continue;
			}
			if(typeof a[key] == "string" && a[key].indexOf("\n")>=0){
				//改行文字含む文字列は含めない
				continue;
			}
			_ret[key] = a[key];
		}
		return _ret;
	}

	/**
	* メール送信を即時送信するリクエストを送信する
	* @method sendMail
	* @param query_code {String}
	* @return {Boolean} true(success) or false(failed)
	*/
	function sendMail(query_code){
		var _success = false;
		postAjax("/sendmail/"+query_code, {},
			function(result, st, xhr) {
				_success = true;
			},
			function(xhr, st, err) {
				errorMessage("sendMail\n"+err.message+"\n"+xhr.responseText);
			}
		);
		return _success;
	}
	/**
	* セッションタイムアウトエラーを表示し、ログアウトする
    * @private
    * @method errorMessage
	* @param msg {String}
    * @return {void} return nothing
    */
	function errorMessage(msg){
		dom.errorMessage(msg, _logout);
	}
	/**
	* メッセージコード、パラメータより、メッセージを表示
    * @method alertMessage
	* @param messageCode {String}
	* @param messageParam {String}
	* @param callback {Function}
    * @return {void} return nothing
    */
	function alertMessage(messageCode, messageParam, callback){
		var message = getMessage(messageCode, messageParam);
		if(_isDebug) message["body"] = message["body"]+"<br>"+messageParam;
		if(util.isEmpty(message["title"])) message["title"] = messageCode;
		if(util.isEmpty(message["body"])) message["body"] = messageParam;
		dom.alertMessage(message["title"], message["body"], callback);
	}
	/**
	* メッセージコード、パラメータより、確認メッセージを表示
    * @private
	* @method alertMconfirmMessageessage
	* @param messageCode {String}
	* @param messageParam {String}
	* @param callback {Function}
    * @return {void} return nothing
    */
	function confirmMessage(messageCode, messageParam, callback){
		var message = getMessage(messageCode, messageParam);
		dom.confirmMessage(message["title"], message["body"], callback);
	}
	root.service = $.extend({}, root.service, public_method);

})(this);