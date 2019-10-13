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
    protected static $states       = [];
    protected static $types        = [];
    protected static $system_roles = [];

    /*
     * Types:
     */
    public static function setTypesConfig($types)
    {
        self::$types = $types;
    }

    public static function getTypesConfig()
    {
        return self::$types;
    }

    public static function getTypesFor($thingie, $type)
    {
        if (!isset(self::$types[$thingie])) return array();
        return isset(self::$types[$thingie][$type]) ? self::$types[$thingie][$type] : array();
    }

    public static function getTypesAsChoicesFor($thingie, $type)
    {
        $types = self::getTypesFor($thingie, $type);
        $choices = array();
        foreach ($types as $type => $params) {
            if (!$params['chooseable']) continue;
            $choices[$params['label']] = $type;
        }
        return $choices;
    }

    /*
     * System roles:
     */
    public static function setSystemRoles($system_roles)
    {
        self::$system_roles = $system_roles;
    }

    public static function getSystemRoles()
    {
        return self::$system_roles;
    }

    public static function getSystemRolesAsChoices()
    {
        $choices = array();
        foreach (self::$system_roles as $system_role => $params) {
            $choices[$params['label']] = $system_role;
        }
        return $choices;
    }

    /*
     * States:
     */
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

    public static function getDefaultStateFor($thingie)
    {
        // And yes, I will hard code a state here.
        return self::$states[$thingie]['default_state'] ?? "ACTIVE";
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

    public static function getOpenStatesFor($thingie)
    {
        return self::$states[$thingie]['open_states'] ?? [];
    }

    public static function getDoneStatesFor($thingie)
    {
        return isset(self::$states[$thingie]) ? self::$states[$thingie]['done_states'] : self::$states['default']['done_states'];
    }

    public static function getEnableLoginStatesFor($thingie)
    {
        return isset(self::$states[$thingie]) ? self::$states[$thingie]['enable_login'] : self::$states['default']['enable_login'];
    }

    public static function getStatesAsChoicesFor($thingie)
    {
        $states = self::getStatesFor($thingie);
        $choices = array();
        foreach ($states as $state => $params) {
            $choices[$params['label']] = $state;
        }
        return $choices;
    }
}
