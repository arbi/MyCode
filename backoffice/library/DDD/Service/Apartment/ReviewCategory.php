<?php

namespace DDD\Service\Apartment;

use Library\Constants\Objects;

use DDD\Service\ServiceBase;

class ReviewCategory extends ServiceBase 
{
    /**
     *
     * @var \DDD\Dao\Apartment\ReviewCategory
     */
    protected $reviewCategory;
    
    const STATUS_LIKE = 1;
    const STATUS_DISLIKE = 2;

    /**
     * @param $reviewCategoryId
     * @return int
     */
    public function delete($reviewCategoryId)
    {
        /**
         * @var \DDD\Dao\Apartment\ReviewCategory $reviewCategoryDao
         */
        $reviewCategoryDao = $this->getServiceLocator()->get('dao_apartment_review_category');

        $result = $reviewCategoryDao->delete([
            'id' => $reviewCategoryId
        ]);

        return $result ? true : false;
    }

    /**
     * @return \ArrayObject|\DDD\Domain\Apartment\ReviewCategory\ReviewCategory[]
     */
    public function getAllReviewCategories()
    {
        /**
         * @var \DDD\Dao\Apartment\ReviewCategory $reviewCategoryDao
         */
        $reviewCategoryDao = $this->getServiceLocator()->get('dao_apartment_review_category');

		return $reviewCategoryDao->getAllReviewCategories();
	}
    
    /**
     * 
     * @return type
     */
    public function getDatatableData()
    {
        $categoryList = $this->getAllReviewCategories();

        return $this->prepareData($categoryList);
    }

    /**
	 * @param \DDD\Domain\Apartment\ReviewCategory[]|\ArrayObject
	 * @return array
	 */
	private function prepareData($reviewCategoryList)
    {
		$data = [];

		if ($reviewCategoryList->count()) {
			foreach ($reviewCategoryList as $reviewCategory) {
                
                $router = $this->getServiceLocator()->get('router');
                $editUrl = $router->assemble(['controller' => 'apartment-review-category', 'action' => 'edit', 'id' => $reviewCategory['id']], 
                                                  ['name' => 'backoffice/default']);
				array_push($data, [
					$reviewCategory['name'],
					Objects::getApartmentReviewCategoryStatus()[$reviewCategory['type']],
					'<a class="btn btn-xs btn-primary" href="' . $editUrl . '" data-html-content="Edit"></a>'
				]);
			}
		}

		return $data;
	}

    /**
     * @param $categoryId
     * @return array|\ArrayObject
     */
    public function getReviewCategoryData($categoryId)
    {
        /**
         * @var \DDD\Dao\Apartment\ReviewCategory $reviewCategoryDao
         */
        $reviewCategoryDao = $this->getServiceLocator()->get('dao_apartment_review_category');

        $reviewCategoryData = $reviewCategoryDao->getReviewCategoryData($categoryId);
        if ($reviewCategoryData)
            return $reviewCategoryData;
        return [];
    }


    /**
     * @param $reviewCategoryData []
     * @param $reviewCategoryId int
     * @return bool|int
     */
    public function saveReviewCategory($reviewCategoryData, $reviewCategoryId)
    {
		try {
            /**
             * @var \DDD\Dao\Apartment\ReviewCategory $reviewCategoryDao
             */
            $reviewCategoryDao = $this->getServiceLocator()->get('dao_apartment_review_category');
            
            $params = [ 
                'name'=> $reviewCategoryData['name'],
                'type'=> $reviewCategoryData['type']
            ]; 
            if ($reviewCategoryId) {
                $reviewCategoryDao->save($params, ['id'=>$reviewCategoryId]);
            } else {
               $reviewCategoryId = $reviewCategoryDao->save($params);
            }
		} catch (\Exception $ex) {
			return false;
		}

		return $reviewCategoryId;
	}
}
