<?php 

/**
* the SS class
*/
class SS {

	// pattern types
	const PHP 		= "php";
	const SNIPPET 	= "snippet";
	const REPEATER 	= "repeater";
	const SECTION 	= "section";

	private 
		$regex = [
			'snippet' => "/#([a-zA-Z][\w]+?) *?{{[\s\r\t\n]*(.*?)[\s\r\t\n]*?}}(?=\s*\#|;)/is"
		];
	private static 
		$patterns = [],
		$snippets = [];
	/**
	 * SS constructor method
	 * @param [string] $file [the text file containing the HTMLS code]
	 */
	public function __construct($file) {
		// get file data
		$data = trim(file_get_contents($file)).";"; 
		// parse snipp
		$data = $this->parse($data);
		// sort patterns keys from hight to low ()
		krsort(self::$patterns);
		// assign the parsed data to the snippets static variable
		self::$snippets = $data;

	}

	/**
	 * Parse every snippet 
	 * @param  string $data the HTMLS string
	 * @return [array]       containing parsed snippets
	 */
	public function parse($data = ""){
		$snippets = [];
		// get regex matches
		preg_match_all($this->regex['snippet'], $data,$matches);
		// assign snippets to snippets var
		foreach ($matches[1] as $id => $snippet)
			$snippets[$snippet] = $matches[2][$id];

		return $snippets;
	}

	/**
	 * Return the snippet string 
	 * @param  [string] $name [the snippet name]
	 * @return [string]       [snippet string]
	 */
	public static function get($name){
		return self::$snippets[$name];
	}

	/**
	 * Return a specific pattern function result 
	 * @param  [string] $pattern [the pattern name]
	 * @param  [string] $snippet [the HTMLS string to work on]
	 * @param  [array] $data    [the data used in the snippet]
	 * @return [string]          [the parsed string]
	 */
	public static function usePattern($pattern,$snippet,$data){
		$func = self::$patterns[$pattern];
		return $func($snippet,$data);
	}
	/**
	 * Assigns the pattern function to the static $pattern variable
	 * @param  [string] $name [name of the pattern]
	 * @param  [function] $FUNC [the function sued to parse]
	 */
	public static function pattern($name,$func){
		self::$patterns[$name] = $func;
	}

	/**
	 * Iterates over all patterns and compiles the snippet
	 * @param  [string] $snippet [the snippet string]
	 * @return [string]          [compiled version of the snippet]
	 */
	public static function compile($snippet,$data = []){
		foreach (self::$patterns as $func) {
			$snippet = $func($snippet,$data);
		}
		return $snippet;
	}

	/**
	 * Function called when user calls a snippet method. echoes the parsed snippet
	 * @param  [string] $closure [snippet name]
	 * @param  [array] $ARGS    [data used in the parse process]
	 */
	public function __call($closure, $args){
		// use patterns
		$snippet = self::$snippets[$closure];
		echo SS::compile($snippet,$args[0]);
	}

}

/**
 * PHP pattern function
 * DEF: 
 * 		Parses custom PHP like variables
 * EG:
 * 		$variable_name 					=> simple variable 
 * 		$big_array.small_arary.index 	=> array variable 
 */
SS::pattern(SS::PHP,function($snippet,$data = []){
	preg_match_all("/\\\$([a-z][\w\d\.]*)/i", $snippet, $matches);

	// replace $$(keys) with data if it is not an array
	if(!is_array($data))
		$snippet = str_replace("$$",$data, $snippet);

	foreach ($matches[1] as $key => $variable) {
		$var 	= explode(".", $variable);
		$d 		= $data;
		foreach ($var as $k => $v) {
			if(isset($d[$v]))
				$d = $d[$v];
			else
				$d = $matches[0][$key];
		}
		$snippet = str_replace($matches[0][$key],$d, $snippet);
	}
	return $snippet;
});

/**
 * SNIPPET pattern
 * EG : #<snippetname>
 */
