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

        $jobs = array();
        if ($shiftfunction_id = $request->get('shiftfunction')) {
            $em = $this->getDoctrine()->getManager();
            if ($shiftfunction = $em->getRepository('CrewCallBundle:ShiftFunction')->find($shiftfunction_id)) {
                $jobs = $shiftfunction->getJobs();
            }
        } else {
            $jobs = $em->getRepository('CrewCallBundle:Job')->findAll();
        }
        if ($this->isRest($access)) {
            return $this->render('job/_index.html.twig', array(
                'jobs' => $jobs
            ));
        }

        return $this->render('job/index.html.twig', array(
            'jobs' => $jobs,
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
            return $this->redirectToRoute('shiftfunction_show', array('id' => $job->getShiftFunction()->getId()));
        }
    }
}
