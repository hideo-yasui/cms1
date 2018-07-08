var _kitIdStr = "";
var _graphType = "";

$(function(){
    // パラメーター取得
    base.setQueryParam();
    _kitIdStr = service.getQueryParam("kit");
    _graphType = service.getQueryParam("graph");

    // データ取得と設定
    var params = { "kit_id_str" : _kitIdStr };
    getData("get_resultdata_by_kit_id", params, function(dataSource){
        setResult(dataSource);
    });
});

// 検査結果データ設定
function setResult(dataSource){
    var _idx = 0;

    //カレントのデータを取得
    for(var i=0,n=dataSource.length; i<n; i++){
        var kitIdStr = dataSource[i]["kit_id_str"];
        if(_kitIdStr == kitIdStr) {
            _idx = i;
        }
    }

    var data = dataSource[_idx];

    // 検査データリストの設定
    setJudgeResultData(dataSource, data, _idx);

    // ツールチップデータ設定
    setTooltipData();

    // 菌叢についてのデータ設定
    getData("get_flora_judge", {"kit_id_str" : data["kit_id_str"]}, function(data){
        setFloraJudgeData(data, {"diarrhea" : data["is_diarrhea"], "constipation" : data["is_constipation"]});
    });

    // チャート設定
    setCharts(data, dataSource);
}

// チャート設定
function setCharts(data, dataSource){
    setBarChart();
    setStackbarchartV2(data, getPreviousData(dataSource, data));
	setPiechart(data);
	setStackbarchart(data);
}
