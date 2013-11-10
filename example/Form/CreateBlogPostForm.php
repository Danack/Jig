<?php

namespace Form;

use Intahwebz\Form\Form;

class CreateBlogPostForm extends Form{

    function getDefinition() {
        $definition = array(

            'class' => 'createBlogPostForm',

            'errorMessage' => 'Please check errors below',

            'rowElements' => array(

                array(
                    'title',
                    'type' =>  \Intahwebz\FormElement\Text::class,
                    'label' => 'Title',
                    'name' => 'title',
                    'validation' => array(
                        "Zend\\Validator\\StringLength" => array(
                            'min' => 4,
                        ),
                    )
                ),
                array(
                    'title',
                    'type' =>  \Intahwebz\FormElement\TextArea::class,
                    'label' => 'Text',
                    'name' => 'text',
                    'rows' => 15,
                    'validation' => array(
                        "Zend\\Validator\\StringLength" => array(
                            'min' => 10,
                        ),
                    )
                ),
            ),

            'endElements' => array(
                array(
                    'submitButton',
                    'type' => \Intahwebz\FormElement\SubmitButton::class,
                    'label' => null,
                    'text' => 'Submit',
                ),
            ),

            'validation' => array(
                //form level validation.
            )
        );

        return $definition;
    }
}






 