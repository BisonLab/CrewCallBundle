<?php

namespace CrewCallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;

use CrewCallBundle\Entity\Person;
use CrewCallBundle\Entity\PersonState;
use CrewCallBundle\Entity\PersonFunction;
use CrewCallBundle\Entity\PersonFunctionOrganization;
use CrewCallBundle\Entity\FunctionEntity;
use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 * Person controller.
 *
 * @Route("/admin/{access}/person", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class PersonController extends CommonController
{
    /**
     * Lists absolutely all person entities.
     *
     * @Route("/", name="person_index", methods={"GET"})
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $people = $em->getRepository('CrewCallBundle:Person')->findAll();
        $fe_repo = $em->getRepository('CrewCallBundle:FunctionEntity');
        $job_repo = $em->getRepository('CrewCallBundle:Job');

        $fid = $request->get('function_id');
        $ftype = $request->get('function_type');
        $select_grouping = $request->get('select_grouping');
        if (!$functionEntity = $fe_repo->find($fid))
            return $this->returnNotFound($request, 'No function to filter');

        // Looks stupid, and is. Should definately be a repo action with
        // filters.
        if ($select_grouping == 'all') {
            $people = $functionEntity->getPeople(false);
        } else {
            $people = [];
            // I am already going through them all here,
            foreach ($functionEntity->getPeople(false) as $p) {

                if (!in_array($p->getState(),
                        ExternalEntityConfig::getActiveStatesFor('Person')))
                    continue;

                if ($on_date = $request->get('on_date')) {
                    $booked = $select_grouping == 'booked' || $select_grouping == 'available';
                    $got_jobs = $job_repo->findJobsForPerson($p, [
                            'booked' => $booked,
                            'from' => $on_date,
                            'to' => $on_date,
                            ]);
                    if (count($got_jobs) == 0 && $select_grouping == 'available') {
                            $people[] = $p;
                            continue;
                    } elseif (count($got_jobs) > 0) {
                        $people[] = $p;
                        continue;
                    }
                }
            }
        }

        $functions = $fe_repo->findBy(['function_type'
            => $functionEntity->getFunctionType()], ['name' => 'ASC']);
        return $this->render('person/index.html.twig', array(
            'people' => $people,
            'functions' => $functions,
            'functionEntity' => $functionEntity,
        ));
    }

    /**
     * Lists all person entities with a function
     *
     * @Route("/function", name="person_function", methods={"GET"})
     */
    public function listByFunctionAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $fe_repo = $em->getRepository('CrewCallBundle:FunctionEntity');
        $job_repo = $em->getRepository('CrewCallBundle:Job');

        $fid = $request->get('function_id');
        $select_grouping = $request->get('select_grouping');
        if (!$functionEntity = $fe_repo->find($fid))
            return $this->returnNotFound($request, 'No function to filter');

        // Looks stupid, and is. Should definately be a repo action with
        // filters.
        if ($select_grouping == 'all') {
            $people = $functionEntity->getPeople(false);
        } else {
            $people = [];
            // I am already going through them all here,
            foreach ($functionEntity->getPeople(false) as $p) {

                if (!in_array($p->getState(),
                        ExternalEntityConfig::getActiveStatesFor('Person')))
                    continue;

                if ($on_date = $request->get('on_date')) {
                    $booked = $select_grouping == 'booked' || $select_grouping == 'available';
                    $got_jobs = $job_repo->findJobsForPerson($p, [
                            'booked' => $booked,
                            'from' => $on_date,
                            'to' => $on_date,
                            ]);
                    if (count($got_jobs) == 0 && $select_grouping == 'available') {
                            $people[] = $p;
                            continue;
                    } elseif (count($got_jobs) > 0) {
                        $people[] = $p;
                        continue;
                    }
                }
            }
        }

        $functions = $fe_repo->findBy(['function_type'
            => $functionEntity->getFunctionType()], ['name' => 'ASC']);
        return $this->render('person/index.html.twig', array(
            'people' => $people,
            'functions' => $functions,
            'functionEntity' => $functionEntity,
        ));
    }

    /**
     * Lists all person entities with a function_type
     *
     * @Route("/{function_type}/function_type", name="person_function_type", methods={"GET"})
     */
    public function listByFunctionTypeAction(Request $request, $function_type)
    {
        $em = $this->getDoctrine()->getManager();
        $job_repo = $em->getRepository('CrewCallBundle:Job');

        // Ok, implementing this here aswell.
        $people = [];
        if ($fid = $request->get('function_id')) {
            $fe_repo = $em->getRepository('CrewCallBundle:FunctionEntity');
            if (!$functionEntity = $fe_repo->find($fid))
                return $this->returnNotFound($request, 'No function to filter');
            $people = $functionEntity->getPeople(false);
        } else {
            $people = $em->getRepository('CrewCallBundle:Person')
                ->findByFunctionType($function_type);
        }

        $select_grouping = $request->get('select_grouping');

        // Pure laziness I presume, but there are not that many people and
        // since I collect them in different ways above here, it's simple.
        // (OK, lazy)

        if ($select_grouping != 'all') {
            $parr = [];
            // I am already going through them all here, 
            foreach ($people as $p) {
                if (!in_array($p->getState(),
                        ExternalEntityConfig::getActiveStatesFor('Person')))
                    continue;

                if ($on_date = $request->get('on_date')) {
                    $booked = $select_grouping == 'booked' || $select_grouping == 'available';;
                     
                    $got_jobs = $job_repo->findJobsForPerson($p, [
                            'booked' => $booked,
                            'from' => $on_date,
                            'to' => $on_date,
                            ]);
                    if (count($got_jobs) == 0 && $select_grouping == 'available') {
                            $parr[] = $p;
                            continue;
                    } elseif (count($got_jobs) > 0) {
                        $parr[] = $p;
                        continue;
                    }
                } else {
                    $parr[] = $p;
                }
            }
            $people = $parr;
        }

        $ftypes = ExternalEntityConfig::getTypesFor('FunctionEntity', 'FunctionType');
        $function_type_plural = $ftypes[$function_type]['plural'];
        $function_type_label = $ftypes[$function_type]['label'];
        $functions = $em->getRepository('CrewCallBundle:FunctionEntity')
            ->findByFunctionType($function_type);
        return $this->render('person/index.html.twig', array(
            'people' => $people,
            'function_type' => $function_type,
            'function_type_plural' => $function_type_plural,
            'function_type_label' => $function_type_label,
            'all' => $request->get('all') ?? null,
            'functionEntity' => $functionEntity ?? null,
            'functions' => $functions,
        ));
    }

    /**
     * Lists all persons without a state, aka newly registered and ready to be
     * accepted or denied.
     *
     * @Route("/applicants", name="person_applicants", methods={"GET"})
     */
    public function listApplicantsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $people = $em->getRepository('CrewCallBundle:Person')->findByState('APPLICANT');

        return $this->render('person/applicants.html.twig', array(
            'applicants' => $people));
    }

    /**
     * Creates a new person entity.
     * This is only used when you add a crewmember. People with roles
     * will be created on the Organization or Location controller.
     *
     * @Route("/new_crewmember", name="person_new_crewmember", methods={"GET", "POST"})
     */
    public function newCrewmemberAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $person = new Person();
        $person->addRole('ROLE_USER');
        $addressing = $this->container->get('crewcall.addressing');
        $address_elements = $addressing->getFormElementList($person);
        $internal_organization_config = $this->container->getParameter('internal_organization');
        $first_org = $em->getRepository('CrewCallBundle:Organization')->findOneBy(array('name' => $internal_organization_config['name']));
        $first_role = $em->getRepository('CrewCallBundle:FunctionEntity')->findOneBy(array('name' => $internal_organization_config['default_role']));

        $form = $this->createForm('CrewCallBundle\Form\NewPersonType',
            $person, [
               'address_elements' => $address_elements,
               'organization' => $first_org,
               'role' => $first_role,
               'internal_organization_config' => $internal_organization_config,
            ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $form_data = $form->getData();
            $pf = new PersonFunction();
            $pf->setPerson($person);
            $pf->setFunction($form->get('function')->getData());

            $pfo = new PersonFunctionOrganization();
            if ($internal_organization_config['allow_external_crew']) {
                $pfo->setPerson($person);
                $pfo->setOrganization($form->get('organization')->getData());
                $pfo->setFunction($form->get('role')->getData());
            } else {
                $pfo->setPerson($person);
                $pfo->setOrganization($first_org);
                $pfo->setFunction($first_role);
            }
            $em->persist($person);
            $em->persist($pf);
            $em->persist($pfo);
            $em->flush($person);

            return $this->redirectToRoute('person_show', array('id' => $person->getId()));
        }

        return $this->render('person/new.html.twig', array(
            'person' => $person,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a person entity.
     *
     * @Route("/{id}/show", name="person_show", methods={"GET"})
     */
    public function showAction(Person $person)
    {
        $deleteForm = $this->createDeleteForm($person);
        $stateForm = $this->createStateForm($person);

        return $this->render('person/show.html.twig', array(
            'person' => $person,
            'delete_form' => $deleteForm->createView(),
            'state_form' => $stateForm->createView(),
        ));
    }

    /**
     * Calendar for person
     *
     * @Route("/{id}/calendar", name="person_calendar", methods={"POST"})
     */
    public function personCalendarAction(Request $request, $access, Person $person)
    {
        $calendar = $this->container->get('crewcall.calendar');
        $jobservice = $this->container->get('crewcall.jobs');

        // Gotta get the time scope.
        $from = $request->get('start');
        $to = $request->get('end');
        $jobs = $jobservice->jobsForPerson($person,
            array('all' => true, 'from' => $from, 'to' => $to));
        $states = $person->getStates();
        
        $calitems = array_merge(
            $calendar->toFullCalendarArray($jobs, $person),
            $calendar->toFullCalendarArray($states, $person)
        );
        // Not liked by OWASP since we just return an array.
        return new JsonResponse($calitems, Response::HTTP_OK);
    }

    /**
     * Displays a form to edit an existing person entity.
     *
     * @Route("/{id}/edit", name="person_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, Person $person)
    {
        $deleteForm = $this->createDeleteForm($person);
        $addressing = $this->container->get('crewcall.addressing');
        $address_elements = $addressing->getFormElementList($person);
        $editForm = $this->createForm('CrewCallBundle\Form\PersonType', $person, ['address_elements' => $address_elements]);
        $editForm->remove('plainPassword');
        // $addressing->addToForm($editForm, $person);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('person_show', array('id' => $person->getId()));
        }

        return $this->render('person/edit.html.twig', array(
            'person' => $person,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a person entity.
     *
     * @Route("/{id}", name="person_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, Person $person)
    {
        $form = $this->createDeleteForm($person);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($person);
            $em->flush($person);
        }

        return $this->redirectToRoute('person_index');
    }

    /**
     * Sends messages to a batch of persons.
     *
     * @Route("/persons_send_message", name="persons_send_message", methods={"POST"})
     */
    public function personsSendMessageAction(Request $request)
    {
        $sm = $this->get('sakonnin.messages');
        $body = $request->request->get('body');
        $subject = $request->request->get('subject') ?? "Message from CrewCall";
        $persons = $request->request->get('person_list');
        $message_type = $request->request->get('message_type');
        $sm->postMessage(array(
            'subject' => $subject,
            'body' => $body,
            'to' => implode(",", $persons),
            'from' => $this->getParameter('system_emails_address'),
            'message_type' => $message_type,
            'to_type' => "INTERNAL",
            'from_type' => "INTERNAL",
        ));
        return new Response("Sent: " . $body, Response::HTTP_OK);
    }

    /**
     * Finds and displays the gedmo loggable history
     *
     * @Route("/{id}/log", name="person_log")
     */
    public function showLogAction(Request $request, $access, $id)
    {
        return  $this->showLogPage($request,$access, "CrewCallBundle:Person", $id);
    }

    /**
     * Finds and returns the jobs for a person.
     *
     * @Route("/{id}/jobs", name="person_jobs", methods={"GET"})
     */
    public function showJobsAction(Request $request, $access, Person $person)
    {
        $options = [];
        // I'll default today +2 days. Add options at will and need.
        $options['from'] = new \DateTime();
        $options['to'] = new \DateTime('+2days');
        $summary = [];
        foreach($this->get('crewcall.jobs')->jobsForPerson(
            $person, $options) as $job) {
                $summary[] = [(string)$job, $job->getStart()->format("d M H:i"), $job->getEnd()->format("d M H:i"), $job->getState()];
        }
        
        if ($this->isRest($access)) {
            return $this->returnRestData($request, $summary,
                array('html' => 'CrewCallBundle::summaryPopContent.html.twig'));
        }
    }

    /**
     * Creates a form to delete a person entity.
     *
     * @param Person $person The person entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Person $person)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('person_delete', array('id' => $person->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Creates a form to edit a password.
     *
     * @param Person $person The person entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createChangePasswordForm(Person $person)
    {
        return $this->createFormBuilder()
            ->add('plainpassword')
            ->setAction($this->generateUrl('person_change_password', array('id' => $person->getId())))
            ->setMethod('POST')
            ->getForm()
        ;
    }

    /**
     * Creates a form to delete a person entity.
     *
     * @param Person $person The person entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createStateForm(Person $person)
    {
        $stateform = $this->createFormBuilder()
            ->add('from_date', DateType::class, array(
                'label' => "From",
                'format' => 'yyyy-MM-dd',
                'widget' => "single_text"))
            ->add('to_date', DateType::class, array(
                'required' => false,
                'format' => 'yyyy-MM-dd',
                'label' => "To",
                'widget' => "single_text"))
            ->add('state', ChoiceType::class, array(
                'choices' => ExternalEntityConfig::getStatesAsChoicesFor('Person')))
            ->add('submit', SubmitType::class)
            ->setAction($this->generateUrl('person_state',
                array('id' => $person->getId())))
            ->setMethod('POST')
            ->getForm();
//        $stateform->find('person_id')->setData($person->getId());

        return $stateform;
    }

    /**
     * Set state on a person.
     *
     * @Route("/{id}/state", name="person_state", methods={"POST"})
     */
    public function stateAction(Request $request, Person $person)
    {
        // Security? This is the admin area, they can mess it all up anyway.
        // If form:
        $form = $this->createStateForm($person);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $form_data = $form->getData();
            $person->setState($form_data['state'], array(
                'from_date' => $form_data['from_date'] ?: null,
                'to_date' => $form_data['to_date'] ?: null,
                ));
            $em->flush();
            return $this->redirectToRoute('person_show',
                array('id' => $person->getId()));
        }
        // Hopefully from the applicants page: (Or REST)
        if (!$state = $request->request->get('state'))
            return $this->redirectToRoute('person_show',
                array('id' => $person->getId()));
        $options = array();
        if ($from_date = $request->request->get('from_date'))
            $options['from_date'] = $from_date;
        if ($to_date = $request->request->get('to_date'))
            $options['to_date'] = $to_date;
        $person->setState($state, $options);
        $this->getDoctrine()->getManager()->flush();
        $applicant = $request->request->get('applicant');
        if ($applicant)
            return $this->redirectToRoute('person_applicants');
        else
            return $this->redirectToRoute('person_show',
                array('id' => $person->getId()));
    }

    /**
     * @param UserInterface $user
     * @Route("/{id}/reset_password", name="person_reset_password")
     */
    public function resetPasswordAction(Person $person)
    {
        if (null === $person->getConfirmationToken()) {
            $tokenGenerator = $this->get('fos_user.util.token_generator');
            $person->setConfirmationToken($tokenGenerator->generateToken());
        }

        // send email you requested
        $mailer = $this->get('fos_user.mailer');
        $mailer->sendResettingEmailMessage($person);

        // this depends on requirements
        $person->setPasswordRequestedAt(new \DateTime());
        $userManager = $this->get('fos_user.user_manager');
        $userManager->updateUser($person);
        return $this->redirectToRoute('person_show', array('id' => $person->getId()));
    }

    /**
     * @Route("/search", name="person_search", methods={"GET"})
     */
    public function searchPersonAction(Request $request, $access)
    {
        if (!$term = $request->query->get("term"))
            $term = $request->query->get("username");

        // Gotta be able to handle two-letter usernames.
        if (strlen($term) > 1) {
            $userManager = $this->container->get('fos_user.user_manager');
            /* No searching for users in the manager. */
            // $users = $userManager->findUserByUsername($term);
            $class = $userManager->getClass();
            $em = $this->getDoctrine()->getManagerForClass($class);
            $repo = $em->getRepository($class);
            $result = array();
            $q = $repo->createQueryBuilder('u')
                ->where('lower(u.usernameCanonical) LIKE :term')
                ->orWhere('lower(u.emailCanonical) LIKE :term')
                ->setParameter('term', strtolower($term) . '%');
            if (property_exists($class, 'full_name')) {
                $q->orWhere('lower(u.full_name) LIKE :full_name')
                ->setParameter('full_name', '%' . strtolower($term) . '%');
            }
            if (property_exists($class, 'mobile_phone_number')) {
                $q->orWhere('lower(u.mobile_phone_number) LIKE :mobile_phone_number')
                ->setParameter('mobile_phone_number', '%' . strtolower($term) . '%');
            }
            if (property_exists($class, 'phone_number')) {
                $q->orWhere('lower(u.phone_number) LIKE :phone_number')
                ->setParameter('phone_number', '%' . strtolower($term) . '%');
            }
            if (property_exists($class, 'phone_number')) {
                $q->orWhere('lower(u.phone_number) LIKE :phone_number')
                ->setParameter('phone_number', '%' . strtolower($term) . '%');
            }

            $people = [];
            if ($users = $q->getQuery()->getResult()) {
                foreach ($users as $user) {
                    // Here comes the difference from commonbundle:
                    // Filtering here, since I already go through them.
                    if ($request->query->get("enabled")) {
                        if (!$user->getEnabled())
                            continue;
                    }

                    // TODO: Add full name.
                    $res = array(
                        'userid' => $user->getId(),
                        'value' => $user->getUserName(),
                        'email' => $user->getEmail(),
                        'label' => $user->getUserName(),
                        'username' => $user->getUserName(),
                    );
                    // Override if full name exists.
                    if (property_exists($user, 'full_name') 
                            && $user->getFullName()) {
                        $res['label'] = $user->getFullName();
                        $res['value'] = $user->getFullName();
                    }
                    if ($request->get("value_with_all")) {
                        $res['value'] = $res['value'] . " - " . $user->getEmail();
                        $res['label'] = $res['label'] . " - " . $user->getEmail();
                        $res['value'] = $res['value'] . " - " . $user->getMobilePhoneNumber();
                        $res['label'] = $res['label'] . " - " . $user->getMobilePhoneNumber();
                    }
                    $people[] = $res;
                }        
            }
        } else {
            $result = "Too little information provided for a viable search";
        }

        if ($this->isRest($access)) {
            // Format for autocomplete.
            return $this->returnRestData($request, $people);
        }

        $people = array(
            'entities' => $people,
        );
        return $this->render('BisonLabCommonBundle:User:index.html.twig',
            $params);
    }
}
