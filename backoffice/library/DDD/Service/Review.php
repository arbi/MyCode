<?php

namespace DDD\Service;

use DDD\Service\ServiceBase;

class Review extends ServiceBase
{
    protected $serviceLocator;
    protected $_productService = false;
    protected $_user = null;
    
    /**
     * @var $reviewDao \DDD\Domain\Review\ReviewBase
     * @return Ambigous <\Library\DbManager\Ambigous, \Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
     */
    public function getPendingReviews()
    {
        /**
         * @var \DDD\Dao\Accommodation\Review $accommodationReviewDao
         */
        $accommodationReviewDao = $this->getServiceLocator()->get('dao_accommodation_review');

    	return $accommodationReviewDao->getPendingReviews();
    }

    /**
     * @var $reviewDao \DDD\Domain\Review\ReviewBase
     * @return int
     */
    public function getPendingReviewsCount()
    {
        /**
         * @var \DDD\Dao\Accommodation\Review $accommodationReviewDao
         */
        $accommodationReviewDao = $this->getServiceLocator()->get('dao_accommodation_review');

    	return $accommodationReviewDao->getPendingReviewsCount();
    }
    
    public function approveReview($reviewID, $status, $apartmentId)
    {
        /**
         * @var \DDD\Dao\Accommodation\Review $accommodationReviewDao
         */
        $accommodationReviewDao = $this->getServiceLocator()->get('dao_accommodation_review');
    	
    	$data = [
    		'status' => $status
    	];

        $accommodationReviewDao->save(
    		$data,
    		['id' => $reviewID]
    	);

    	/* @var $apartmentService \DDD\Service\Accommodations */
      	$apartmentService = $this->getServiceLocator()->get('service_accommodations');
      	$apartmentService->updateProductReviewScore($apartmentId);
      	return true;
    }
}