SS::pattern(SS::SNIPPET,function($snippet,$data){
	$regex = '/#([a-zA-Z][a-zA-Z0-9]+)\[?([a-zA-Z0-9]+)?\]?/i';

	$snippets = [];

	preg_match_all($regex, $snippet,$matches);
	// sort matches so the it gets parsed the right way ( %name[PARAM] first then %name second )
	arsort($matches[0]);
	foreach ($matches[0] as $key => $value) {

		$snippetData 	= $matches[1][$key];
		$param 			= $matches[2][$key];
		$snippetData 	=  SS::get($snippetData);

		/**
		 * check is the snippet has a parameter
		 */
		// in case the parameter is missing use the name of the snippet as parameter
		if(!isset($data[$param]))
			$param = $matches[1][$key];	
		// in case paramater is found check if it isset() in the $data ARG
		if(!isset($data[$param])){
			// if it is not set user the snippet as it is (don't perform any parsing)
			$snippets[$matches[1][$key]] = $matches[0][$key]; continue;
		}

		$sectionData 	=  $data[$param];
		$string 		= "";
		$string .= SS::compile($snippetData,$sectionData);

		$snippet = str_replace($matches[0][$key], $string, $snippet);

	}

	// replace the snippets as they are written
	foreach ($snippets as $key => $value) {
		$snip 		= SS::compile(SS::get($key));
		$snippet 	= str_replace($value, $snip, $snippet);
	}

	return $snippet;
});
/**
 * Repeater pattern
 * EG : #<snippetname>
 */
SS::pattern(SS::REPEATER,function($snippet,$data){
	$regex = '/%([a-zA-Z][a-zA-Z0-9]+)\[?([a-zA-Z0-9]+)?\]?/i';

	$snippets = [];

	preg_match_all($regex, $snippet,$matches);
	// sort matches so the it gets parsed the right way ( %name[PARAM] first then %name second )
	arsort($matches[0]);
	
	
	foreach ($matches[0] as $key => $value) {

		$snippetData 	= $matches[1][$key];
		$param 			= $matches[2][$key];
		$snippetData 	=  SS::get($snippetData);

		/**
		 * check is the snippet has a parameter
		 */
		// in case the parameter is missing use the name of the snippet as parameter
		if(!isset($data[$param]))
			$param = $matches[1][$key];	

		// in case paramater is found check if it isset() in the $data ARG
		if(!isset($data[$param])){
			// if it is not set user the snippet as it is (don't perform any parsing)
			$snippets[$matches[1][$key]] = $matches[0][$key]; continue;
		}

		$sectionData 	=  $data[$param];
		$string 		= "";

		foreach ($sectionData as $sectionDataValue) 
			$string .= SS::usePattern(SS::PHP,$snippetData,$sectionDataValue);

		$snippet = str_replace($matches[0][$key], $string, $snippet);

	}

	// replace the snippets as they are written
	foreach ($snippets as $key => $value) {
		$snip 		= SS::compile(SS::get($key));
		$snippet 	= str_replace($value, $snip, $snippet);
	}

	return $snippet;
});

/**
 * SNIPPET pattern
 * EG : #[<data>]{{ <html> }}
 */
SS::pattern(SS::SECTION,function($snippet,$data){
	$regex = '/#\[ *?(\w+?) *?\]{{[\s\t\n\r]*(.*?)?[\s\t\n\r]*?}}/is';

	preg_match_all($regex, $snippet,$matches);

	foreach ($matches[0] as $key => $value) {
		$dataVal = $matches[1][$key];

		// check if section has data
		if(!isset($data[$dataVal]))
			continue;

		$sectionData =  $data[$dataVal];
		$snippetData =  $matches[2][$key];

		$string = "";

		foreach ($sectionData as $sectionDataValue) 
			$string .= SS::usePattern(SS::PHP,$snippetData,$sectionDataValue);
		$snippet = str_replace($matches[0][$key], $string, $snippet);

	}

	return $snippet;
})

?>
