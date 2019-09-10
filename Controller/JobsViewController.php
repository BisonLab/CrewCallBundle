<?php

namespace CrewCallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

use BisonLab\CommonBundle\Controller\CommonController as CommonController;

use CrewCallBundle\Entity\Event;
use CrewCallBundle\Entity\Shift;
use CrewCallBundle\Entity\Job;
use CrewCallBundle\Entity\JobLog;

/**
 * The big bad controller for the big bad job view page.
 * It'll mainly be AJAX calls. Alas no access/ isRest.
 *
 * Yup, this will almost be a SPA all by itself.
 *
 * And if you look closely, you will see a sort of duplication of actions in
 * other controllers. I will try to keep that at a minumum, but adding the
 * functionality we need here to the other controllers may be complicating too
 * much.
 *
 * The other reason to do that is if this will be moved to the custom bundle
 * for tailoring this per application and needs of the admins.
 *
 * This one does not use forms, alas there are no automatic CSRF protection.
 * I don't like it, but will do it like this for now.
 *
 * 
 * @Route("/admin/jobsview")
 */
class JobsViewController extends CommonController
{
    /**
     * Start the whole shebang, give out the 
     *
     * @Route("/", name="jobsview_index", methods={"GET"})
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // Gotta give out some filters, hopefully static is OK.
        $eventrepo = $em->getRepository('CrewCallBundle:Event');
        $functionrepo = $em->getRepository('CrewCallBundle:FunctionEntity');
        // Future must include current.
        $future_events = $eventrepo->findEvents(['future' => true,
            'parents_only' => true]);
        $past_month_events = $eventrepo->findEvents(['past' => true,
            'from' => new \DateTime('first day of last month')]);
        $functions = $functionrepo->findByFunctionType('SKILL');
        $event_states = Event::getStatesList();
        $shift_states = Shift::getStatesList();
        $job_states   = Job::getStatesList();

        return $this->render('jobsview/index.html.twig', array(
            'future_events' => $future_events,
            'past_month_events' => $past_month_events,
            'functions' => $functions,
            'event_states' => $event_states,
            'shift_states' => $shift_states,
            'job_states' => $job_states,
        ));
    }

    /**
     * Then we need to serve jobs.
     *
     * @Route("/jobs", name="jobsview_jobs", methods={"GET"})
     */
    public function jobsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $jobrepo = $em->getRepository('CrewCallBundle:Job');
        $so_repo = $em->getRepository('CrewCallBundle:ShiftOrganization');

        $persons = new ArrayCollection();
        // In case we add jobs during the "filter extraction"
        $jobs = [];
        // The batch of jobs..
        $shiftorganizations = [];

        $from_date = $request->get('from_date');
        $to_date   = $request->get('to_date');

        // Dates? Almost always.
        $event_filters = [];
        $shift_filters = ['from' => $from_date, 'to' => $to_date];
        $job_filters   = ['from' => $from_date, 'to' => $to_date];

        // Do we have an event? Can only pick one.
        if (!$event_id = $request->get('cur_event'))
            $event_id = $request->get('past_event');

        if ($event_id) {
            $eventrepo = $em->getRepository('CrewCallBundle:Event');
            if (!$event = $eventrepo->find($event_id))
                throw new \InvalidArgumentException('Could not find the event');
            $events = $event->getAllChildren();
            $events[] = $event;
            $event_filters['events'] = $events;
            $job_filters['events'] = $events;
            // If you pick an event, I presume you do not care about dates.
            unset($job_filters['from']);
            unset($job_filters['to']);
        }

        // Looks stupid, but userid does not get cleared. I need to understand jQuery autocomplete.
        if ($request->get('username') && $userid = $request->get('userid') ) {
            $person = $this->container->get('fos_user.user_manager')
                ->findUserBy(array('id' => $userid));
            if (!$person)
                throw new \InvalidArgumentException('Could not find the person');
            $persons->add($person);
            $person_filters['persons'] = $persons;
            $job_filters['persons'] = $persons;
        }

        if ($function_id = $request->get('function') ) {
            $functionrepo = $em->getRepository('CrewCallBundle:FunctionEntity');
            if (!$function = $functionrepo->find($function_id))
                throw new \InvalidArgumentException('Could not find the function');
            $functions = $function->getAllChildren();
            $functions[] = $function;
            $function_filters['functions'] = $functions;
            $job_filters['functions'] = $functions;
        }

        // Any event criterias?
        $event_filters = [];
        if ($event_state = $request->get('event_state')) {
            $event_filters['states'] = [$event_state];
            $job_filters['event_states'] = [$event_state];
        }

        if ($shift_state = $request->get('shift_state')) {
            $shift_filters['states'] = [$shift_state];
            $job_filters['shift_states'] = [$shift_state];
        }

        if ($job_state = $request->get('job_state')) {
            $job_filters['states'] = [$job_state];
        }

        // Last, everything that can be handled by the QueryBuilder.
        if (empty($jobs)) {
            $jobs = $jobrepo->findJobs($job_filters);
        }
        // I wonder how much this costs instead of counting in the twig
        // template, but I'll do it anywa.
        $count_by_state = [];
        $count_by_function = [];
        // Must be able to count people from an organization. No need to show
        // unless there are any.
        $jobs_organization = false;
        foreach ($jobs as $j) {
            // Gawd I'm lazy. Must find out the idioms for this one.
            if (isset($count_by_state[$j->getState()]))
                $count_by_state[$j->getState()]++;
            else
                $count_by_state[$j->getState()] = 1;
            if (isset($count_by_function[$j->getFunction()->getName()]))
                $count_by_function[$j->getFunction()->getName()]++;
            else
                $count_by_function[$j->getFunction()->getName()] = 1;
        }

        if (empty($shiftorganizations)) {
            $shiftorganizations = $so_repo->findJobs($job_filters);
            foreach ($shiftorganizations as $so) {
                // Gawd I'm lazy. Must find out the idioms for this one.
                if (isset($count_by_state[$so->getState()]))
                    $count_by_state[$so->getState()] += $so->getAmount();
                else
                    $count_by_state[$so->getState()] = $so->getAmount();
                if (isset($count_by_function[$so->getShift()->getFunction()->getName()]))
                    $count_by_function[$so->getShift()->getFunction()->getName()] += $so->getAmount();
                else
                    $count_by_function[$so->getShift()->getFunction()->getName()] = $so->getAmount();
            }
        }

        return $this->render('jobsview/_index.html.twig', array(
            'from'  => $from_date,
            'to'  => $to_date,
            'jobs'  => $jobs,
            'job_states' => Job::getStatesList(),
            'shiftorganizations'  => $shiftorganizations,
            'count_by_state'  => $count_by_state,
            'count_by_function'  => $count_by_function,
        ));
    }

    /**
     *
     * @Route("/states/{state}", name="jobsview_state", methods={"POST"})
     */
    public function stateAction(Request $request, Job $job, $state, $access)
    {
        $job->setState($state);
        
        $em = $this->getDoctrine()->getManager();
        if ($job->isBooked() && $overlap = $em->getRepository('CrewCallBundle:Job')->checkOverlapForPerson($job, array('booked' => true))) {
            return new Response("Can not set job to a booked status because it will overlap with " . (string)current($overlap)->getShift(), Response::HTTP_CONFLICT);
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
     * Then we need to serve jobs.
     *
     * @Route("/jobs_joblog/{id}", name="jobsview_job_joblog", methods={"GET"})
     */
    public function jobsJobLogsAction(Request $request, Job $job)
    {
        return $this->render('jobsview/_joblogs.html.twig', ['job' => $job]);
    }
}
