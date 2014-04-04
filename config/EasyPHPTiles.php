<?php
/**
*   EasyPhpTiles 1.0, Copyright (C) 2014 c. zanotti
*	This program is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   This program is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* @link    	Real application http://www.marinahotel.it
* @author  	C. Zanotti 
* @Desc		It's an easy and basic PHP version inspired to struts tiles that I have found useful
* 			to handle a simple hotel website with nested contents. Compatible with PHP >= 5.2.17
* 
* 			<!-- 1) DEFINE layout.html -->
* 			<html>
* 			<head>
* 				<title><tiles::insert attribute="title"/></title>
* 			</head>
* 			<body>
* 				<div><tiles::insert attribute="header"/></div>
* 				<div><tiles::insert attribute="content"/></div>
* 				<div><tiles::insert attribute="footer"/></div>
* 			<body>
* 			</html>
* 
* 			<!-- 2) DEFINE TILES-DEFS.XML-->
* 			<definition name="Tiles.BaseDefinition" layout="layout.html">
*  				<put name="header" value="header.html"/>
*   			<put name="footer" value="footer.html"/>
*			</definition>
*
*			<definition name="Tiles.whateverPage" extends="Tiles.BaseDefinition">
*				<put name="title" value="Hello World!"/>
*  				<put name="content" value="whateverPage.html"/>
* 			</definition>
* 
* 			<!-- 3) DEFINE HTML PAGES: header.html, footer.html, whateverPage.html-->
* 
* 			<!-- 4) DEFINE whateverPage.php-->
* 			include_once 'EasyPHPTiles.PHP';
* 			$tilesMap->load('Tile.whateverPage');
* 			
*/

/**
 * Tile rapresents tile definition (here below Tiles.whateverPage) in tiles-defs.xml.
 */
class Tile{

	/**
	 * $map is built by PUT element of the corresponding tile definition on which NAME is 
	 * the attribute in layout and VALUE the page or text to inject and replace '<tiles::insert attribute=NAME/>')
	 * { 
	 * 	 ['title']   => ['Hello World!'],
	 * 	 ['header']  => ['header.html'] , 
	 * 	 ['content'] => ['content.html'],
	 *   ['footer']  => ['footer.html']
	 * }
	 */
	private $map;
	
	/**
	 * layout.html
	 */
	private $layout;
	
	
	/**
	 * Tiles.whateverPage
	 */
	private $name;
	
	public function __construct() {
       $this->map=array();
   	}
   
   	/**
   	 * for every pattern matching within the layout.html 
   	 * 		<tiles::insert attribute="title"/>
   	 * 		<tiles::insert attribute="header"/>
   	 *		<tiles::insert attribute="content"/>
   	 * 		<tiles::insert attribute="footer"/>
   	 * the callback function 'replaceMatchedContent' is called
   	 */
   	public function solveLayout(){
   		$pattern = "/<[\s]*tiles::insert(.*)\/>/i";
		$data 	 = file_get_contents($_SERVER['DOCUMENT_ROOT']."/".$this->layout);
		$data	 = preg_replace_callback($pattern,array(&$this,"solveMatching") , $data);
		return $data;
   	}
    
   	/**
   	 * for every matching
   	 * 		<tiles::insert attribute="header"/>
   	 * the attribute name is extracted and 
   	 * the corresponding element in the map is extracted:
   	 * 		$element=$this->map['header'];
   	 * then the page or the text is returned and will subsitute 
   	 * the starting matching 
   	 */
	private function solveMatching($matching){
		$attributeStringArray=explode("=",$matching[1]);
		$attribute=substr(trim($attributeStringArray[1]),1,strlen(trim($attributeStringArray[1]))-2);
		if(!array_key_exists($attribute,$this->map)){
			return "";
		}
		$element=$this->map[$attribute];
		
		if(strpos($element,'.html') != 0){
			return file_get_contents($_SERVER['DOCUMENT_ROOT'].'/'.$element);
		}else{
			return $element;
		}	
	}	
	
	/**
   	 * the tile absorbs the configuration of another tile.
   	 * It's useful to easily handle the tile inheritance
   	 */
	public function absorb(Tile $tile){
		$this->setMap($tile->getMap());
		$this->setLayout($tile->getLayout());
	}
	   	
   	public function setMap($map){
   		$this->map=$map;
   	}
   
   	public function getMap(){
   		return $this->map;
   	}
	
 	public function getLayout(){
   		return $this->layout;
   	}
   
