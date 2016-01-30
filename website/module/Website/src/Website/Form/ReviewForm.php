<?php

namespace Website\Form;

use Library\Form\FormBase;

class ReviewForm extends FormBase
{
    public function __construct($name = 'review', $options = array())
    {
        parent::__construct($name);

        $this->setAttributes([
            'action' => '',
            'method' => 'post',
	        'class' => 'form',
            'id' => 'review-form',
        ]);

        // Like
        $this->add([
            'name' => 'like',
            'required' => true,
            'attributes' => [
                'type' => 'textarea',
	            'class' => 'form-control',
                'id' => 'like',
                'rows' => '10',
                'maxlength' => 2000
            ],
            'options' => [
                'label' => 'Like'
            ]
        ]);

        // Suggestions
        $this->add([
            'name' => 'suggestions',
            'attributes' => [
                'type' => 'textarea',
	            'class' => 'form-control',
                'id' => 'suggestions',
                'rows' => '10',
                'maxlength' => 2000
            ],
            'options' => [
                'label' => 'Suggestions'
            ]
        ]);

        // Reservation Ticket Review Hash (review_page_hash)
        $this->add([
            'name' => 'review-hash',
            'attributes' => [
                'type' => 'hidden',
            ],
        ]);


        //starts
         $this->add([
            'name' => 'stars',
            'options' => [
                'value_options' => $this->getStars()
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id' => 'stars',
                'class' => 'form-control'
            ],
        ]);

        // Submit
        $this->add([
            'name' => 'submit',
            'type' => 'button',
            'attributes' => [
                'id' => 'submit',
	            'class' => 'btn btn-primary',
            ],
            'options' => [
                'label' => 'Submit Review'
            ]
        ]);
    }

      /**
     *
     * @return type
     */
    private function getStars(){
        $stars = ['' => ''];
        for($i = 1; $i <= 5; $i++){
            $stars[$i] = $i;
        }
        return $stars;
    }
}
