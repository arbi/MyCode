<?php

namespace DDD\Service\Apartment;

use DDD\Dao\Apartment\ReviewCategory as ReviewCategoryDAO;
use DDD\Dao\Apartment\ReviewCategoryRel as ReviewCategoryRelDAO;
use DDD\Service\ServiceBase;

use DDD\Dao\Accommodation\Review as ReviewDao;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;


use Library\Constants\Constants;

/**
 * Service class providing methods to work with apartment reviews
 * @author Tigran Petrosyan
 * @package core
 * @subpackage core/service
 */
class Review extends ServiceBase
{
	/**
	 * @var int
	 */
    const REVIEW_STATUS_PENDING = 0;

    /**
     * @var int
     */
    const REVIEW_STATUS_REJECTED = 2;

    /**
     * @var int
     */
    const REVIEW_STATUS_APPROVED = 3;

    /**
     * @var int
     */
    const REVIEW_STATUS_APPROVED_WITH_TEXT = 1;

    const DATE_RANGE_15_DAYS_IN_SECONDS = 1296000;
    const DATE_RANGE_60_DAYS_IN_SECONDS = 5184000;

    const DATE_FORMAT_MONTH_AND_YEAR = 'M Y';
    const DATE_FORMAT_WEEK_IN_YEAR = 'W';
    const DATE_FORMAT_MONTH_AND_DAY = 'M d';

    static $notUsedCityList = [
        'n/a',
        '.',
        '???'
    ];

    public function getDatableData($apartmentId, $thisUrl)
    {
        $dao = new ReviewDao($this->getServiceLocator(), 'DDD\Domain\Apartment\Review\View');
		$reviews = $dao->getReviews ( $apartmentId );
		$reviewArray = array ();

		foreach ( $reviews as $review ) {
            // ToDo understand whether we need $prevRes or no
            /* $prevRes = $bookingDao->getPreviousReservation($apartmentId, $review->getDate_from()); */
            $url = $thisUrl->fromRoute ( 'apartment/review', array (
					'controller' => 'review',
					'action' => 'status',
					'apartment_id' => $apartmentId,
					'review_id' => $review->getId(),
			) );

			$buttons = '<div class="btn-group btn-type-damage dropup">
                            <button class="btn btn-sm btn-primary state" data-toggle="dropdown" data-loading-text="Loading...">Actions</button>
                            <button class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown">
                                <span class="caret" style="border-bottom: 4px solid #fff;"></span>
                            </button>
                            <ul class="btn-medium dropdown-menu">';
			$status = '';
			switch ($review->getStatus()) {
				case self::REVIEW_STATUS_APPROVED:
					$buttons .= '<li><a href="' . $url . '/' . self::REVIEW_STATUS_PENDING . '">Pending</a></li>
                                 <li><a href="' . $url . '/' . self::REVIEW_STATUS_REJECTED . '">Reject</a></li>';
					$status = 'Approved';
					break;
				case self::REVIEW_STATUS_REJECTED:
					$buttons .= '<li><a href="' . $url . '/' . self::REVIEW_STATUS_PENDING . '">Pending</a></li>
                                 <li><a href="'.$url.'/'.self::REVIEW_STATUS_APPROVED.'">Approve</a></li>';
					$status = 'Rejected';
					break;
				case self::REVIEW_STATUS_PENDING:
					$buttons .= '<li><a href="'.$url.'/'.self::REVIEW_STATUS_APPROVED.'">Approve</a></li>
					             <li><a href="'.$url.'/'.self::REVIEW_STATUS_REJECTED.'">Reject</a></li>';
					$status = 'Pending';
					break;
			}

            $buttons .= '<li><a href="#deleteModal" data-toggle="modal" onclick="deleteReview('.$review->getId().')">Delete</a></li>';

            $buttons .=     '</ul>
                        </div>';

            $reservationURL = $thisUrl->fromRoute ( 'backoffice/default', array (
            		'controller' => 'booking',
            		'action' => 'edit',
            		'id' => $review->getResNumber(),
            ) );
            $reservationLink = '<a name="'.$review->getResNumber().'" class="hidden-anchor"></a><a target="_blank" href="' . $reservationURL . '">' . $review->getResNumber() . '</a>';

			$reviewArray [] = array (
                    $reservationLink,
					$review->getScore(),
					date(Constants::GLOBAL_DATE_FORMAT, strtotime($review->getReviewDate())),
                    $status,
					$review->getLiked(),
					$review->getDislike(),
                    $this->getReviewListByReviewId($review->getId(), $apartmentId),
					$buttons
			);
		}

        return $reviewArray;
    }

