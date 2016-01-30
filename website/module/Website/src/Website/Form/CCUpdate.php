<?php
namespace Website\Form;

use Library\Form\FormBase;
use Zend\Form\Element;
use Library\Constants\Objects;
use Library\Validator\ClassicValidator;
use DDD\Dao\Geolocation\Countries;

class CCUpdate extends FormBase
{

    public function __construct($sm, $data)
    {
        parent::__construct('cc-update-form');

        $this->setAttributes([
            'action' => '',
            'method' => 'post',
            'role' => 'form',
            'id' => 'cc-update-form',
        ]);

        $this->setName('cc-update-form');

        $this->add(array(
		    'name' => 'country',
		    'type' => 'Zend\Form\Element\Select',
		    'attributes' => array(
			    'type' => 'text',
			    'class' => 'input-lg form-control',
                'value' => $data['guest_country_id'],
			    'tabindex' => '1',
		    ),
		    'options' => array(
			    'value_options' => $this->getCountris($sm)
		    ),

	    ));

	    $this->add(array(
		    'name' => 'city',
		    'attributes' => array(
			    'type' => 'text',
			    'class' => 'input-lg form-control',
			    'tabindex' => '2',
                'value' => $data['guest_city_name'],
		    ),
	    ));

	    $this->add(array(
		    'name' => 'zip',
		    'attributes' => array(
			    'type' => 'text',
			    'class' => 'input-lg form-control',
			    'tabindex' => '3',
                'value' => $data['guest_zip_code'],
		    ),
	    ));
        
        $this->add(array(
            'name' => 'number',
            'attributes' => array(
                'type' => 'number',
                'class' => 'input-lg form-control',
                'id' => 'cc-number',
                'max' => 9999999999999999,
	            'tabindex' => '4',
                'autofocus' => true
            ),
        ));

        $this->add(array(
            'name' => 'holder',
            'attributes' => array(
                'type' => 'text',
                'class' => 'input-lg form-control',
                'id' => 'form-cc-holder-name',
                'maxlength' => 250,
	            'tabindex' => '5',
                'value' => strtoupper($data['guest_name']),
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
	            'tabindex' => '6',
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
	            'tabindex' => '7',
	            'data-today' => date('Ym'),
            ),
        ));

        $this->add(array(
            'name' => 'cvc',
            'attributes' => array(
                'type' => 'number',
                'class' => 'input-lg form-control',
                'id' => 'form-cc-cvc',
                'max' => 9999,
	            'tabindex' => '8',
            ),
        ));

        $this->add(array(
            'name' => 'credit_card_type',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'credit_card_type',
            ),
        ));
//        $this->add(array(
//            'type' => 'Zend\Form\Element\Csrf',
//            'name' => 'csrf_secure',
//            'options' => array(
//                    'csrf_options' => array(
//                            'timeout' => 7200
//                    )
//            )
//        ));


    }

    /**
     *
     * @return type
     */
    private function getYear(){
        $fromYear = date('Y');
        $year = [0 => 'yy'];
        for($i = 0; $i<12; $i++){
            $y = $fromYear + $i;
            $year[$y] = $y;
        }
        return $year;
    }

    /**
     *
     * @return type
     */
    private function getMonth(){
        $month = [0 => 'mm'];
        for($i = 1; $i<=12; $i++){
            $val = ($i < 10) ? "0$i" : $i;
            $month[$i] = $val;
        }
        return $month;
    }
    
      /**
     *
     * @param type $options
     */
    private function getCountris($sm){
        $countrisDao = new Countries($sm);
        $countris = $countrisDao->getCountriesList();
        
        $countries = [0 => 'Country'];
        foreach ($countris as $row){
            $countries[$row->getId()] = $row->getName();
        }
        return $countries;
    }
}
