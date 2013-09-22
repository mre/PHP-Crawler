<?php
class crawler
{
    /* * * * * * * * *\
     * Author: Tux.
     * 
     * Usage
     * -----------
     * $foo = new crawler('FULL_URL_HERE','BASE_URL',DPETH,GET_EMAILS,STAY_ON_SAME_DOMAIN);
     *
     * EXAMPLE: 
     * $foo = new crawler('http://bostonherald.com/about/contact','bostonherald.com',2,true,true);
     *
     * TO EXECUTE: 
     * $foo->init()
     *
     * OTHER FUNCTIONS:
     * ---------------
     *
     *  SET A PROXY: 
     * $foo->set_proxy('PROXY_IP','PORT'); //if you want a proxy (optional)
     *
     * CHANGE URL WITHOUT CREATING A NER OBJECT:
     * $src = $foo->getSource("URL_HERE");
     *
     * GET EMAIL LIST IF YOU SET EMAIL SCRAPING TO TRUE:
     * # $foo->parseHTML($src,'email'));
     *
     * DUMP ANY ERRORS:
     * $foo->getErrors());
     *
     * QUICKLY PARSE A WEBPAGE FOR URLS AND RETURN THEM:
     * crawler::parseHTML($html);
     */
    # set the variables
    private $url,$errors = array(),$proxy = array(0),$emails;
    public  $userAgent,$sameDomain,$depth,$type;
    
    #iniciate the class (requires url)
    public function __construct($url,$domain,$depth=1,$emails=0,$sameDomain=0,$type='url',$userAgent='Googlebot/2.1 (http://www.googlebot.com/bot.html)') {
        $this->setUrl($url);
        $this->emails     = $emails;
        $this->type       = $type;
        $this->userAgent  = $userAgent;
        $this->sameDomain = $sameDomain;
        $this->depth      = $depth;
        $this->domain     = $domain;
    }
    
    # # # # # # # # # # # # # # # # # # #
    # status and information displaying #
    # # # # # # # # # # # # # # # # # # #
    
    public function getErrors() {
        return $this->errors;
    }
    
    # # # # # # # # # # # # # # #
    # set parrameter functions  #
    # # # # # # # # # # # # # # #
    
    # set url or set error
    private function setUrl($url) {
        ( ( $this->is_url($url) ) ? ( ( $this->url_exists($url) ) ? $this->url = $url : $this->errors['url'] .= "error: url not reachable.\n" ) : $this->errors['url'] .= "error: malformed url.\n" );
    }
    
    public function set_proxy($ip,$port) {
        if($this->check_proxy($ip,$port)) {
            $this->proxy[0] = 1;
            $this->proxy[1] = "$ip:$port";
        } else {
            $this->errors['proxy'] .= "Could not connect to given proxy.\n";
        }
    }
    
    private function check_proxy($ip,$port) {
        $ch = curl_init('http://api.proxyipchecker.com/pchk.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,'ip='.$ip.'&port='.$port);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        list($res_time, $speed, $country, $type) = explode(';', curl_exec($ch));
        return ( ($country) ? true : false );
    }
    
    # # # # # # # # #
    # url functions #
    # # # # # # # # #
    
    #verrify url syntax
    private function is_url($url) { 
        return filter_var($url, FILTER_VALIDATE_URL);
    }
    
    # verrify server can access the url
    private function url_exists($url){
        $resURL = curl_init(); 
        curl_setopt($resURL, CURLOPT_URL, $url); 
        curl_setopt($resURL, CURLOPT_BINARYTRANSFER, 1); 
        curl_setopt($resURL, CURLOPT_HEADERFUNCTION, 'curlHeaderCallback'); 
        curl_setopt($resURL, CURLOPT_FAILONERROR, 1); 
        curl_setopt($resURL, CURLOPT_USERAGENT,$this->userAgent);
        curl_setopt($resUR, CURLOPT_TIMEOUT, 5);
        curl_exec ($resURL); 
        $intReturnCode = curl_getinfo($resURL, CURLINFO_HTTP_CODE); 
        curl_close ($resURL); 
        return ( ($intReturnCode != 200 && $intReturnCode != 302 && $intReturnCode != 304) ? 0 : 1 );
    }
    
    # # # # # # # # # # # # # # # # # #
    # crawling and parsing functions  #
    # # # # # # # # # # # # # # # # # #
    
    # fetch a pages source
    public function getSource($url='',$curlMaxExecTime=5) {
        $url = ( ($url=='') ? (($this->url=='') ? 'bad' : $this->url ) : $url );
        if(is_numeric($curlMaxExecTime) && $this->is_url($url) && $this->url_exists($url) ) {
            $ch = curl_init();
            if($this->proxy[0]) {
                curl_setopt($ch, CURLOPT_PROXY, $this->proxy[1]);
            }
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $curlMaxExecTime);
            $html = curl_exec($ch);
            if(!$html) {
                $this->errors['src'][$url] = "\$could not fetch source.\n";
            } else {
                return $html;
            }
        } else {
            if(!is_numeric($curlMaxExecTime)) $this->errors['src']['settings'] .= "\$curlMaxExecTime must be numeric.\n";
            if(!$this->is_url($url) || !$this->url_exists($url)) $this->errors['src']['url'] .= "\$url must be a valed url.\n";
        }
    }
    
