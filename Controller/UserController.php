<?php

namespace CrewCallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;

use CrewCallBundle\Entity\Person;
use CrewCallBundle\Entity\Shift;
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
     * Lists all Jobs for a user.
     *
     * @Route("/me", name="user_me", methods={"GET"})
     */
    public function meAction(Request $request, $access)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // Again, ajax-centric.
        if ($this->isRest($access)) {
            return $this->render('user/_me.html.twig', array(
                'past' => $request->get('past'),
                'user' => $user
            ));
        }

        return $this->render('user/me.html.twig', array(
            'past' => $request->get('past'),
            'user' => $user,
        ));
    }

    /**
     * Lists all the users jobs as calendar events.
     *
     * @Route("/me_calendar", name="user_me_calendar")
     */
    public function meCalendarAction(Request $request, $access)
    {
        $user = $this->getUser();
        if ($this->isRest($access)) {
            $calendar = $this->container->get('crewcall.calendar');
            $jobservice = $this->container->get('crewcall.jobs');

            // Gotta get the time scope.
            $from = $request->get('start');
            $to = $request->get('end');
            $jobs = $jobservice->jobsForPerson($user,
                array('all' => true, 'from' => $from, 'to' => $to));
            // $states = $user->getStates();
            $em = $this->getDoctrine()->getManager();
            $states = $em->getRepository('CrewCallBundle:PersonState')
                ->findByPerson($user,
                array('from_date' => $from, 'to_date' => $to));
error_log("From: " . $from . " To:" . $to);
            $calitems = array_merge(
                $calendar->toFullCalendarArray($jobs, $this->getUser()),
                $calendar->toFullCalendarArray($states, $this->getUser())
            );
    $from_t = strtotime($from);
    $to_t   = strtotime($to);
    if (($to_t - $from_t) > 1728000) {
        $calitems = array_merge(
            $calendar->toFullCalendarSummary($jobs, $this->getUser()),
            $calendar->toFullCalendarSummary($states, $this->getUser())
        );
    } else {
        $calitems = array_merge(
            $calendar->toFullCalendarArray($jobs, $this->getUser()),
            $calendar->toFullCalendarArray($states, $this->getUser())
        );
    }
            // Not liked by OWASP since we just return an array.
            return new JsonResponse($calitems, Response::HTTP_OK);
        }
        return $this->render('user/calendar.html.twig', array(
            'user' => $user,
        ));
    }

    /**
     *
     * @Route("/confirm/{id}", name="user_confirm_job", methods={"POST"})
     */
    public function confirmJobAction(Request $request, Job $job, $access)
    {
        $user = $this->getUser();
        // TODO: Move to a service.
        $job->setState('CONFIRMED');
        $em = $this->getDoctrine()->getManager();
        $em->persist($job);
        $em->flush($job);
        if ($this->isRest($access)) {
            return new JsonResponse("OK", Response::HTTP_OK);
        }
        return $this->redirectToRoute('user_me');
    }

    /**
     *
     * @Route("/register_interest/{id}", name="user_register_interest", methods={"POST"})
     */
    public function registerInterestAction(Request $request, Shift $shift, $access)
    {
        $user = $this->getUser();

        $job = new Job();
        $job->setShift($shift);
        $job->setPerson($user);
        $job->setState('INTERESTED');
        $em = $this->getDoctrine()->getManager();
        $em->persist($job);
        $em->flush($job);
        return $this->redirectToRoute('user_me');
    }

    /**
     *
     * @Route("/job_calendaritem/{id}", name="user_job_calendar_item", methods={"GET"})
     */
    public function jobCaledarItemAction(Request $request, Job $job, $access)
    {
        $user = $this->getUser();
        // Better find the right exception later.
        if ($user->getId() != $job->getPerson()->getId())
            throw new \InvalidArgumentException("You are not the one to grab this.");

        $calendar = $this->container->get('crewcall.calendar');
        $ical = $calendar->toIcal($job);

        $response = new Response($ical, Response::HTTP_OK);
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="cal.ics"');
        return $response;
    }

    /**
     *
     * @Route("/delete_interest/{id}", name="user_delete_interest", methods={"DELETE", "POST"})
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
