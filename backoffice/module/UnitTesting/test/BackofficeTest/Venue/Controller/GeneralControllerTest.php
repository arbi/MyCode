<?php

namespace BackofficeTest\Venue\Controller;

use DDD\Service\Venue\Venue;
use Library\UnitTesting\BaseTest;

class GeneralControllerTest extends BaseTest
{
    /**
     * Venue index page test
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/venue');
        $this->assertResponseStatusCode(200);
    }

    /**
     * Venue edit page test
     */
    public function testEditActionCanBeAccessed()
    {
        $this->dispatch('/venue/edit');
        $this->assertResponseStatusCode(200);
    }

    /**
     * Venue Ajax-Get-Venue-List page test
     */
    public function testAjaxGetVenueListCanBeAccessed()
    {
        $this->dispatch('/venue/ajax-get-venue-list');
        $this->assertResponseStatusCode(200);
    }

    /**
     * Venue Ajax-Save page test
     */
    public function testAjaxSaveActionCanBeAccessed()
    {
        $accountsDao = $this->getApplicationServiceLocator()->get('dao_finance_transaction_transaction_accounts');
        $account     = $accountsDao->fetchOne();

        $postData = [
            'name'           => 'Test Venue',
            'type'           => Venue::VENUE_TYPE_LUNCHROOM,
            'currencyId'     => 14,
            'cityId'         => 6,
            'thresholdPrice' => 200,
            'discountPrice'  => 100,
            'perdayMaxPrice' => 200,
            'perdayMinPrice' => 0,
            'acceptOrders'   => Venue::VENUE_ACCEPT_ORDERS_ON,
            'status'         => Venue::VENUE_STATUS_ACTIVE,
            'managerId'      => 288,
            'cashierId'      => 288,
            'account_id'     => $account->getId(),
        ];

        $request = $this->getRequest();
        $headers = $request->getHeaders();
        $headers->addHeaders(
            [
                'X-Requested-With' =>'XMLHttpRequest'
            ]
        );

        $this->dispatch('/venue/ajax-save', 'POST', $postData);
        $this->assertResponseStatusCode(200);

        $response = json_decode($this->getResponse()->getContent(), true);
        $this->assertEquals($response['status'], 'success', 'Response status is not "success"');
    }
}