<?php

namespace CrewCallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;

use CrewCallBundle\Entity\Job;
use CrewCallBundle\Entity\JobLog;

/**
 * Job controller.
 *
 * @Route("/admin/{access}/job", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class JobController extends CommonController
{
    /**
     * Lists all job entities.
     *
     * @Route("/", name="job_index", methods={"GET"})
     */
    public function indexAction(Request $request, $access)
    {
        $em = $this->getDoctrine()->getManager();

        $shift = null;
        if ($shift_id = $request->get('shift')) {
            $em = $this->getDoctrine()->getManager();
            $shift = $em->getRepository('CrewCallBundle:Shift')->find($shift_id);
        }
        /*
         * If you ask yourself why this is not set as a route option you are
         * into something. Reason is that there might be more than shifts for
         * filtering this.
         */
        if (!$shift)
            return $this->returnNotFound($request, 'No shift to tie the jobs to');

        $jobs = $shift->getJobs();
        $sos = $shift->getShiftOrganizations();
        if ($this->isRest($access)) {
            return $this->render('job/_index.html.twig', array(
                'shift' => $shift,
                'jobs'  => $jobs,
                'sos'   => $sos
            ));
        }

        return $this->render('job/index.html.twig', array(
            'jobs' => $jobs,
            'sos'  => $sos
        ));
    }

    /**
     *
     * @Route("/{id}/state/{state}", name="job_state", methods={"GET", "POST"})
     */
    public function stateAction(Request $request, Job $job, $state, $access)
    {
        $job->setState($state);
        
        $em = $this->getDoctrine()->getManager();
        if ($job->isBooked() && $overlap = $em->getRepository('CrewCallBundle:Job')->checkOverlapForPerson($job, array('booked' => true))) {
            return new Response("Can not set job to a booked state because it will overlap with " . (string)current($overlap)->getShift(), Response::HTTP_CONFLICT);
        }

        $em->persist($job);
        $em->flush($job);

        if ($this->isRest($access)) {
            return new JsonResponse(array("status" => "OK"), Response::HTTP_CREATED);
        } else { 
            return $this->redirectToRoute('shift_show', array('id' => $job->getShift()->getId()));
        }
    }

    /**
     *
     * @Route("/states", name="jobs_state", methods={"POST"})
     */
    public function stateOnJobsAction(Request $request)
    {
        $jobs = $request->get('jobs');
        $state = $request->get('state');

        $em = $this->getDoctrine()->getManager();
        $jobrepo = $em->getRepository('CrewCallBundle:Job');
        foreach ($jobs as $job_id) {
            if (!$job = $jobrepo->find($job_id))
                return new JsonResponse(array("status" => "NOT FOUND"), Response::HTTP_NOT_FOUND);
            $job->setState($state);
            if ($job->isBooked() && $overlap = $jobrepo->checkOverlapForPerson($job, array('booked' => true))) {
            return new Response("Can not set job to a booked state because it will overlap with " . (string)current($overlap)->getShift(), Response::HTTP_CONFLICT);
            }
        }
        $em->flush();

        return new JsonResponse(array("status" => "OK"), Response::HTTP_OK);
    }

    /**
     * Creates a new Job
     *
     * @Route("/new", name="job_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, $access)
    {
        $job = new Job();
        $form = $this->createForm('CrewCallBundle\Form\JobType', $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($job);
            $em->flush($job);

            if ($this->isRest($access)) {
                return new JsonResponse(array("status" => "OK"), Response::HTTP_CREATED);
            } else { 
                return $this->redirectToRoute('job_show', array('id' => $job->getId()));
            }
        }

        // If this has a shift set here, it's not an invalid create attempt.
        if ($shift_id = $request->get('shift')) {
            $em = $this->getDoctrine()->getManager();
            if ($shift = $em->getRepository('CrewCallBundle:Shift')->find($shift_id)) {
                $job->setShift($shift);
                $form->setData($job);
            }
        }
        if ($this->isRest($access)) {
            return $this->render('job/_new.html.twig', array(
                'job' => $job,
                'form' => $form->createView(),
            ));
        }
        return $this->render('job/new.html.twig', array(
            'job' => $job,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays the gedmo loggable history
     *
     * @Route("/{id}/log", name="job_log")
     */
    public function showLogAction(Request $request, $access, $id)
    {
        return  $this->showLogPage($request,$access, "CrewCallBundle:Job", $id);
    }
}
