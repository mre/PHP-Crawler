<pre>
<?php
require 'crawler.class.php';
$foo = new crawler('http://bostonherald.com/about/contact','bostonherald.com',2,true,true);
$results = $foo->init();
print_r($results);
?>
