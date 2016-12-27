<?php

namespace CrewCallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;

use CrewCallBundle\Entity\Person;

/**
 * User controller.
 * This is the controller for the front end par of the application.
 * 
 * It's the only one for now, and may be pushed onto it's own bundle in case
 * someone means we need different front ends. Which might be true, as this
 * starts very simple as I just need it for testing functionality.
 *
 * @Route("/user/{access}", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class UserController extends CommonController
{
    /**
     * Lists all shiftFunction entities.
     *
     * @Route("/me", name="user_me")
     * @Method("GET")
     */
    public function meAction(Request $request, $access)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // Again, ajax-centric.
        if ($this->isRest($access)) {
            return $this->render('user/_me.html.twig', array(
                'user' => $user
            ));
        }

        return $this->render('user/me.html.twig', array(
            'user' => $user,
        ));
    }
}
