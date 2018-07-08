/**
* questionnaire
* アンケート画面共通プラグイン
* @namespace
* @class questionnaire
**/
$(function() {
    //__theme_id = テーマID、__display_order＝設問の表示ページ数
    //main : アンケートページは、テーマIDと、表示ページの設定が必要
    var __theme_id, __display_order;
    var __cache = {};
    var __serviceUrl = "/survey";


    //初期処理実行
    init();

    $("button.submit_btn").on("click", function(){
        var isSuccess = front.validateFormValue("content .inner");
        if(!isSuccess) return;
        $("#content form").submit();
    });


    /**
    * 初期処理
    * @method init
    * @return {void} return nothing
    */
    function init(){
        //プログレスバーの表示
        if($("#progressbar").length){
            var answer_count =$('input[name="answer_count"]').val();
            var max_answer_count =$('input[name="max_answer_count"]').val();
            $("#progressbar").progressBar({
                percent: Math.floor((answer_count / max_answer_count)*100),
                split: 0,
                width: "100%",
                height: "25px"
            });
        }
        var _req = front.getFormValue("content .input_content");
        __theme_id = _req["page"]|0;
        __display_order =_req["answer_display_order"];
        get_questionnaire_data(_req, function(array_theme, array_theme_question, array_answer){
            var c = 0;
            __cache["array_theme"] = array_theme;
            __cache["array_theme_question"] = array_theme_question;
            __cache["array_answer"] = array_answer;

        });
        refresh(__theme_id, __display_order);
    }
    /**
    * アンケートフォーム再表示
    * @method refresh
    * @param theme_id {Int}
    * @param display_order {Int}
    * @return {void} return nothing
    */
    function refresh(theme_id, display_order){
        //設問内容部分の設定
        set_question_form(__cache["array_theme"] , __cache["array_theme_question"] , __cache["array_answer"],theme_id, display_order );
        if ( $('.js_selector_toggle').length ) {
            $('.js_selector_toggle').on("change",function(){
            	var togglename = $(this).find(":selected").attr("data-toggle")
    	        var targetid = "#" + togglename;
            	if(togglename != undefined){
    	            $(".toggle_block").hide()
    	            $(targetid).show()
            	}else{
            		$(".toggle_block").hide()
            	}
            }).trigger('change')
        }

    	//追加input
    	if($(".js_addcheck input[type=checkbox]").length){
    		var addinputs = $('.js_addcheck input[type=checkbox]:checked');
    		$('.js_addcheck input[type=checkbox]').on('change',function(){
    			var target = $(this).parent().find(".js_inputadd")
    			if($(this).prop("checked") && target.length){
    				target.show()
    			}else{
    				target.hide()
    			}
    		})
    		if(addinputs.length){
    			addinputs.each(function(){
    				var target = $(this).parent().find(".js_inputadd")
    				if(target.length){
    					target.show()
    				}else{
    					target.hide()
    				}
    			})
    		}
    	}

    	//checkbox parent check
        if($(".js_parentcheck").length){
            $('.js_parentcheck input[type=checkbox]').on('change',function(e){
                var parentwrp =  $(this).parents("ul");
                var parentcheck = parentwrp.data("parent");
                if($("#" + parentcheck).prop('checked') == true && parentcheck == $(this).attr("id")){
                    $(this).prop("checked","checked");
                    $(this).parents("li").addClass("selected");
                    $(parentwrp).find("li:not(.selected) input[type=checkbox]").prop({"checked":"",disabled:"disabled"});
                    $(parentwrp).find("li:not(.selected) .js_inputadd").hide();
                    $(parentwrp).find("li:not(.selected)").addClass("disabled");
                }else if($("#" + parentcheck).prop('checked') == false && parentcheck == $(this).attr("id")){
                    $(parentwrp).find("li input[type=checkbox]").prop({"checked":"",disabled:""});
                    $(parentwrp).find("li").removeClass("selected").removeClass("disabled");
                }
            });
        }

    	//いいえclose
    	if($(".js_opencheck").length){
    		$('.js_opencheck input[type=radio]').on('change',function(e){
    			var close = $(this),
    				closetarget = close.data("open"),
    				closechk = close.attr("data-open")
                $(".js_opencontent").hide()
                $(".js_opencontent input").prop("disabled", true);
                $(".js_opencontent select").prop("disabled", true);
    			if(closechk){
    				$(closetarget).show()
                    $("input", $(closetarget)).prop("disabled", false);
                    $("select", $(closetarget)).prop("disabled", false);
    			}else{
    				$(closetarget).hide()
    			}
    		})
    		if($('.js_opencheck input[type=radio]:checked').data("open")){
    			$( $('.js_opencheck input[type=radio]:checked').data("open")).show()
    		}
    	}

    	//input追加処理
    	if($(".js_inputadd").length){
    		$(".js_inputadd").each(function(){
    			var wrap = $(this),
    				lis = $(this).find(".js_inputaddwrp"),
    				btn = $(this).find(".js_inputaddbtn"),
    				cnt = 1

    			//独自表示件数取得
    			if($(this).data("add")){
    				cnt = $(this).data("add")
    			}
    			//非表示項目がないときボタン削除
    			if(!lis.children(".js_hidden").length){
    				btn.hide()
    			}
    			//独自表示件数取得
    			$(btn).on("click",function(){
    				lis.children(":hidden").each(function(i){
    					if(i < cnt){
    						$(this).show().removeClass("js_hidden")
    					}
    				})
    				if(!lis.children(":hidden:first").length){
    					btn.hide()
    				}
    			})
    		})
    	}
        //日付フォームの場合はデフォルト値に現在日時を設定する
        $('select[accesskey="year"]').val( (new Date()).getFullYear());
        $('select[accesskey="month"]').val( (new Date()).getMonth()+1);
        $('select[accesskey="day"]').val( (new Date()).getDate());
        //戻るボタン
        $(".box_back_header .back_btn").on("click", function(){
            window.history.back();
        });
        //設問回答値の設定
        var _req = front.getFormValue("content .input_content");
        _req["surveys_theme_id"] = __theme_id;
        _req["display_order"] = __display_order;
        get_answer_data(_req, function(answer_data){
            window.scroll(0,0);
            set_answer_data(answer_data );
        });
    }

    /**
    * 設問データ取得理
    * @method get_questionnaire_data
    * @param req {JSON}
    * @param callback {function}
    * @return {void} return nothing
    */
    function get_questionnaire_data(req, callback){
        service.getAjax(false, __serviceUrl+"/questions", req,
        function(result, st, xhr) {
			if(result["status"]!="success") return;
            var data = result["data"];
            var array_theme={};
            var array_theme_question={};
            var array_answer={};
            for(var i=0, n=data.length;i<n;i++){
                //設問テーマデータ取得
                var theme_id = data[i]["theme_id"];
                if(!array_theme[theme_id]){
                    array_theme[theme_id] = {
                        title : data[i]["theme_title"],
                        parent_id : data[i]["parent_theme_id"],
                        max_page : 0
                    };
                }
                //if(theme_id != page) continue;
                //カレント設問データを取得
    			if(array_theme[theme_id].max_page < (data[i]["display_order"]|0)){
    				array_theme[theme_id].max_page = (data[i]["display_order"]|0);
    			}
                //if(data[i]["display_order"] != display_order) continue;
                if(util.isEmpty(data[i]["question_id"])) continue;
                var question_id = data[i]["question_id"];
                var _order = data[i]["display_order"];
                var json_input_type = data[i]["input_type"].replaceAll('&quot;', '"');
                if(!util.isEmpty(json_input_type)) json_input_type = JSON.parse(json_input_type);
                if(!array_theme_question[theme_id]) array_theme_question[theme_id] = {};
                if(!array_theme_question[theme_id][_order]) array_theme_question[theme_id][_order] = {};
                array_theme_question[theme_id][_order][question_id] = {
                    text : data[i]["question_text"],
                    jobs_questions_id : data[i]["tjsq_rid"],
                    input_type : json_input_type,
                    parent_question_id : data[i]["parent_question_id"],
                    parent_question_answer_id : data[i]["parent_question_answer_id"]
                };
                if(!array_answer[question_id])  array_answer[question_id] = {};

                //設問フォームデータを取得
                var offered_answer_id = data[i]["offered_answer_id"];
                if(!array_answer[question_id][offered_answer_id]){
                    array_answer[question_id][offered_answer_id] = {
                            text : data[i]["offered_answer_text"],
                            img : data[i]["offered_answer_img"],
                            class : data[i]["offered_answer_class"],
                            make_text_flg : data[i]["make_text_flg"],
                            disabled : data[i]["disabled_flg"],
                            placeholder : data[i]["placeholder"]
                    };
                }
            }

            if(util.isFunction(callback)) callback(array_theme, array_theme_question, array_answer);

    	});
    }
    /**
    * 回答データ取得理
    * @method get_answer_data
    * @param req {JSON}
    * @param callback {function}
    * @return {void} return nothing
    */
    function get_answer_data(req, callback){
        service.getAjax(false, __serviceUrl+"/answers", req,
            function(result, st, xhr) {
			 if(result["status"]!="success") return;
                var data = result["data"];
                var answer_data ={};
                for(var i=0,n=data.length;i<n;i++){
                    var _key = data[i]["jobs_surveys_questions_id"]+"_"+data[i]["question_id"];
                    if(!answer_data[_key]) answer_data[_key] = [];
                    answer_data[_key].push({
                        "offered_answer_id" : data[i]["surveys_questions_offered_answer_id"],
                        "text_answer" : data[i]["text_answer"],
                        "numeric_answer" : data[i]["numeric_answer"],
                        "other_text" : data[i]["other_text"]
                    });
                }
                if(util.isFunction(callback)) callback(answer_data);
            },
    	  function(xhr, st, err) {
    	  }, true
       );
    }
    /**
    * 設問フォーム設定処理
    * admin 経由の場合、
    * current_theme_id、current_orderの指定をせず
    * 設問・ページを跨いで、１ページに指定した設問をすべて表示したい
    * @method set_question_form
    * @param array_question {JSON}
    * @param array_answer {JSON}
    * @param [current_theme_id] {Int}
    * @param [current_order] {Int}
    * @return {void} return nothing
    */
    function set_question_form(array_theme, array_theme_question, array_answer, current_theme_id, current_order){
        //テーマフォームテンプレート
        var _theme_form = [
            	'<div class="back_btn">',
            		'<img alt="back" src="/images/main/back_btn.svg">',
            	'</div>',
            	'<p id="theme_title">#theme_title#</p>',
            	'<div class="page">#pager#</div>'
        ].join('');

        var _theme_html = "";
        var strHtml = "";
        var kitIdStr = $('input[name="kit"]').val();
        current_theme_id = current_theme_id|0;
        current_order = current_order|0;
        //設問数分ループ

        for(var theme_id in array_theme_question){
            //current_theme_id の指定があれば、該当設問のみ表示する
            if(!util.isEmpty(current_theme_id) && current_theme_id > 0 && theme_id!=current_theme_id) continue;
            var _current_theme = array_theme[theme_id];
            var _theme_title = "-";
            if(array_theme[_current_theme.parent_id]) _theme_title = array_theme[_current_theme.parent_id].title+' - '+_current_theme.title;
            for(var p=0;p<_current_theme.max_page;p++){
                //current_order の指定があれば、該当設問の対象ページのみ表示する
                if(!util.isEmpty(current_order)  && current_order > 0 && current_order!=(p+1)) continue;
                var array_question = array_theme_question[theme_id][p+1];
                var _question_html = get_question_form(array_question, array_answer);
                //設問見出し部分の設定
                $("#content .content_box .box_back_header").html(dom.textFormat(_theme_form, {
                    "kit" :  kitIdStr,
                    "pager" :  (p+1)+"/"+_current_theme.max_page,
                    "theme_title" : _theme_title
                }));
                strHtml += _question_html;
            }
        }
        //設問詳細部分の設定
        $("#content .content_box .input_block").html(strHtml);
        if(!util.isEmpty(current_theme_id) && array_theme[current_theme_id]) $('#content input[name="max_page"]').val(array_theme[current_theme_id].max_page);

        //独自追加フォーム設定
        set_specific_add_form();

        $('input[type="text"][inputtype="number"]').removeClass("middle");
        $('input[type="text"][inputtype="number"]').addClass("small");
        base.pageSettinged("content .inner .input_block");

    }
    /**
    * 設問フォーム設定処理
    * @method get_question_form
    * @param array_question {JSON}
    * @param array_answer {JSON}
    * @return {void} return nothing
    */
    function get_question_form(array_question, array_answer){
        //設問＋回答フォームテンプレート
        var _question_form = [
    		'<div class="#parent_class#" id="#parent_id#" >',
    			'<div class="header">',
    				'<div class="timing">',
    					'<p>#question_text#</p>',
    				'</div>',
    			'</div>',
    			'<div class="#class#">#answer_form#',
    			'</div>',
    		'</div>',
        ].join("");
        //設問数分ループ
        var _question_html = "";
        for(var question_id in array_question){
            var count = 0;
            //設問文章と関連IDをセット
            var data = {
                "parent_id" : "",
                "question_id" : question_id,
                "question_text" : array_question[question_id].text,
                "jobs_questions_id" :  array_question[question_id].jobs_questions_id,
                "parent_class" : "input_block",
                "class" : "inner"
            };
            data = set_specific_question_data(question_id, data, array_question);

            var strForm = [];
            //回答フォームをセット
            //設問内の回答フォーム数分ループ
            for(var form_id in array_question[question_id]["input_type"]["forms"]){
                var form_data = array_question[question_id]["input_type"]["forms"][form_id];
                count++;

                //個別対応タグClose
                strForm = set_question_form_before(question_id, count, strForm);

                //回答フォーム（パターン分け）
                switch(form_data["inputtype"]|0){
                    case 1:
                        strForm.push(get_checkbox_form(question_id, count,  array_question, array_answer, form_data));
                        break;
                    case 2:
                        strForm.push(get_select_form(question_id, count,  array_question, array_answer, form_data));
                        break;
                    case 3:
                        strForm.push(get_radiobutton_form(question_id, count,  array_question, array_answer, form_data));
                        break;
                    case 4:
                        strForm.push(get_textbox_form(question_id, count,  array_question, array_answer, form_data));
                        break;
                }

                //個別対応タグClose
                strForm = set_question_form_after(question_id, count, strForm);
            }
            data["answer_form"] = strForm.join('');
            _question_html += dom.textFormat(_question_form, data);
        }

        return _question_html;
    }
    /**
    * chexkboxフォーム取得処理
    * @method get_checkbox_form
    * @param question_id {Int}
    * @param count {Int}
    * @param array_question {JSON}
    * @param array_answer {JSON}
    * @param form_data {JSON}
    * @return {String} innerhtml
    */
    function get_checkbox_form(question_id, count,  array_question, array_answer, form_data){
        var _answer_key = array_question[question_id].jobs_questions_id + "_"+ array_question[question_id].text;
        var _template1 = [
    			'<div class="input_checkbox">',
    				'<dl><dd>',
    					'<ul class="#class#" >#template2#',
    					'</ul>',
    				'</dd></dl>',
    			'</div>'
        ].join("");

        //li.targetに、question_idを仕込んでおく（name指定が、#jobs_questions_id#_#offered_answer_id#のため）
        var _template2 = [
    		'<li target="#question_id#">',
    		'<input type="checkbox" id="checkbox#offered_answer_id#"  name="#jobs_questions_id#_#offered_answer_id#_#count#_1" value="#offered_answer_id#"  #required# groupname="#jobs_questions_id#">',
    		'<label for="checkbox#offered_answer_id#">#offered_answer_text#</label>#template3#',
    		'</li>'
        ].join("");
        var _template3 = [
    		'<dl>',
    			'<dd>',
    				'<input type="text" name="#jobs_questions_id#_#offered_answer_id#_#count#_2"  placeholder="#placeholder#" class="js_inputadd input_other middle"  required="true" style="display:none;"/>',
    				'#select#',
    			'</dd>',
    		'</dl>'
        ].join("");

        var data = get_form_param(question_id, count,  array_question, array_answer, form_data);
        if(util.isEmpty(data["class"])) data["class"] = "list_2column js_addcheck";

        var disabled=false;
        for (var offered_answer_id in array_answer[question_id]) {
            if(array_answer[question_id][offered_answer_id].disabled == "1"){
                //特になし等のcheckbox（同列のcheckboxをdisabledにする）
                disabled=true;
                data["class"] += '" data-parent="checkbox'+offered_answer_id;
                break;
            }
        }

        var frmcnt=0;
        var strHtml2 = "";

        if(!util.isEmpty(form_data["add_text_placeholder"])) data["placeholder"] = form_data["add_text_placeholder"];
        for (var offered_answer_id in array_answer[question_id]) {
            data["offered_answer_id"] = offered_answer_id;
            data["offered_answer_text"] = array_answer[question_id][offered_answer_id].text;
            data["template3"] = '';
            data["select"] = '';
            var checkflag=0;
            var checkvalue="";
            var checkvalue2="";
            frmcnt++;
            if(!util.isEmpty(form_data["required"]) && frmcnt==1) data["required"] = ' required="true" ';

            if(array_answer[question_id][offered_answer_id].make_text_flg == "1"){
                if(((__theme_id=="7"&&__display_order=="2") || (__theme_id=="10"&&__display_order=="5"))
                	&& !util.isEmpty(array_answer[question_id][offered_answer_id].placeholder)){
                	data["placeholder"] = array_answer[question_id][offered_answer_id].placeholder;
                }
                if(question_id == "44"){
                    var _name = "#jobs_questions_id#_#offered_answer_id#_#count#_4";
            		data["select"] = '<label class="js_inputadd " style="display:none;">状況：</label>';
                    optionData = [
                        {"value" : "1", "name" : "治療中"},
                        {"value" : "2", "name" : "既往・治療済み"}
                    ];
                    data["select"] += '<select name="'+_name+'" class="js_inputadd"  style="display:none;">';
                    data["select"] += dom.getOptionString(_name, optionData, "value", "name", "", true);
                    data["select"] += '</select>';
                    data["select"] = dom.textFormat(data["select"], data);
                }
                data["template3"] = dom.textFormat(_template3, data);
            }
            strHtml2 += dom.textFormat(_template2, data);
        }
        data["template2"] = strHtml2;

        return  dom.textFormat(_template1, data);
    }
    /**
    * selectフォーム取得処理
    * @method get_select_form
    * @param question_id {Int}
    * @param count {Int}
    * @param array_question {JSON}
    * @param array_answer {JSON}
    * @param form_data {JSON}
    * @return {String} innerhtml
    */
    function get_select_form(question_id, count,  array_question, array_answer, form_data){
        var _answer_key = array_question[question_id].jobs_questions_id + "_"+ array_question[question_id].text;
        var _template1 = [
    			'<dl class="column2_block">',
        			'<dt>#form_before#</dt>',
        			'<dd>',
                        '#template2#',
        			'</dd>',
    			'</dl>',
        ].join("");
        var _template2 = [
                '<select name="#jobs_questions_id#_#question_id#_#count#_1"  #attribute#>#option#',
                '</select>#template3#'
        ].join("");

        var data = get_form_param(question_id, count,  array_question, array_answer, form_data);
        var default_flg = false;
        var _checkText = "";
        var _templateNo = 1;

        //TODO : _templateNo=2の場合、入力フォームを横並びにする（form_dataからとれると良さそう）
        if(question_id == 21 || question_id==22 || question_id==24 || question_id==50 || question_id==57){
            _templateNo = 2;
        }
        if(question_id == 57){
            data["class"] = "pdr5";
        }

        if(!util.isEmpty(form_data["required"])) data["required"] = ' required="true" ';
        if(!util.isEmpty(form_data["form_before"])) data["form_before"] =form_data["form_before"];

        var frmcnt=0;
        var strHtml2 = "";
        var optionData =[];
        if(!util.isEmpty(form_data["add_text_placeholder"])) data["placeholder"] = form_data["add_text_placeholder"];
        for (var offered_answer_id in array_answer[question_id]) {
            //採取日のYMDの値部分
            if(!util.isEmpty(form_data["accesskey"])) continue;

            data["offered_answer_id"] = offered_answer_id;
            data["offered_answer_text"] = array_answer[question_id][offered_answer_id].text;
            optionData.push({"value" :  offered_answer_id, "name" : array_answer[question_id][offered_answer_id].text});
        }
        if(form_data["form_before"] == "都道府県") {
            var _template3 = [
                '<div class="toggle_block" id="overseas">',
                    '<strong>海外とお答えされた方は入力ください</strong>',
                    '<input name="#jobs_questions_id#_#question_id#_#count#_2" ',
                        'type="text" class="input_other" placeholder="国や地域" value="" required="true"/>',
                    '<br /><span style="color:salmon"></span>',
                '</div>'
            ].join("");
            data["class"] = "js_selector_toggle";
            data["template3"] = dom.textFormat(_template3, data);
        }
        _name = "#jobs_questions_id#_#question_id#_#count#_1";
        data["option"] = dom.getOptionString(_name, optionData, "value", "name", "", false);
        var _template = _template1;
        data["template2"] = dom.textFormat(_template2, data);
        if(_templateNo==2) return  data["template2"];
        return  dom.textFormat(_template, data);
    }
    /**
    * radiobuttonフォーム取得処理
    * @method get_radiobutton_form
    * @param question_id {Int}
    * @param count {Int}
    * @param array_question {JSON}
    * @param array_answer {JSON}
    * @param form_data {JSON}
    * @return {String} innerhtml
    */
    function get_radiobutton_form(question_id, count,  array_question, array_answer, form_data){
        var _answer_key = array_question[question_id].jobs_questions_id + "_"+ array_question[question_id].text;
        var _template1 = [
    			'#form_before#',
    				'<dl><dd>',
    					'<ul class="#class#" >#template2#',
    					'</ul>',
    				'</dd></dl>'
        ].join("");

        var _template2 = [
    		'<li>',
    		'<input type="radio" id="#count#_#offered_answer_id#" name="#jobs_questions_id#_#question_id#_#count#_1" value="#offered_answer_id#" #required# data-open="#dataopen#">',
    		'<label for="#count#_#offered_answer_id#" class="#labelclass#">',
                '#template3#',
            '</label>',
    		'</li>'
        ].join("");
        var _template3 = [
            '#offered_answer_text#',
            '<span class="#offered_answer_class#"></span>'
        ].join("");

        var _template3img = [
            '<figure>',
                '<img src="#offered_answer_img#" alt="" class="item01">',
            '</figure>',
            '#offered_answer_text#',
        ].join("");

        var data = get_form_param(question_id, count,  array_question, array_answer, form_data);
        if(util.isEmpty(data["class"])) data["class"] = "list_vertical";
        var childflag = false;
        var childanswer = [];
        for(var child_question_id in array_question){
            if(question_id == array_question[child_question_id].parent_question_id){
                childflag = true;
                childanswer.push(array_question[child_question_id].parent_question_answer_id);
            }
        }
        if(!util.isEmpty(form_data["form_before"])) {
            data["form_before"] = '<div class="sub-title">'+form_data["form_before"]+'</div>';
        }
        if(!util.isEmpty(form_data["add_text_placeholder"])){
            data["placeholder"] = form_data["add_text_placeholder"];
        }
        var frmcnt=0;
        var strHtml2 = "";

        for (var offered_answer_id in array_answer[question_id]) {
            data["offered_answer_id"] = offered_answer_id;
            data["offered_answer_text"] = array_answer[question_id][offered_answer_id].text;
            data["offered_answer_class"] = array_answer[question_id][offered_answer_id].class;
            data["dataopen"] = "";
            data["checked"] = '';
            data["style"] = '';
            frmcnt++;
            if(!util.isEmpty(form_data["required"]) && frmcnt==1) data["required"] = ' required="true" ';

            for(var j=0,m=childanswer.length;j<m;j++){
                    if(childanswer[j] == offered_answer_id){
                        data["dataopen"] += '#q_supply_'+offered_answer_id;
                        if(offered_answer_id==126 || offered_answer_id==127 || offered_answer_id==252){
                            data["dataopen"] += ',#q_supply_'+offered_answer_id+'_2';
                        }
                        if(offered_answer_id==252){
                            data["dataopen"] += ',#q_supply_'+offered_answer_id+'_3';
                        }
                    }
            }

            if(!util.isEmpty(array_answer[question_id][offered_answer_id].img)){
                data["offered_answer_img"] = array_answer[question_id][offered_answer_id].img;
                data["template3"] = dom.textFormat(_template3img, data);
                data["labelclass"] = "shape";
            }
            else {
                data["template3"] = dom.textFormat(_template3, data);
                data["labelclass"] = "";
            }
            strHtml2 += dom.textFormat(_template2, data);
        }
        data["template2"] = strHtml2;
        if(childflag) _template1 = '<div class="input_radio js_opencheck">'+_template1+'</div>';
        return  dom.textFormat(_template1, data);
    }
    /**
    * textboxフォーム取得処理
    * @method get_textbox_form
    * @param question_id {Int}
    * @param count {Int}
    * @param array_question {JSON}
    * @param array_answer {JSON}
    * @param form_data {JSON}
    * @return {String} innerhtml
    */
    function get_textbox_form(question_id, count,  array_question, array_answer, form_data){
        var _template1 = [
    			'<div class="input_text js_inputadd" data-add="2">',
    				'<dl class="#class#"><dd>',
    					'#template2#',
                    '</dd></dl>',
    			'</div>'
        ].join("");

        var _template2 = [
            '#form_before#<input type="text" name="#jobs_questions_id#_#question_id#_#count#_3"',
                ' value="" #attribute#>#form_after#',
    	].join("");

        var data = get_form_param(question_id, count,  array_question, array_answer, form_data);
        var _templateNo = 1;
        //TODO : _templateNo=2の場合、入力フォームを横並びにする（form_dataからとれると良さそう）
        if(question_id == 21 || question_id==22 || question_id==24
            || question_id==46 || question_id==48 || question_id==50){
            _templateNo = 2;
        }

        data["template2"] = dom.textFormat(_template2, data);
        var _template = _template1;
        if(_templateNo==2) _template = dom.textFormat(_template2, data);
        if(question_id==50 && util.isEmpty(data["class"])) {
            data["class"] = "list_vertical js_inputaddwrp";
        }

        return  dom.textFormat(_template, data);
    }
    /**
    * フォーム共通パラメータ取得処理
    * @method get_form_param
    * @param question_id {Int}
    * @param count {Int}
    * @param array_question {JSON}
    * @param array_answer {JSON}
    * @param form_data {JSON}
    * @return {String} innerhtml
    */
    function get_form_param(question_id, count,  array_question, array_answer, form_data){
        var data = {
            "question_id" : question_id,
            "count" : count,
            "question_text" : array_question[question_id].text,
            "jobs_questions_id" :  array_question[question_id].jobs_questions_id,
            "form_before" : "",
            "form_after" : "",
            "template2" : "",
            "template3" : "",
            "attribute" : "",
            "class" : "",
            "required" : "",
            "placeholder" : ""
        };
        if(!util.isEmpty(form_data["form_before"])) data["form_before"] = form_data["form_before"];
        if(!util.isEmpty(form_data["form_after"])) data["form_after"] = form_data["form_after"];
        if(!util.isEmpty(form_data["classname"])) data["class"] = form_data["classname"];
        if(!util.isEmpty(form_data["required"])) data["required"] = ' required="true" ';

        var attr = {
            "class" : "middle",
            "placeholder" : "",
            "required" : "",
            "maxlength" : "",
            "accesskey" : "",
            "style" : "",
            "inputtype" : "",
        };

        if(!util.isEmpty(form_data["classname"])) attr["class"] = form_data["classname"];
        if(!util.isEmpty(form_data["required"])) attr["required"] = "true";
        if(!util.isEmpty(form_data["maxlength"])) attr["maxlength"] = form_data["maxlength"];
        if(!util.isEmpty(form_data["validatetype"])) attr["inputtype"] = form_data["validatetype"];
        if(!util.isEmpty(form_data["placeholder"])) attr["placeholder"] = form_data["placeholder"];
        if(!util.isEmpty(form_data["accesskey"])) attr["accesskey"] = form_data["accesskey"];

        for(var key in attr){
            if(util.isEmpty(attr[key])) continue;
            data["attribute"] += ' '+key+'="'+attr[key]+'"';
        }
        return data;
    }
    /**
    * 設問フォーム回答値設定処理
    * @method set_answer_data
    * @param answer_data {JSON}
    * @return {void} return nothing
    */
    function set_answer_data(answer_data){
        var _isset = {};
        $("input, textarea, select",  $("#content .inner .input_block")).each(function(){
            var tag = $(this).prop("tagName")
            var type = $(this).attr("type");
            var inputtype = $(this).attr("inputtype");
            var classname = $(this).attr("class");
            if(tag.toLowerCase() == "select") type="select";
            var isset = true;
            var is_other_text = false;
            var val = [];
            //nameは、_区切りで、以下のルールとなっている
            //[jobs_surveys_quesition_id]_[question_id か、offered_answer_id]_[サブNo]_[t_answersのフィールド名]
            var field = $(this).attr("name");
            //namenのルールを_でばらす
            var _ids = field.split("_");

            //_idsの第１LV+第２Lv（question_idの場合）をanswer_dataを取得するためのkeyとする
            var _key = _ids[0]+"_"+_ids[1];
            var question_id = "";

            //answer_dataから、回答値として使うフィールドを取得
            var fieldNames = ["offered_answer_id", "other_text", "text_answer", "numeric_answer", "time_answer", "date_answer"];
            var fieldName = fieldNames[(_ids[3]|0)-1];

            if(!answer_data[_key] || answer_data[_key].length < 1){
                //回答が取れなかった場合(第２Lvがoffered_answer_idのケース）
                question_id = $(this).parents("*[target]").attr("target");
                _key = _ids[0]+"_"+question_id;
            }

            var _answer_data = answer_data[_key];
            if(!_answer_data) return;
            for(var i=0, n=_answer_data.length;i<n;i++ ){
                if(fieldName=="other_text" && _ids[1] != _answer_data[i]["offered_answer_id"]) continue;
                val.push(_answer_data[i][fieldName]);
            }
            switch(type){
                case "checkbox" :
                    $(this).val(val).change();
                    break;
                case "radio" :
                case "select":
                case "text":
                    if(val.length > 0){
                        var _no = (_ids[2]|0)-1;
                        val = val[_no];
                    }
                    if(type == "radio") {
                        $(this).val([val]);
                        if($(this).attr("value") == val) $(this).change();
                    }
                    else  {
                        $(this).val(val).change();
                    }
                    break;
                default:
                    $(this).val(val[val.length-1]);
            }
            if(!util.isEmpty(inputtype)){
                $(this).blur();
            }
        });
    }

    /**
    * 独自フォーム設定
    * @method set_specific_form
    * @return {void} return nothing
    */
    function set_specific_add_form(){
        $("#content .inner .input_block option:contains('海外')").attr("data-toggle", "overseas");
        $("#content .inner .input_block option:contains('海外')").attr("value", "999");
        if($("#q_supply_126_2").length){
            $("#q_supply_126_2 dl dd").append(get_drink_html());
        }
        if($("#q_supply_127_2").length){
            $("#q_supply_127_2 dl dd").append(get_drink_html());
        }
    }
    /**
    * 独自設問データ設定
    * @method set_specific_question_data
    * @return {JSON} return data
    */
    function set_specific_question_data(question_id, data, array_question){
        //個別対応スタイルの設定
        if(question_id==50){
            data["class"] += ' fl mgr25';
        }
        if(array_question[question_id].parent_question_id != "0"){
            data["parent_class"] += ' js_opencontent js_inputaddwrp" style="display:none;"';
            data["parent_id"] = 'q_supply_'+array_question[question_id].parent_question_answer_id;
            //case3にて必要
            if(array_question[question_id].parent_question_answer_id == 252){
                if(question_id==40) data["parent_id"] += '_2';
                else if(question_id==41) data["parent_id"] += '_3';
            }
            //case4にて必要
            if(question_id==19 || question_id==124){
                data["parent_id"] += '_2';
            }
        }
        return data;
    }
    /**
    * 独自設問データ設定 Open
    * @method set_question_form_before
    * @return {JSON} return data
    */
    function set_question_form_before(question_id, count, strForm){
        var _template = '';
        if(question_id==21&& count==1){
            _template = '<div class="input_text js_inputadd" data-add="2">';
            _template +='<dl class="list_vertical js_inputaddwrp"><dd>';
            strForm.push(_template);
        }
        else if((question_id==22 || question_id==24)  && count%2==1){
            _template ='<dl class="column2_block"><dt></dt><dd>';
            strForm.push(_template);
        }
        else if((question_id==46 || question_id==48)  && count%2==1){
            if(count==1)  _template +='<dl class="list_vertical js_inputaddwrp">';
            _template +='<dd class="mgb16">';
            strForm.push(_template);
        }
        return strForm;
    }
    /**
    * 独自設問データ設定 Close
    * @method set_question_form_after
    * @return {JSON} return data
    */
    function set_question_form_after(question_id, count, strForm){
        if(question_id==21 && count==3){
            strForm.push('</dd></dl></div>');
        }
        else if((question_id==22 || question_id==24) && count%2==0){
            strForm.push('</dd></dl>');
        }
        else if((question_id==46 || question_id==48)  && count%2==0){
            strForm.push('</dd>');
            if(count==10)  strForm.push('</dl>');
        }
        return strForm;
    }
    /**
    * 酒1単位目安表（HTML)取得
    * @method get_drink_html
    * @return {String} return html
    */
    function get_drink_html(){
            return ['<p class="title">酒1単位の目安</p>',
            '<table class="is_pc">',
            	'<tr>',
            	'<th>酒の種類</th>',
            		'<td><figure>ビール<img src="/images/main/enquete/alcohol_beer.png" alt=""></figure></td>',
            		'<td><figure>清酒<img src="/images/main/enquete/alcohol_sake.png" alt=""></figure></td>',
            		'<td><figure>ワイン<img src="/images/main/enquete/alcohol_wine.png" alt=""></figure></td>',
            		'<td><figure>ウイスキー<br>水割り<img src="/images/main/enquete/alcohol_wisky.png" alt=""></figure></td>',
            		'<td><figure>焼酎お湯割り<img src="/images/main/enquete/alcohol_shochu.png" alt=""></figure></td>',
            	'</tr>',
            	'<tr>',
            	'<th rowspan="2">酒の容量</th>',
            		'<td><p>1本</p></td>',
            		'<td><p>1合</p></td>',
            		'<td><p>1/3本</p></td>',
            		'<td><p>シングル2杯、<br>ダブル1杯</p></td>',
            		'<td><p>0.6合</p></td>',
            	'</tr>',
            	'<tr>',
            		'<td><p>（633ml）</p></td>',
            		'<td><p>（180ml）</p></td>',
            		'<td><p>（240ml）</p></td>',
            		'<td><p>（60ml）</p></td>',
            		'<td><p>（110ml）</p></td>',
            	'</tr>',
            	'</table>',
            	'<table class="is_sp">',
            	'<tr>',
            	'<th>酒の種類</th>',
            	'<th>酒の容量</th>',
            	'</tr>',
            	'<tr>',
            		'<td>ビール<figure><img src="/images/main/enquete/alcohol_beer.png" alt=""></figure></td>',
            		'<td><p><strong>1本</strong></p><p>（633ml）</p></td>',
            	'</tr>',
            	'<tr>',
            		'<td>清酒<figure><img src="/images/main/enquete/alcohol_sake.png" alt=""></figure></td>',
            		'<td><p><strong>1合</strong></p><p>（180ml）</p></td>',
            	'</tr>',
            	'<tr>',
            		'<td>ワイン<figure><img src="/images/main/enquete/alcohol_wine.png" alt=""></figure></td>',
            		'<td><p><strong>1/3本</strong></p><p>（240ml）</p></td>',
            	'</tr>',
            	'<tr>',
            		'<td>ウイスキー<br>水割り<figure><img src="/images/main/enquete/alcohol_wisky.png" alt=""></figure></td>',
            		'<td><p><strong>シングル2杯、<br>ダブル1杯</strong></p><p>（60ml）</p></td>',
            	'</tr>',
            	'<tr>',
            		'<td>焼酎お湯割り<figure><img src="/images/main/enquete/alcohol_shochu.png" alt=""></figure></td>',
            		'<td><p><strong>0.6合</strong></p><p>（110ml）</p></td>',
            	'</tr>',
            '</table>'].join('');
    }
});