    public function deleteReview($reviewId, $apartmentId)
    {
        $dao = new ReviewDao($this->getServiceLocator());
        $dao->delete(['id'=>$reviewId]);
        $productService = $this->getServiceLocator()->get('service_accommodations');
        $productService->updateProductReviewScore($apartmentId);
    }

    public function updateReview($reviewID, $status, $apartmentId)
    {
        $dao = new ReviewDao($this->getServiceLocator());
        $dao->save(
        	[
        		'status' => $status
			],
        	[
        		'id' => $reviewID
			]
		);
        $productService = $this->getServiceLocator()->get('service_accommodations');
        $productService->updateProductReviewScore($apartmentId);

        return true;
    }

    /**
     * @param $reviewId
     * @param bool|false $apartmentId
     * @return string
     */
    public function getReviewListByReviewId($reviewId, $apartmentId = false)
    {
        /**
         * @var ReviewCategoryRelDAO $reviewCategoryRelDao
         * @var ReviewCategoryDAO $reviewCategoryDao
         */
        $reviewCategoryRelDao = $this->getServiceLocator()->get('dao_apartment_review_category_rel');
        $reviewCategoryDao = $this->getServiceLocator()->get('dao_apartment_review_category');

        $allReviewCategoryList = $reviewCategoryDao->getAllReviewCategories();
        $getItemReviewCategoryList = $reviewCategoryRelDao->getAllReviewCategoryListByReviewId($reviewId);
        $itemList = [];
        foreach ($getItemReviewCategoryList as $item) {
            $itemList[] = $item['apartment_review_category_id'];
        }
        $dataApartment = ($apartmentId) ? 'data-apartment-id="' . $apartmentId . '"' : '';
        $select = '<select class="review-category-list" multiple="multiple" data-review-id="' . $reviewId . '" ' . $dataApartment . '>';
        foreach ($allReviewCategoryList as $row) {
            $select .= '<option value="' . $row['id'] . '"'. (in_array($row['id'], $itemList) ? ' selected="selected"' : '') . '>' . $row['name'] . '</option>';
        }
        $select .= '</select>';
        return $select;
    }

    public function saveReviewCategory($reviewId, $selectData)
    {
        $reviewCategoryRelDao = $this->getServiceLocator()->get('dao_apartment_review_category_rel');
        $reviewCategoryRelDao->delete(['review_id' => $reviewId]);
        if($selectData) {
            foreach ($selectData as $row) {
                $reviewCategoryRelDao->save(['review_id' => $reviewId, 'apartment_review_category_id' => $row]);
            }
        }
        return true;
    }

    /**
     * @param $apartmentId
     * @return mixed
     */
    public function getOptions($apartmentId)
    {
        $now = date('Y-m-d');
        $lastTwoYears = date('Y-m-d', strtotime('-2 year'));
        $lastThreeMonth = date('Y-m-d', strtotime('-3 month'));

        //score
        $apartmentScore = $this->getApartmentReviewScore($apartmentId);
        $params['scoreLastTwoYears'] = $apartmentScore['scoreLastTwoYears'];
        $params['scoreLastThreeMonth'] = $apartmentScore['scoreLastThreeMonth'];

        //Review Category
        /** @var ReviewCategoryRelDAO $reviewCategoryRelDao */
        $reviewCategoryRelDao = $this->getServiceLocator()->get('dao_apartment_review_category_rel');
        $reviewCategoryLastTwoYears = $reviewCategoryRelDao->getReviewCategoryCountByRange($apartmentId, $lastTwoYears, $now);
        $reviewCategoryLastThreeMonth = $reviewCategoryRelDao->getReviewCategoryCountByRange($apartmentId, $lastThreeMonth, $now);
        $params['reviewCategoryLastTwoYears'] = $reviewCategoryLastTwoYears;
        $params['reviewCategoryLastThreeMonth'] = $reviewCategoryLastThreeMonth;
        return $params;
    }

