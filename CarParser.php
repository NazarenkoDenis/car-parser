<?php
require('lib/DomParser.php');

class CarParser {

	const IS_DEBUG = true;
	
	private $pageLimit = self::IS_DEBUG ? 5 : 25;
	
	public function __construct() {
		$this->parser = new simple_html_dom;
	}
	
	public function getPreparedCars() {
		$preparedCars = [];
		$carLinks = $this->getCarLinks();

		foreach ($carLinks as $carLink) {
			$preparedCars[] = $this->getPreparedCar($carLink);
		}

		return $preparedCars;
	}

	public function getCarLinks() {
		$carLinks = [];
		
		for ($page=0; $page <= $this->pageLimit; $page++) { 
echo ('Processed page ' . $page . '<br>');
        	$this->parser->load_file("https://www.olx.ua/uk/transport/legkovye-avtomobili/?page=$page");
        	$carRows = $this->parser->find('.marginright5');
		
			foreach ($carRows as $key => $carRow) {
				$link =  $carRow->attr['href'];
				
				if ($endIndex = strpos($link, '#')) {
					$link = substr($link, 0, $endIndex);
				}

				$carLinks[] = $link;
			}
		}

		return $carLinks;
	}

	public function getPreparedCar($url) {
		$carData =[];
		$this->parser->load_file($url);
		$options = $this->parser->find('.offer-details__item a');

		foreach ($options as $option) {
			var_dump($option->find('span')); die;
			$optionValue = $option->attr['title'];
			if ($endIndex = strpos($optionValue, '-')) {
					$optionValue = substr($optionValue, 0, $endIndex);
				}
			$carData[] = ($optionTitle);
		}

		$preparedCar = [
			'offerType' => $carData[0],
			'make'      => $carData[1],
			'model'     => $carData[2],
			'type'      => $carData[3],
			'color'     => $carData[4]
		];

		var_dump($preparedCar); die;
	}

}