<?php

namespace CrewCallBundle\Lib\Sakonnin;

use BisonLab\SakonninBundle\Entity\MessageContext;

/*
 */

class SendListEmail
{
    use \BisonLab\SakonninBundle\Lib\Sakonnin\CommonFunctions;

    /*
     */
    public function execute($options = array())
    {
        $sm = $this->container->get('sakonnin.messages');
        $message = $options['message'];
        $this->sendMail($message, null, $options);

        return true;
    }
}
