<style>
	#test_meta_data {
		border : solid #AAA 1px;
		border-collapse:collapse;
	}
	#test_meta_data th{
		background-color:#F0F0F0;
		border-bottom : solid #AAA 1px;
		padding:8px 12px;
	}
	#test_meta_data td{
		border-bottom : solid #AAA 1px;
		padding:8px 12px;
	}
</style>
<script >
/**
* test実施結果
**/
$(function(){
    $("#capture_list .capture img").unbind("mouseover");
    $("#capture_list .capture img").on("mouseover", function(e){ img_preview(e.target);});
    /**
    * 画像プレビューダイアログを表示
    * @method img_preview
	* @param obj {Object} dom(img)
    * @return (void)
    */
    function img_preview(obj){
        var img_html = '<img src="'+obj.src+'" ></img>';
        $("#previewDialog").html(img_html);
        dom.dialogOpen("previewDialog", "画像プレビュー");
        //プレビューからmouseout or clickで、closeする
        $("#previewDialog").on("mouseout", img_preview_close);
        $("#previewDialog").on("mouseup", img_preview_close);
    }
    /**
    * 画像プレビューダイアログを閉じる
    * @method img_preview_close
    * @return (void)
    */
    function img_preview_close(){
        $("#previewDialog").unbind("mouseout");
        $("#previewDialog").unbind("mouseup");
        dom.dialogClose("previewDialog");
    }
});
</script>

<section class="section--basic">
	<div class="section--basic__body">
		<table id="test_meta_data">
			<tr>
				<th>ID</th>
				<td id="test_name">
					<?php echo $data["data"]["get_scenario_test_by_id"][0]["test_name"];?>
				</td>
				<th>実施日時</th>
				<td id="add_time">
					<?php echo $data["data"]["get_scenario_test_by_id"][0]["add_time"];?>
				</td>
				<th>差異数</th>
				<td id="error_count">
					<?php echo $data["data"]["get_scenario_test_by_id"][0]["error_count"];?>
				</td>
			</tr>
			<tr>
				<th>説明</th>
				<td id="test_remark">
					<?php echo $data["data"]["get_scenario_test_by_id"][0]["test_remark"];?>
				</td>
				<th>前回日時</th>
				<td id="prev_add_time">
					<?php echo $data["data"]["get_scenario_test_by_id"][0]["prev_add_time"];?>
				</td>
				<th>前回差異数</th>
				<td id="prev_error_count">
					<?php echo $data["data"]["get_scenario_test_by_id"][0]["prev_error_count"];?>
				</td>
			</tr>
		</table>
		<br>
		<div class="section--list__body">
			<input type="hidden" name="test_id" />
			<div class="list--user">
				<table class="list--user__table">
					<thead>
						<tr>
							<th>No</th>
							<th>Event</th>
							<th colspan=2>今回</th>
							<th colspan=2>前回</th>
							<th>差分</th>
							<th>差分値</th>
							<th>登録日時</th>
						</tr>
					</thead>
					<tbody id="capture_list">
					<?php for($i=0;$i<count($data["data"]["get_scenario_test_capture_by_id"]);$i++){ ?>
						<tr id="<?php echo $data["data"]["get_scenario_test_capture_by_id"][$i]["scenario_event_id"]; ?>">
							<td title="<?php echo "capture_id = ".$data["data"]["get_scenario_test_capture_by_id"][$i]["id"]; ?>">
								<?php echo $data["data"]["get_scenario_test_capture_by_id"][$i]["scenario_event_id"]; ?>
							</td>
							<td title="<?php echo $data["data"]["get_scenario_test_capture_by_id"][$i]["remark"]; ?>">
								<?php echo $data["data"]["get_scenario_test_capture_by_id"][$i]["name"]; ?>
							</td>
							<td>
								<?php echo $data["data"]["get_scenario_test_capture_by_id"][$i]["error_message"]; ?>
							</td>
							<td class="capture">
								<?php if (empty($data["data"]["get_scenario_test_capture_by_id"][$i]["capture"])) :?>
									-
								<?php else : ?>
									<img src="data:image/png;base64,<?php echo $data["data"]["get_scenario_test_capture_by_id"][$i]["capture"]; ?>" width="80" height="80" class="img_preview"></img>
								<?php endif; ?>
							</td>
							<td>
								<?php echo $data["data"]["get_scenario_test_capture_by_id"][$i]["prev_error_message"]; ?>
							</td>
							<td class="capture">
								<?php if (empty($data["data"]["get_scenario_test_capture_by_id"][$i]["prev_capture"])) :?>
									-
								<?php else : ?>
									<img src="data:image/png;base64,<?php echo $data["data"]["get_scenario_test_capture_by_id"][$i]["prev_capture"]; ?>" width="80" height="80" class="img_preview"></img>
								<?php endif; ?>
							</td>
							<td class="capture">
								<?php if ($data["data"]["get_scenario_test_capture_by_id"][$i]["diff_capture_val"] < 10) :?>
									差分なし
								<?php elseif (empty($data["data"]["get_scenario_test_capture_by_id"][$i]["diff_capture"])) :?>
									-
								<?php else : ?>
									<img src="data:image/png;base64,<?php echo $data["data"]["get_scenario_test_capture_by_id"][$i]["diff_capture"]; ?>" width="80" height="80" class="img_preview"></img>
								<?php endif; ?>
							</td>
							<td>
								<?php echo $data["data"]["get_scenario_test_capture_by_id"][$i]["diff_capture_val"]; ?>
							</td>
							<td>
								<?php echo $data["data"]["get_scenario_test_capture_by_id"][$i]["add_time"]; ?>
							</td>
						</tr>
					<?php }?>
					</tbody>
				</table>
			</div>
		</div>
		<div class="form__submit">
			<a href="javascript:void(0);" class="btn icons" style="padding:0 12px 0 2px;display:inline-block;font-size:12px;" accesskey="back">
				<span class="icon back"></span>戻る
			</a>
		</div>
	</div>
</section>
