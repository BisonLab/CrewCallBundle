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
     * Lists all person entities.
     *
     * @Route("/", name="person_index", methods={"GET"})
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $people = $em->getRepository('CrewCallBundle:Person')->findAll();

        $fe_repo = $em->getRepository('CrewCallBundle:FunctionEntity');
        $functions = $fe_repo->findAll(['name' => 'ASC']);
        return $this->render('person/index.html.twig', array(
            'people' => $people,
            'functions' => $functions,
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

        $fid = $request->get('function_id');
        if (!$functionEntity = $fe_repo->find($fid))
            return $this->returnNotFound($request, 'No function to filter');
        $people = $functionEntity->getPeople();
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
        $people = $em->getRepository('CrewCallBundle:Person')
            ->findByFunctionType($function_type);
        $functions = $em->getRepository('CrewCallBundle:FunctionEntity')
            ->findByFunctionType($function_type);
        return $this->render('person/index.html.twig', array(
            'people' => $people,
            'function_type' => ucfirst(strtolower($function_type)),
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
        $people = $em->getRepository('CrewCallBundle:Person')->findBy(array('state' => null));

        return $this->render('person/applicants.html.twig', array(
            'applicants' => $people));
    }

    /**
     * Creates a new person entity.
     *
     * @Route("/new", name="person_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $person = new Person();
        $addressing = $this->container->get('crewcall.addressing');
        $address_elements = $addressing->getFormElementList($person);
        $form = $this->createForm('CrewCallBundle\Form\PersonType', $person, ['address_elements' => $address_elements]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($person);
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
     * @Route("/{id}", name="person_show", methods={"GET"})
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
            $calendar->toFullCalendarArray($jobs, $this->getUser()),
            $calendar->toFullCalendarArray($states, $this->getUser())
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
     * Change password on a Person.
     *
     * @Route("/{id}/change_password", name="person_change_password")
     */
    public function changePasswordAction(Request $request, Person $person)
    {
        // Yup, disabled. Probably to be removed totally.
        return $this->redirectToRoute('person_show', array('id' => $person->getId()));
        
        $form = $this->createChangePasswordForm($person);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userManager = $this->get('fos_user.user_manager');
            $password = $form->getData()['plainpassword'];
            $person->setPlainPassword($password);
            $userManager->updateUser($person);
            return $this->redirectToRoute('person_show', array('id' => $person->getId()));
        } else {
            return $this->render('person/edit.html.twig', array(
                'person' => $person,
                'edit_form' => $form->createView(),
                'delete_form' => null,
        ));
        }
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
        $persons = $request->request->get('person_list');
        $message_type = $request->request->get('message_type');
        $sm->postMessage(array(
            'body' => $body,
            'to' => implode(",", $persons),
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
     * @Route("/{id}", name="person_jobs", methods={"GET"})
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
}
