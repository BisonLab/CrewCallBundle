<?php

namespace CrewCallBundle\Lib\Summarizer;

class Person
{
    private $router;

    public function __construct($router)
    {
        $this->router = $router;
    }

    public function summarize(\CrewCallBundle\Entity\Person $person, $access = null)
    {
        $summary = array();

        $summary[] = array(
            'name' => 'name',
            'value' => (string)$person,
            'label' => 'Name'
            );

        $summary[] = array(
            'name' => 'mobile_phone_number',
            'value' => (string)$person->getMobilePhoneNumber(),
            'label' => 'Mobile'
            );

        if ($stateobj = $person->getStateOnDate()) {
            $text = $stateobj->getState();
            if ($fd = $stateobj->getFromDate())
                $text .= "  From:" . $fd->format('Y-m-d');
            if ($td = $stateobj->getToDate())
                $text .= "  To:" . $td->format('Y-m-d');
            $summary[] = array(
                'name' => 'state',
                'value' => $text,
                'label' => 'State'
                );
        }

        $summary[] = array(
            'name' => 'diets',
            'value' => implode(", ", $person->getDietsLabels()),
            'label' => 'Diets'
            );

        return $summary;
    }
}
