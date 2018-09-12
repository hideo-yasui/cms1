<div class="login-box" style="width:95%;margin:0 auto;">
<div class="" style="min-height:768px;">
	<div class="row">
		<div class="col-md-12 bg-white">
			<h2 class="main-title" id="curriculum">カリキュラム別学習</h2>
			<h6 class="mb-4">
				カリキュラム別の学習内容は、決まった内容を実施するものではなく、<br>
				自由に選択し学ぶことができます。
			</h6>
			<?php view($data["_directory"]["contents_partial"]."scratch.php", $data);?>
			<?php view($data["_directory"]["contents_partial"]."raspberrypi.php", $data);?>
			<?php view($data["_directory"]["contents_partial"]."webapp.php", $data);?>
		</div>
	</div>
</div>
</div>