    /**
     * @param $apartmentId
     * @return mixed
     */
    public function getApartmentReviewScore($apartmentId)
    {
        $now = date('Y-m-d');
        $lastThreeMonth = date('Y-m-d', strtotime('-3 month'));

        //score
        $apartmentGeneralDao = $this->getServiceLocator()->get('dao_apartment_general');
        $params['scoreLastTwoYears'] = $apartmentGeneralDao->getReviewScore($apartmentId)['score'];
        $scoreLastThreeMonth = $this->getAVGReviewScore($apartmentId, $lastThreeMonth, $now);
        $params['scoreLastThreeMonth'] = $scoreLastThreeMonth;
        return $params;
    }

    /**
     * @param $apartmentId
     * @param bool $start
     * @param bool $end
     * @return float|int
     */
    public function getAVGReviewScore($apartmentId, $start = false, $end = false)
    {
        $dao = $this->getApartmentReviewDao();
        $result = $dao->apartmentAvgReviewSore($apartmentId, $start, $end);
        if ($result && $result['avg_score'])
            return round($result['avg_score'], 2);

        return 0;
    }

    /**
     * @return array
     */
    public function getAllReviewCategories()
    {
        /**
         * @var \DDD\Dao\Apartment\ReviewCategory $reviewsCategoriesDao
         */
        $reviewsCategoriesDao = $this->getServiceLocator()->get('dao_apartment_review_category');
        $allReviewCategories = $reviewsCategoriesDao->getAllReviewCategories();
        $resArray = ["" => "-- All tags --"];
        foreach ($allReviewCategories as $row) {
            $resArray[$row['id']] = $row['name'];
        }
        return $resArray;
    }

    /**
     * @param $iDisplayStart
     * @param $iDisplayLength
     * @param $queryParams
     * @param $sortCol
     * @param $sortDir
     * @return array
     */
    public function getSearchResult(
        $iDisplayStart,
        $iDisplayLength,
        $queryParams,
        $sortCol,
        $sortDir
    )
    {
        /**
         * @var \DDD\Dao\Apartment\Review $reviewsDao
         */
        $reviewsDao = $this->getServiceLocator()->get('dao_apartment_review');
        $where = $this->constructWhereFromFilterParams($queryParams);
        $result = $reviewsDao->getSearchResult(
            $where,
            $iDisplayStart,
            $iDisplayLength,
            $sortCol,
            $sortDir
        );
        $reviewArray = [];
        foreach ($result['result'] as $review ) {
            // ToDo understand whether we need $prevRes or no
            /* $prevRes = $bookingDao->getPreviousReservation($apartmentId, $review->getDate_from()); */


            $buttons = '<div class="btn-group btn-type-damage dropup">
                            <button class="btn btn-sm btn-primary state" data-toggle="dropdown" data-loading-text="Loading...">Actions</button>
                            <button class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown">
                                <span class="caret" style="border-bottom: 4px solid #fff;"></span>
                            </button>
                            <ul class="btn-medium dropdown-menu change-review-status">';
            switch ($review->getStatus()) {
                case self::REVIEW_STATUS_APPROVED:
                    $buttons .= '<li><a data-status="' . self::REVIEW_STATUS_PENDING . '">Pending</a></li>
                                 <li><a data-status="' . self::REVIEW_STATUS_REJECTED . '">Reject</a></li>';
                    break;
                case self::REVIEW_STATUS_REJECTED:
                    $buttons .= '<li><a data-status="' . self::REVIEW_STATUS_PENDING . '">Pending</a></li>
                                 <li><a data-status="' . self::REVIEW_STATUS_APPROVED.'">Approve</a></li>';
                    break;
                case self::REVIEW_STATUS_PENDING:
                    $buttons .= '<li><a data-status="' . self::REVIEW_STATUS_APPROVED.'">Approve</a></li>
					             <li><a data-status="' . self::REVIEW_STATUS_REJECTED.'">Reject</a></li>';
                    break;
            }
            $status = self::getGlyphiconByStatus($review->getStatus());
            $buttons .= '<li><a href="#" class="delete-review">Delete</a></li>';

            $buttons .=     '</ul>
                        </div>';

            $reservationURL = '/booking/edit/' . $review->getResNumber();
            $reservationLink = '<a target="_blank" href="' . $reservationURL . '">' . $review->getResNumber() . '</a>';
            $apartmentUrl = '/apartment/' . $review->getApartmentId() . '/reviews';
            $apartmentLink = '<a target="_blank" href="' . $apartmentUrl . '">' . $review->getApartmentName() . '</a>';
            $reviewArray [] = array (
                $reservationLink . ' ' . $apartmentLink,
                $review->getScore(),
                date(Constants::GLOBAL_DATE_FORMAT, strtotime($review->getReviewDate())),
                $status,
                $review->getLiked(),
                $review->getDislike(),
                $this->getReviewListByReviewId($review->getId()),
                $buttons
            );
        }

        return [
            'count' => $result['total'],
            'result' => $reviewArray
        ];
    }

