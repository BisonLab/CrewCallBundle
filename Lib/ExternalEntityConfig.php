<?php

namespace CrewCallBundle\Lib;

/*
 *  Idea blatantly nicked from:
 * http://dev4theweb.blogspot.pt/2012/08/how-to-access-configuration-values.html
 * and is it as wrong as people say?
 * It works, well. That makes me happier than "bad pattern"
 * (He even uses Lib/ as I am alot already, so it cannot be wrong!)
 */

class ExternalEntityConfig
{
    protected static $states = array();
    protected static $types = array();

    public static function setStatesConfig($states)
    {
        self::$states = $states;
    }

    public static function getStatesConfig()
    {
        return self::$states;
    }

    public static function getStatesFor($thingie)
    {
        return isset(self::$states[$thingie]) ? self::$states[$thingie]['states'] : self::$states['default']['states'];
    }

    public static function getActiveStatesFor($thingie)
    {
        return isset(self::$states[$thingie]) ? self::$states[$thingie]['active_states'] : self::$states['default']['active_states'];
    }

    public static function getWishlistStatesFor($thingie)
    {
        return isset(self::$states[$thingie]) ? self::$states[$thingie]['wishlist_states'] : self::$states['default']['wishlist_states'];
    }

    public static function getBookedStatesFor($thingie)
    {
        return isset(self::$states[$thingie]) ? self::$states[$thingie]['booked_states'] : self::$states['default']['booked_states'];
    }

    public static function getEnableLoginStatesFor($thingie)
    {
        return isset(self::$states[$thingie]) ? self::$states[$thingie]['enable_login'] : self::$states['default']['enable_login'];
    }

    public static function getStatesAsChoicesFor($thingie)
    {
        $states = self::getStatesFor($thingie);
        return array_combine(array_keys($states), array_keys($states));
    }
}
