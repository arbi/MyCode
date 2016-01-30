<?php

namespace Website\Form;

use Library\Form\FormBase;
use Zend\Debug\Debug;
use Zend\Form\Element;
use Library\Utility\Helper;

class Booking extends FormBase
{

    public function __construct($options)
    {
        parent::__construct('search-form');

        $this->setAttributes([
            'action' => '',
            'method' => 'post',
            'role' => 'form',
            'id' => 'booking-form',
        ]);

        $this->setName('booking-form');

        $this->add(array(
            'name' => 'first-name',
            'attributes' => array(
                'type' => 'hidden',
                'maxlength' => 250
            ),
        ));

        $this->add(array(
            'name' => 'last-name',
            'attributes' => array(
                'type' => 'hidden',
                'maxlength' => 250
            ),
        ));

        $this->add(array(
            'name' => 'email',
            'attributes' => array(
                'type' => 'hidden',
            ),
        ));

	    $this->add(array(
		    'name' => 'phone',
		    'attributes' => array(
			    'type' => 'hidden',
		    ),
	    ));

        $this->add(array(
            'name' => 'remarks',
            'attributes' => array(
                'type' => 'hidden',
                'maxlength' => 1000
            ),
        ));

        $this->add(array(
            'name' => 'aff-id',
            'attributes' => array(
                'type' => 'hidden',
            ),
        ));

        $this->add(array(
            'name' => 'aff-ref',
            'attributes' => array(
                'type' => 'hidden',
                'maxlength' => 200
            ),
        ));
        $this->add([
            'name' => 'apartel',
            'attributes' => [
                'id' => 'apartel',
            ],
        ]);
        $this->add(array(
            'name' => 'not_send_mail',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'not_send_mail',
            ),
        ));
	    $this->add(array(
            'name' => 'credit_card_type',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'credit_card_type',
            ),
        ));

	    $this->add(array(
		    'name' => 'address',
		    'attributes' => array(
			    'type' => 'text',
			    'class' => 'input-lg form-control',
			    'tabindex' => '8',
			    'id' => 'address',
                'maxlength' => 400,
                'minlength' => 2
		    ),
	    ));

	    $this->add(array(
		    'name' => 'country',
		    'type' => 'Zend\Form\Element\Select',
		    'attributes' => array(
			    'type' => 'text',
			    'class' => 'input-lg form-control',
                'value' => $this->getUserCountry(),
			    'tabindex' => '9',
		    ),
		    'options' => array(
			    'value_options' => $this->getCountris($options['countris'])
		    ),

	    ));

	    $this->add(array(
		    'name' => 'city',
		    'attributes' => array(
			    'type' => 'text',
			    'class' => 'input-lg form-control',
			    'tabindex' => '10',
                'maxlength' => 100
		    ),
	    ));

	    $this->add(array(
		    'name' => 'zip',
		    'attributes' => array(
			    'type' => 'text',
			    'class' => 'input-lg form-control',
			    'tabindex' => '11',
                'maxlength' => 50,
                'minlength' => 3
		    ),
	    ));

        $this->add(array(
            'name' => 'number',
            'attributes' => array(
                'type' => 'number',
                'class' => 'input-lg form-control',
                'id' => 'cc-number',
                'maxlength' => 16,
	            'tabindex' => '12',
            ),
        ));

        $this->add(array(
            'name' => 'holder',
            'attributes' => array(
                'type' => 'text',
                'class' => 'input-lg form-control',
                'id' => 'form-cc-holder-name',
                'maxlength' => 300,
	            'tabindex' => '13',
            ),
        ));

        $this->add(array(
            'name' => 'month',
            'options' => array(
                'value_options' => $this->getMonth()
            ),
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id' => 'form-cc-exp-month',
                'class' => 'input-lg form-control expdate',
	            'tabindex' => '14',
	            'data-today' => date('Ym'),
            ),
        ));

        $this->add(array(
            'name' => 'year',
            'options' => array(
                'value_options' => $this->getYear()
            ),
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id' => 'form-cc-exp-year',
                'class' => 'input-lg form-control expdate',
	            'tabindex' => '15',
	            'data-today' => date('Ym'),
            ),
        ));

        $this->add(array(
            'name' => 'cvc',
            'attributes' => array(
                'type' => 'number',
                'class' => 'input-lg form-control',
                'id' => 'form-cc-cvc',
                'maxlength' => 4,
                'minlength' => 3,
	            'tabindex' => '16',
            ),
        ));

        $this->add( array(
				'name' => 'noCreditCard',
				'type' => 'Zend\Form\Element\Checkbox',
				'attributes' => array(
						'id' => 'noCreditCard',
				),
				'options' => array(
                        'use_hidden_element' => true,
                        'checked_value' => 1,
                        'unchecked_value' => 0
				),
		));

        $this->add(array(
            'name' => 'search_button',
            'type' => 'Zend\Form\Element\Button',
            'options' => array(
                'label' => 'Search',
            ),
            'attributes' => array(
                'class' => 'btn btn-primary btn-medium',
                'data-loading-text' => 'Saving...',
                'value'=>'Search',
                'id'=>'search_button',
            ),
        ));
    }

    /**
     * @return array
     */
    private function getYear()
    {
        $fromYear = date('Y');
        $year = [0 => 'Year'];

        for ($i = 0; $i<12; $i++) {
            $y = $fromYear + $i;
            $year[$y] = $y;
        }

        return $year;
    }

    /**
     *
     * @return type
     */
    private function getMonth()
    {
        $month = [0 => 'Month'];
        for($i = 1; $i<=12; $i++){
            $val = ($i < 10) ? "0$i" : $i;
            $month[$i] = $val;
        }
        return $month;
    }

    /**
     * @param \DDD\Domain\Geolocation\Countries[]|\ArrayObject $list
     * @return array
     */
    private function getCountris($list)
    {
        $countries = [0 => 'Country'];

        foreach ($list as $row) {
            $countries[$row->getId()] = $row->getName();
        }

        return $countries;
    }

    /**
     * @param \DDD\Domain\Geolocation\Countries[]|\ArrayObject $list
     * @return array
     */
    public function getCountryPostalCodes($list)
    {
        $countries = [];

        foreach ($list as $row) {
            $countries[$row->getId()] = $row->getRequiredPostalCode();
        }

        return $countries;
    }

    public function getPostalCodeStatus($countryId, $list)
    {
        $codes = $this->getCountryPostalCodes($list);

        if (!isset($codes[$countryId])) {
            return 3;
        }

        return $codes[$countryId];
    }

    private function getUserCountry()
    {
        $userCountry = Helper::getUserCountry();

        return (!Helper::isBackofficeUser() && isset($userCountry['country_id']) && $userCountry['country_id']) ? $userCountry['country_id'] : 0;
    }
}
