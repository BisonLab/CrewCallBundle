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
 * @Route("/uf")
 */
class UserFrontController extends CommonController
{
    /**
     * Login
     *
     * @Route("/login", name="uf_login", methods={"GET"})
     */
    public function loginAction(Request $request)
    {
        // Create a csrf token for use in the next step
        $csrfman = $this->get('security.csrf.token_manager');
        $csrfToken = $csrfman->getToken('authenticate')->getValue();

        return new JsonResponse([
            '_csrf_token' => $csrfToken,
            '_username' => "",
            "_password" => '',
            "_remember_me" => "on",
            "login_url" => $this->generateUrl('fos_user_security_check')
            ],
            Response::HTTP_OK);
    }

    /**
     * Login
     *
     * @Route("/login_check", name="user_login_check", methods={"POST"})
     */
    public function loginCheckAction(Request $request)
    {
        $lm = $this->container->get('fos_user.login_manager');

        return new JsonResponse("Not yet", Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Lists all Jobs for a user.
     *
     * @Route("/me", name="uf_me", methods={"GET"})
     */
    public function meAction(Request $request, $access)
    {
        $ccjobs = $this->container->get('crewcall.jobs');
        // Create a csrf token for use in the next step
        $csrfman = $this->get('security.csrf.token_manager');
        $csrfToken = $csrfman->getToken('confirm-job')->getValue();

        $from = $request->get('from') ?? null;
        $to = $request->get('to') ?? null;
        // Should I add a "Limit"?

        return new JsonResponse([
            'confirm_job' => [
                '_csrf_token' => $csrfToken,
            ],
            'interested' =>  $ccjobs->getJobsForPersonArray($user, [
                'from' => $from,
                'to' => $to,
                'state' => 'INTERESTED']),
            'assigned' =>  $ccjobs->getJobsForPersonArray($user, [
                'from' => $from,
                'to' => $to,
                'state' => 'ASSIGNED']),
            'booked' =>  $ccjobs->getJobsForPersonArray($user, [
                'from' => $from,
                'to' => $to,
                'booked' => true]),
        ]);
    }

    /**
     * Lists all the users jobs as calendar events.
     *
     * @Route("/me_calendar", name="uf_me_calendar")
     */
    public function meCalendarAction(Request $request)
    {
        $user = $this->getUser();
        $calendar = $this->container->get('crewcall.calendar');
        $jobservice = $this->container->get('crewcall.jobs');

        // Gotta get the time scope.
        $from = $request->get('start');
        $to = $request->get('end');
        $jobs = $jobservice->jobsForPerson($user,
            array('all' => true, 'from' => $from, 'to' => $to));
        $states = $user->getStates();
        
        $calitems = array_merge(
            $calendar->toFullCalendarArray($jobs, $this->getUser()),
            $calendar->toFullCalendarArray($states, $this->getUser())
        );
        // Not liked by OWASP since we just return an array.
        return new JsonResponse($calitems, Response::HTTP_OK);
    }

    /**
     *
     * @Route("/confirm/{id}", name="uf_confirm_job", methods={"POST"})
     */
    public function confirmJobAction(Request $request, Job $job)
    {
        $token = $request->request->get('end');
        if (!$this->isCsrfTokenValid('confirm-job', $token)) {
            return new Response("No", Response::HTTP_FORBIDDEN);
        }
        
        $job->setState('CONFIRMED');
        $em = $this->getDoctrine()->getManager();
        $em->persist($job);
        $em->flush($job);
        return new Response("OK", Response::HTTP_OK);
    }

    /**
     *
     * @Route("/job_calendaritem/{id}", name="uf_job_calendar_item", methods={"GET"})
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
     * @Route("/delete_interest/{id}", name="uf_delete_interest", methods={"DELETE", "POST"})
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
        return $this->redirectToRoute('uf_me');
    }
}
