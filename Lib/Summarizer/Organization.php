<?php

namespace CrewCallBundle\Lib\Summarizer;

class Organization
{
    private $router;

    public function __construct($router)
    {
        $this->router = $router;
    }

    public function summarize(\CrewCallBundle\Entity\Organization $organization, $access = null)
    {
        $summary = array();

        $summary[] = array(
            'name' => 'name',
            'value' => (string)$organization,
            'label' => 'Name'
            );

        return $summary;
    }
}
