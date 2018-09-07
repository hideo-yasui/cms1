<!-- Content Wrapper. Contains page content -->
<div class="login-box" style="width:95%;margin:0 auto;">
	<div class="card" style="min-height:768px;">
		<div class="card-body login-card-body">
			<?php view($data["_directory"]["contents_partial"]."title.php", $data);?>
			<?php view($data["_directory"]["contents_partial"]."feature.php", $data);?>
	</div>
</div>
<?php view($data["_directory"]["contents_partial"]."access.php", $data);?>
<?php view($data["_directory"]["contents_partial"]."teacher.php", $data);?>
