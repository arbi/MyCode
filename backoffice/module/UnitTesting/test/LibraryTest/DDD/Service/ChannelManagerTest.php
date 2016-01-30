<?php
namespace LibraryTest\DDD\Service;

use DDD\Service\Taxes;
use Library\UnitTesting\BaseTest;
use Library\Utility\Helper;
use Zend\Db\Sql\Select;
use Library\ChannelManager\Provider\Cubilis\ReservationCubilisXMLParser;

class ChannelManagerTest extends BaseTest
{
    public function testPullReservation()
    {
        $output = new \stdClass();

        $files = ['reservation.xml', 'modification.xml', 'cancellation.xml'];

        foreach ($files as $file) {
            $xmlFile = __DIR__ . '/../../../../../../data/xmlFiles/' . $file;

            $xml = file_get_contents($xmlFile);
            $this->assertTrue(file_exists($xmlFile), "XML sample file not found");

            $parser = new ReservationCubilisXMLParser($xml);

            $this->assertTrue($parser->isSuccess(), "Problem in xml parser");

            if ($parser->isSuccess()) {
                $output->status  = 'success';
                $output->message = 'Check successfull.';
                $output->data    = $parser->getReservationList();
            } else {
                $output->status  = 'error';
                $output->message = $parser->getError()->message;
                $output->code    = $parser->getError()->code;
            }

            if ($output->status == 'success') {
                $reservation = $output->data;

                // test for apartment reservation
                $this->handleReservations($reservation, 42, false);

                // test for apartel reservation
                $this->handleReservations($reservation, 1, true);
            }
        }
    }

    public function handleReservations($data, $productId, $isApartel = false)
    {
        $channelManagerService = $this->getApplicationServiceLocator()->get('service_channel_manager');

        if ($data->getLength()) {

            // START of checking method existance

            $this->assertTrue(
                method_exists($channelManagerService, 'getPossibleStatuses'),
                'Class does not have method getPossibleStatuses'
            );

            $this->assertTrue(
                method_exists($channelManagerService, 'getChannelResId'),
                'Class does not have method getChannelResId'
            );

            $this->assertTrue(
                method_exists($channelManagerService, 'getRoomStayList'),
                'Class does not have method getRoomStayList'
            );

            $this->assertTrue(
                method_exists($channelManagerService, 'initCancelation'),
                'Class does not have method initCancelation'
            );

            $this->assertTrue(
                method_exists($channelManagerService, 'initReservationWithType'),
                'Class does not have method initReservationWithType'
            );

            $this->assertTrue(
                method_exists($channelManagerService, 'conquerMyTrustCubilis'),
                'Class does not have method conquerMyTrustCubilis'
            );

            $this->assertTrue(
                method_exists($channelManagerService, 'initDefault'),
                'Class does not have method initDefault'
            );

            $this->assertTrue(
                method_exists($channelManagerService, 'handleReservations'),
                'Class does not have method handleReservations'
            );
            // END of checking method existance

            $channelResIdList = $channelManagerService->handleReservations($data, $productId, $isApartel);

            $result  = empty($channelResIdList) ? 'true' : 'false';

            $this->assertNotTrue($result, "Reservation handling is not correct");
        }
    }
}