    /**
     * @param $params
     * @return array
     */
    public function getChartInfo($params)
    {
        /**
         * @var \DDD\Dao\Apartment\Review $reviewsDao
         */
        if (!empty($params['tags'])) {
            //when coming for datatable, it's
            //coming like this from javaScript array("0" => 'id1,id2'),
            //and i am going to use the same methods, so i make this
            // data convenient to the other one
            $implode = implode(',',$params['tags']);
            $params['tags'] = [$implode];
        }
        $reviewsDao = $this->getServiceLocator()->get('dao_apartment_review');
        $where = $this->constructWhereFromFilterParams($params);
        $result = $reviewsDao->getSearchResult($where)['result'];
        $total = $result->count();
        if ($total == 0) {
            return [
                'categories' => [],
                'series'     => []
            ];
        }
        $resultArray = [];
        $i = 1;

        foreach ($result as $row) {
            if ($i == 1) {
                $startDate = $row->getDate_from();
            } elseif ($i == $total) {
                $endDate = $row->getDate_from();
            }
            array_push($resultArray,
                [
                   'score' =>  $row->getScore(),
                    'date' =>  $row->getDate_from()
                ]);
            $i++;
        }

        if (isset($endDate) && isset($startDate)) {
            $dateRange = strtotime($endDate) - strtotime($startDate);
        } else {
            $dateRange = 0;
        }
        $suffix = '';
        if ($dateRange >= self::DATE_RANGE_60_DAYS_IN_SECONDS) {
            $dateFormat = self::DATE_FORMAT_MONTH_AND_YEAR;
        } elseif ($dateRange >= self::DATE_RANGE_15_DAYS_IN_SECONDS) {
            $dateFormat = self::DATE_FORMAT_WEEK_IN_YEAR;
            $suffix = 'th week of year';
        } else {
            $dateFormat = self::DATE_FORMAT_MONTH_AND_DAY;
        }

        $avgScoreData = [];
        $reviewsCountData = [];
        $totalAverageRanking = 0;
        $totalRankingSum = 0;
        $totalRanksCount = 0;
        foreach ($resultArray as $row) {
            $reviewsCountData[date($dateFormat, strtotime($row['date'])) . $suffix] =
            $avgScoreData[date($dateFormat, strtotime($row['date'])) . $suffix] =
                [
                  'score5stars' => 0,
                  'score4stars' => 0,
                  'score3stars' => 0,
                  'score2stars' => 0,
                  'score1stars' => 0,
                  'reviewCount' => 0,
                  'y' => 0,
                  'averageScore' => 0,
                  'explanation' => date($dateFormat, strtotime($row['date'])) . $suffix
                ];
        }

        foreach ($resultArray as $row) {
            if ($row['score'] <= 1) {
                $avgScoreData[date($dateFormat, strtotime($row['date'])) . $suffix]['score1stars']++;
                $totalRankingSum++;
            } elseif ($row['score'] <= 2) {
                $avgScoreData[date($dateFormat, strtotime($row['date'])) . $suffix]['score2stars']++;
                $totalRankingSum += 2;
            }  elseif ($row['score'] <= 3) {
                $avgScoreData[date($dateFormat, strtotime($row['date'])) . $suffix]['score3stars']++;
                $totalRankingSum += 3;
            }  elseif ($row['score'] <= 4) {
                $avgScoreData[date($dateFormat, strtotime($row['date'])) . $suffix]['score4stars']++;
                $totalRankingSum += 4;
            }  else {
                $avgScoreData[date($dateFormat, strtotime($row['date'])) . $suffix]['score5stars']++;
                $totalRankingSum += 5;
            }
            $totalRanksCount++;
            $avgScoreData[date($dateFormat, strtotime($row['date'])) . $suffix]['reviewCount']++;
            $reviewScoreCombined = 0;
            for ($i = 1; $i <= 5; $i++) {
                $reviewScoreCombined += $i *
                    $avgScoreData[date($dateFormat, strtotime($row['date'])) . $suffix]['score' . $i . 'stars'];
            }
            $avgScoreData[date($dateFormat, strtotime($row['date'])) . $suffix]['y'] =
                $reviewScoreCombined/$avgScoreData[date($dateFormat, strtotime($row['date'])) . $suffix]['reviewCount'];
            $avgScoreData[date($dateFormat, strtotime($row['date'])) . $suffix]['averageScore'] =
                number_format($avgScoreData[date($dateFormat, strtotime($row['date'])) . $suffix]['y'], 2, '.', '');
            $totalAverageRanking = number_format($totalRankingSum/$totalRanksCount, 2, '.', '');
            $reviewsCountData[date($dateFormat, strtotime($row['date'])) . $suffix] =
                $avgScoreData[date($dateFormat, strtotime($row['date'])) . $suffix];
            $reviewsCountData[date($dateFormat, strtotime($row['date'])) . $suffix]['y'] = $reviewsCountData[date($dateFormat, strtotime($row['date'])) . $suffix]['reviewCount'];
        }

        return [
            'categories' => array_keys($avgScoreData),
            'series'     => [
                'avgScores' => array_values($avgScoreData),
                'reviewCounts' => array_values($reviewsCountData),
            ],
            'totalRanksCount' => $totalRanksCount,
            'totalAverageRanking' => $totalAverageRanking
        ];

    }

