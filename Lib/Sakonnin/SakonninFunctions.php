<?php

namespace CrewCallBundle\Lib\Sakonnin;

/*
 */

class SakonninFunctions implements \BisonLab\SakonninBundle\Lib\Sakonnin\SakonninFunctionsInterface
{
    protected $container;

    public $callback_functions = array();

    public $forward_functions = array(
        'smscodehandle' => array(
            'class' => 'CrewCallBundle\Lib\Sakonnin\SmsHandler',
            'description' => "Act from code and content in SMS.",
            'attribute_spec' => null,
            'needs_attributes' => false,
        ),
        'sendlistemail' => array(
            'class' => 'CrewCallBundle\Lib\Sakonnin\SendListEmail',
            'description' => "Send mail to specified email address and log to Event",
            'attribute_spec' => null,
            'needs_attributes' => false,
        ),
    );

    public function __construct($container, $options = array())
    {
        $this->container = $container;
    }

    public function getCallbackFunctions()
    {
        return $this->callback_functions;
    }

    public function getForwardFunctions()
    {
        return $this->forward_functions;
    }
}
