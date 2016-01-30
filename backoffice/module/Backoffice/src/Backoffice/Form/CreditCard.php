<?php
namespace Backoffice\Form;

use Library\Form\FormBase;
use Zend\Form\Element;
use Library\Constants\Objects;
use Library\Validator\ClassicValidator;
use DDD\Dao\Geolocation\Countries;

class CreditCard extends FormBase
{

    public function __construct()
    {
        parent::__construct('cc-new-form');

        $this->setAttributes([
            'action' => '',
            'method' => 'post',
            'role' => 'form',
            'id' => 'cc-new-form',
        ]);

        $this->setName('cc-new-form');
        $this->add(array(
            'name' => 'number',
            'attributes' => array(
                'type' => 'number',
                'class' => 'form-control margin-bottom-5',
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
                'class' => 'form-control',
                'id' => 'form-cc-holder-name',
                'maxlength' => 250,
	            'tabindex' => '5',
                'value' => '',
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
                'class' => 'form-control expdate',
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
                'class' => 'form-control expdate',
	            'tabindex' => '7',
	            'data-today' => date('Ym'),
            ),
        ));

        $this->add(array(
            'name' => 'cvc',
            'attributes' => array(
                'type' => 'number',
                'class' => 'form-control',
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
        $year = [0 => 'Year'];
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
        $month = [0 => 'Month'];
        for($i = 1; $i<=12; $i++){
            $val = ($i < 10) ? "0$i" : $i;
            $month[$i] = $val;
        }
        return $month;
    }
}