    /**
     * @param array $filterParams
     * @return Where
     */
    protected function constructWhereFromFilterParams($filterParams)
    {
        $where = new Where();

        if (!empty($filterParams['apartment_groups'])) {
            $where->equalTo('apartment_groups_items.apartment_group_id', $filterParams['apartment_groups']);
        }

        if (!empty($filterParams['tags'])) {
            $where->in('review_category_rel.apartment_review_category_id', explode(',', $filterParams['tags'][0]));
        }

        if (!empty($filterParams['arrival_date_range'])) {
            $dateArray = explode(' - ', $filterParams['arrival_date_range']);
            if (isset($dateArray[1])) {
                $dateFrom = $dateArray[0];
                $dateTo   = $dateArray[1];
                $where->expression(
                    'DATE(reservations.date_from) >= DATE("' . $dateFrom . '") AND ' .
                    'DATE(reservations.date_from) <= DATE("' . $dateTo . '")', []
                );
            }
        }

        if (!empty($filterParams['departure_date_range'])) {
            $dateArray = explode(' - ', $filterParams['departure_date_range']);
            if (isset($dateArray[1])) {
                $dateFrom = $dateArray[0];
                $dateTo   = $dateArray[1];
                $where->expression(
                    'DATE(reservations.date_to) >= DATE("' . $dateFrom . '") AND ' .
                    'DATE(reservations.date_to) <= DATE("' . $dateTo . '")', []
                );
            }
        }

        if (!empty($filterParams['stay_length_from'])) {
            $where->expression('DATEDIFF(reservations.date_to, reservations.date_from) >= ' . $filterParams['stay_length_from'],[]);
        }

        if (!empty($filterParams['stay_length_to'])) {
            $where->expression('DATEDIFF(reservations.date_to, reservations.date_from) <= ' . $filterParams['stay_length_to'],[]);
        }

        if (!empty($filterParams['score_filter']) && is_array($filterParams['score_filter']) && count($filterParams['score_filter']) != 5) {
            $where->in(DbTables::TBL_PRODUCT_REVIEWS . '.score', $filterParams['score_filter']);
        }

        return $where;
    }

