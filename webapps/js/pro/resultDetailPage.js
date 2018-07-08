$(function(){
	var kitIdStr = $("input[name=kit_id_str]").val();
	if(kitIdStr|0 > 0) showResultDetail(kitIdStr);
});

// デフォルト
var _initial_view_rank = "genus";
var _current_rank = 0;

var ranks = function(){
	var ordinal = d3.scale.ordinal()
		.domain(["kingdom", "phylum", "class", "order", "family", "genus"])
		.range([         0,        1,       2,       3,       4,        5]);
	var inversion = d3.scale.ordinal()
		.domain(ordinal.range())
		.range(ordinal.domain());
	ordinal.invert = function(index){
        return inversion(index);
	};
	return ordinal;
}();

// 検査結果を取得して、セットする
function showResultDetail(kitIdStr){
	getData("get_bacteriadata_by_kit_id", {"kit" : kitIdStr}, function(data){
		var data_source = parseJson(data);
        var listTable = $("#listTable").listtable({
			"data" : data_source,
			"header" : {
                0 : {"text" : "no", "class" : "no", "type" : "number", "field" : null},
				1 : {"text" : "taxon(菌名)", "class" : "taxon", "type" : "taxon", "field" : "name", "sort" : "name"},
				2 : {"text" : "ratio(割合)", "class" : "ratio", "type" : "percent", "field" : "ratio", "sort" : "ratio"},
				3 : {"text" : "平均", "class" : "ratio", "type" : "percent", "field" : "avg", "sort" : "avg"},
				4 : {"text" : "対平均比", "class" : "", "type" : "", "field" : "diffper", "sort" : "diffper"}
			},
			"styleName" : "table",
			"tableStyleName" : "list-chart",
			"filterVal" : ranks(_initial_view_rank),
			"zeroPaddingSize" : 3,
			"display" : false,
			"onFilter" : function(data, filterVal){
				var _viewData = filterData(data, filterVal);
				return _viewData;
			},
			"onSort" : function(data, sortField){
				var _sortField = [];
				for(var key in sortField){
					_sortField.unshift(key);
				}
				for(var i=0,n=_sortField.length;i<n;i++){
					var key = _sortField[i];
					var _order = 0;
					if(sortField[key]=="desc") _order = -1;
					else if(sortField[key]=="asc") _order = 1;
					if(_order==0) continue;
					data.timsort(function(first, second){
						var diff = util.diffVal(first[key] , second[key]);
						if (diff<0) return _order;
						else if (diff>0) return -_order;
						else return 0;
					});
				}
				return data;
			}
		});

        // Viewの表示を行う
        showView(data_source);
	});
}

// Viewを表示する
function showView(data_source) {
    var listTable = $("#listTable").listtable();
    var svg = d3.select("body").append("div").attr("id", "svg_wrp").append("svg").attr("id", "canvas_area");

	setCurrentRank(ranks(_initial_view_rank));
	//showBubbleChart(data_source, svg);

    // 表示する階級を選択するためのオブジェクトを生成。
    var nav_ui = d3.select("body").append("div").attr("id", "nav_ui");

    var rank_axis_group = nav_ui.append("div").attr("id", "rank_axis_group");
	//MYK_MAIN-162 kingdom表示不要
	//rank_axis_group.append("button").attr("id", "kingdom").classed("tick", true).text("kingdom").append("span").classed("jp", true).text("界");
    rank_axis_group.append("button").attr("id", "phylum").classed("tick", true).text("phylum").append("span").classed("jp", true).text("門");
    rank_axis_group.append("button").attr("id", "class").classed("tick", true).text("class").append("span").classed("jp", true).text("綱");
    rank_axis_group.append("button").attr("id", "order").classed("tick", true).text("order").append("span").classed("jp", true).text("目");
    rank_axis_group.append("button").attr("id", "family").classed("tick", true).text("family").append("span").classed("jp", true).text("科");
    rank_axis_group.append("button").attr("id", "genus").classed("tick selected_button", true).text("genus").append("span").classed("jp", true).text("属");

    // ボタンにクリックイベント追加
    rank_axis_group.selectAll("button").on("click", function(){
        d3.selectAll(".tick").classed("selected_button", false);
        d3.select(this).classed("selected_button", true);
        // 選択された階級を(リスト|バブル)・チャートへ伝達。
        var target_rank = ranks(this.id);
        listTable.listtable("filter", target_rank);
		setCurrentRank(target_rank);
		showBubbleChart(data_source, svg);
    });

    // リスト、または、バブル・チャートの表示を切り替えるボタン。
    var bubble_and_list_switch = nav_ui.append("div").attr("id", "bubble_and_list_switch");

    // バブル・チャートを表示するボタン。
    var bubble_button = bubble_and_list_switch
        .append("button")
        .attr("id", "bubble_button")
        .text("Bubble表示")
        .classed("bubble_and_list_switch_button", true);

    // リスト・チャートを表示するボタン。
    var list_button = bubble_and_list_switch
        .append("button")
        .attr("id", "list_button")
        .text("List表示")
        .classed("bubble_and_list_switch_button selected_button", true);

    bubble_button.append("span");
    list_button.append("span");
	$("#svg_wrp").hide();
	listTable.listtable("visible", true);

    // バブル・チャート表示ボタンが押下された場合はバブル・チャートを表示。
    bubble_button.on("click", function(){
        bubble_button.classed("selected_button", true);
        list_button.classed("selected_button", false);
        listTable.listtable("visible", false);
        $("#svg_wrp").show();
		showBubbleChart(data_source, svg);
    });

    // リスト・チャート表示ボタンが押下された場合はリスト・チャートを表示。
    list_button.on("click", function(){
        bubble_button.classed("selected_button", false);
        list_button.classed("selected_button", true);
		svg.selectAll("#bubble_chart_group").remove();
        $("#svg_wrp").hide();
        listTable.listtable("visible", true);
    });
}

