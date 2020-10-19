<?php
require('lib/DomParser.php');
use simple_html_dom as DomParser;

class CarParser {
	const IS_DEBUG = true;
	private $pageLimit = self::IS_DEBUG ? 1 : 25;

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
        $options = $this->domParser->find('.offer-details__item');

        foreach ($options as $option) {
            $optionTitle = $this->getOptionTitle($option);
            $optionValue = $this->getOptionValue($option);

            if ($endIndex = strpos($optionValue, '-')) {
                $optionValue = substr($optionValue, 0, $endIndex);
            }

            $optionTitle = ucfirst(strtolower($optionTitle));
            $optionValue = ucfirst(strtolower($optionValue));

            $carData[$optionTitle] =  trim($optionValue);

        }

        return $this->prepareCarData($carData);
    }

    private function prepareCarData($carData) {
        return [
            'ownerType'        => $this->getOwnerType($carData),
            'make'             => $this->getMake($carData),
            'model'            => $this->getModel($carData),
            'year'             => $this->getYear($carData),
            'odometer'         => $this->getOdometerValue($carData),
            'fuelType'         => $this->getFuelType($carData),
            'engineType'       => $this->getEngineType($carData),
            'hash'             => $this->getHash($carData),
            'price_usd'        => $this->getPriceInUsd(),
            'bodyStyle'        => $this->getBodyStyle($carData),
            'color'            => $this->getColor($carData),
            'carState'         => $this->getCarState($carData),
            'transmission'     => $this->getTransmissionType($carData),
            'climateControl'   => $this->isClimatControlExists($carData),
            'isDamaged'        => $this->isDamaged($carData),
            'isRepaint'        => $this->isRepaint($carData),
            'isCustomsCleared' => $this->getIsCustomsCleared($carData),
            'url'              => $this->getUrl(),
            'isPromoted'       => $this->getIsPromoted(),
            'description'      => $this->getDescription(),
            'published_at'     => $this->getPublishedDate()
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

    private function getYear($carData) {
        return (int)$carData['Рік випуску'];
    }

    private function getOdometerValue($carData) {
        if (isset($carData['Пробіг'])) {
            $odometerValue = str_replace(' ', '', $carData['Пробіг']);

            return (int)$odometerValue;
        } else {
            return 'N/A';
        }
    }

    private function getOdometerCoefficient($carData)
    {
        if (isset($carData['Пробіг'])) {
            $odometerValue = str_replace(' ', '', $carData['Пробіг']);
        } else {
            return 0;
        }

        switch ($odometerValue) {
            case ($odometerValue >= 1 && $odometerValue < 10000):
                return 1;

            case ($odometerValue >= 10000 && $odometerValue < 50000):
                return 2;

            case ($odometerValue >= 50000 && $odometerValue < 100000):
                return 3;

            case ($odometerValue >= 100000 && $odometerValue < 150000):
                return 4;

            case ($odometerValue >= 150000 && $odometerValue < 200000):
                return 5;

            case ($odometerValue >= 200000 && $odometerValue < 250000):
                return 6;

            case ($odometerValue >= 250000 && $odometerValue < 300000):
                return 7;

            case ($odometerValue >= 300000):
                return 8;
        }
    }

    private function getBodyStyle($carData) {
        return $carData['Тип кузова'];
    }

    private function getColor($carData) {
        return $carData['Kолір'];
    }

    private function getFuelType($carData) {
        return $carData['Вид палива'];
    }

    private function getEngineType($carData) {
        $engineType = $carData['Об\'єм двигуна'];
        $engineType = str_replace(' ', '', $engineType);

        return (float)$engineType;
    }

    private function getHash($carData) {
        $make                 = $this->getMake($carData);
        $model                = $this->getModel($carData);
        $year                 = $this->getYear($carData);
        $odometerCoefficient  = $this->getOdometerCoefficient($carData);
        $bodyStyle            = $this->getBodyStyle($carData);

        return md5($make . $model . $year . $odometerCoefficient . $bodyStyle);

    }

    private function getTransmissionType($carData) {
        return $carData['Коробка передач'];
    }

    private function getIsCustomsCleared($carData) {
        return ($carData['Розмитнена'] === 'Так') ? true : false;
    }

    private function isClimatControlExists($carData) {
        return (strpos($carData['Додаткові опції'], 'Кондиціонер') >= 0) ? true : false;
    }

    private function isDamaged($carData) {
        return (strpos($carData['Стан машини'],'Не бита') >= 0) ? false : true;
    }

    private function getCarState($carData) {
        return $carData['Стан машини'];
    }

    private function isRepaint($carData) {
        return (strpos($carData['Стан машини'], 'Не фарбований') >= 0) ? false : true;
    }

    private function getUrl() {
        if ($endIndex = strpos($this->url, '#')) {
            return substr($this->url, 0, $endIndex);
        }

        return $this->url;
    }

    private function getIsPromoted() {
        if (strpos($this->url, 'promoted') > 0){
            return true;
        }

        return false;
    }

    private function getDescription() {
        $description =  $this->domParser->find('#textContent');
        $description = strip_tags($description[0]->innertext);

        return  trim($description);
    }

    private function getPublishedDate() {
        $date = $this->domParser->find('.offer-bottombar__item > em');
        $date = strip_tags($date[0]->innertext);
        $date = str_replace('в ', '', $date);

        return $date;
    }

    private function getPriceInUsd() {
        // TODO: solve currency issue (currency symbol should be parsed also)
        $price = $this->domParser->find('.pricelabel__value');
        $price = $price[0]->innertext;
        $price = str_replace(' ', '', $price);

        if (strpos($price, '$') == 0 ) {
            $price = $this->convertToUsd($price);
        }

        return (float)$price;
    }

    private function convertToUsd($price) {
        if (strpos($price, '€') > 0) {
            return (float)$price *  0.85;
        } elseif (strpos($price, 'грн') > 0) {
            return (float)$price * 28.37;
        } else {
            return false;
        }
    }

    /**
     * @param DomParser $option
     *
     * @return string|false
     */
    private function getOptionTitle($option) {
        if (isset($option->find('.offer-details__name')[0])) {
            return $option->find('.offer-details__name')[0]->innertext;
        } else {
            return false;
        }
    }

    /**
     * @param DomParser $option
     *
     * @return string|false
     */
    private function getOptionValue($option) {
        if (isset($option->find('.offer-details__value--multiple')[0])) {
            $selector = $option->find('.offer-details__value--multiple')[0];
            $additionalOptions =[];

            foreach ($selector->find('a') as $a) {
                $additionalOptions[] = $a->innertext;
            }

            $additionalOptions = implode(',', $additionalOptions);

            return $additionalOptions;
        }

        if (isset($option->find('.offer-details__value')[0])) {
            $value = $option->find('.offer-details__value')[0];

            return $value->innertext;
        }

        return false;
    }
}