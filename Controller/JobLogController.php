<?php

namespace CrewCallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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
 * @Route("/admin/{access}/joblog", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class JobLogController extends CommonController
{
    /**
     * a Time Sheet.
     *
     * @Route("/", name="joblog_index")
     * @Method("GET")
     */
    public function indexAction(Request $request, $access)
    {
        $em = $this->getDoctrine()->getManager();
        $job = null;
        if ($job_id = $request->get('job')) {
            $job = $em->getRepository('CrewCallBundle:Job')->find($job_id);
        }
        if (!$job)
            return $this->returnNotFound($request, 'No job to tie the log to');

        $joblogs = $job->getJobLogs();

        if ($this->isRest($access)) {
            return $this->render('joblog/_index.html.twig', array(
                'job' => $job,
                'joblogs' => $joblogs,
            ));
        }

        return $this->render('joblog/index.html.twig', array(
            'job' => $job,
            'joblogs' => $joblogs,
        ));
    }

    /**
     * Creates a new JobLog entry
     *
     * @Route("/new", name="joblog_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, $access)
    {
        $joblog = new JobLog();
        $form = $this->createForm('CrewCallBundle\Form\JobLogType', $joblog);
        $form->handleRequest($request);
        $em = $this->getDoctrine()->getManager();

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($joblog);
                $em->flush($joblog);

                if ($this->isRest($access)) {
                    return new JsonResponse(array("status" => "OK"), Response::HTTP_CREATED);
                } else { 
                    return $this->redirectToRoute('job_show', array('id' => $job->getId()));
                }
            } else {
                $errors = $this->handleFormErrors($form);
                return new JsonResponse(array("status" => "ERROR",
                    'errors' => $errors), 422);
            }
        }

        // Start anew.
        $job = null;
        if ($job_id = $request->get('job')) {
            $job = $em->getRepository('CrewCallBundle:Job')->find($job_id);
        }
        if (!$job)
            return $this->returnNotFound($request, 'No job to tie the log to');

        $joblog->setJob($job);
        $joblog->setIn($job->getShift()->getStart());
        $joblog->setOut($job->getShift()->getEnd());
error_log("Out" . print_r($job->getShift()->getEnd(), true));
error_log("Out" . print_r($joblog->getOut(), true));
        $form = $this->createForm('CrewCallBundle\Form\JobLogType', $joblog);

        if ($this->isRest($access)) {
            return $this->render('joblog/_new.html.twig', array(
                'form' => $form->createView(),
            ));
        }
        return $this->render('joblog/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