// ランクをセットする
function setCurrentRank(rank) {
	_current_rank = rank;
}

// バブルチャートを表示する
function showBubbleChart(data, svg){
	$(this).unbind("basicchart");
	var	bubble_chart = $(this).basicchart({
		data : filterData(data, _current_rank),
		xScale : {},
		yScale : {}
	});

	bubble_chart.basicchart("bubbleChart", {
		selector: svg,
		onMouseover : mouseover,
		onMousemove : mousemove,
		onMouseout : mouseout
	});
}

// Filter Data
function filterData(data, rank){
	var r = {};
	for(var t = 0; t < data.length; ++t){
	var nm = name(data[t], rank);
		if(r[nm] === undefined){
			r[nm] = {"ratio" : 0, "avg" : 0, "diffper" : 0};
		}
		r[nm]["ratio"] += data[t]["ratio"];
		r[nm]["avg"] += data[t]["avg"];
	}

	var ret = [];
	for(var n in r){
		var _diffper = 0;
		if(r[n]["ratio"]!=0 && r[n]["avg"]!=0) _diffper = ((r[n]["ratio"] / r[n]["avg"] * 1000)|0)/1000;

		ret.push({ name : n,
			ratio : r[n]["ratio"],
			avg : r[n]["avg"],
			diffper : _diffper
		});
	}
	return ret;
}

// objectが空かを判定する
function isEmpty(obj) {
	if (obj == null) return true;
	if (obj.length > 0) return false;
	if (obj.length === 0) return true;

	for (var key in obj) {
        if (Object.prototype.hasOwnProperty.call(obj, key)) return false;
	}
	return true;
}

// 取得した可視化用データをパースして配列に変換。
// jsonの形式についてはReadmeを参照のこと。
function parseJson(data){
	if(isEmpty(data)) {
        return [];
	}
	else {
        var genus_ratios = [];
        // keyは菌の分類。kingdom名からgenus名までをセミコロンで連結した文字列。
    	var json ={};
    	for(var i=0,n=data.length;i<n;i++){
    		if(!json[data[i]["bacteria_name"]]) json[data[i]["bacteria_name"]] = {};
    		json[data[i]["bacteria_name"]]["ratio"] = data[i]["ratio"];
    		json[data[i]["bacteria_name"]]["avg"] = data[i]["avg"];
    		json[data[i]["bacteria_name"]]["diffper"] = data[i]["diffper"];
    	}
    	for (var key in json){
    		if(Object.prototype.hasOwnProperty.call(json, key)) {
        		var phylum_ranks    = key.split(';'), e = {};
        		e[ranks("kingdom")] = phylum_ranks[0];
        		e[ranks("phylum")]  = phylum_ranks[1];
        		e[ranks("class")]   = phylum_ranks[2];
        		e[ranks("order")]   = phylum_ranks[3];
        		e[ranks("family")]  = phylum_ranks[4];
        		e[ranks("genus")]   = phylum_ranks[5];
        		e["ratio"]          = parseFloat(json[key]["ratio"]);
        		e["avg"]            = parseFloat(json[key]["avg"]);
        		e["diffper"]        = parseFloat(json[key]["diffper"]);
        		genus_ratios.push(e);
    		}
    	}
        return genus_ratios;
	}
}

function name(datum, rankForView) {
	var n = "";
	for(var i = ranks("kingdom") - 1; i < rankForView; ++i){
        n += datum[i+1] + ";";
	}
	return n;
}

// マウスオーバー
function mouseover(d, i){
	function prepend_rank(currentValue, index, array){
		return ranks.invert(index) + " : " + currentValue;
	};
	function not_empty(str){
		return str.length != 0;
	};
	d3.select(this).classed({"selected_node": true, "unselected_node": false});
	var html = d.name.split(";").filter(not_empty).map(prepend_rank).join("<br>") + "<br>" + "ratio : " + (((parseFloat(d.ratio)*100000)|0)/1000+"%");
	return d3.select("body").select("#tooltip").style("visibility", "visible").html(html);
}
// マウスアウト
function mouseout(d, i){
	d3.select(this).classed({"selected_node": false, "unselected_node": true});
	d3.select("body").select("#tooltip").style("visibility", "hidden");
}
// マウス移動
function mousemove(d, i) {
	var top = (d3.event.pageY-20);
	var left = (d3.event.pageX+10);
	d3.select("body").select("#tooltip").style("top" , top+"px").style("left", left+"px");
}
