<?php
if(isset($data["data"]["get_headermenu"])){
	$headermenu = $data["data"]["get_headermenu"];
}
else {
	$headermenu = array();
}
?>
<div class="pl-2 bg-white">
	<span class="text-lg"><img src="/img/school/logo.png"  style="width:200px; height:auto;"/></span>
	<span class="text-sm ml-3 d-none d-sm-inline-block">～プログラミング学習を通した自立学習の促進！～</span>
	<a class="float-right mr-4 mt-3" data-toggle="dropdown" href="#" style="color:#000;font-size:2rem;">
		<i class="fa fa-bars"></i>
	</a>
	<div class="bg-white dropdown-menu dropdown-menu-right">
	<?php
	for($i=0,$n=count($headermenu);$i<$n;$i++){
		$name = $headermenu[$i]["NAME"];
		$option = $headermenu[$i]["OPTION_STRING"];
		$style = $headermenu[$i]["STYLE"];
echo <<<EOT
		<a href="/$option" class="dropdown-item">
			<i class="fa fa-$style mr-2"></i>
			$name
		</a>
EOT;
		if($i!==$n-1){
		}
	}
	?>
			</div>

</div>

<!-- Navbar -->
<nav class="navbar navbar-expand bg-info navbar-light border-bottom">
<!-- Left navbar links -->
<ul class="navbar-nav" style="margin:0 auto;">
<?php
for($i=0,$n=count($headermenu);$i<$n;$i++){
	$name = $headermenu[$i]["NAME"];
	$option = $headermenu[$i]["OPTION_STRING"];
	$style = $headermenu[$i]["STYLE"];
echo <<<EOT
	<li class="nav-item d-none d-sm-inline-block" >
		<a href="/$option" class="nav-link">$name
		</a>
	</li>
EOT;
}
?>
</ul>

</nav>
<!-- /.Navbar -->
