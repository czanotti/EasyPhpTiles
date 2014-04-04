Hello!
This is a very basic tiles managememt inspired to struts tiles.
For advanced purposes please download more sophisticated PHP frameworks.
I used this engine to set up a SIMPLE web site with highly nested content (www.marinahotel.it) with the intent to easily mantain every sub part.
(I implemented also a JQUERY layer to dinamically inject web pages and make all the mechanism a little prettier (not yet present here!))
=======================================================================
You can define your tiles, into "config/tiles-defs" and call them from php files like in index.php.
In the demo there is a common Tile based on layout/newLayout.html and inherited by the other three tiles (home, page1 and page3). 
I used MAMP with PHP 5.5.3, I copied all project's content into MAMP/htdocs then via http//localhost you could see 
the demo. 
If you don't want to use it in the root server folder you should update all paths beside to ($_SERVER['DOCUMENT_ROOT'].'/yourAppFolder/')

Regards! 