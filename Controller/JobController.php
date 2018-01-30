<?php

namespace CrewCallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;

use CrewCallBundle\Entity\Job;

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
     * @Route("/", name="job_index")
     * @Method("GET")
     */
    public function indexAction(Request $request, $access)
    {
        $em = $this->getDoctrine()->getManager();

        $with_orgs = $request->get('with_orgs') ?: false;

        $jobs = array();
        $sos = array();
        if ($shift_id = $request->get('shift')) {
            $em = $this->getDoctrine()->getManager();
            if ($shift = $em->getRepository('CrewCallBundle:Shift')->find($shift_id)) {
                $jobs = $shift->getJobs();
                $sos = $shift->getShiftOrganizations();
            }
        } else {
            $jobs = $em->getRepository('CrewCallBundle:Job')->findAll();
        }
        if ($this->isRest($access)) {
            return $this->render('job/_index.html.twig', array(
                'jobs' => $jobs,
                'sos' => $sos
            ));
        }

        return $this->render('job/index.html.twig', array(
            'jobs' => $jobs,
            'sos' => $sos
        ));
    }

    /**
     *
     * @Route("/{id}/state/{state}", name="job_state")
     * @Method({"GET", "POST"})
     */
    public function stateAction(Request $request, Job $job, $state, $access)
    {
        $job->setState($state);
        $em = $this->getDoctrine()->getManager();
        $em->persist($job);
        $em->flush($job);

        if ($this->isRest($access)) {
            return new JsonResponse(array("status" => "OK"), Response::HTTP_CREATED);
        } else { 
            return $this->redirectToRoute('shift_show', array('id' => $job->getShift()->getId()));
        }
    }

    /**
     * Creates a new Job
     *
     * @Route("/new", name="job_new")
     * @Method({"GET", "POST"})
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
