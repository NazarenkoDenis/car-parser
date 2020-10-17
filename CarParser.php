<?php
require('lib/DomParser.php');

class CarParser {

	public function __construct() {
		$this->parser = new simple_html_dom;
	}
	
	public function parse() {
		$carLinks = [];
		for ($page=0; $page <= 25; $page++) { 
			echo ('Processed page ' . $page);
        	$this->parser->load_file("https://www.olx.ua/uk/transport/legkovye-avtomobili/?page=$page");
        	$carRows = $this->parser->find('.marginright5');
		
			foreach ($carRows as $key => $carRow) {
				$carLinks[] = $carRow->attr['href'];
			}
		}

		return $carLinks;
	} 

}