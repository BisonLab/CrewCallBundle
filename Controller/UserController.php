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
use CrewCallBundle\Entity\ShiftFunction;
use CrewCallBundle\Entity\Job;

use CrewCallBundle\Model\FullCalendarEvent;

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

    /**
     * Lists all shiftFunction entities as calendar events.
     *
     * @Route("/me_calendar", name="user_me_calendar")
     */
    public function meCalendarAction(Request $request, $access)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // Gotta get the time scope.
        $from = $request->get('start');
        $to = $request->get('end');
error_log($from . " " . $to);
        
        $events = array();
        $e = new FullCalendarEvent();
        $e['start'] = new \DateTime('2018-01-12 12:30');
        $e['end'] = new \DateTime('2018-01-12 18:30');
        $events[] = $e;
error_log(json_encode($e, true));
error_log(json_encode($e));

        return new JsonResponse($events, Response::HTTP_OK);
    }

    /**
     *
     * @Route("/register_interest/{id}", name="user_register_interest")
     * @Method("POST")
     */
    public function registerInterestAction(Request $request, ShiftFunction $shiftFunction, $access)
    {
        $user = $this->getUser();

        $job = new Job();
        $job->setShiftFunction($shiftFunction);
        $job->setPerson($user);
        // I guess it's just too much work finding whatever state you'd rather
        // use for this. Like "REGISTERED" instead.
        // They want it, alas they show interest.
        $job->setState('INTERESTED');
        $em = $this->getDoctrine()->getManager();
        $em->persist($job);
        $em->flush($job);
        return $this->redirectToRoute('user_me');
    }

    /**
     *
     * @Route("/delete_interest/{id}", name="user_delete_interest")
     * @Method("POST")
     */
    public function deleteInterestAction(Request $request, Job $job, $access)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        /*
         * In case of someone trying..
         * TODO: Decide on wether to add an isDeleteable() on Job and other
         * entities or do something else if it's smarter.
         * The reason is that it's not allowed to delete a confirmed job.
         */
        if ($job->getPerson() !== $user) {
            throw new \InvalidArgumentException('Nice try');
        }
        $em->remove($job);
        $em->flush($job);
        return $this->redirectToRoute('user_me');
    }
}
