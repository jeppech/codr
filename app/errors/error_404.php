<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>
			<?=$heading?>
		</title>
		
		<style type="text/css">
		#wrapper
		{
			width: 800px;
			margin: 40px auto;
		}
		#wrapper img
		{
			width: 300px;
			margin-bottom: 20px;
		}
		h1
		{
			font-family: 'Verdana', 'Century Gothic';
			margin: 0;
			padding: 0;
		}
		p
		{
			border: 1px solid #b9cfd5;
			background: #deeff4;
			font-size: 14px;
			font-family: 'Verdana', 'Century Gothic';
			padding: 10px;
		}
		</style>
	</head>
	<body>
		<div id="wrapper">
			<img src="/<?=APPPATH.'layout/media/codr.png'?>" alt="codr logo" title="Welcome to codr Framework" />
			<h1><?=$heading?></h1>
			<p><?=$message?></p>
		</div>
	</body>
</html>