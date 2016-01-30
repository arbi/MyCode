<?php

namespace Console\Controller;

use Library\Controller\ConsoleBase;
use Zend\Text\Table\Table;
use Library\Constants\ExternalServices;
use Library\Constants\EmailAliases;
use Library\Constants\TextConstants;

/**
 * Class CurrencyController
 * @package Console\Controller
 */
class CurrencyController extends ConsoleBase
{
    private $nosend = FALSE;

    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode', 'show');

        if ($this->getRequest()->getParam('nosend')) {
            $this->nosend = TRUE;
        }

        switch ($action) {
            case 'show':    $this->showAction();
                break;
            case 'update':  $this->updateAction();
                break;
            case 'check':   $this->checkAction();
                break;
            case 'update-currency-vault':   $this->updateCurrencyVaultAction();
                break;
            default :
                echo '- type true parameter ( currency show | currency update | currency check | currency update-currency-vault )'.PHP_EOL;
                return FALSE;
        }
    }

    public function updateCurrencyVaultAction()
    {
        try {

            $currencyService = $this->getServiceLocator()->get('service_currency_currency');
            $currencyVaultDao = $this->getServiceLocator()->get('dao_currency_currency_vault');
            $currencies =  $currencyService->getCurrencyList('auto_update', 1);
            $currencyArray = [];
            foreach ($currencies as $row) {
                $queryArray[] = 'EUR' . $row->getCode() . '=X';
                $currencyArray[$row->getCode()] = $row->getId();
            }
            $queryString = implode(',', $queryArray);
            if (($handle = fopen('http://finance.yahoo.com/d/quotes.csv?s='.$queryString.'&f='.ExternalServices::YAHOO_APPLICATION_ID.'&e=.csv', 'r')) !== FALSE) {
                $result = '';

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $twoCurrenciesString = $data[6];
                    $twoCurrenciesArray = explode("/", $twoCurrenciesString);

                    if(array_key_exists(1 ,$twoCurrenciesArray)){
                        $toCurrencyCode = trim($twoCurrenciesArray[1]);
                        $rateValue = floatval($data[1]);

                        if($toCurrencyCode && $rateValue){
                            $updateArray = array(
                                'value' => $rateValue,
                                'date' => date('Y-m-d'),
                                'currency_id' => $currencyArray[$toCurrencyCode]
                            );

                            $updateResult = $currencyVaultDao->save($updateArray);

                            if(is_numeric($updateResult) && (int)$updateResult > 0){
                                $result .= $toCurrencyCode.' - saved'.PHP_EOL;
                            } else {
                                $result .= $toCurrencyCode.' - saving failed'.PHP_EOL;
                            }
                        } else {
                            $result .= $toCurrencyCode.' - not saved'.PHP_EOL;
                        }
                    } else {
                        throw new \Exception('Currency conversion string does not have right structure (CUR1/CUR2)');
                    }
                }
                fclose($handle);
                $this->outputMessage($result);
                return TRUE;
            } else {
                $this->gr2emerg('Update currency exchange rates impossible. Yahoo is down!');

                $this->outputMessage('Update currency exchange rates impossible. Yahoo is down!');
                return FALSE;
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Update currency exchange rates possible');

            $this->outputMessage('Update currency exchange rates impossible. Script broken! ' . $e->getMessage());
            return FALSE;
        }
    }

    public function showAction()
    {
        /**
         * @var \DDD\Service\Currency\Currency $currencyService
         */
        $currencyService = $this->getServiceLocator()->get('service_currency_currency');

        $currencies = $currencyService->getCurrencyList();

        $table = new Table(array('columnWidths' => array(3, 20, 5, 7, 14, 12, 5, 20)));
        $table->appendRow(array(
            'id',
            'label',
            'code',
            'symbol',
            'current value',
            'auto update',
            'gate',
            'last updated'
        ));

        foreach ($currencies as $row) {
            $table->appendRow(array(
                $row->getId(),
                $row->getName(),
                $row->getCode(),
                $row->getSymbol(),
                $row->getValue(),
                $row->getAutoUpdate(),
                $row->getGate(),
                $row->getLastUpdated()
            ));
        }
        echo $table;
    }

    public function updateAction()
    {
        try {
            /**
             * @var \DDD\Service\Currency\Currency $currencyService
             */
            $currencyService = $this->getServiceLocator()->get('service_currency_currency');

            $currencies = $currencyService->getCurrencyList('auto_update', 1);

            foreach ($currencies as $row) {
                $queryArray[] = 'EUR' . $row->getCode() . '=X';
            }
            $queryString = implode(',', $queryArray);

            if (($handle = fopen('http://finance.yahoo.com/d/quotes.csv?s='.$queryString.'&f='.ExternalServices::YAHOO_APPLICATION_ID.'&e=.csv', 'r')) !== FALSE) {
                $result = '';

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $twoCurrenciesString = $data[6];
                    $twoCurrenciesArray = explode("/", $twoCurrenciesString);

                    if(array_key_exists(1 ,$twoCurrenciesArray)){
                        $toCurrencyCode = trim($twoCurrenciesArray[1]);
                        $rateValue = floatval($data[1]);

                        if($toCurrencyCode && $rateValue){
                            $updateArray = array(
                                'value' => $rateValue,
                                'last_updated' => date('Y-m-d H:i:s')
                            );
                            $updateResult = $currencyService->updateCurrencyValue(
                                $updateArray,
                                array('code' => $toCurrencyCode)
                            );

                            if($updateResult === 1){
                                $result .= $toCurrencyCode.' - updated'.PHP_EOL;
                            } else {
                                $result .= $toCurrencyCode.' - update failed'.PHP_EOL;
                            }
                        } else {
                            $result .= $toCurrencyCode.' - not updated'.PHP_EOL;
                        }
                    } else {
                        throw new \Exception('Currency conversion string does not have right structure (CUR1/CUR2)');
                    }
                }
                fclose($handle);
                $this->outputMessage($result);
                return TRUE;
            } else {
                $this->gr2emerg('Update currency exchange rates possible. Yahoo is down!');

                $this->outputMessage('Update currency exchange rates possible. Yahoo is down!');
                return FALSE;
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Update currency exchange rates possible');

            $this->outputMessage('Update currency exchange rates impossible. Script broken! ' . $e->getMessage());
            return FALSE;
        }
    }

    public function checkAction()
    {
        try {
            /**
             * @var \DDD\Service\Currency\Currency $currencyService
             */
            $currencyService = $this->getServiceLocator()->get('service_currency_currency');

            $currencies = $currencyService->getCurrencyList();

            $serviceLocator = $this->getServiceLocator();
            $mailer = $serviceLocator->get('Mailer\Email');
            $notUpdatedCurrencies = array();

            foreach ($currencies as $row) {
                $diff = strtotime('now') - strtotime($row->getLastUpdated());

                if ($diff >= 108000) { // 108000 - 30 hours
                    $notUpdatedCurrencies[] = $row->getCode();

                    $this->outputMessage($row->getCode() . ' - has not been updated in more than 30 hours');
                } else {
                    $this->outputMessage($row->getCode() . ' - all right');
                }
            }

            if (!empty($notUpdatedCurrencies)
                AND $this->nosend === FALSE) {
                $mailer->send(
                    'currency-check',
                    array(
                        'layout'       => 'clean',
                        'to'           => EmailAliases::TO_CURRENCY_CHECK,
                        'to_name'      => 'Currency Manager',
                        'from_address' => EmailAliases::FROM_CURRENCY_CHECK,
                        'from_name'    => 'Currency Manager',
                        'subject'      => TextConstants::CRON_CURRENCY_NOT_UPDATED,
                        'currencyCode' => implode(', ', $notUpdatedCurrencies),
                ));

                $this->gr2alert('Currency exchange rates has not been updated in more than 30 hours!',
                        [
                            'currencies' => implode(', ', $notUpdatedCurrencies)
                        ]);

                $this->outputMessage('...a warning email sent to ' . EmailAliases::TO_CURRENCY_CHECK);
            }

            return TRUE;
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Check currency exchange rates possible');

            $this->outputMessage('Check currency exchange rates possible. Script broken! ' . $e->getMessage());
            return FALSE;
        }
    }
}
