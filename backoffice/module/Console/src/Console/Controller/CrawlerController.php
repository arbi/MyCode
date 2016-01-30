<?php

namespace Console\Controller;

use Library\Constants\EmailAliases;
use Library\Controller\ConsoleBase;
use Library\OTACrawler\OTACrawler;
use Library\OTACrawler\Product\Apartelle;
use Library\OTACrawler\Product\Apartment;

/**
 * Class CrawlerController
 * @package Console\Controller
 */
class CrawlerController extends ConsoleBase
{
    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode', 'help');

        switch ($action) {
            case 'help': $this->helpAction();
                break;
            case 'update': $this->update();
                break;
            case 'check': $this->update(true);
                break;
            default :
                echo '- type "ginosole crawler help" for instructions' . PHP_EOL;
                return false;
        }
    }

    public function update($check = false)
    {
        $product = $this->getRequest()->getParam('product', null);

        if (!is_null($product) && !in_array($product, ['apartment', 'apartel'])) {
            echo '- type "crawler help" for instructions' . PHP_EOL;
            return false;
        }

        $identityListRAW = $this->getRequest()->getParam('identity', null);
        $otaListRAW = $this->getRequest()->getParam('ota', null);

        $identityList = [];
        $otaList = [];

        if (!is_null($identityListRAW)) {
            $identityListRAW = str_replace(' ', '', $identityListRAW);

            if (strpos($identityListRAW, ',')) {
                $identityList = explode(',', $identityListRAW);
            } else {
                array_push($identityList, (int) $identityListRAW);
            }
        }

        if (!is_null($otaListRAW)) {
            $otaListRAW = str_replace(' ', '', $otaListRAW);

            if (strpos($otaListRAW, ',')) {
                $otaList = explode(',', $otaListRAW);
            } else {
                array_push($otaList, (int) $otaListRAW);
            }
        }

        $action = $check ? 'check' : 'update';

        try {
            $this->outputMessage('Crawler is running');

            if (is_null($product)) {
                $productApartment = new Apartment($identityList);
                $productApartelle = new Apartelle($identityList);

                $crawlerApartment = new OTACrawler($productApartment, $otaList);
                $crawlerApartment->setServiceLocator($this->getServiceLocator());

                if ($this->verboseMode) {
                    $crawlerApartment->setEchoFlag(true);
                }

                $crawlerApartment->$action();

                $crawlerApartelle = new OTACrawler($productApartelle, $otaList);
                $crawlerApartelle->setServiceLocator($this->getServiceLocator());

                if ($this->verboseMode) {
                    $crawlerApartelle->setEchoFlag(true);
                }

                $crawlerApartelle->$action();
            } else {
                $productClass = $product == 'apartment' ? new Apartment($identityList) : new Apartelle($identityList);

                $crawler = new OTACrawler($productClass, $otaList);
                $crawler->setServiceLocator($this->getServiceLocator());

                if ($this->verboseMode) {
                    $crawler->setEchoFlag(true);
                }

                $crawler->$action();
            }

            $this->outputMessage('Crawler ended his job');
        } catch (\Exception $ex) {
            echo $ex->getMessage() . ': ' . $ex->getPrevious()->getMessage() . PHP_EOL;
        }
    }

    public function helpAction()
    {
        echo PHP_EOL;
        echo 'type "ginosole crawler update" to force update all apartments and apartels. ' . PHP_EOL;
        echo 'type "ginosole crawler check" to force check all apartments and apartels. ' . PHP_EOL;
        echo 'type "ginosole crawler update --product=apartment" to force update all apartments. ' . PHP_EOL;
        echo 'type "ginosole crawler check --product=apartment" to force check all apartment connections. ' . PHP_EOL;
        echo 'type "ginosole crawler update --product=apartel" to force update all apartels. ' . PHP_EOL;
        echo 'type "ginosole crawler check --product=apartel" to force update all apartel connections. ' . PHP_EOL;
        echo PHP_EOL;
        echo 'there are extra features to customize request (works for both of action - check and update). ' . PHP_EOL;
        echo PHP_EOL;
        echo '--identity=[identity-id-1,[identity-id-2[, ...]]] filter by apartment or apartel id depends on product type' . PHP_EOL;
        echo '--ota=[ota-id-1,[ota-id-2[, ...]]] filter by OTA id' . PHP_EOL;
        echo PHP_EOL;
        echo 'available OTA ids' . PHP_EOL;
        echo '  1050 - Bookin.com' . PHP_EOL;
        echo '  1052 - EasyToBook' . PHP_EOL;
        echo '  1053 - Venere' . PHP_EOL;
        echo '  1054 - Expedia' . PHP_EOL;
        echo '  1071 - Laterooms' . PHP_EOL;
        echo '  1072 - Hotels.nl' . PHP_EOL;
        echo '  1101 - Yahoo!' . PHP_EOL;
    }
}
