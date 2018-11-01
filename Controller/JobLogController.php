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
use CrewCallBundle\Entity\Person;
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
                // TODO: Let the joblog_handler handle this.
                $em->persist($joblog);
                $em->flush($joblog);

                if ($this->isRest($access)) {
                    return new JsonResponse(array("status" => "OK"),
                        Response::HTTP_CREATED);
                } else { 
                    return $this->redirectToRoute('job_show',
                        array('id' => $job->getId()));
                }
            } else {
                $errors = $this->handleFormErrors($form);
                return new JsonResponse(array("status" => "ERROR",
                    'errors' => $errors), 422);
            }
        }

        // Start anew.
        // TODO: Let the joblog_handler handle this aswell.
        $job = null;
        if ($job_id = $request->get('job')) {
            $job = $em->getRepository('CrewCallBundle:Job')->find($job_id);
        }
        if (!$job)
            return $this->returnNotFound($request, 'No job to tie the log to');

        $joblog->setJob($job);
        $joblog->setIn($job->getShift()->getStart());
        $joblog->setOut($job->getShift()->getEnd());
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

    /**
     * The time log per person.
     *
     * @Route("/{id}/person", name="joblog_person")
     * @Method("GET")
     */
    public function indexPersonAction(Request $request, $access, Person $person)
    {
        $handler = $this->get('crewcall.joblogs');
        $job = null;
        $options['summary_only'] = $request->get('summary_only');
        $options['from_date'] = $request->get('from_date');
        $options['to_date'] = $request->get('to_date');

        $logs = $handler->getJobLogsForPerson($person, $options);

        if ($this->isRest($access)) {
            return $this->render('joblog/_indexPerson.html.twig', array(
                'joblogs' => $logs['joblogs'],
                'summary' => $logs['summary'],
            ));
        }

        return $this->render('joblog/indexPerson.html.twig', array(
            'joblogs' => $logs['joblogs'],
            'summary' => $logs['summary'],
        ));
    }
}
