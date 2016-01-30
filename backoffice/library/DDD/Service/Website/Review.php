<?php

namespace DDD\Service\Website;

use DDD\Service\ServiceBase;
use Library\Constants\TextConstants;

class Review extends ServiceBase
{
    /**
     *
     * @param string $reviewHash - Booking Ticket Review Hash (review_page_hash)
     * @param array $data
     * @return boolean
     */
    public function addNewReview($data)
    {
        // if hash incorrect

        if (!isset($data['review-hash']) || !($resData = $this->getBookingData($data['review-hash']))) {
            return [
                'status' => 'error',
                'message' => TextConstants::WEBSITE_REVIEW_HASH_INCORRECT
                ];
        }

        $resNumber = $resData->getResNumber();
        // if exists
        if ($this->checkExistingReview($resNumber)) {
            return [
                'status' => 'error',
                'message' => TextConstants::WEBSITE_REVIEW_ALREADY_EXISTS
                ];
        }

        /**
         * @var \DDD\Dao\Location\Country $countryDao
         */
        $countryDao = $this->getServiceLocator()->get('dao_location_country');

        $countryData = $countryDao->getDetailsByCountryId($resData->getGuestCountryId());

        $countryDetailId = null;
        $countryCode     = null;

        if ($countryData) {
            $countryDetailId = $countryData->getDetailsID();
            $countryCode     = strtolower($countryData->getIso());
        }

        $saveData = [
            'apartment_id'  => $resData->getApartmentIdAssigned(),
            'res_id'        => $resData->getId(),
            'res_number'    => $resNumber,
            'user_email'    => $resData->getGuestEmail(),
            'user_name'     => $resData->getGuestFirstName(),
            'city'          => $resData->getGuestCityName(),
            'country_id'    => $resData->getGuestCountryId(),
            'country_did'   => $countryDetailId,
            'country_code'  => $countryCode,
            'liked'         => $data['like'],
            'dislike'       => $data['suggestions'],
            'score'         => (int)$data['stars'],
            'date'          => date('Y-m-d H:i:s') // date(now)
        ];

        /**
         * @var \DDD\Dao\Booking\ReviewDao $bookingReviewDao
         */
        $bookingReviewDao = $this->getServiceLocator()->get('dao_booking_review');
        $bookingReviewDao->setEntity(new \DDD\Domain\Booking\ReviewPage());

        // Save Review
        $bookingReviewDao->save($saveData);

        //recalculate apartment average score
        $productService = $this->getServiceLocator()->get('service_accommodations');
        $productService->updateProductReviewScore($resData->getApartmentIdAssigned());
        return [
            'status' => 'success'
            ];
    }

    /**
     * @param bool|string $resNumber
     * @return \DDD\Domain\Review\ReviewBase
     */
    public function checkExistingReview($resNumber = FALSE)
    {
        /**
         * @var \DDD\Dao\Booking\ReviewDao $bookingReviewDao
         */
        $bookingReviewDao = $this->getServiceLocator()->get('dao_booking_review');

        return $bookingReviewDao->getReviewByReservationNumber($resNumber);
    }

    /**
     * @param bool|string $reviewPageHash Booking Ticket Review Hash (review_page_hash)
     * @return \DDD\Domain\Booking\ReviewPage|boolean
     */
    public function getBookingData($reviewPageHash = FALSE)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao  = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingData = $bookingDao->getBookingTicketByReviewCode($reviewPageHash);

	    if ($bookingData) {
		    $resNumber =$bookingData->getResNumber();

		    if ($this->checkExistingReview($resNumber)) {
			    return FALSE;
		    }

		    return $bookingData;
	    }

	    return false;
    }
}
