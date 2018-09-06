/**
* domプラグイン
* dom操作、dom入力値設定・取得に関する操作をまとめる
* utilを利用
* @namespace
* @class dom
**/
(function(root, undefined) {
	"use strict";
	var _dialog = {};
	var _cache = {};
	var _isDebug = true; //trueの場合、errorMessageで、詳細が表示されるようになる
	var public_method ={
		setEditForm : setEditForm,
		textFormat : textFormat,
		selectFormLoad : selectFormLoad,
		getOptionString : getOptionString,
		setPasswordForm : setPasswordForm,
		setBarcodeForm : setBarcodeForm,
		setPostnoForm : setPostnoForm,
		setCalenderForm : setCalenderForm,
		setNumberForm : setNumberForm,
		paramPageLoad : paramPageLoad,
		confirmMessage : confirmMessage,
		alertMessage : alertMessage,
		errorMessage : errorMessage,
		setFileForm : setFileForm
	};

	/**
	* テキストフォーマット
	* 文字列にパラメータを埋め込み返却する
	* @method textFormat
	* @param {String} textSource
	* @param {JSON} data
	* @return (String)
	*/
	function textFormat(textSource, data){
		if(typeof data != "object") return textSource;
		for(var key in data){
			if(textSource.indexOf("#") < 0) break;
			if(textSource.indexOf("#"+key+"#") < 0) continue;
			textSource = textSource.replace_all("#"+key+"#", data[key]);
		}
		return textSource;
	}
	/**
	* selectフォームの選択肢を動的生成する
	* @method selectFormLoad
	* @param formId {String}
	* @param self {Object} select DOM
	* @param [selectValue] {String} select初期値
	* @param [template] {String} 選択肢のdomテンプレート
	* @param [param] {JSON} クエリ実行する際のパラメータで利用
	* @return (voide)
	*/
	function selectFormLoad (formId, self, selectValue, template, param){
		var accesskey = $(self).attr("accesskey");
		var target = $(self).attr("target");
		var name = $(self).attr("name");
		var required = $(self).attr("required");
		var alt = $(self).attr("alt");
		var suggest = $(self).attr("suggest");
		var form = "";
		var val = null;
		switch(accesskey){
			case "m_query":
				var query = $(self).attr("query");
				var code_field = $(self).attr("code_field");
				var name_field = $(self).attr("name_field");
				var param_field = $(self).attr("param_field");
				var data = front.getFormValue(formId);
				var _req = {};
				if(!util.isEmpty(param_field)){
					if(data[param_field] && !util.isEmpty(data[param_field])) _req[param_field] = data[param_field];
					if(param[param_field] && !util.isEmpty(param[param_field])) _req[param_field] = param[param_field];
				}
				//フォーム作成後に値をセットするため、同期取得する
				service.getAjax(false, "/get/"+query, _req,
					function(result, st, xhr) {
						var data =  result["data"];
						var name = $(self).attr("name");
						var required = $(self).attr("required");
						form = getOptionString(name, data, code_field, name_field, template, required);
						if(data.length > 0){
							if(form!="") val = data[0][code_field];
						}
					},
					function(xhr, st, err) {
						errorMessage("selectFormLoad\n"+err.message+"\n"+xhr.responseText);
					}
				);
				if(target && target!="") {
					//関連する親フォーム(target)指定があれば、changeイベントにて、フォームの内容を変更する
					group = $('*[name="'+target+'"]').val();
					if(util.isEmpty($('*[name="'+target+'"]').attr("suggest"))) $('*[name="'+target+'"]').unbind("change");
					$('*[name="'+target+'"]').on("change", function(e){ selectFormLoad(formId, $(self) , selectValue, template, param ); });
				}
				break;
			case "m_group":
				//汎用コード（グループ）からデータ取得
				var _group = service.getGroupData();
				form = getOptionString(name, _group, 0, 1, template, required);
				if(_group.length > 0)	val = _group[0][0];
				break;
			case "m_code":
				//汎用グループからデータ取得、グループフォームとの親子連携付き
				var group = "";
				if(target && target!="") {
					//関連する親フォーム(target)指定があれば、changeイベントにて、フォームの内容を変更する
					group = $('*[name="'+target+'"]').val();
					$('*[name="'+target+'"]').unbind("change");
					$('*[name="'+target+'"]').on("change", function(e){
						selectFormLoad(formId, $(self) , selectValue, template, param );
					});
				}
				else if(alt && alt!="") group = alt;
				else group=$(self).attr("name");
				if(group && group!="") {
					var _groupData = service.getCodeData(group);
					if(_groupData != null){
						form = getOptionString(name, _groupData, 0, 1, template, required);
						val = _groupData[0][0];
					}
				}
				break;
				case "year":
					var _maxlength = $(self).attr("maxlength");
					var _from = (util.nowDate().substring(0,4))|0;
					val = "";
					if(util.isEmpty(_maxlength)) _maxlength = 120;
					var data = [];
					var _to = _from - _maxlength;
					while(_maxlength >= 0){
						var _year = _from - _maxlength;
						data.unshift([_year, _year+"年 "]);
						_maxlength--;
					}
					form = getOptionString(name, data, 0, 1, template, required);
					break;
				case "month":
					var data = [];
					for(var i=1;i<13;i++){
						var _text = i+"月";
						if(i<10) _text = " "+i+"月";
						data.push([i,  _text]);
					}
					val = "";
					form = getOptionString(name, data, 0, 1, template, required);
					break;
				case "day":
					var data = [];
					for(var i=1;i<32;i++){
						var _text = i+"日";
						if(i<10) _text = " "+i+"日";
						data.push([i,  _text]);
					}
					val = "";
					form = getOptionString(name, data, 0, 1, template, required);
					break;
				default:
					var val = $(self).attr("value");
					var title = $(self).attr("title");
					form = getOptionString(name, [[val, title]], 0, 1, template, required);
					break;
		}

		$(self).html(form);
		if(!util.isEmpty(selectValue)) val = selectValue;
		if(required && val != null){
			if(util.isEmpty(template)){
				//select
				$(self).val(val).change();
			}
			else  {
				//checkbox,radio
				//$("*[name="+name+"]", self).val([val]).change();
				$("*[name="+name+"]", self).change();
			}
		}
	}

	/**
	* option dom取得
	* codes	から、valueとnameのフィールドを指定し、option dom文字列を生成する
	* @method getOptionString
	* @param id {String}
	* @param codes {String}
	* @param valueField {String}
	* @param nameField {String}
	* @param [template] {String}
	* @param [noEmpty] {Boolean} noEmpty trueの場合(選択無し)を先頭に追加
	* @return (String)
	*/
	function getOptionString(id, codes, valueField, nameField, template, noEmpty){
		if(util.isEmpty(template)) template = '<option value="#VAL#">#NAME#</option>';;
		var form = "";
		var _form = "";
		if(!noEmpty){
			_form = template;
			_form = textFormat(_form , {"ID" :  id, "VAL" :  "", "NAME" :  "(選択無し)"});
			form += _form;
		}
		if(codes){
			for(var i=0,n=codes.length;i<n;i++){
				_form = template;
				_form = textFormat(_form , {"ID" : id, "VAL" : codes[i][valueField], "NAME" : codes[i][nameField]});
				form += _form;
			}
		}
		return form;
	}
	/**
	* パスワードフォーム設定
	* minlength=8,maxlength=32,placeholder=半角英数8文字以上
	* @method setPasswordForm
	* @param formId {String}
	* @return {void} return nothing
	*/
	function setPasswordForm(formId){
		$("input[type=password]", $("#"+formId)).each(function(){
			$(this).attr("title", "使用可能な文字は、半角英数です");
			$(this).attr("placeholder", "半角英数８文字以上");
			$(this).attr("minlength", "8");
			$(this).attr("maxlength", "32");
		});
	}
	/**
	* 郵便番号入力フォーム設定
	* uitype:jpostalに対し、jquery.jpostalを設定する
	* @method setPostnoForm
	* @param formId {String}
	* @return {void} return nothing
	*/
	function setPostnoForm(formId){
		$("input[type=text][uitype=jpostal]", $("#"+formId)).each(function(){
			var name = $(this).attr("name");
			var _postcode = [];
			_postcode.push('input[name="'+name+'"]');
			var _response = {};
			$("input[type=text][accesskey="+name+"]").each(function(){
				var name = $(this).attr("name");
				_response['input[name="'+name+'"]'] = $(this).attr("alt")
			});
			$(this).jpostal({
				postcode : _postcode,
				address : _response
			});
		});
	};
	/**
	* バーコードフォーム設定
	* span[type:barcode]に対し、jquery.barcodeを設定する
	* @method setBarcodeForm
	* @param formId {String}
	* @return {void} return nothing
	*/
	function setBarcodeForm(formId){
		$("div[type=barcode]", $("#"+formId)).each(function(){
			var inputtype = $(this).attr('inputtype');
			var maxl = $(this).attr('maxlength');
			var w = $(this).width;
			var h = $(this).height;
			if(util.isEmpty(w)) w="200px";
			if(util.isEmpty(h)) h="80px";
			var template = $(this).attr('template');
			var val = $(this).html();
			if(inputtype=="integer"){
				//入力値＝数値の場合、maxlengthにより0埋め
				if(util.isEmpty(val)) val="0";
				if(!util.isEmpty(maxl) && maxl>1) val = util.leftPadZero(val,maxl);
			}
			//template指定があれば、#val#の箇所にコードを埋め込む
			if(!util.isEmpty(template)) val = template.replace("#val#", val);

			$(this).html(val);
			$(this).attr('title', val);
			var alt = $(this).attr('alt');

			$(this).width(w).height(h).barcode();
			if(!util.isEmpty(alt)) {
				//altに指定した文言（ラベル）を追加
				$(this).prepend('<div class="label">'+alt+'</div>');
			}
		$(this).append('<div class="label">'+val+'</div>');
		$("img", $(this)).css("width", "100%");
			$("img", $(this)).css("height", "90%");
		});
	};
	/**
	* 数値入力フォーム設定
	* uitype:spinnerに対し、jqueryUI.spinnerを設定する
	* @method setNumberForm
	* @param formId {String}
	* @return {void} return nothing
	*/
	function setNumberForm(formId){
		$("input[type=text][uitype=spinner]", $("#"+formId)).each(function(){
			var val = $(this).val();
			var max = $(this).attr("maxvalue");
			var min = $(this).attr("minvalue");
			var step = $(this).attr("stepvalue");
			var inputtype = $(this).attr("inputtype");
			var option = {};
			if(!util.isEmpty(min)) {
				option["min"] = min;
				if(util.isEmpty(val) || (val|0) < (min|0)){
					val = min;
				}
			}
			if(!util.isEmpty(max)) {
				option["max"] = max;
				if(util.isEmpty(val) || (val|0) > (max|0)){
					val = max;
				}
			}
			if(!util.isEmpty(step)) option["step"] = step;
			$(this).spinner(option);
			if(util.isEmpty(inputtype)) $(this).attr("inputtype", "number");
			$(this).val(val);
		});
	};
	/**
	* 日付入力フォーム設定
	* uitype:datepickerに対し、jqueryUI.datepickerを設定する
	* @method setCalenderForm
	* @param formId {String}
	* @return {void} return nothing
	*/
	function setCalenderForm(formId){
		$("input[type=text][uitype=datepicker]", $("#"+formId)).each(function(){
			var val = $(this).val();
			var max = $(this).attr("maxvalue");
			var min = $(this).attr("minvalue");
			var inputtype = $(this).attr("inputtype");
			var defaultDate = $(this).attr("defaultDate");
			var option = {};
			if(!util.isEmpty(val)){
				if(val.indexOf(",")>=0){
					var dateparam = val.split(",");
					if(dateparam.length==3) val = util.nowDate(dateparam[0],dateparam[1],dateparam[2]);
				}
			}
			if(!util.isEmpty(min)){
				if(util.isDate(min)) {
					option["minDate"] = min;
				}
				else if(min.indexOf(",")>=0){
					var dateparam = min.split(",");
					if(dateparam.length==3) option["minDate"] = util.nowDate(dateparam[0],dateparam[1],dateparam[2]);
				}
			}
			if(!util.isEmpty(max)){
				if(util.isDate(max)) {
					option["maxDate"] = max;
				}
				else if(max.indexOf(",")>=0){
					var dateparam = max.split(",");
					if(dateparam.length==3) option["maxDate"] = util.nowDate(dateparam[0],dateparam[1],dateparam[2]);
				}
			}
			if(!util.isEmpty(defaultDate)){
				if(util.isDate(defaultDate)) {
					option["defaultDate"] = defaultDate;
				}
				else if(defaultDate.indexOf(",")>=0){
					var dateparam = defaultDate.split(",");
					if(dateparam.length==3) option["defaultDate"] = util.nowDate(dateparam[0],dateparam[1],dateparam[2]);
				}
			}
			$(this).datepicker(option);
			if(util.isEmpty(inputtype)) $(this).attr("inputtype", "date");
			if(util.isDate(val)) $(this).val(val);
		});
	};
	/**
	* JSON変数からフォームを作成する
	* @method paramPageLoad
	* @param fields {JSON}
	* @param buttons {JSON}
	* @param listTable {Object}
	* @return {String} innerHTML
	*/
	function paramPageLoad(fields, buttons, listTable){

		var _tpl = [
					'<div class="card-body">',
						'#_rows_#',
					'</div>',
					'<div class="card-body">',
						'#_buttons_#',
					'</div>'
					].join("")

		var _row = [
			'<div class="row">#_form_#',
			'</div>'
		].join("");


		var _commonform = [
			'<div class="#class#">',
				'<label for="#field#">#field_title#</label>',
				'<div class="form-group" #option#>#_form_#',
				'</div>',
			'</div>'
		].join("");

		var _form ={
			"text" : '<input #attr# class="form-control" />',
			"textarea" : '<textarea class="textarea col-12" rows=5 #attr#>#default#</textarea>',
			"select" : '<select class="form-control select2" #attr# style="width:100%;"></select>',
			"radio" : [
				'<label class="mr-2">',
				'<input type="radio" class="flat-red mr-1" name="#ID#" id="#ID##VAL#" value="#VAL#"">',
				'#NAME#',
				'</label>'
				].join("")
			,
			"checkbox" : [
				'<label class="mr-2">',
				'<input type="checkbox" class="flat-red mr-1" name="#ID#" id="#ID##VAL#" value="#VAL#" #attr# default="#default#">',
				'#NAME#',
				'</label>'
				].join("")
			,
			"label" : '<h5 for="l3" class="ml3 lbl" #attr#>#default#</h5>',
			"image" : '<img class="mw-64px" #attr# src="#default#"></img>',
			"link" : '<a #attr#>#default#</a>',
			"file" : [
						'<span id="filename" alt="#field#" accesskey="filename" class="mr-2">ファイルが指定されていません</span>',
						'<input type="file" id="#field#" #attr# class="hide"/>',
						'<button href="javascript:void(0);" accesskey="fileopen" alt="#field#" class="btn btn-sm btn-info mr-1">',
							'<i class="fa fa-folder-open mr-1"></i>参照',
						'</button>',
						'<button href="javascript:void(0);" accesskey="fileclear" alt="#field#" class="btn btn-sm btn-info">',
							'<i class="fa fa-times mr-1"></i>クリア',
						'</button>'].join("")
		};
		var _requiredText = '<span class="right badge badge-danger ml-1">必須</span>';
		//存在する場合のみ追加する属性
		var _attr = ["target", "alt", "accesskey",
					"name", "type","placeholder","data-placeholder", "multiple",
					"style","uitype","inputtype","value",
					"maxlength","minlength","maxvalue","minvalue","stepvalue","defaultDate", "defaultSelect",
					"query", "code_field", "name_field", "param_field",
					"scan", "scan_field", "scaned",
					"query_check", "query_check_nodata", "query_check_error",
					"equal", "equalerror", "greater", "greatererror", "less", "lesserror"];
		var rowDom = [];
		for(var i in fields ){
			var row = _row;
			var field = fields[i];
			if(util.isEmpty(field["type"])) continue;
			var _type = field["type"];
			field["option"] = "";
			if(_type=="number" || _type=="hidden" || _type=="date" || _type=="postno" || _type=="password" || _type=="listcheckbox"){
				//
				switch(_type){
					case "postno" :
						field["type"] = "text";
						field["style"] = "border:solid 1px #CCC";
						field["uitype"] = "jpostal";
						break;
					case "listcheckbox":
						field["type"] = "hidden";
						field["multiple"] = "multiple";
						var selectData = listTable.getSelectData(field["alt"]);
						field["default"] = selectData;
						break;
				}
				_type="text";
			}
			field["_type"] = _type;
			var form = _form[_type];
			var attr = "";
			if(_type=="select" && field["placeholder"]){
				field["data-placeholder"] = field["placeholder"];
				delete field["placeholder"];
			}

			for(var j=0,m=_attr.length;j<m;j++){
				if(!util.isEmpty(field[_attr[j]])){
					attr+=' '+_attr[j]+'="'+field[_attr[j]]+'"';
				}
			}


			if(util.isEmpty(field["class"])) field["class"] = "col-lg-12";
			field["field_title"] = "　";

			if(!util.isEmpty(field["text"])) field["field_title"] = field["text"];
			if(!util.isEmpty(field["subtitle"])) field["field_title"] = field["subtitle"];

			if(!util.isEmpty(field["field"])){
				if(_type=="label" || _type=="link") attr+=' id="'+field["field"]+'"';
				else attr+=' name="'+field["field"]+'"';
			}
			if(!util.isEmpty(field["default"])){
				if(form.indexOf("#default#")>=0) form = form.replace("#default#", field["default"]);
				else attr+=' value="'+field["default"]+'"';
			}
			if(form.indexOf("#default#")>=0) form = form.replace("#default#", "");

			if(!util.isEmpty(field["edittype"])) {
				if(!util.isEmpty(field["edittype"]["new"])) {
					attr+=' new="'+field["edittype"]["new"]+'"';
				}
				if(!util.isEmpty(field["edittype"]["edit"])) {
					attr+=' edit="'+field["edittype"]["edit"]+'"';
				}
			}

			if(field["type"]!="hidden") {
				form = _commonform.replace("#_form_#", form);
				if(!util.isEmpty(field["required"])){
					//項目見出しに必須の表示をつける
					//フォームが複数ある場合は、最初フォーム設定の有無にて決定する
					form = form.replace('#field_title#', '#field_title#'+_requiredText);
					attr+=' required="'+field["required"]+'"';
				}
				if(field["type"] == "file") {
					field["class"]+=' dragdropupload';
				}
				form = form.replace("#class#", field["class"]);
			}

			field["attr"] = attr;
			if(_type=="radio" || _type=="checkbox"){
				//#attr# default="#default#
				field["option"] = attr;
				field["option"] += ' uitype="radio"';
				field["option"] += ' required="true"';
				field["option"] += ' value="'+field["value"]+'"';
				field["option"] += ' title="'+field["placeholder"]+'"';
			}
			row = textFormat(row, field);
			form = textFormat(form, field);
			/*
			if(field["type"]=="description"){
				rowDom.push({ "row" : "#_form_#", "form" : [form]});
			}
			*/
			if(util.isEmpty(field["text"]) || field["type"]=="hidden"){
				if(rowDom.length>0) rowDom[rowDom.length-1]["form"].push(form);
				else rowDom.push({ "row" : "#_form_#", "form" : [form]});
			}
			else {
				rowDom.push({"row" : row, "form" : [form]});
			}
		}
		var rows = "";
		for(var i=0,n=rowDom.length;i<n;i++){
			var form = rowDom[i]["form"].join("");
			rows+=rowDom[i]["row"].replace("#_form_#", form);
		}

		var _button_tpl = [
			'<button type="button" class="btn btn-outline-primary mr-2" #attr#>',
				'<i class="fa fa-#class# mr-1"></i>#text#',
			'</button>'
		].join('');
		var _buttonHtml = "";
		for(var i in buttons ){
			var button = buttons[i];
			//_buttonHtml += '<a href="javascript:void(0);" class="btn icons" style="padding:0 12px 0 2px;display:inline-block;font-size:12px;"';
			_attr = "";
			for(var attr in button){
				if(attr=="text") continue;
				if(attr=="class") continue;
				if(!util.isEmpty(button[attr])){
					_attr+=' '+attr+'="'+button[attr]+'"';
				}
			}
			button["attr"] = _attr;
			_buttonHtml += textFormat(_button_tpl, button);
		}
		var res = textFormat(_tpl, {"_rows_" : rows, "_buttons_" : _buttonHtml});
		return res;
	}
	/**
	* 件名、内容を元に、確認メッセージを表示
    * @method confirmMessage
	* @param {String} title
	* @param {String} body
	* @param {Function} callback
    * @return {void} return nothing
    */
	function confirmMessage(title, body, callback){
		_message(title, body, "confirm",callback);
	}
	/**
	* セッションタイムアウトエラーを表示する
    * @method errorMessage
	* @param {String} msg
	* @param {Function} callback
    * @return {void} return nothing
    */
	function errorMessage(msg, callback){
		//_alertMessage("E_AUTH_FAIL",  msg, service.logout);
		var _msg = "セッションタイムアウトしました。<br>ログアウト後、もう一度ログインしてください";
		if(_isDebug) {
			msg = msg.replace_all("\n", "<br>");
			_msg+=+"<br>"+msg
		}
		_message("システムエラー",
			_msg,
			"alert",
			callback
			);
		if(!_isDebug) {
			//10秒経過で自動ログアウト
			setTimeout(callback,  10000);
		}
	}
	/**
	* 件名、内容を元に、メッセージを表示
    * @method alertMessage
	* @param {String} title
	* @param {String} body
	* @param {Function} callback
    * @return {void} return nothing
    */
	function alertMessage(title, body, callback){
		_message(title, body,"alert",callback);
	}
	/**
	* 件名、内容を元に、メッセージを表示
    * @method _message
	* @private
	* @param {String} title
	* @param {String} body
	* @param {String} url
	* @param {Function} callback
    * @return {void} return nothing
    */
	function _message(title, body, type, callback){
		$("button[accesskey]", $("#message")).hide();
		switch(type){
			case "confirm":
				$("button[accesskey=yes]", $("#message")).show();
				$("button[accesskey=no]", $("#message")).show();
				break;
			case "alert":
				$("button[accesskey=ok]", $("#message")).show();
				break;
			default:
		}
		$("button.btn[accesskey]", $("#message")).click(function(e){
			var alt = $(this).attr("alt");
			var accesskey = $(this).attr("accesskey");
			var target = $(this).attr("target");
			var type = $(this).attr("type");
			//console.log("accesskey="+accesskey+",alt="+alt+",target="+target);
			switch(accesskey){
				case "yes":
				case "ok":
					$('#message').modal('hide');
					if(util.isFunction(_cache["__callback"])) _cache["__callback"]();
					break;
				case "no":
					$('#message').modal('hide');
					_cache["__callback"] = null;
					break;
			}
			e.preventDefault();
		});
		$("#message .modal-body").html(body);
		$("#message .modal-title").html(title);
		$('#message').modal('show');
		_cache["__callback"] = callback;
	}



	//引数(json)を登録用のフォームに値をセット
	function setEditForm(data, form, _isUpdate){
		$("input[type=text], textarea", $("#"+form)).val("");
		$("input[type=radio],input[type=checkbox]", $("#"+form)).prop("checked", false);
		if(data){
			$("input, textarea, select", $("#"+form)).each(function(){
				var field = $(this).attr("name");
				var tag = $(this).prop("tagName");
				var formControl = $(this).attr("new");
				var type = $(this).attr("type");
				var inputtype = $(this).attr("inputtype");
				if(_isUpdate) formControl = $(this).attr("edit");
				var isset = true;
				var val = null;
				if(type == "checkbox" || type=="radio"){
					val = $("input[name="+field+"]:checked").val();
					if(!val && $(this).attr("defaultSelect")){
							val = $(this).attr("defaultSelect");
					}
				}

				if(!util.isEmpty(formControl)){
					switch(formControl){
						case "clear":
							isset=false;
							break;
						case "disabled":
							$(this).attr("disabled", "true");
							break;
						case "hidden":
							$(this).attr("disabled", "true");
							$("dl:has("+tag+"[name="+field+"])").hide();
							break;
					}
				}

				if(isset && field && field in data) {
					var p = $("<p></p>");
					p.html(data[field]);
					val = p.text();
				}

				if(val !== null){
					switch(type){
						case "radio":
						case "checkbox":
							$(this).val([val]).change();
							break;
						case "select":
							$(this).val(val);
							$(this).change();
						default:
							$(this).val(val);
					}
				}
				if(!util.isEmpty(inputtype)){
					$(this).blur();
				}

				var accesskey = $(this).attr("accesskey");
				var alt = $(this).attr("alt");
				if(type=="hidden" && accesskey=="fileid" && !util.isEmpty(alt)){
					if($("input[name="+alt+"]").attr("type")=="file"){
						setFileForm(form, alt, val);
					}
				}
			});
			$("div, span, td, .lbl", $("#"+form)).each(function(){
				var field = $(this).attr("id");
				var type =  $(this).attr("type");
				var accesskey =  $(this).attr("accesskey");
				var alt =  $(this).attr("alt");

				if(util.isEmpty(field)) return;
				if(!(field in data)) return;
				var val =data[field];
				if(type=="filesize"){
					val = util.setFileUnit(val);
				}
				if(typeof val === 'string' && val.indexOf("\n")>=0) val = val.replace_all("\n", "<br>");

				if(!util.isEmpty(accesskey) && !util.isEmpty(val)){
					switch (accesskey){
						case "m_code":
							var group = field;
							if(!util.isEmpty(alt)) group = alt;
							var _codedata = service.getCodeData(group, val);
							if(_codedata != null){
								val = _codedata[1];
							}
							break;
						}
				}
				$(this).html(val);
			});
			$("a", $("#"+form)).each(function(){
				var field = $(this).attr("id");
				if(field && field in data) {
					$(this).html(data[field]);
					var type =  $(this).attr("type");
					var url = data[field];
					if(type=="fileid"){
						url = "/download/"+url;
					}
					$(this).attr("href", url);
				}
			});
		}
	}

	function setFileForm(formId, name, fileid){
		if(util.isEmpty(formId)) return;
		if(util.isEmpty(name)) return;
		if(util.isEmpty(fileid)) return;
		var url = "/download/"+fileid;
		$("a[alt="+name+"][accesskey=fileid]", $("#"+formId)).html(fileid);
		$("a[alt="+name+"][accesskey=fileid]", $("#"+formId)).attr("href", url);
		$("input[type=hidden][alt="+name+"][accesskey=fileid]", $("#"+formId)).val(fileid);
		$("img[alt="+name+"][accesskey=preview]", $("#"+formId)).attr("title", fileid);
		$("img[alt="+name+"][accesskey=preview]", $("#"+formId)).attr("src", "/getfile/"+fileid);
		$("img[alt="+name+"][accesskey=preview]", $("#"+formId)).show();

		/*
		$("input[type=hidden]", $("#"+_currentForm)).each(function(){
			var field = $(this).attr("name");
			var val = $("span[accesskey='"+field+"']", $("#"+_currentForm)).html();
			if(field=="filesize") val = result["fsize"];
			if(field=="fileid") val = fileid;
			if(!util.isEmpty(val)) $(this).val(val);
			else {
				val = $("a[accesskey="+field+"]", $("#"+_currentForm)).html();
				if(!util.isEmpty(val)) $(this).val(val);
			}
		});
		*/
	}

	root.dom = $.extend({}, root.dom, public_method);

})(this);
