<?php
namespace LibraryTest\DDD\Service\Apartment;

use Library\UnitTesting\BaseTest;

class GeneralTest extends BaseTest
{
    /**
     * Testing saveApartmentGeneral method
     *
     * @throws \League\Flysystem\Exception
     */
    public function testSaveApartmentGeneral()
    {
        /**
         * @var \DDD\Service\Apartment\General $apartmentService
         */
        $apartmentService = $this->getApplicationServiceLocator()->get('service_apartment_general');
        $apartmentName = 'test name' . time();
        $data = [
            'apartment_name'               => $apartmentName,
            'max_capacity'                 => 3,
            'square_meters'                => 45,
            'bedrooms'                     => 2,
            'bathrooms'                    => 2,
            'room_count'                   => 4,
            'building_id'                  => 49,
            'building_section'             => 5,
            'chekin_time'                  => '17:00',
            'chekout_time'                 => '18:00',
            'id'                           => 0,
            'status'                       => 1,
            'general_description_textline' => 0,
            'general_description'          => 'Lorem Ipsum'
        ];

        $apartmentId = $apartmentService->saveApartmentGeneral(0, $data);
        $adapter     = $this->getApplicationServiceLocator()->get('dbadapter');
        $statement   = $adapter->createStatement('SELECT * FROM ga_apartments ORDER BY id DESC LIMIT 1');
        $result      = $statement->execute();

        $this->assertEquals($result->count(), 1);

        $row = $result->current();

        $this->assertEquals($apartmentId, $row['id']);
        $this->assertEquals($apartmentName, $row['name']);
    }
}