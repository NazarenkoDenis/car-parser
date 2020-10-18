<?php
require('lib/DomParser.php');
use simple_html_dom as DomParser;
class CarParser {
	const IS_DEBUG = true;
	private $pageLimit = self::IS_DEBUG ? 5 : 25;

    /**
     * @var simple_html_dom
     */
    private $domParser;

    /**
     * @var String
     */
    private $url;

    public function __construct() {
		$this->domParser = new DomParser();
	}

	public function getPreparedCars() {
		$preparedCars = [];
		$carLinks = $this->getCarLinks();

		foreach ($carLinks as $carLink) {
			$preparedCars[] = $this->getCarData($carLink);
		}

		return $preparedCars;
	}

	public function getCarLinks() {
		$carLinks = [];

		for ($page=0; $page <= $this->pageLimit; $page++) {
        	$this->domParser->load_file("https://www.olx.ua/uk/transport/legkovye-avtomobili/?page=$page");
        	$carRows = $this->domParser->find('.marginright5');

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

    public function getCarData($url) {
        $this->url = $url;
        $this->domParser->load_file($url);
        $carData = [];

        /** @var DomParser $options  */
        $options = $this->domParser->find('.offer-details__item a');

        foreach ($options as $option) {
            $optionTitle = $this->getOptionTitle($option);
            $optionValue = $option->attr['title'];

            if ($endIndex = strpos($optionValue, '-')) {
                $optionValue = substr($optionValue, 0, $endIndex);
            }

            $optionTitle = ucfirst(strtolower($optionTitle));
            $optionValue = ucfirst(strtolower($optionValue));

            if (!$optionTitle) {
                $carData['additional_options'][] = trim($optionValue);
            }

            $carData[$optionTitle] =  trim($optionValue);

        }

        return $this->prepareCarData($carData);
    }

    private function prepareCarData($carData) {
        return [
            'ownerType'        => $this->getOwnerType($carData),
            'make'             => $this->getMake($carData),
            'model'            => $this->getModel($carData),
            'bodyStyle'        => $this->getBodyStyle($carData),
            'color'            => $this->getColor($carData),
            'engineType'       => $this->getEngineType($carData),
            'transmission'     => $this->getTransmissionType($carData),
            'climatControl'    => $this->getIsClimatControlExists($carData),
            'isDamaged'        => $this->getIsDamaged($carData),
            'isRepaint'        => $this->getIsRepaint($carData),
            'isCustomsCleared' => $this->getIsCustomsCleared($carData),
            'url'              => $this->url
        ];
    }

    private function getOwnerType($carData) {
        if ($carData['Оголошення від'] = 'Приватної особи') {
            return 'private';
        } else {
            return $carData['Оголошення вiд'];
        }
    }

    private function getMake($carData) {
        return $carData['Марка'];
    }

    private function getModel($carData) {
        return $this->removeMakeFromModel($carData['Марка'], $carData['Модель']);
    }

    private function removeMakeFromModel($make, $model) {
        $model = str_replace($make, '', $model);
        $model = ltrim($model);
        $model = ucfirst(strtolower($model));

        return $model;
    }

    private function getBodyStyle($carData) {
        return $carData['Тип кузова'];
    }

    private function getColor($carData) {
        return $carData['Kолір'];
    }

    private function getEngineType($carData) {
        return $carData['Вид палива'];
    }

    private function getTransmissionType($carData) {
        return $carData['Коробка передач'];
    }

    private function getIsCustomsCleared($carData) {
        return ($carData['Розмитнена'] === 'Так') ? true : false;
    }

    private function getIsClimatControlExists($carData) {
        foreach ($carData['additional_options'] as $option) {
            if ($option === 'Кондиціонер') {
                return true;
            }
        }

        return false;
    }

    private function getIsDamaged($carData) {
        foreach ($carData['additional_options'] as $option) {
            if ($option == 'Не бита') {
                return false;
            }
        }

        return true;
    }

    private function getIsRepaint($carData) {
        foreach ($carData['additional_options'] as $option) {
            if ($option == 'Не фарбований') {
                return false;
            }
        }

        return true;
    }

    private function getOptionTitle($option) {
        if (isset($option->find('.offer-details__name')[0])) {
            return $option->find('.offer-details__name')[0]->innertext;
        } else {
            return false;
        }
    }

}