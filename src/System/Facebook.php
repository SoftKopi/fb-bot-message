<?php
namespace System;

defined('fb_data') or die('fb_data is not defined !');
use Curl\CMCurl;

/**
* @author Ammar Faizi <ammarfaizi2@gmail.com> https://www.facebook.com/ammarfaizi2
* @package CURL
* @license RedAngel PHP Concept
*/
class Facebook extends CraynerMachine
{
    const FB_URL = 'https://m.facebook.com';
    private $email;
    private $password;
    private $user;
    private $token;
    public $usercookies;
    /**
    *
    */
    public function __construct($email, $pass, $user=null, $token=null)
    {
        $this->email = $email;
        $this->password = $pass;
        if ($user===null) {
            $email = explode("@", $email);
            $this->user = $email[0];
        } else {
            if (preg_match("#[^\w\d\_\.]#", $user)) {
                throw new \Exception("Error username !", 1);
            }
            $this->user = $user;
        }
        is_dir(fb_data) or mkdir(fb_data);
        is_dir(fb_data.DIRECTORY_SEPARATOR.'cookies') or mkdir(fb_data.DIRECTORY_SEPARATOR.'cookies');
        $this->usercookies = fb_data.DIRECTORY_SEPARATOR.'cookies'.DIRECTORY_SEPARATOR.$this->user.'.txt';
        file_exists($this->usercookies) or file_put_contents($this->usercookies, '');
    }

    public function set_socks($socks, $mode=1)
    {
        if ($mode==1) {
            $this->socks = $socks;
        } else {
            unset($this->socks);
        }
    }

    public function get_page($to, $post=null, $op=null)
    {
        $st = new CMCurl(((substr($to, 0, 8)=="https://" or substr($to, 0, 7)=="http://")? $to : self::FB_URL.'/'.$to));
        $st->set_cookie($this->usercookies);
        $st->set_useragent();
        if (isset($this->socks)) {
            $st->set_socks($this->socks);
        }
        if (isset($post)) {
            $st->set_post($post);
        }
        if (isset($op)) {
            if (!is_array($op)) {
                throw new Exception("Error get_page options", 1);
            }
            $st->set_optional($op);
        }
        $out = $st->execute();
        $inf = $st->get_info();
        $ern = $st->errno();
        $err = ((bool) $ern ? $ern.' : '.$st->error() : null) and $out = $err;
        $st->close();
        if (isset($inf['redirect_url']) and !empty($inf['redirect_url'])) {
            $out = $this->get_page($inf['redirect_url'], null, array(CURLOPT_REFERER=>$inf['url']));
        }
        return $out;
    }

    public function login()
    {
        $a = $this->get_page('');
        $post = 'email='.urlencode($this->email).'&pass='.urlencode($this->password).'&';
        $a = explode('<form', $a, 2);
        $a = explode('</form', $a[1], 2);

        $ga = explode('action="', $a[0], 2);
        $ga = explode('"', $ga[1], 2);
        $ga = html_entity_decode($ga[0], ENT_QUOTES, 'UTF-8');
    
        $sb = explode('type="submit" name="login"', $a[0], 2);
        $sb = explode('value="', $sb[0]);
        $sb = explode('"', end($sb));
        $sb = html_entity_decode($sb[0], ENT_QUOTES, 'UTF-8');
        $post .= "login=".urlencode($sb)."&";

        $a = explode('<input type="hidden"', $a[0]);

        for ($i=1;$i<count($a);$i++) {
            $b = explode('name="', $a[$i], 2);
            $b = explode('"', $b[1], 2);
            $c = explode('value="', $a[$i], 2);
            $c = isset($c[1]) ? explode('"', $c[1], 2) : array('');
            $post .= '&'.$b[0].'='.urlencode($c[0]);
        }
        return $this->get_page($ga, $post, array(CURLOPT_REFERER=>self::FB_URL));
    }
    public function send_message($messages, $to, $stpr=null, $source=null)
    {
        if ($source===null) {
            $source = $this->get_page("https://m.facebook.com/".$to);
        } else {
            $source = $source;
        }
        $q = explode('action="/messages/send/', $source);
        $q = explode('</form>', $q[1]);
        $a = explode('<input type="hidden"', $q[0]);
        $postfields = array();
        for ($i=1;$i<count($a);$i++) {
            $b = explode(">", $a[$i]);
            $c = explode('name="', $b[0]);
            $d = explode('"', $c[1]);
            $e = explode('value="', $a[$i]);
            $f = isset($e[1]) ? explode('"', $e[1]) : array("","");
            $postfields[$d[0]] = $f[0];
        }
        $action = explode('"', $q[0]);
        $action = $action[0];
        $source=$q=$a=$b=$c=$d=$e=$f=null;
        if ($stpr===null) {
            $postfields['body'] = empty($messages)?"~":$messages;
            $postfields['Send'] = 'Kirim';
            $rt = null;
        } else {
            $postfields['send_photo'] = true;
            $rt = "curl_getinfo";
        }
        return $this->get_page("https://m.facebook.com/messages/send/?".$action, $postfields);
    }
}
