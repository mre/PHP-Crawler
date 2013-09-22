PHP-Crawler
===========

Web Crawler - with email/link scraping and proxy support


USAGE:
$foo = new crawler('FULL_URL_HERE','BASE_URL',DPETH,GET_EMAILS,STAY_ON_SAME_DOMAIN);

EXAMPLE: 
$foo = new crawler('http://bostonherald.com/about/contact','bostonherald.com',2,true,true);

TO EXECUTE: 
$foo->init()

OTHER FUNCTIONS:

SET A PROXY: 
$foo->set_proxy('PROXY_IP','PORT'); //if you want a proxy (optional)

CHANGE URL WITHOUT CREATING A NEW OBJECT:
$src = $foo->getSource("URL_HERE");

GET EMAIL LIST IF YOU SET EMAIL SCRAPING TO TRUE:
$foo->parseHTML($src,'email'));

DUMP ANY ERRORS:
$foo->getErrors());

QUICKLY PARSE A WEBPAGE FOR URLS AND RETURN THEM:
crawler::parseHTML($html);