    /**
     * @param array $filterParams
     * @return array
     */
    public function getCategoriesInfo($filterParams)
    {
        $whereString = ' WHERE ';
        $haveAlreadyOneCondition = false;

        if (!empty($filterParams['apartment_groups'])) {
            $whereString .= ' apartment_group_items.apartment_group_id=' . $filterParams['apartment_groups'] . ' ';
            $haveAlreadyOneCondition = true;
        }

        if (!empty($filterParams['tags']) && is_array($filterParams['tags'])) {
            if ($haveAlreadyOneCondition) {
                $and = ' AND ';
            } else {
                $and = ' ';
            }
            $whereString .= $and . DbTables::TBL_APARTMENT_REVIEW_CATEGORY_REL . '.apartment_review_category_id' .
                ' IN (' . implode(',',$filterParams['tags']) . ') ';
            $haveAlreadyOneCondition = true;
        }


        if (!empty($filterParams['arrival_date_range'])) {
            $dateArray = explode(' - ', $filterParams['arrival_date_range']);
            if (isset($dateArray[1])) {
                $dateFrom = $dateArray[0];
                $dateTo   = $dateArray[1];
                if ($haveAlreadyOneCondition) {
                    $and = ' AND ';
                } else {
                    $and = ' ';
                }
                $whereString .= $and . 'DATE(reservations.date_from) >= DATE("' . $dateFrom . '") AND ' .
                'DATE(reservations.date_from) <= DATE("' . $dateTo . '") ';
                    $haveAlreadyOneCondition = true;
            }
        }


        if (!empty($filterParams['departure_date_range'])) {
            $dateArray = explode(' - ', $filterParams['departure_date_range']);
            if (isset($dateArray[1])) {
                $dateFrom = $dateArray[0];
                $dateTo   = $dateArray[1];
                if ($haveAlreadyOneCondition) {
                    $and = ' AND ';
                } else {
                    $and = ' ';
                }
                $whereString .= $and . 'DATE(reservations.date_to) >= DATE("' . $dateFrom . '") AND ' .
                    'DATE(reservations.date_to) <= DATE("' . $dateTo . '") ';
                $haveAlreadyOneCondition = true;
            }
        }

        if (!empty($filterParams['stay_length_from'])) {
            if ($haveAlreadyOneCondition) {
                $and = ' AND ';
            } else {
                $and = ' ';
            }
            $whereString .= $and . 'DATEDIFF(reservations.date_to, reservations.date_from) >= ' . $filterParams['stay_length_from'];
            $haveAlreadyOneCondition = true;
        }

        if (!empty($filterParams['stay_length_to'])) {
            if ($haveAlreadyOneCondition) {
                $and = ' AND ';
            } else {
                $and = ' ';
            }
            $whereString .= $and . 'DATEDIFF(reservations.date_to, reservations.date_from) <= ' . $filterParams['stay_length_to'];
            $haveAlreadyOneCondition = true;
        }

        if (!empty($filterParams['score_filter']) && is_array($filterParams['score_filter']) && count($filterParams['score_filter']) != 5) {
            if ($haveAlreadyOneCondition) {
                $and = ' AND ';
            } else {
                $and = ' ';
            }
            $whereString .= $and . 'reviews.score IN (' . implode(',',$filterParams['score_filter']) . ') ';
            $haveAlreadyOneCondition = true;
        }

        if (!$haveAlreadyOneCondition) {
            $whereString = ' ';
        }
        $sql = '
        SELECT main.name AS category_name, main.id AS category_id, COUNT(*) as num FROM (
        SELECT
          ' . DbTables::TBL_APARTMENT_REVIEW_CATEGORY_REL . '.apartment_review_category_id,
          categories.name,
          categories.id
        FROM
          ' . DbTables::TBL_APARTMENT_REVIEW_CATEGORY_REL . '
          INNER JOIN ' . DbTables::TBL_APARTMENT_REVIEW_CATEGORY . ' AS categories ON categories.id = ga_apartment_review_category_rel.apartment_review_category_id
          INNER JOIN ' . DbTables::TBL_PRODUCT_REVIEWS . ' AS reviews ON ga_apartment_review_category_rel.review_id=reviews.id
          INNER JOIN ' . DbTables::TBL_APARTMENTS . ' AS apartments ON reviews.apartment_id=apartments.id
          INNER JOIN ' . DbTables::TBL_BOOKINGS . ' AS reservations ON reviews.res_id=reservations.id
          LEFT JOIN ' . DbTables::TBL_APARTMENT_GROUP_ITEMS . ' AS apartment_group_items ON apartment_group_items.apartment_id=apartments.id' .
            $whereString  . '
        GROUP BY
          ' . DbTables::TBL_APARTMENT_REVIEW_CATEGORY_REL . '.id
        ORDER BY
          ' . DbTables::TBL_APARTMENT_REVIEW_CATEGORY_REL . '.apartment_review_category_id)
          AS main GROUP BY main.apartment_review_category_id
          ORDER BY num DESC
          ;
  ';
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');
        return iterator_to_array($dbAdapter->createStatement($sql)->execute());
    }

