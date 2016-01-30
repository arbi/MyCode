<?php

namespace BackofficeTest\Backoffice\Controller;

use DDD\Service\Booking;
use DDD\Service\Task;
use Library\Constants\DbTables;
use Library\UnitTesting\BaseTest;
use Zend\Db\Sql\Select;

class BookingControllerTest extends BaseTest
{
    /**
     * Test index access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/booking');
        $this->assertResponseStatusCode(200);
    }

    /**
     * Test
     */
    public function testAutoDoneCCCATaskViaAjaxSave()
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getApplicationServiceLocator()->get('dao_booking_booking');
        $bookingDao->getResultSetPrototype()->setArrayObjectPrototype(new \ArrayObject());

        $reservationData = $bookingDao->fetchOne(function (Select $select) {
            $thisTable = DbTables::TBL_BOOKINGS;

            $select->join(
                ['task' => DbTables::TBL_TASK],
                $thisTable . '.id = task.res_id',
                ['task_id' => 'id'],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo($thisTable . '.status', Booking::BOOKING_STATUS_BOOKED)
                ->and
                ->equalTo('task.task_type', Task::TYPE_CCCA)
                ->and
                ->in('task.task_status', [Task::STATUS_NEW, Task::STATUS_VIEWED, Task::STATUS_STARTED]);

            $select->order('id DESC');
        });

        if ($reservationData) {
            $postData = [
                'guest_name'                                => 'Unit Test',
                'guest_last_name'                           => 'Was Here',
                'guest_email'                               => 'test@ginosi.com',
                'second_guest_email'                        => $reservationData['secondary_email'],
                'guest_phone'                               => $reservationData['guest_phone'],
                'guest_travel_phone'                        => $reservationData['guest_travel_phone'],
                'guest_country'                             => isset($reservationData['guest_country_id']) ? $reservationData['guest_country_id'] : 0,
                'guest_city'                                => $reservationData['guest_city_name'],
                'guest_address'                             => $reservationData['guest_address'],
                'guest_zipcode'                             => $reservationData['guest_zip_code'],
                'booking_statuses'                          => Booking::BOOKING_STATUS_BOOKED,
                'overbooking_status'                        => $reservationData['overbooking_status'],
                'finance_booked_state'                      => 0,
                'finance_booked_state_changed'              => 0,
                'apartel_id'                                => $reservationData['apartel_id'],
                'occupancy'                                 => $reservationData['occupancy'],
                'booking_arrival_time'                      => '15:00',
                'booking_partners'                          => 1054,
                'booking_affiliate_reference'               => 123456789,
                'finance_key_instructions'                  => 0,
                'ginosi_collect_debt_customer_currency'     => 0,
                'ginosi_collect_debt_apartment_currency'    => -1234.56,
                'partner_collect_debt_customer_currency'    => 0,
                'partner_collect_debt_apartment_currency'   => 123.45,
                'model'                                     => 2,
                'finance_valid_card'                        => 0,
                'ccca_verified'                             => 1,  // <-- TEST POINT
                'finance_reservation_settled'               => 0,
                'finance_no_collection'                     => 0,
                'doc_description'                           => '',
                'delete_data'                               => '',
                'validAttachment'                           => 0,
                'datatable_tasks_length'                    => 25,
                'show_status'                               => 1,
                'datatable_history_length'                  => 25,
                'booking_ginosi_comment'                    => '',
                'booking_ginosi_comment_team'               => 0,
                'booking_ginosi_comment_frontier'           => 0,
                'penaltyAccPrice'                           => 123.45,
                'penaltyCustomerPrice'                      => 123.45,
                'accPrice'                                  => 123.45,
                'accPriceValidate'                          => 123.45,
                'customerPrice'                             => 123.45,
                'customerCurrency'                          => $reservationData['guest_currency_code'],
                'accommodationCurrency'                     => $reservationData['apartment_currency_code'],
                'accId'                                     => $reservationData['apartment_id_assigned'],
                'booking_id'                                => $reservationData['id'],
                'booking_res_number'                        => $reservationData['res_number'],
                'acc_currency_rate'                         => 1.1334,
                'acc_currency_sign'                         => $reservationData['apartment_currency_code'],
                'customer_currency_rate'                    => $reservationData['currency_rate'],
                'selected-email'                            => 'test@ginosi.com',
                'check_mail'                                => 'no'
            ];

            $request = $this->getRequest();

            $headers = $request->getHeaders();
            $headers->addHeaders(['X-Requested-With' => 'XMLHttpRequest']);

            $this->dispatch('/booking/ajaxsave', 'POST', $postData);
            $this->assertResponseStatusCode(200);

            $response = json_decode($this->getResponse()->getContent(), true);

            if ($response['status'] == 'reload') {
                /**
                 * @var \DDD\Dao\Task\Task $taskDao
                 */
                $taskDao = $this->getApplicationServiceLocator()->get('dao_task_task');
                $taskDao->getResultSetPrototype()->setArrayObjectPrototype(new \ArrayObject());

                $taskData = $taskDao->fetchOne(function (Select $select) use ($reservationData) {
                    $select->where
                        ->equalTo('id', $reservationData['task_id']);
                });

                if ($taskData) {
                    $this->assertEquals($taskData['task_status'], Task::STATUS_DONE, 'Task status was not stored as done');
                } else {
                    $this->fail('Cannot found task');
                }
            } else {
                $this->assertEquals($response['status'], 'reload', $response['msg']);
            }
        }
    }
}
