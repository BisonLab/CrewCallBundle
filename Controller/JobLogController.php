<?php

namespace CrewCallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

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
     * @Route("/", name="joblog_index", methods={"GET"})
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
     * Creates one or more new JobLog entries.
     * If it's gotten and posted with a shift, add a JobLog with the same
     * in/out on all jobs in the shift.
     * And if it's a Job, only add to that.
     *
     * @Route("/new", name="joblog_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, $access)
    {
        $em = $this->getDoctrine()->getManager();
        $joblog = new JobLog();
        $form = $this->createForm('CrewCallBundle\Form\JobLogType', $joblog);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            // So much ado for csrf:
            if ($form->isValid()) {
                $data = $request->request->get($form->getName());
                // We have a loblog, but is it a cheat or not?
                // First, check cheat, aka "All in the shift".
                if ($shift_id = $data['shift'] ?? null) {
                    $shift = $em->getRepository('CrewCallBundle:Shift')
                        ->find($shift_id);
                    $in = new \DateTime($data['in']['date'] . " " . $data['in']['time']);
                    $out = new \DateTime($data['out']['date'] . " " . $data['out']['time']);
                    foreach($shift->getJobs() as $job) {
                        $joblog = new JobLog();
                        $joblog->setIn($in);
                        $joblog->setOut($out);
                        $joblog->setJob($job);
                        if ($overlap = $em->getRepository('CrewCallBundle:JobLog')->checkOverlapForPerson($joblog)) {
                            return new Response("Found existing work in the timeframe you entered. Shift is " . (string)current($overlap)->getJob()->getShift() . " and person is " . (string)$job->getPerson(), Response::HTTP_CONFLICT);
                        }
                        $em->persist($joblog);
                    }
                // And if not, this is just one persons in and out.
                } else {
                    // Check overlap.
                    if ($overlap = $em->getRepository('CrewCallBundle:JobLog')->checkOverlapForPerson($joblog)) {
                        return new Response("Found existing work in the timeframe you entered. Shift is " . (string)current($overlap)->getJob()->getShift(), Response::HTTP_CONFLICT);
                    }
                    $em->persist($joblog);
                }

                $em->flush();

                if ($this->isRest($access)) {
                    return new JsonResponse(array("status" => "OK"),
                        Response::HTTP_CREATED);
                } else { 
                    // Should I have non-rest at all?
                    return $this->redirectToRoute('job_show',
                        array('id' => $job->getId()));
                }
            } else {
                $errors = $this->handleFormErrors($form);
                return new JsonResponse(array("status" => "ERROR",
                    'errors' => $errors), 422);
            }
        }

        // Start anew. (Result of newAction doing both and not new and create)
        // TODO: Let the joblog_handler handle this aswell.
        $job = null;
        if ($job_id = $request->get('job')) {
            $job = $em->getRepository('CrewCallBundle:Job')->find($job_id);
        }
        $shift = null;
        if ($shift_id = $request->get('shift')) {
            $shift = $em->getRepository('CrewCallBundle:Shift')->find($shift_id);
        }

        $joblog = new JobLog();
        if ($job)
            $joblog->setJob($job);
        if (!$job && !$shift)
            return $this->returnNotFound($request, 'No job to tie the log to');

        if ($shift) {
            $joblog->setIn($shift->getStart());
            $joblog->setOut($shift->getEnd());
            $form = $this->createForm('CrewCallBundle\Form\JobLogType', $joblog);
            $form->remove('job');
            $form->add('shift', EntityType::class, [
                'class' => 'CrewCallBundle:Shift', 'data' => $shift]);
        } else {
            $joblog->setJob($job);
            $joblog->setIn($job->getShift()->getStart());
            $joblog->setOut($job->getShift()->getEnd());
            $form = $this->createForm('CrewCallBundle\Form\JobLogType', $joblog);
        }

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
     * @Route("/{id}/person", name="joblog_person", methods={"GET"})
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
                'person' => $person,
                'joblogs' => $logs['joblogs'],
                'summary' => $logs['summary'],
            ));
        }

        return $this->render('joblog/indexPerson.html.twig', array(
            'person' => $person,
            'joblogs' => $logs['joblogs'],
            'summary' => $logs['summary'],
        ));
    }

    /**
     * Displays a form to edit an existing shift entity.
     *
     * @Route("/{id}/edit", name="joblog_edit", defaults={"id" = 0}, methods={"GET", "POST"})
     */
    public function editAction(Request $request, JobLog $joblog, $access)
    {
        $editForm = $this->createForm('CrewCallBundle\Form\JobLogType', $joblog);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return new JsonResponse(array("status" => "OK"),
                Response::HTTP_OK);
        }

        if ($this->isRest($access)) {
            return $this->render('joblog/_edit.html.twig', array(
                'joblog' => $joblog,
                'edit_form' => $editForm->createView(),
            ));
        }

        return $this->render('joblog/edit.html.twig', array(
            'joblog' => $joblog,
            'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * Deletes a joblog entity.
     *
     * @Route("/{id}", name="joblog_delete", methods={"DELETE", "POST"})
     */
    public function deleteAction(Request $request, $access, JobLog $joblog)
    {
        // Bloody good question here, because CSRF.
        // This should add some sort of protection.
        if ($this->isRest($access)) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($joblog);
            $em->flush($joblog);
            return new JsonResponse(array("status" => "OK"),
                Response::HTTP_OK);
        }
    }
}