    /**
     * @param array $post
     */
    public function changeReviewCategoriesInfo($post)
    {
        /**
         * @var \DDD\Dao\Apartment\ReviewCategoryRel $reviewsCategoryRelDao
         */
        $reviewId = $post['review_id'];
        $categories = $post['categories'];
        $reviewsCategoryRelDao = $this->getServiceLocator()->get('dao_apartment_review_category_rel');
        $oldReviews = $reviewsCategoryRelDao->getAllReviewCategoryListByReviewId($reviewId);

        $oldCategoryIds = [];
        $deletedCategoryIds = [];
        $addedCategoryIds = [];

        foreach ($oldReviews as $oldReview) {
            array_push($oldCategoryIds, $oldReview['apartment_review_category_id']);
        }
        foreach ($oldCategoryIds as $oldCategoryId) {
            if (!in_array($oldCategoryId, $categories)) {
                array_push($deletedCategoryIds, $oldCategoryId);
            }
        }

        foreach ($categories as $category) {
            if (!in_array($category, $oldCategoryIds)) {
                array_push($addedCategoryIds, $category);
            }
        }
        if (!empty($addedCategoryIds)) {
            $reviewsCategoryRelDao->add($reviewId, $addedCategoryIds);
        }

        if (!empty($deletedCategoryIds)) {
            $reviewsCategoryRelDao->remove($reviewId, $deletedCategoryIds);
        }

    }

    /**
     * @param int $reviewId
     * @param int $status
     * @return string
     */
    public function changeStatus($reviewId, $status)
    {
        /**
         * @var \DDD\Dao\Apartment\Review $reviewsDao
         */
        $reviewsDao = $this->getServiceLocator()->get('dao_apartment_review');
        $reviewsDao->save(['status' => $status], ['id' => $reviewId]);
        return self::getGlyphiconByStatus($status);
    }

    /**
     * @param int $reviewId
     */
    public function delete($reviewId)
    {
        /**
         * @var \DDD\Dao\Apartment\Review $reviewsDao
         */
        $reviewsDao = $this->getServiceLocator()->get('dao_apartment_review');
        $reviewsDao->delete(['id' => $reviewId]);
    }

    /**
     * @param $status
     * @return string
     */
    protected static function getGlyphiconByStatus($status)
    {
        $glyphicons = [
            self::REVIEW_STATUS_PENDING => '<i class="glyphicon glyphicon-question-sign color-warning"></i>',
            self::REVIEW_STATUS_APPROVED => '<i class="glyphicon glyphicon-ok color-success"></i>',
            self::REVIEW_STATUS_REJECTED => '<i class="glyphicon glyphicon-remove color-danger"></i>',

        ];

        return isset($glyphicons[$status]) ? $glyphicons[$status] : '';
    }


    /**
     * @param string $domain
     * @return \DDD\Dao\Apartment\Review
     */
    private function getApartmentReviewDao($domain = 'ArrayObject')
    {
		return new \DDD\Dao\Apartment\Review($this->getServiceLocator(), $domain);
	}
}
