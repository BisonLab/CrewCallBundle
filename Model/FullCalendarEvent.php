<?php

namespace CrewCallBundle\Model;

class FullCalendarEvent implements \ArrayAccess
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var boolean
     */
    protected $allDay = false;

    /**
     * @var \DateTime
     */
    protected $start;

    /**
     * @var \DateTime
     */
    protected $end;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var boolean
     */
    protected $editable = false;

    /**
     * @var boolean
     */
    protected $startEditable = false;

    /**
     * @var boolean
     */
    protected $durationEditable = false;

    /**
     * @var boolean
     */
    protected $resourceEditable = false;

    /**
     * @var string
     */
    protected $rendering;

    /**
     * @var boolean
     */
    protected $overlap = false;

    /**
     * @var integer
     */
    protected $constraint;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var string
     */
    protected $color;

    /**
     * @var string
     */
    protected $backgroundColor;

    /**
     * @var string
     */
    protected $borderColor;

    /**
     * @var string
     */
    protected $textColor;

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        if (property_exists($this, $offset)) {
            $this->$offset = $value;
            return $this;
        }
        throw new \InvalidArgumentException("Something tried to set " . $offset . " which does not exist");
    }

    public function offsetUnset($offset)
    {
        return $this;
    }

    public function offsetExists($offset)
    {
        return property_exists($this, $offset);
    }

    public function toArray()
    {
        return array('title' => 'Blah');
    }
}
