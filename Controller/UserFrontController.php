<?php

namespace CrewCallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;

use CrewCallBundle\Entity\Person;
use CrewCallBundle\Entity\Event;
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
    private $shiftcache = [];
    private $eventcache = [];

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
        return new JsonResponse($retarr, 200);
    }

    /**
     * Everything, and maybe more.
     *
     * @Route("/me_profile", name="uf_me_profile", methods={"GET"})
     */
    public function meProfileAction(Request $request)
    {
        $user = $this->getUser();
        $sakonnin_files = $this->container->get('sakonnin.files');
        $addressing = $this->container->get('crewcall.addressing');
        $pfiles = $sakonnin_files->getFilesForContext([
                'file_type' => 'ProfilePicture',
                'system' => 'crewcall',
                'object_name' => 'person',
                'external_id' => $user->getId()
            ]);
        $profile_picture_url = null;
        if (count($pfiles) > 0) {
            $router = $this->container->get('router');
            $profile_picture_url = $router->generate('uf_file', [
                'id' => $pfiles[0]->getFileId(), 'x' => 200, 'y' => 200]);
        }

        $retarr = [
            'firstname' => $user->getFirstName(),
            'lastname' => $user->getLastName(),
            'diets' => $user->getDietsLabels(),
            'email' => $user->getEmail(),
            'mobile_phone_number' => $user->getMobilePhoneNumber(),
            'mobile_phone_number' => $user->getMobilePhoneNumber(),
            'profile_picture_url' => $profile_picture_url,
            'address' => [],
            'functions' => [],
        ];
        if ($address = $user->getAddress()) {
            $retarr['address'] = $addressing->compose($address);
            $retarr['address_flat'] = $addressing->compose($address, 'flat');
        }
        foreach ($user->getStates() as $ps) {
            if ($ps->getState() == "ACTIVE") continue;
            $retarr['absence'][] = [
                'reason' => ucfirst(strtolower($ps->getState())),
                'state' => $ps->getState(),
                'from_date' => $ps->getFromDate()->format('Y-m-d'),
                'to_date' => $ps->getToDate()->format('Y-m-d'),
            ];
        }
        foreach ($user->getPersonFunctions() as $pf) {
            $retarr['functions'][] = (string)$pf;
        }
        foreach ($user->getPersonFunctionOrganizations() as $pfo) {
            $retarr['roles'][] = [
                'function' => (string)$pfo->getFunction(),
                'function_type' => $pfo->getFunction()->getFunctionType(),
                'organization' => (string)$pfo->getOrganization(),
                'description' => (string)$pfo,
            ];
        }
        return new JsonResponse($retarr, 200);
    }

    /**
     * Notes
     * @Route("/me_notes", name="uf_me_notes", methods={"GET"})
     */
    public function meNotes(Request $request, $as_array = false)
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
     * Messages part
     * @Route("/me_messages", name="uf_me_messages", methods={"GET"})
     */
    public function meMessages(Request $request, $as_array = false)
    {
        $user = $this->getUser();
        $sakonnin = $this->container->get('sakonnin.messages');
        $pmessages = [];

        foreach ($sakonnin->getMessagesForUser($user, ['state' => 'UNREAD']) as $m) {
            $pmessages[] = [
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
        $retarr = [
            'personal' => $pmessages,
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

        $today = new \DateTime();
        $from = new \DateTime($request->get('from') ?? null);
        $to = new \DateTime($request->get('to') ?? '+1 year');
        $state = $request->get('state') ?? null;
        // Hack, by request.
        if ($month = $request->get('month')) {
            $year = date("Y");
            $now_month = date("m");
            if ($month < $now_month)
                $year++;
            $from = new \DateTime($year . "-" . $month);
            $to = clone($from);
            $to->modify('last day of this month');
        }

        // Either way, never go below today. Historical jobs will be handled
        // somewhere else or with a query option to override.
        if ($from < $today)
            $from = $today;

        $user = $this->getUser();
        // Should I add a "Limit"?

        $retarr = [
            'period' => [ 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d') ],
            'state' => $state
            ];

        // There is no real state for opportunities, logically.
        if (!$state || $state == 'OPPORTUNITIES') {
            $signuptoken = $csrfman->getToken('signup-shift')->getValue();
            $retarr['signup_shift'] = [
                '_csrf_token' => $signuptoken,
                'url' => $this->generateUrl('uf_signup_shift', ['id' => 'ID'], UrlGeneratorInterface::ABSOLUTE_URL)
            ];
            $retarr['opportunities'] = $this->opportunitiesForPersonAsArray(
                $user,
                [ 'from' => $from, 'to' => $to ]
                );
            $retarr['opportunities_count'] = count($retarr['opportunities']);
        }
            
        if (!$state || $state == 'INTERESTED') {
            $ditoken = $csrfman->getToken('delete-interest')->getValue();
            $retarr['delete_interest'] = [
                '_csrf_token' => $ditoken,
                'url' => $this->generateUrl('uf_delete_interest', ['id' => 'ID'], UrlGeneratorInterface::ABSOLUTE_URL)
            ];
            $retarr['interested'] = $this->jobsForPersonAsArray($user, [
                'from' => $from, 'to' => $to,
                'state' => 'INTERESTED']);
            $retarr['interested_count'] = count($retarr['interested']);
        }

        if (!$state || $state == 'ASSIGNED') {
            $confirmtoken = $csrfman->getToken('confirm-job')->getValue();
            $retarr['confirm_job'] = [
                '_csrf_token' => $confirmtoken,
                'url' => $this->generateUrl('uf_confirm_job', ['id' => 'ID'], UrlGeneratorInterface::ABSOLUTE_URL)
            ];
            $retarr['assigned'] = $this->jobsForPersonAsArray($user, [
                'from' => $from, 'to' => $to,
                'state' => 'ASSIGNED']);
            $retarr['assigned_count'] = count($retarr['assigned']);
        }

        if (!$state || $state == 'CONFIRMED') {
            $retarr['confirmed'] = $this->jobsForPersonAsArray($user, [
                'from' => $from, 'to' => $to,
                'booked' => true]);
            $retarr['confirmed_count'] = count($retarr['confirmed']);
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
        if (!$token = $request->request->get('_csrf_token')) {
            $json_data = json_decode($request->getContent(), true);
            $token = $json_data['_csrf_token'];
        }
        if (!$this->isCsrfTokenValid('confirm-job', $token)) {
            return new JsonResponse(["ERRROR" => "No luck"], Response::HTTP_FORBIDDEN);
        }

        // From the part this called, the previous state *shall* be ASSIGNED.
        // Just check it.
        if ($job->getState() != 'ASSIGNED')
            return new JsonResponse(["ERRROR" => "No luck"], Response::HTTP_FORBIDDEN);

        if ($job->getPerson() !== $this->getUser())
            return new JsonResponse(["ERRROR" => "No luck"], Response::HTTP_FORBIDDEN);
        
        $job->setState('CONFIRMED');
        $em = $this->getDoctrine()->getManager();
        $em->persist($job);
        $em->flush($job);
        return new JsonResponse(["OK" => "Well done"], Response::HTTP_OK);
    }

    /**
     *
     * @Route("/signup/{id}", name="uf_signup_shift", methods={"POST"})
     */
    public function signupShiftAction(Request $request, Shift $shift)
    {
        $json_data = json_decode($request->getContent(), true);
        if (!$token = $request->request->get('_csrf_token')) {
            $token = $json_data['_csrf_token'];
        }

        if (!$this->isCsrfTokenValid('signup-shift', $token)) {
            return new JsonResponse(["ERRROR" => "No luck"], Response::HTTP_FORBIDDEN);
        }

        $user = $this->getUser();
        $job = new Job();
        $job->setShift($shift);
        $job->setPerson($user);
        $job->setState('INTERESTED');
        $em = $this->getDoctrine()->getManager();
        $em->persist($job);
        $em->flush($job);

        if (!$comment = $request->request->get('comment')) {
            $comment = $json_data['comment'] ?? null;
        }
        if ($comment) {
            $sm = $this->get('sakonnin.messages');

            $message_context = [
                'system' => 'crewcall',
                'object_name' => 'job',
                'external_id' => $job->getId(),
            ];

            $sm->postMessage(array(
                'body' => $comment,
                'message_type' => 'JobComment',
                'to_type' => "NONE",
                'from_type' => "NONE",
            ), $message_context);
        }
        if (!$checks = $request->request->get('checks')) {
            $checks = $json_data['checks'] ?? array();
        }
        foreach ($checks as $check_data) {
            $sm = $this->get('sakonnin.messages');
            $shift_check = $sm->getMessages([
                'id' => $check_data['id']
                ]);

            $message_context = [
                'system' => 'crewcall',
                'object_name' => 'job',
                'external_id' => $job->getId(),
            ];

            $posted = $sm->postMessage(array(
                'message_type' => $shift_check->getMessageType()->getName(),
                'body' => $shift_check->getBody(),
                'in_reply_to' => $shift_check->getMessageId(),
                'state' => "CHECKED",
                'to_type' => "NONE",
                'from_type' => "NONE",
            ), $message_context);
        }
        return new JsonResponse(["OK" => "Well done"], Response::HTTP_OK);
    }

    /**
     *
     * @Route("/delete_interest/{id}", name="uf_delete_interest", methods={"DELETE", "POST"})
     */
    public function deleteInterestAction(Request $request, Job $job)
    {
        if (!$token = $request->request->get('_csrf_token')) {
            $json_data = json_decode($request->getContent(), true);
            $token = $json_data['_csrf_token'];
        }
        if (!$this->isCsrfTokenValid('delete-interest', $token)) {
            return new JsonResponse(["ERRROR" => "No luck"], Response::HTTP_FORBIDDEN);
        }
        // From the part this called, the previous state *shall* be ASSIGNED.
        // Just check it.
        $user = $this->getUser();
        if ($job->getState() != 'INTERESTED')
            return new JsonResponse(["ERRROR" => "No luck"], Response::HTTP_FORBIDDEN);

        if ($job->getPerson() !== $user)
            return new JsonResponse(["ERRROR" => "No luck"], Response::HTTP_FORBIDDEN);

        $em = $this->getDoctrine()->getManager();
        $em->remove($job);
        $em->flush($job);
        return new JsonResponse(["OK" => "Well done"], Response::HTTP_OK);
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
        if (($to_t - $from_t) > 1728000) {
            $calitems = $calendar->toFullCalendarSummary($jobs, $user);
        } else {
            $calitems = $calendar->toFullCalendarArray($jobs, $user);
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

    /**
     * The time log per person.
     *
     * @Route("/me_joblog", name="uf_me_joblog", methods={"GET"})
     */
    public function jobLogAction(Request $request)
    {
        $handler = $this->get('crewcall.joblogs');
        $job = null;
        $options['summary_only'] = $request->get('summary_only');
        $options['from_date'] = $request->get('from_date');
        $options['to_date'] = $request->get('to_date');

        $person = $this->getUser();
        $logs = $handler->getJobLogsForPerson($person, $options);

        return new JsonResponse([
                'jobslog' => $logs['joblog_array'],
                'summary' => $logs['summary'],
            ], Response::HTTP_OK);
    }

    /**
     * Profilepicture
     *
     * @Route("/{id}/file", name="uf_file", methods={"GET"})
     */
    public function fileAction(Request $request, $id)
    {
        $sf = $this->container->get('sakonnin.files');
        $sfile = $sf->getFiles(['fileid' => $id]);
        if (!$sfile)
            return new JsonResponse([
                'ERROR'=> 'Not found'], Response::HTTP_NOT_FOUND);

        if ($sfile->getThumbnailable() && $x = $request->get('y')) {
            $y = $request->get('x') ?: $y;
            // TODO: Add access control.
            // Gotta get the thumbnail then.
            $thumbfile = $sf->getThumbnailFilename($sfile, $x, $y);
            $response = new BinaryFileResponse($thumbfile);
        } else {
            $filename = $sf->getStoredFileName($sfile);
            $response = new BinaryFileResponse($filename);
        }
        return $response;
    }

    /**
     * Everything, and maybe more.
     *
     * @Route("/me_files", name="uf_me_files", methods={"GET"})
     */
    public function meFiles(Request $request)
    {
        $user = $this->getUser();
        $sakonnin_files = $this->container->get('sakonnin.files');
        $addressing = $this->container->get('crewcall.addressing');
        $sfiles = $sakonnin_files->getFilesForContext([
                'system' => 'crewcall',
                'object_name' => 'person',
                'external_id' => $user->getId()
            ]);
        $fileslist = [];
        foreach($sfiles as $sfile) {
            $f = [];
            $router = $this->container->get('router');
            $f['url'] = $router->generate('uf_file', [
                'id' => $sfile->getFileId()]);
            $f['name'] = $sfile->getName();
            $f['file_type'] = $sfile->getFileType();
            $f['description'] = $sfile->getDescription() ?: "None";
            $fileslist[] = $f;
        }
        return new JsonResponse([
                'files' => $fileslist,
            ], Response::HTTP_OK);
    }

    /**
     * Get change password form
     * @Route("/me_password", name="uf_me_password", methods={"GET", "POST"})
     */
    public function mePassword(Request $request)
    {
        $user = $this->getUser();
        
        $form = $this->createForm("FOS\UserBundle\Form\Type\ChangePasswordFormType");
        $form->add('Change password', SubmitType::class);
        $form->setData($user);
        $errors = [];
        if ($data = json_decode($request->getContent(), true)) {
            // Hack, Angfular does not comply.
            if (isset($data['first'])) {
                $data['plainPassword'] = [];
                $data['plainPassword']['first'] = $data['first'];
                unset($data['first']);
                $data['plainPassword']['second'] = $data['second'] ?: null;
                unset($data['second']);
            }
            $form->submit($data);
            if ($form->isSubmitted() && $form->isValid()) {
                $userManager = $this->get('fos_user.user_manager');
                $userManager->updateUser($user);
                return new JsonResponse(["OK" => "Well done"], Response::HTTP_OK);
            }
            $errors = $this->handleFormErrors($form);
        }

        $form = $form->createView();
        $csrfman = $this->get('security.csrf.token_manager');
        $csrfToken = $csrfman->getToken('change_password')->getValue();
        if (count($errors) > 0) {
            return new JsonResponse([
                "ERROR" => [
                    "errors" => $errors,
                    "fos_user_change_password" => [
                        "_token" => $csrfToken,
                        "current_password" => "",
                        "plainPassword" => ["first" => "", "second" => "" ]
                        ]
                    ]
                ],
                Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            "fos_user_change_password" => [
                "_token" => $csrfToken,
                "current_password" => "",
                "plainPassword" => ["first" => "", "second" => "" ]
                ],
            ],
            Response::HTTP_OK);
    }


    /**
     * Helpers
     */

    public function jobsForPersonAsArray(Person $person, $options = array())
    {
        $em = $this->getDoctrine()->getManager();
        $ccjobs = $this->container->get('crewcall.jobs');

        $jobs = $em->getRepository('CrewCallBundle:Job')
            ->findJobsForPerson($person, $options);

        // Just walk throug it once, alas overlap check here aswell.
        $lastjob = null;
        $lastarr = null;
        $checked = new \Doctrine\Common\Collections\ArrayCollection();
        foreach ($jobs as $job) {
            $arr = [
                'name' => (string)$job,
                'id' => $job->getId(),
            ];
            $shiftarr = $this->getShiftArr($job->getShift());
            $arr = array_merge($arr, $shiftarr);

            if ($lastjob && $ccjobs->overlap($job->getShift(), $lastjob->getShift())) {
                $arr['overlap'] = true;
                $checked->last()['overlap'] = true;
            } else {
                $arr['overlap'] = false;
            }
            $checked->add($arr);
            $lastjob = $job;
        }
        return $checked->toArray();
    }

    public function opportunitiesForPersonAsArray(Person $person, $options = array())
    {
        $em = $this->getDoctrine()->getManager();
        $ccjobs = $this->container->get('crewcall.jobs');

        $opps = [];
        foreach ($ccjobs->opportunitiesForPerson($person, $options) as $o) {
            $arr = [
                'name' => (string)$o,
                'id' => $o->getId(),
            ];
            $opps[] = array_merge($arr, $this->getShiftArr($o));
        }
        return $opps;
    }

    public function getShiftArr(Shift $shift)
    {
        $sakonnin = $this->container->get('sakonnin.messages');
        // TODO: Eventcache

        // So, what do we need here? To be continued..
        if (!isset($this->shiftcache[$shift->getId()])) {
            $event = $shift->getEvent();
            $eventparent = $event->getParent();
            $location = $event->getLocation();
            $organization = $event->getOrganization();
            $confirm_notes = [];
            $checks = [];
            $scnc = [
                'system' => 'crewcall',
                'object_name' => 'shift',
                'message_types' => ['Note'],
                'external_id' => $shift->getId(),
            ];
            foreach ($sakonnin->getMessagesForContext($scnc) as $c) {
                $confirm_notes[] = [
                    'id' => $c->getId(),
                    'subject' => $c->getSubject(),
                    'confirm_required' => false,
                    'body' => $c->getBody()
                    ];
            }
            $sccc = [
                'system' => 'crewcall',
                'object_name' => 'shift',
                'message_types' => ['ConfirmCheck', 'InformCheck'],
                'external_id' => $shift->getId(),
            ];
            foreach ($sakonnin->getMessagesForContext($sccc) as $c) {
                $checks[] = [
                    'id' => $c->getId(),
                    'type' => (string)$c->getMessageType(),
                    'confirm_required' => (string)$c->getMessageType() == "ConfirmCheck" ? true : false,
                    'body' => $c->getBody()
                    ];
            }
            $eventarr = $this->getEventArr($event);
            if (count($eventarr['checks']) > 0) {
                $checks = array_merge($checks, $eventarr['checks']);
            }
            if (count($eventarr['confirm_notes']) > 0) {
                $confirm_notes = array_merge($confirm_notes, $eventarr['confirm_notes']);
            }
            unset($eventarr['checks']);
            unset($eventarr['confirm_notes']);

            $shiftarr = [
                'event' => $eventarr,
                'shift' => [
                    'name' => (string)$shift,
                    'id' => $shift->getId(),
                    'function' => (string)$shift->getFunction(),
                    'start_date' => $shift->getStart()->format("Y-m-d H:i"),
                    'start_string' => $shift->getStart()->format("d.m.y H:i"),
                    'end_date' => $shift->getEnd()->format("Y-m-d H:i"),
                    'end_string' => $shift->getEnd()->format("d M H:i"),
                ],
                'checks' => $checks,
                'confirm_notes' => $confirm_notes
            ];
            $this->shiftcache[$shift->getId()] = $shiftarr;
        }
        return $this->shiftcache[$shift->getId()];
    }

    public function getEventArr(Event $event)
    {
        $sakonnin = $this->container->get('sakonnin.messages');

        // So, what do we need here? To be continued..
        if (!isset($this->eventcache[$event->getId()])) {
            $eventparent = $event->getParent();
            $location = $event->getLocation();
            $organization = $event->getOrganization();
            $contacts = $event->getPersons('Contact');
            if (count($contacts) <1 && $eventparent)
                $contacts = $eventparent->getPersons('Contact');
            $confirm_notes = [];
            $checks = [];
            $all_events = [$event];
            if ($eventparent) {
                $all_events[] = $eventparent;
            }
            foreach ($all_events as $e) {
                $ecnc = [
                    'system' => 'crewcall',
                    'object_name' => 'event',
                    'message_types' => ['Note'],
                    'external_id' => $e->getId(),
                ];
                foreach ($sakonnin->getMessagesForContext($ecnc) as $c) {
                    $confirm_notes[] = [
                        'id' => $c->getId(),
                        'subject' => $c->getSubject(),
                        'confirm_required' => false,
                        'body' => $c->getBody()];
                }
                $eccc = [
                    'system' => 'crewcall',
                    'object_name' => 'event',
                    'message_types' => ['ConfirmCheck', 'InformCheck'],
                    'external_id' => $e->getId(),
                ];
                foreach ($sakonnin->getMessagesForContext($eccc) as $c) {
                    $checks[] = [
                        'id' => $c->getId(),
                        'type' => (string)$c->getMessageType(),
                        'confirm_required' => (string)$c->getMessageType() == "ConfirmCheck" ? true : false,
                        'body' => $c->getBody()
                        ];
                }
            }
            $eventarr = [
                'name' => (string)$event,
                'id' => $event->getId(),
                'description' => $event->getDescription(),
                'location' => [
                    'name' => $location->getName(),
                ],
                'organization' => [
                    'name' => $organization->getName(),
                ],
                'contacts' => [],
                'checks' => $checks,
                'confirm_notes' => $confirm_notes
            ];
            if ($address = $location->getAddress()) {
                $addressing = $this->container->get('crewcall.addressing');
                $eventarr['location']['address'] = $addressing->compose($address);
                $eventarr['location']['address_flat'] = $addressing->compose($address, 'flat');
            }
            foreach ($contacts as $contact) {
                $eventarr['contacts'][] = [
                    'name' => (string)$contact,
                    'mobile_phone_number' => $contact->getMobilePhoneNumber(),
                ];
            }
            $this->eventcache[$event->getId()] = $eventarr;
        }
        return $this->eventcache[$event->getId()];
    }
}