    # fetch URLs from html and return array
    public function parseHTML($html,$data='url') {
        if($data == 'email') {
            preg_match_all('/[a-z0-9_\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i', $html, $matches);
            return $matches[0];
        } elseif($data == 'google'){
            preg_match_all('/a href="([^"]+)" class=l.+?>.+?<\/a>/', $html, $matches);
            return $matches[0];
        } else {
            # url pattern
            $dom = new DOMDocument;
            $dom->loadHTML($html);
            $links = $dom->getElementsByTagName('a');
            $ret = array();
            foreach ($links as $link){
                array_push($ret,$link->getAttribute('href'));
            }
            return $ret;
        }
    }
    public function init() {
        $depth      = $this->depth-1;
        $url        = $this->url;
        $domain     = $this->domain;
        $sameDomain = $this->sameDomain;
        $i          = 0;
        $urls       = array($url);
        $data       = array($url);
        $elist      = array();
        while($i<=$depth) {
            $tmp = array();
            foreach($urls as $u) {
                array_push($data,$u);
                $html       = $this->getSource($u);
                $xyz       = $this->parseHTML($html,$this->type);
                if($this->emails) {
                    $xxxx = $this->parseHTML($html,'email');
                    $elist = array_merge($elist,array_unique($xxxx));
                }
                $tmp = array_merge($tmp, array_unique($xyz));
            }
            if($sameDomain) {
                $jar = array();
                foreach($tmp as $x) {
                    $tld_regex = '/\.(com|org|net|me|in|io|cm|uk|biz|ly|tk|edu|gov|mil|info|xxx|pw|ws|ru|ro|asia|us|se)(\.|\/|)/i';
                    if(preg_match("/$domain/i",$x) || !preg_match('/(\:\/\/|www\.)/i',$x) && !preg_match($tld_regex,$x) ) array_push($jar,$x);
                }
                $tmp = $jar;
            }
            $urls = $tmp;
            $i++;
        } 
        $tmp = array();
        foreach($data as $x) {
            $tld_regex = '/\.(com|org|net|me|in|io|cm|uk|biz|ly|tk|edu|gov|mil|info|xxx|pw|ws|ru|ro|asia|us|se)(\.|\/|)/i';
            if(!preg_match("/$domain/i",$x) && !preg_match('/(\:\/\/|www\.)/i',$x) && !preg_match($tld_regex,$x)) $x = "http://$domain/$x";
            if(!preg_match('/(htt|ft)p(s|)\:\/\//i',$x)) $x = "http://$x";
            $x = str_replace('////','//',$x);
            $x = str_replace('///','//',$x);
            array_push($tmp,$x);
        }
        $hacky['urls'] = array_unique($tmp);
        if($this->emails) {
            $hacky['emails'] = $elist;
        }
        return $hacky;
    }
}
?>
