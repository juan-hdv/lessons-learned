<?php
	echo "Hellow World! PHP";
	echo nl2br (print_r (apache_get_modules(),true));
	//shell_exec('/usr/local/apache/bin/apachectl -l')
	echo "<br><br>";
	phpinfo();
		
?>
<html>
	<body>
		<b>Hellow Word! HTML</b>
	</body>
</html>
