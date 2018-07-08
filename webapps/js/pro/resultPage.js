var _cacheData = {};

//on ready statement
$(function(){
	window.onresize = function(){
		setChart(_cacheData["dataSource"], _cacheData["data"]);
	}
	cacheLoad();
	var kitIdStr = $("input[name=kit_id_str]").val();
	//検査結果画面旧・新の状態合わせ
	var pageId ="result";
	var mode = 0;
	//pageId = result_total → mode =1 （推移・比較　→　総合検査結果）
	if(pageId == "result_total"){
		mode=1;
	}
	if(!util.isEmpty(kitIdStr)) showResult(kitIdStr, mode);
	//切り替えた場合は、画面をスクロールトップにする
	$('body, html').scrollTop(0);

});
//新・旧検査結果ページ切り替え
function pageModeChange(pagemode){
	var _url = location.href;
	if(_url.indexOf("https://pro.mykinso.com")>=0 && pagemode==="old"){
		ga('send', 'event', 'content', 'click', 'old_result');
	}
	var kitIdStr = $("input[name=kit_id_str]").val();
	var h = base.getPageHistory();
	var pageCode = h["linkParam"];
	if(util.isEmpty(pageCode)) return;
	_cacheData["pagemode"] = pagemode;
	cacheSave();
	pageCode = pageCode.replace("2", "");
	pageCode = pageCode.replace("1", "");
	var h = base.getPageHistory();
	h["resultKit"] = kitIdStr;
	h["resultPage"] = "result";
	h["resultUrl"] = pageCode+"2";
	base.pageHistoryAdd(h);
	base.showPage("", pageCode, "", {"kit" : kitIdStr});
}
//検査結果ページ設定保存
function cacheSave(){
	util.setLocalData("_pro_result_mode", {"pagemode" : _cacheData["pagemode"]});
}
//検査結果ページ設定ロード
function cacheLoad(){
	var c = util.getLocalData("_pro_result_mode");
	if(c!=null && !util.isEmpty(c["pagemode"]))_cacheData["pagemode"] = c["pagemode"];
	if(!_cacheData["pagemode"]) _cacheData["pagemode"] = "old";
}
//検査結果表示
function showResult(kitIdStr, mode){
	var _req = { "kit_id_str" : kitIdStr };
	//TODO _modeの決め方
	//検査キット一覧⇔検査結果⇔総合検査結果
	//総合検査結果への遷移は、検査キットが複数ある場合のみ
	$("*[pageMode=singleResult]").hide();
	$("*[pageMode=totalResult]").hide();
	if(!_cacheData["dataSource"] || _cacheData["dataSource"] == null){
		var _req = { "kit_id_str" : kitIdStr };
		getData("get_resultdata_by_kit_id", _req, function(data){
			console.log("showResult:"+data.length);
			setResultdata(data, kitIdStr, mode);
		});
	}
	else {
		setResultdata(_cacheData["dataSource"], kitIdStr, mode);
	}
}
//検査結果データ設定
function setResultdata(dataSource, kitIdStr, mode){
	var _idx =0;
	var _kit_list = '';

	//履歴から、検査結果表示タブ取得（総合検査結果か否か）
	var h = base.getPageHistory();
	if(h["resultPage"] == "result_total") mode = 1;
	if(h["resultKit"]) kitIdStr = h["resultKit"];

	if(dataSource.length < 2) mode = 0;

	//カレントのデータを取得
	for(var i=0,n=dataSource.length;i<n;i++){
		var _kitIdStr = dataSource[i]["kit_id_str"];
		if(_kitIdStr == kitIdStr) {
			_idx = i;
		}
		if(mode==1) {
			var _kit_title = dataSource[i]["kit_title"];
			var _sampling_date = dataSource[i]["sampling_date"];
			_kit_list+='<li alt="'+_kitIdStr+'" ';
			if(i==0){
				_kit_list+=' class="new"';
			}
			_kit_list+='>'+_kit_title+' -';
			_kit_list+= '<time>'+_sampling_date+'</time>';
			if(i==0){
				_kit_list+= '<span>最新</span>';
			}
			_kit_list+= '</li>';
		}
	}

	var data = dataSource[_idx];

	//総合検査結果の場合、検査キットリストを設定
	if(mode==1) data["kit_list"] = _kit_list;
	$("*[id]", $(".section--result")).each(function(e){
		var tagName = $(this).prop("tagName");
		var id = $(this).attr("id");
		if(!data[id]) return;
		switch(tagName.toUpperCase()){
			case "IMG":
				var src = "/images/result/";
				$(this).attr("src",src+data[id]);
				break;
			default:
				$(this).html(data[id]);
		}
	});
	if(mode==1){
		$('#kit_list li').unbind("click");
		$('#kit_list li').on("click",function(e){
			var h = base.getPageHistory();
			var kitIdStr = $(this).attr("alt");
			h["resultPage"] = "result";
			h["resultKit"] = kitIdStr;
			base.pageHistoryAdd(h);
			showResult(kitIdStr, 0);
			return;
		});
		//各チャートをOpen/Closeするボタン
		$('.js_toggle').unbind("click");
		$('.js_toggle').on("click",function(){
			var t = $(this);
			var wrp = "#" + $(this).attr("id").split("_btn")[0] + "_wrp";
			if($(wrp).is(":visible")){
				$(wrp).slideUp()
				t.addClass("is-selected")
			}
			else{
				$(wrp).slideDown(function(){
					if ( typeof chart == "function")  {
					  chart();
					}
				})
				t.removeClass("is-selected")
			}
		});
		//総合検査結果表示
		$("*[pageMode=totalResult]").show();
	}
	else {
		//結果が1つの場合推移の表示は不要
		if(dataSource.length >1) {
			var kitIdStr = data["kit_id_str"];
			$("#totalResultButton").on("click", function(e){
				var h = base.getPageHistory();
				h["resultPage"] = "result_total";
				h["resultKit"] = kitIdStr;
				base.pageHistoryAdd(h);
				showResult(kitIdStr, 1);
			});
		}
		$("#resultDetailButton").attr("href", "/result_detail?kit="+kitIdStr);
		//（単体）検査結果表示
		$("*[pageMode=singleResult]").show();
		if(dataSource.length<2) $("#totalResultButton").hide();
	}
	setChart(dataSource, data);
	_cacheData["dataSource"] = dataSource;
	_cacheData["data"] = data;
}
//グラフ表示設定
function setChart(dataSource, data){
	if(dataSource.length > 1) {
		//2回以上検査している場合
		setLinechart(dataSource);
		setStackbarchart(dataSource);
	}
	setPiechart(data);
	setBarchart(data);
}
//折れ線グラフ設定
function setLinechart(dataSource){
	$(".basicchart.linechart").each(function(){
		var _id = $(this).attr("id");
		if(util.isEmpty(_id)) return;
		var _labelField = $(this).attr("labelField");
		if(util.isEmpty(_labelField)) return;
		var _dataField = $(this).attr("dataField");
		if(util.isEmpty(_dataField)) return;
		var _chartUnit = $(this).attr("chartUnit");
		if(util.isEmpty(_chartUnit)) _chartUnit="";

		var _chartColor = $(this).attr("chartColor");
		var _averageColor = $(this).attr("averageColor");
		var data = [];
		var _average = null;
		var _fieldId = _dataField+"Value";
		var _averageField = _dataField+"_avg";
		for(var i=0, n=dataSource.length;i<n;i++){
			var _record = {};
			_record[_labelField] = dataSource[i][_labelField];
			_record[_fieldId] = util.parseStrToFloat(dataSource[i][_fieldId]);
			//ツールチップ：日付：データ名：データ値
			_record["tooltip_text"] =dataSource[i][_labelField]+":";
			_record["tooltip_text"] += getDataName(_dataField)+"<br>";
			_record["tooltip_text"] += dataSource[i][_fieldId]+_chartUnit;
			if(_average==null) {
				_average = util.parseStrToFloat(dataSource[i][_averageField]);
			}
			data.push(_record);
		}
		var _averageLineStyle = null;
		if(_average!=null && !util.isEmpty(_averageColor)){
			_averageLineStyle = {
				value: _average,
				stroke : _averageColor,
				lineMargin : [18, 58]
			};
		}

		for(var i=0,n=data.length;i<n;i++){
			data[i][_labelField] = Date.parse(data[i][_labelField]);
			data[i][_labelField] = new Date(data[i][_labelField]);
		}

		var xScale = [d3.min(data, function(d){ return (d[_labelField]); }),
								d3.max(data, function(d){ return  (d[_labelField]); })];
		var xScaleSize = data.length+1;

		var maxy = d3.max(data, function(d){ return (d[_fieldId]); });
		var miny = d3.min(data, function(d){ return (d[_fieldId]); });
		//最小-20%～最大+20%の範囲で出す、ただし範囲の下限＝０、上限＝０の場合は１（暫定）
		miny -= miny*0.2;
		maxy += maxy*0.2;
		if(miny < 0) miny = 0;
		if(maxy == 0) maxy = 1;

		var yScale = [maxy, miny];
		var yScaleSize = 5;
		var options = {
				data :data,
				padding : [30,40,20,40],
				xScale : xScale,
				xScaleSize : xScaleSize,
				yScaleSize : yScaleSize,
				yScale : yScale
		};
		$(this).unbind("basicchart");
		var chart1 = $(this).basicchart(options);

		chart1.basicchart("drawAxisX",{
			type : "time",
			labels : "%m/%d",
			lineMargin : [18,58],
			lineWidth:1,
			innerLineWidth : 0,
			textAlign : "start",
			textSize : 0,
			padding : [0,0],
			visible : true,
			orient: "bottom",
			transform: [0, chart1.basicchart("getAttributes").drawAxisHeight],
		});
		chart1.basicchart("drawAxisY",{
			lineMargin : [0,0],
			padding : [-18,0]
		});

		chart1.basicchart("lineChart",  {
			symbolStyle : "liner",  //cardinal,step,liner
			animationWait : 0,
			animationTime : 1000,
			labels : "%m/%d",
			fieldX : _labelField,
			fieldY : _fieldId,
			fieldLabel : _labelField,
			textSize : 0,
			textOffset : [0,16],
			lineColor: _chartColor,
			lineWidth:3
		},_averageLineStyle
 		);
		chart1.basicchart("scatterChart", {
			lineWidth:0,
			labels : "%m/%d",
			animation : 1000,
			fieldX : _labelField,
			fieldY : _fieldId,
			fieldLabel : _labelField,
			symbolColor: _chartColor,
			symbolSize : 7,
			textSize : 0,
			textAlign : "middle",
			textOffset : [0,-12],
			onMouseover : chartMouseover,
		    onMousemove : chartMousemove,
		    onMouseout :chartMouseout
		});
	});
}
//円グラフ設定
function setPiechart(dataSource){
	$(".basicchart.piechart").each(function(){
		var _dataField = $(this).attr("dataField");
		if(util.isEmpty(_dataField)) return;
		if(_dataField.indexOf(",")>=0) _dataField = _dataField.split(",");
		else return;
		var _chartColor = $(this).attr("chartColor");
		if(_chartColor.indexOf(",")>=0) _chartColor = _chartColor.split(",");
		else _chartColor = [_chartColor];

		var data = {};
		for(var i=0,n=_dataField.length;i<n;i++){
			var f = _dataField[i];
			if(util.isEmpty(dataSource[f])) continue;
			data[f] = util.parseStrToFloat(dataSource[f]);
		}
		var yScale = [d3.max(data, function(d){ return d.y; }),0];
		var yScaleSize = (yScale[1] - yScale[0]);

		var xScale = [d3.min(data, function(d){ return d.x; }),d3.max(data, function(d){ return d.x; })];
		var xScaleSize = (xScale[1] - xScale[0]);
		if(xScaleSize<0) xScaleSize*=-1;
		if(yScaleSize<0) yScaleSize*=-1;
		yScaleSize/=50;
		var options = {
				data : data,
				padding : [8,8,8,8],
				xScale : xScale,
				yScale : yScale,
				yScaleSize : yScaleSize+1,
				xScaleSize : xScaleSize+1
		};
		$(this).unbind("basicchart");
		var chart1 = $(this).basicchart(options);
		chart1.basicchart("pieChart",{
			textColor:"#FFF",
			lineColor : "#FFF",
			lineWidth:1,
			symbolSize:0,
			selectSymbolSize : 8,
			labels : "%v%",
			textSize:8,
			symbolColor : function(d, i, j ){
				return _chartColor[i%_chartColor.length];
			},
			onMouseover : _chartMouseover,
		    onMousemove : chartMousemove,
		    onMouseout :chartMouseout
		});
	});
	function _chartMouseover(d, i, j, selecter, style){
		if(!selecter) return;
		var tag = $(selecter).prop("tagName");
		var cl = $(selecter).attr("class");
		var alt = $(selecter).attr("alt")
		if(tag.toLowerCase() == "path") {
			$(".onSelect "+tag+"[alt="+alt+"]", $(selecter).parent().parent()).attr("opacity", 0.5);
		}
		var _tooltip_text = getDataName(d.data.key.replace("_avg", ""))+"<br>"+d.data.value+"%";
		return d3.select("body").select("#tooltip").style("visibility", "visible").html(_tooltip_text);
	}
}
//棒グラフ（横）設定
function setBarchart(dataSource){
	$(".basicchart.barchart").each(function(){
		var _level = 7;
		var _dataField = $(this).attr("dataField");
		var _chartColor = $(this).attr("chartColor");
		var _highlightColor = $(this).attr("highlightColor");
		var _highlightLabel = $(this).attr("highlightLabel");
		var _averageColor = $(this).attr("averageColor");
		var _averageLabel = $(this).attr("averageLabel");
		if(util.isEmpty(_dataField)) return;
		var data = [];
		var _labels = [];
		var _colors = [];

		var _currentVal = util.parseStrToFloat(dataSource[_dataField+"Value"]);
		var _averageVal = util.parseStrToFloat(dataSource[_dataField+"_avg"]);
		var _segmentData = JSON.parse(dataSource[_dataField+"_segment"].replaceAll('&quot;', '"'));
		var _currentSet = false;
		var _averageSet = false;

		for(var i=0;i<_level;i++){
			var _from = _segmentData[i].val;
			var _to = (_segmentData[i - 1]) ? _segmentData[i - 1].val : "";
			var _ratio = _segmentData[i].ratio;
			var _label = "";

			_from = (util.isEmpty(_from)) ? "" : util.parseStrToFloat(_from);
			_to = (util.isEmpty(_to)) ? "" : util.parseStrToFloat(_to);
			_ratio = (util.isEmpty(_ratio)) ? "" : util.parseStrToFloat(_ratio);

			var _setColor = _chartColor;
			if(!_averageSet && _isInRange(_averageVal, _from, _to)){
				_setColor = _averageColor;
				_label += _averageLabel;
				_averageSet = true;
			}
			if(!_currentSet && _isInRange(_currentVal, _from, _to)){
				//色設定はcurrentを優先とする
				_setColor = _highlightColor;
				if(_label!="") _label = _label+"・";
				_label += _highlightLabel;
				_currentSet = true;
			}
			if(_from==0) _from ="";
			if(_from!="") _from = _from.toFixed(2);
			if(_to==0) _to ="";
			if(_to!="") _to = _to.toFixed(2);
			if(_label != "") _label="─"+_label;
			_colors.unshift(_setColor);
			_labels.unshift( _from+"-"+_to);
			if (_ratio) {
				data.unshift({"x" :_level-1-i, "y" : util.parseStrToFloat(_ratio), "label" : _label, "tooltip_text": "この範囲にいる人の割合："+_ratio+"%"});
			}
		}

		var xScale = [(d3.max(data, function(d){ return d.y; }))|0+1,0];
		var xScaleSize = 5;

		var yScale = [d3.max(data, function(d){ return d.x; }),d3.min(data, function(d){ return d.x; })];
		var yScaleSize = (xScale[1] - xScale[0]);
		if(xScaleSize<0) xScaleSize*=-1;
		if(yScaleSize<0) yScaleSize*=-1;

		var options = {
				data : data,
				padding : [10,96,10,96],
				xScale : xScale,
				yScale : yScale,
				yScaleSize : 7,
				xScaleSize : 5
		};
		$(this).unbind("basicchart");
		var chart1 = $(this).basicchart(options);
		chart1.basicchart("drawAxisY", {
			labels :_labels,
			textSize:12,
			lineWidth : 1,
			lineColor : "#333",
			innerLineWidth : 0,
			padding:[0,0]
		});

		chart1.basicchart("barChart", {
			textSize:12,
			symbolSize:0.7,
			lineWidth:0,
			fieldLabel :"label",
			symbolColor : function(d, i, j ){
				return _colors[i];
			},
			padding:[1,0],
			textOffset:[4,4],
			textAlign:"start",
			onMouseover : chartMouseover,
			onMousemove : chartMousemove,
			onMouseout :chartMouseout
		},true);
	});
}
//積み上げ棒グラフ（横）設定
function setStackbarchart(dataSource){
	$(".basicchart.stackbarchart").each(function(){
		var _labelField = $(this).attr("labelField");
		if(util.isEmpty(_labelField)) return;
		var _dataField = $(this).attr("dataField");
		if(util.isEmpty(_dataField)) return;
		if(_dataField.indexOf(",")>=0) _dataField = _dataField.split(",");
		else _dataField = [_dataField];
		var _chartColor = $(this).attr("chartColor");
		if(_chartColor.indexOf(",")>=0) _chartColor = _chartColor.split(",");
		else _chartColor = [_chartColor];
		var labels = [];
		var data = [];
		for(var i=0, n=dataSource.length;i<n;i++){
			labels.push(dataSource[i][_labelField]);
			var _record = {};
			for(var j=0, m = _dataField.length;j<m;j++){
				var key = _dataField[j];
				_record[key] = util.parseStrToFloat(dataSource[i][key]);
			}
			data.push(_record);
		}
		var options = {
				data : data,
				padding : [16,20,20,80],
				xScaleSize : 10,
				labelY : labels,
				xScaleSize :5,
				yScaleSize :2,
				xScale : [0,100],
				yScale : [0,data.length]
		};
		$(this).unbind("basicchart");
		var	chart1 = $(this).basicchart(options);
		chart1.basicchart("drawAxisX",{
			labels : "%v%",
			lineMargin : [18,18],
			lineWidth :0,
			textAlign : "start",
			textOffset : [0,0],
			padding : [0,10],
			orient: "bottom",
			transform: [0, chart1.basicchart("getAttributes").drawAxisHeight+10],
		});
		chart1.basicchart("setOption", "axisStyleY" , {
			labels : labels,
			lineMargin : [10,10],
			lineWidth :1,
			textAlign : "end",
			textOffset : [0,0]
		});

		chart1.basicchart("stackedBarChart", {
			fieldY : _dataField,
			labels : "%v%",
			textSize : 8,
			textAlign : "end",
			lineColor : "#FFF",
			symbolColor : function(d, i, j ){
				return _chartColor[j%_chartColor.length];
			},
			lineWidth : 1,
			textOffset : [-6, 0],
			symbolSize : 0.9,
			onMouseover : _chartMouseover,
		    onMousemove : chartMousemove,
		    onMouseout :chartMouseout
		},true);
		function _chartMouseover(d, i, j, selecter, style){
			if(!selecter) return;
			var tag = $(selecter).prop("tagName");
			var cl = $(selecter).attr("class");
			var alt = $(selecter).attr("alt")
			if(cl == "chartShape") {
				$(".onSelect "+tag+"[alt="+alt+"]", $(selecter).parent().parent()).attr("opacity", 0.5);
			}
			var _text = labels[d.row]+":"+getDataName(d.col)+"<br>"+d["y"]+"%";
			return d3.select("body").select("#tooltip").style("visibility", "visible").html(_text);
		}
	});
}
//マウスオーバー時の処理（ツールチップ表示）
function chartMouseover(d, i, j, selecter, style){
	if(!selecter) return;
	var tag = $(selecter).prop("tagName");
	var cl = $(selecter).attr("class");
	var alt = $(selecter).attr("alt")
	if(cl == "chartShape") {
		$(".onSelect "+tag+"[alt="+alt+"]", $(selecter).parent().parent()).attr("opacity", 0.5);
	}
	d3.select("body").select("#tooltip").style("visibility", "visible").html(d["tooltip_text"]);
}
//マウスムーブ時の処理（ツールチップの位置設定）
function chartMousemove(d, i, j, selecter, style){
	var top = (d3.event.pageY-20);
	var left = (d3.event.pageX+10);
	return d3.select("body").select("#tooltip").style("top" , top+"px").style("left", left+"px");
}
//マウスアウト時の処理（ツールチップ非表示）
function chartMouseout(d, i, j, selecter, style){
	if(!selecter) return;
	var tag = $(selecter).prop("tagName");
	var cl = $(selecter).attr("class");
	var alt = $(selecter).attr("alt");
	if(tag == "path") {
		$(".onSelect "+tag+"[alt="+alt+"]", $(selecter).parent().parent()).attr("opacity", 0);
	}
	return d3.select("body").select("#tooltip").style("visibility", "hidden");
}
//範囲値(from-to)にvalが収まているか判定する
function _isInRange(val, from, to){
	var _val = util.parseStrToFloat(val);
	if(!util.isEmpty(from)){
		var _from = util.parseStrToFloat(from);
		if(_from <= _val){
			if(util.isEmpty(to)) return true;
			else if(_val <= util.parseStrToFloat(to)) return true;
		}
	}
	else {
		if(util.isEmpty(to)) return false;
		else if(_val <= util.parseStrToFloat(to)) return true;
	}
	return false;
}
//フィールド名からラベルを取得する
function getDataName(key){
	var _dataName = {
			"fb" : "FB比",
			"div" : "多様性",
			"bif" : "ビフィズス菌",
			"lac" : "乳酸産生菌",
			"but" : "酪酸産生菌",
			"equ" : "エクオール産生菌",
			"bacteroidetes" : "バクテロイデーテス門",
			"firmicutes" : "ファーミキューテス門",
			"actinobacteria" : "アクチノバクテリア門",
			"proteobacteria" : "プロテオバクテリア門",
			"others" : "フソバクテリア門、シネルギステス門、レンティスファエラ門、その他"
	};
	return _dataName[key];
}
