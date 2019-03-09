<?php

namespace CrewCallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
            "login_url" => $this->generateUrl('fos_user_security_check', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ],
            Response::HTTP_OK);
    }

    /**
     * Ping
     *
     * @Route("/ping", name="uf_ping", methods={"GET"})
     */
    public function pingAction(Request $request)
    {
        return new JsonResponse([
            'ACK' => true,
            ],
            Response::HTTP_OK);
    }

    /**
     * Everything, and maybe more.
     *
     * @Route("/me", name="uf_me", methods={"GET"})
     */
    public function meAction(Request $request)
    {
        $user = $this->getUser();
        $retarr = [
            'firstname' => $user->getFirstName(),
            'lastname' => $user->getLastName(),
        ];

//        $retarr['jobs'] = $this->meJobs($request, true);
//        $retarr['messages'] = $this->meMessages($request, true);
        return new JsonResponse($retarr, 200);
    }

    /**
     * Messages part
     * @Route("/me_messages", name="uf_me_messages", methods={"GET"})
     */
    public function meMessages(Request $request, $as_array = false)
    {
        $user = $this->getUser();
        $sakonnin = $this->container->get('sakonnin.messages');
        $pncontext = [
            'system' => 'crewcall',
            'object_name' => 'person',
            'message_type' => 'PersonNote',
            'states' => ['UNREAD', 'SENT'],
            'external_id' => $user->getId(),
        ];
        $pnotes = [];
        foreach ($sakonnin->getMessagesForContext($pncontext) as $m) {
            $pnotes[] = [
                'subject' => $m->getSubject(),
                'body' => $m->getBody(),
                'date' => $m->getCreatedAt(),
                'message_type' => (string)$m->getMessageType(),
                'archive_url' => $this->generateUrl('message_state', [
                    'access' => 'ajax',
                    'state' => 'ARCHIVED',
                    'id' => $m->getId()
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL)
                ];
        }

        foreach ($sakonnin->getMessagesForUser($user, ['state' => 'UNREAD']) as $m) {
            $pnotes[] = [
                'subject' => $m->getSubject(),
                'body' => $m->getBody(),
                'date' => $m->getCreatedAt(),
                'message_type' => (string)$m->getMessageType(),
                'archive_url' => $this->generateUrl('message_state', [
                    'access' => 'ajax',
                    'state' => 'ARCHIVED',
                    'id' => $m->getId()
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL)
                ];
        }

        $gnotes = [];
        if ($mt = $sakonnin->getMessageType('Front page logged in')) {
            foreach ($mt->getMessages() as $m) {
                if ($m->getState() == "SHOW") {
                    $gnotes[] = [
                        'subject' => $m->getSubject(),
                        'body' => $m->getBody(),
                        'date' => $m->getCreatedAt(),
                    ];
                }
            }
        }
        $retarr = [
            'personal' => $pnotes,
            'general' => $gnotes,
            ];

        if ($as_array)
            return $retarr;
        return new JsonResponse($retarr, 200);
    }

    /**
     * Jobs part
     * @Route("/me_jobs", name="uf_me_jobs", methods={"GET"})
     */
    public function meJobs(Request $request, $as_array = false)
    {
        $ccjobs = $this->container->get('crewcall.jobs');
        // Create a csrf token for use in the next step
        $csrfman = $this->get('security.csrf.token_manager');

        $from = $request->get('from') ?? null;
        $to = $request->get('to') ?? null;
        $state = $request->get('state') ?? null;
        $user = $this->getUser();
        // Should I add a "Limit"?

        $retarr = [];

        // There is no real state for opportunities, logically.
        if (!$state || $state == 'OPPORTUNITIES') {
            $signuptoken = $csrfman->getToken('signup-shift')->getValue();
            $retarr['signup_shift'] = [
                '_csrf_token' => $signuptoken,
                'url' => $this->generateUrl('uf_signup_shift', ['id' => 'ID'], UrlGeneratorInterface::ABSOLUTE_URL)
            ];
            $retarr['opportunities'] = $ccjobs->opportunitiesForPersonAsArray($user);
        }
            
        if (!$state || $state == 'INTERESTED') {
            $ditoken = $csrfman->getToken('delete-interest')->getValue();
            $retarr['delete_interest'] = [
                '_csrf_token' => $ditoken,
                'url' => $this->generateUrl('uf_delete_interest', ['id' => 'ID'], UrlGeneratorInterface::ABSOLUTE_URL)
            ];
            $retarr['interested'] = $ccjobs->jobsForPersonAsArray($user, [
                'from' => $from, 'to' => $to,
                'state' => 'INTERESTED']);
        }

        if (!$state || $state == 'ASSIGNED') {
            $confirmtoken = $csrfman->getToken('confirm-job')->getValue();
            $retarr['confirm_job'] = [
                '_csrf_token' => $confirmtoken,
                'url' => $this->generateUrl('uf_confirm_job', ['id' => 'ID'], UrlGeneratorInterface::ABSOLUTE_URL)
            ];
            $retarr['assigned'] = $ccjobs->jobsForPersonAsArray($user, [
                'from' => $from, 'to' => $to,
                'state' => 'ASSIGNED']);
        }

        if (!$state || $state == 'CONFIRMED') {
            $retarr['confirmed'] = $ccjobs->jobsForPersonAsArray($user, [
                'booked' => true]);
        }

        if ($as_array)
            return $retarr;
        return new JsonResponse($retarr, 200);
    }

    /**
     *
     * @Route("/confirm/{id}", name="uf_confirm_job", methods={"POST"})
     */
    public function confirmJobAction(Request $request, Job $job)
    {
        $token = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('confirm-job', $token)) {
            return new Response("No", Response::HTTP_FORBIDDEN);
        }

        // From the part this called, the previous state *shall* be ASSIGNED.
        // Just check it.
        if ($job->getState() != 'ASSIGNED')
            return new Response("Bad state", Response::HTTP_FORBIDDEN);

        if ($job->getPerson() !== $this->getUser())
            return new Response("Bad user", Response::HTTP_FORBIDDEN);
        
        $job->setState('CONFIRMED');
        $em = $this->getDoctrine()->getManager();
        $em->persist($job);
        $em->flush($job);
        return new Response("OK", Response::HTTP_OK);
    }

    /**
     *
     * @Route("/signup/{id}", name="uf_signup_shift", methods={"POST"})
     */
    public function signupShiftAction(Request $request, Shift $shift)
    {
        $token = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('signup-shift', $token)) {
            return new Response("No", Response::HTTP_FORBIDDEN);
        }

        $user = $this->getUser();
        $job = new Job();
        $job->setShift($shift);
        $job->setPerson($user);
        $job->setState('INTERESTED');
        $em = $this->getDoctrine()->getManager();
        $em->persist($job);
        $em->flush($job);
        return new Response("OK", Response::HTTP_OK);
    }

    /**
     *
     * @Route("/delete_interest/{id}", name="uf_delete_interest", methods={"DELETE", "POST"})
     */
    public function deleteInterestAction(Request $request, Job $job)
    {
        $token = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('delete-interest', $token)) {
            return new Response("No", Response::HTTP_FORBIDDEN);
        }
        // From the part this called, the previous state *shall* be ASSIGNED.
        // Just check it.
        $user = $this->getUser();
        if ($job->getState() != 'INTERESTED')
            return new Response("Bad state", Response::HTTP_FORBIDDEN);

        if ($job->getPerson() !== $user)
            return new Response("Bad user", Response::HTTP_FORBIDDEN);

        $em = $this->getDoctrine()->getManager();
        $em->remove($job);
        $em->flush($job);
        return new Response("OK", Response::HTTP_OK);
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
        $options = [ 'from' => $from, 'to' => $to ];
        if ($state = $request->get('state'))
            $options['state'] = $state;
        $jobs = $jobservice->jobsForPerson($user, $options);

        // If the date difference exeeds a week, we want to just send the
        // summary. (If you want a complete list, use the UserController
        // version.
        $from_t = strtotime($from);
        $to_t   = strtotime($to);
        // 20 days and above? Summary it is.        
error_log("From: " . $from . " To:" . $to . " State:" . $state);
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
        return new JsonResponse($calitems, Response::HTTP_OK);
    }

    /**
     *
     * @Route("/job_calendaritem/{id}", name="uf_job_calendar_item", methods={"GET"})
     */
    public function jobCaledarItemAction(Request $request, Job $job)
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
}