   	public function setLayout($layout){
   		$this->layout=$layout;
   	}
   
   	public function getName(){
   		return $this->name;
  	}
   
   	public function setName($name){
   		$this->name=$name;
   	}
}

/**
 * TilesMap includes the map of tiles retrieved from tiles-defs.xml and can load
 * the solved layout related to a Tile from a PHP page.
 */
class TilesMap {

	/**
	 * XML parser
	 */
	private $parser;
	
	/**
	 * a stack to keep tracks of what tile is processing 
	 * in according with the xml structure
	 */
	private $stack;
	
	/**
	 * tiles map built from the tiles-defs.xml parsing
 	 *	{ 
	 * 	 ['Tiles.BaseDefinition'] => [TileObject@Tiles.BaseDefinition],
	 * 	 ['Tiles.whateverPage']   => [TileObject@Tiles.whateverPage] , 
	 * 	}
	 */
	private $map;
	
	public function __construct($file) {
		$this->map		= array();
		$this->stack	= array();
       	$this->parser	= xml_parser_create();
   		$this->build($file);
	}
   
   	/**
   	 * tiles map is built. XML parsing of tiles-defs.xml
   	 */
   	public function build($file){
   		xml_set_object($this->parser, $this);
   		xml_set_element_handler($this->parser,"start","stop");
   		xml_set_character_data_handler($this->parser,"char");
   		if(!file_exists($file)){
			die("file '$file' doesn't exist");
		}
		if(!is_readable($file)){
			die("file '$file' is not readable");
		}
		$fp=fopen($file,"r");
		while ($data=fread($fp,4096)){
  			xml_parse($this->parser,$data,feof($fp)) or die (sprintf("XML Error: %s at line %d", 
  			xml_error_string(xml_get_error_code($this->parser)),
  			xml_get_current_line_number($this->parser)));
  		}
		xml_parser_free($this->parser);		
   	}
   	
   	/**
   	 * tile named '$tileName' is retrieved from the map and its related layout is solved
   	 * printing its text (solved)
   	 */
 	public function load($tileName){
  		$map=$this->getMap();
  		$tile=$map[$tileName];
  		echo $tile->solveLayout();
  	}
   	
  	/**
  	 * XML process handler function.
  	 */
	public function start($parser,$element_name,$element_attrs) {
		
		switch($element_name){
	    	case "DEFINITION":
	    		$tile = new Tile();
	    		$tile->setName($element_attrs['NAME']);
	    	
	    		if(!empty($element_attrs['EXTENDS'])){
	    			$parent=$this->map[$element_attrs['EXTENDS']];
	    			$tile->absorb($parent);
	    		} else if(!empty($element_attrs['LAYOUT'])){
	    			$tile->setLayout($element_attrs['LAYOUT']);
	    		}
	    		array_push($this->stack,$tile);
	    	break;
	    	case "PUT":
	    		$tile = array_pop($this->stack);
	    		$map=$tile->getMap();
	    		$map[$element_attrs['NAME']]=$element_attrs['VALUE'];
	    		$tile->setMap($map);
	    		array_push($this->stack,$tile);
	    	break;
	    }
  	}
  	
  	/**
  	 * XML process handler function.
  	 */
	private function stop($parser,$element_name){
		switch($element_name){
		    	case "DEFINITION":
		    		if(!empty($this->stack)){
		    			$previous_tile=array_pop($this->stack);
		    			$this->map[$previous_tile->getName()]=$previous_tile;
		    		}
		    	break;
		    }
  	}
  	
  	/**
  	 * XML process handler function.
  	 */
	public function char($parser,$data){
  		echo '';
  	}
  	
   	public function getMap(){
   		return $this->map;
   	}
  
}

/**
 * Checking if tilesMap has been already instantiated
 */
session_start();
if(!isset($_SESSION['tilesMap'])){
	$tilesMap= new TilesMap($_SERVER['DOCUMENT_ROOT']."/config/tiles-defs.xml");
	$_SESSION['tilesMap'] =$tilesMap; 
} else {
	$tilesMap=$_SESSION['tilesMap'];
}
/*header("Content-Type text/plain; charset=ISO-8859-1");*/

/**
 * DEFINE 'whateverPage.php':
 * <?php
 * require ($_SERVER['DOCUMENT_ROOT']."/yourPath/EasyPHPTiles.php");//this PHP file
 * $tilesMap->load('Tile.whateverPage');
 * 
 */