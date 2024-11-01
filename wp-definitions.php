<?php
/*
Plugin Name:WP-Definitions
Plugin URI: http://www.linksback.org
Description: WP-Definitions is an easy way to bring clarity to your blog. By adding double square brackets around any word like this [[word]] and the definition of that word will be included at the bottom of your post and the word will become a named anchor linking to the definition. Easy to install and easy to use. All definition that you use will be stored your database so the word will only have to be looked up once.
Version: 1.0
Author: Eric Medlin
Author URI: http://www.linksback.org
*/
require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
global $wpdb , $wp_roles;	

function wp_defs_install () {
global $wpdb;
$wp_defs_table = "CREATE TABLE `".$wpdb->prefix."defs` (
  `id` int(10) NOT NULL auto_increment,
  `word` varchar(255) NOT NULL,
  `definition` mediumtext NOT NULL,
  `pronounce` varchar(255) NOT NULL,
  `function` varchar(255) NOT NULL,
  `usage` varchar(255) NOT NULL,
  `date` varchar(100) NOT NULL,
  `etym` mediumtext NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;";

maybe_create_table(($wpdb->prefix."defs"),$wp_defs_table);
}

if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
   add_action('init', 'wp_defs_install');
}

function get_definition($word, $num="1"){
	global $wpdb;
	$sql = "SELECT * FROM {$wpdb->prefix}defs WHERE word = '{$word}'";
	$res = mysql_query($sql);
	$result = mysql_fetch_assoc($res);
	if (is_array($result)){
		
		$pronounce = $result['pronounce'];
		$definition = $result['definition'];
		
		$definition = explode("|||||",$definition);
		$function = $result['function'];
		$usage = $result['usage'];
		$date = $result['date'];
		$etymology = $result['etym'];
	} else {
	    $site = fopen("http://mw1.merriam-webster.com/dictionary/$word",'r');
	    while($cont = fread($site,1024657)){
	        $total .= $cont;
	    }
	    fclose($site);
	    $match_expression = '/\<dd class="pron"\>(.*)\<\/dd\>/Us';
	    preg_match($match_expression,$total,$matches);
	    $pronounce = $matches['1'];
		
		$match_expression = '/[0-9]\<\/span\><span class="sense_content"\>\<strong\>:\<\/strong\>.(.*)sense_break/Us';
		preg_match_all($match_expression,$total,$matches);
		$definition = $matches['1'];
		if(strlen($definition[1]) < 1){
			$match_expression = '/\<span class="sense_content"\>\<strong\>:\<\/strong\>.(.*)\<\/span\>/Us';
			preg_match_all($match_expression,$total,$matches);
			$definition = $matches['1'];
		}
		
		$match_expression = '/\<dd class="func"\>\<em\>(.*)\<\/em\>/Us';
		preg_match($match_expression,$total,$matches);
		$function = $matches['1'];
		
		$match_expression = '/\<dd class="use"\>\<em\>(.*)\<\/em\>/Us';
		preg_match($match_expression,$total,$matches);
		$usage = $matches['1'];
		
		$match_expression = '/\<dd class="date"\>(.*)\<\/dd\>/Us';
		preg_match($match_expression,$total,$matches);
		$date = $matches['1'];
		
		$match_expression = '/\<dd class="ety"\>(.*)\<\/dd\>/Us';
		preg_match($match_expression,$total,$matches);
		$etymology = $matches['1'];
		
		$pronounce = mysql_real_escape_string($pronounce);
		$d = "";
		foreach ($definition as $value) {
			$value = strip_tags($value);
			$value = mysql_real_escape_string($value);
			$d .=  "$value|||||";
		}
		$function = mysql_real_escape_string($function);
		$usage = mysql_real_escape_string($usage);
		$etymology = mysql_real_escape_string($etymology);
		
		$sql = "	INSERT INTO 
						{$wpdb->prefix}defs ( 
							word,
							pronounce,
							definition,
							`function`,
							`usage`,
							`date`,
							`etym` )
					VALUES (
						'{$word}',
						'{$pronounce}',
						'{$d}',
						'{$function}',
						'{$usage}',
						'{$date}',
						'{$etymology}' )";
		mysql_query($sql);
		$from_def = "True";
	}
		
		
		$m = "<a name=\"wp_def$num\"></a>$num <b>$word</b><br />";
		$i=1;
		$m .= "Definitions<br /><ol>";
		$a_num = count($definition);
		foreach ($definition as $value) {
			$value = strip_tags($value);
			if ($i != "$a_num" || $from_def == "True"){
				$m .=  "<li class='wp_def_li'>$value</li>";
			}
			$i++;
		}
		$m .= "</ol>";
		$function = strip_tags($function);
		$usage = strip_tags($usage);
		$date = strip_tags($date);
		$pronounce = str_replace("\\n","",$pronounce);
		$pronounce = str_replace("\\","",$pronounce);
		$etymology = strip_tags($etymology);
		if ($pronounce != ""){
			$m .= "Pronounciation: $pronounce<br />";
		}
		if ($function != ""){
			$m .= "Function: $function";
		}
		if ($usage != ""){
			$m .= "<br /> Usage: $usage";
		}
		if ($date != ""){
			$m .= "<br /> Date: $date";
		}
		if ($etymology != ""){
			$m .= "<br /> Etymology: $etymology <br />";
		}
		$from_def == "f";
		return $m;
	}
	
	
function definition_init($data){
	if(!preg_match("/\[\[(.*)\]\]/", $data)) {
		return $data;
	} else {
	$def = "<span class='wp_def'>";
	$i = 0;
	 	$match_expression = '/\[\[(.*)\]\]/Us';
		$num = preg_match_all($match_expression,$data,$nbm);
		while ($i < $num) {
			preg_match($match_expression,$data,$matches);
			$def_num = $i+1;
			$data = preg_replace($match_expression,"<a href=\"#wp_def$def_num\">{$matches[1]}<sup>$def_num</sup></a>",$data,1);
			$def .= get_definition($matches['1'], $def_num);
			$def .= "<br />";
			$i++;
		}
		$anchor = '~~Definitions~~<br />';
		$def_style = '<style type="text/css">
		.wp_def {
			font-size:10px;
			color:#000000;
		}
		.wp_def_li {
			margin-top:-3px;
		}
		</style>';
		$def .= "<a href='http://www.linksback.org' title='Wordpress Definitions Plugin'>Definitions By WP-Definitions!</a></span>";
		$data = $def_style.$data.$anchor.$def;
		return $data;
	}
}
if( function_exists('add_filter') ) {
		add_filter('the_content', 'definition_init'); 
}

function wp_def_info() {
	echo "<style type=\"text/css\">#def_info {
	left:0px;
	z-index:-50;
	width:2px;
	height:2px;
	bottom:1px;
}</style><div id='def_info'><a href='http://www.linksback.org' title='Wordpress Plugins'>WP-Definitions</a></div>";	
}
if( function_exists('add_filter') ) {
		add_action('wp_footer', 'wp_def_info');
}
	
	

?>