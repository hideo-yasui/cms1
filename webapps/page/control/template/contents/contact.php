<!-- Content Wrapper. Contains page content -->
<div class="" style="width:95%;margin:0 auto;">
	<div class="" style="min-height:768px;">
		<h2 class="main-title">お申込み・お問い合わせ</h2>
		<h6 class="mb-4 ml-4">
			当塾に関するお問い合わせについて、以下のフォームにてお受けいたします。<br>
		</h6>
		<?php view($data["_directory"]["partial"]."subpage.php", $data);?>
	</div>
</div>
<script>
$(function(){
	base.showPage("sub", "contact_add", {}, function(){
		base.pageOpen("sub");
	});
})
</script>
