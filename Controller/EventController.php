<?php

namespace CrewCallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use BisonLab\CommonBundle\Controller\CommonController as CommonController;
use CrewCallBundle\Entity\Event;
use CrewCallBundle\Entity\PersonRoleEvent;

/**
 * Event controller.
 *
 * @Route("/admin/{access}/event", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class EventController extends CommonController
{
    /**
     * Lists all event entities.
     *
     * @Route("/", name="event_index", methods={"GET"})
     */
    public function indexAction(Request $request, $access)
    {
        $em = $this->getDoctrine()->getManager();
        $eventrepo = $em->getRepository('CrewCallBundle:Event');

        $past = $upcoming = false;
        if ($request->get('past')) {
            $events = $eventrepo->findEvents(['past' => true,
                'parents_only' => true]);
            $past = true;
        } else {
            // Yeah, "future" vs "upcoming".
            $events = $eventrepo->findEvents(['future' => true,
                'parents_only' => true]);
            $upcoming = true;
        }

        if ($this->isRest($access)) {
            if ($access == "ajax")
                return $this->render('event/_index.html.twig', array(
                    'events'  => $events,
                    'past'    => $past,
                    'upcoming'=> $upcoming,
                ));
            else
                return $this->returnRestData($request, [
                    'events'  => $events,
                    'past'    => $past,
                    'upcoming'=> $upcoming,
                ]);
        }

        return $this->render('event/index.html.twig', array(
            'events'  => $events,
            'past'    => $past,
            'upcoming'=> $upcoming,
        ));
    }

    /**
     * Creates a new event entity.
     *
     * @Route("/new", name="event_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $event = new Event();
        if ($parent_id = $request->get('parent')) {
            $em = $this->getDoctrine()->getManager();
            if ($parent = $em->getRepository('CrewCallBundle:Event')->find($parent_id)) {
                $event->setParent($parent);
            }
        }

        $form = $this->createForm('CrewCallBundle\Form\EventType', $event);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($event);
                $em->flush($event);

                if ($event->getParent())
                    return $this->redirectToRoute('event_show', array('id' => $event->getParent()->getId()));
                else
                    return $this->redirectToRoute('event_show', array('id' => $event->getId()));
            }
            return $this->render('event/new.html.twig', array(
                'event' => $event,
                'form' => $form->createView(),
            ));
        }

        // If this has a parent set here, it's not an invalid create attempt.
        if ($parent = $event->getParent()) {
            $event->setStart($parent->getStart());
            $event->setEnd($parent->getEnd());
            $event->setOrganization($parent->getOrganization());
            $event->setLocation($parent->getLocation());
            // TODO: Consider setting manager, location and organization
            // aswell. But not before I've decided on wether I want to
            // inherit from the parent or not. And on which properties.
            // (Org and loc are included, for now at least.)
            $form->setData($event);
        } else {
            // Can't be in the past, not usually anyway.
            $event->setStart(new \DateTime());
            $form->setData($event);
        }
        return $this->render('event/new.html.twig', array(
            'event' => $event,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a event entity.
     *
     * @Route("/{id}/show", name="event_show", methods={"GET"})
     */
    public function showAction(Request $request, Event $event)
    {
        $em = $this->getDoctrine()->getManager();

        if ($request->get('printable')) {
            $mailForm = $this->createSendMailForm($event, $request->get('state'));
            return $this->render('event/printable.html.twig', array(
                'event' => $event,
                'all' => true,
                'mailform' => $mailForm->createView(),
                'state' => $request->get('state'),
            ));
        }

        $rolerepo = $em->getRepository('CrewCallBundle:Role');
        $pre = new PersonRoleEvent();
        $pre->setEvent($event);
        if ($contact = $rolerepo->findOneByName('Contact'))
            $pre->setRole($contact);
        // Gotta find all available people
        $people = $event->getOrganization()->getPeople();
        foreach ($event->getLocation()->getPeople() as $p) {
            if (!$people->contains($p))
                $people->add($p);
        }

        $add_contact_form = null;
        if (count($people) > 0) {
            $add_contact_form = $this->createForm('CrewCallBundle\Form\PersonEventType', $pre, ['people' => $people])->createView();
        }

        $deleteForm  = $this->createDeleteForm($event);
        $stateForm = $this->createStateForm($event);
        return $this->render('event/show.html.twig', array(
            'event' => $event,
            'last_shift' => !empty($event->getShifts()) ? $event->getShifts()->last() : false,
            'delete_form' => $deleteForm->createView(),
            'add_contact_form' => $add_contact_form,
            'state_form' => $stateForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing event entity.
     *
     * @Route("/{id}/edit", name="event_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, Event $event)
    {
        $editForm = $this->createForm('CrewCallBundle\Form\EventType', $event);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            if ($event->getParent())
                return $this->redirectToRoute('event_show', array('id' => $event->getParent()->getId()));
            else
                return $this->redirectToRoute('event_show', array('id' => $event->getId()));
        }

        return $this->render('event/edit.html.twig', array(
            'event' => $event,
            'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * Deletes a event entity.
     *
     * @Route("/{id}/delete", name="event_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, Event $event, $access)
    {
        $form = $this->createDeleteForm($event);
        $form->handleRequest($request);
        $parent = $event->getParent();

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($event);
            $em->flush($event);
        }

        if ($this->isRest($access)) {
            return new Response("Deleted", Response::HTTP_OK);
        }

        if ($parent instanceOf Event) {
            return $this->redirectToRoute('event_show',
                array('id' => $parent->getId()));
        }

        return $this->redirectToRoute('event_index');
    }

    /**
     * Sets "CONFIRMED" on the event and all shifts underneith.
     *
     * @Route("/{id}/state/{state}", name="event_state", methods={"POST"})
     */
    public function stateAction(Request $request, Event $event, $state, $access)
    {
        $form = $this->createStateForm($event);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $event->setState($state);
            $em = $this->getDoctrine()->getManager();
            $em->flush();
        }
        if ($this->isRest($access)) {
            return new Response("OK", Response::HTTP_OK);
        }
        return $this->redirectToRoute('event_show', array('id' => $event->getId()));
    }

    /**
     * Clone or copy? It takes an event and creates a new set of subevents and
     * shifts based on a new start date.
     *
     * @Route("/{event}/clone", name="event_clone", methods={"GET", "POST"})
     */
    public function cloneAction(Request $request, Event $event)
    {
        $clone = new Event();
        $form = $this->createForm('CrewCallBundle\Form\EventType', $clone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $events = $this->container->get('crewcall.events');

            $em->getConnection()->beginTransaction();
            try {
                $clone = $events->cloneEvent($event, $clone);
                $em->persist($clone);
                $em->flush($clone);
                $em->getConnection()->commit();
            } catch (\Exception $e) {
                error_log("cloneAction ERROR:" . $e->getMessage());
                $em->getConnection()->rollback();
                $em->close();
                throw $e;
            }
            return $this->redirectToRoute('event_show', array('id' => $clone->getId()));
        }

        $clone->setParent($event->getParent());
        $clone->setStart($event->getStart());
        // This will be set automagically, gotta  make sure it's in the future
        // and hidden in the form view.
        $clone->setEnd(new \DateTime("3000-01-01"));
        $clone->setOrganization($event->getOrganization());
        $clone->setLocation($event->getLocation());
        // TODO: Consider setting manager, location and organization
        // aswell. But not before I've decided on wether I want to
        // inherit from the parent or not. And on which properties.
        // (Org and loc are included, for now at least.)
        $form->setData($clone);
        return $this->render('event/new.html.twig', array(
            'event' => $clone,
            'clone' => $event,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays the gedmo loggable history
     *
     * @Route("/{id}/log", name="event_log")
     */
    public function showLogAction(Request $request, $access, $id)
    {
        return  $this->showLogPage($request,$access, "CrewCallBundle:Event", $id);
    }

    /**
     * Calendar for event
     *
     * @Route("/calendar", name="event_calendar", methods={"GET", "POST"})
     */
    public function eventCalendarAction(Request $request, $access)
    {
        if ($this->isRest($access)) {
            $calendar = $this->container->get('crewcall.calendar');
            $jobservice = $this->container->get('crewcall.jobs');

            // Gotta get the time scope.
            $from = new \Datetime($request->get('start'));
            $to = new \Datetime($request->get('end'));

            $em = $this->getDoctrine()->getManager();
            $qb = $em->createQueryBuilder();
            $qb->select('e')
                 ->from('CrewCallBundle:Event', 'e')
                 ->where('e.end > :from')
                 ->andWhere('e.start < :to')
                 ->setParameter('from', $from, \Doctrine\DBAL\Types\Type::DATETIME)
                 ->setParameter('to', $to, \Doctrine\DBAL\Types\Type::DATETIME);
            $events = $qb->getQuery()->getResult();
            
            $calitems = $calendar->toFullCalendarArray($events, $this->getUser());
            // Not liked by OWASP since we just return an array.
            return new JsonResponse($calitems, Response::HTTP_OK);
        }

        return $this->render('event/calendar.html.twig', array());
    }

    /**
     * Creates a new PersonRoleEvent entity.
     * But it's only the Contact role here. Simplicity for now.
     * Pure REST/AJAX.
     *
     * @Route("/{id}/add_contact", name="event_add_contact", methods={"GET", "POST"})
     */
    public function addContactAction(Request $request, Event $event, $access)
    {
        $em = $this->getDoctrine()->getManager();
        $pre = new PersonRoleEvent();
        $pre->setEvent($event);

        $people = $event->getOrganization()->getPeople();
        foreach ($event->getLocation()->getPeople() as $p) {
            if (!$people->contains($p))
                $people->add($p);
        }
        $add_contact_form = null;
        if (count($people) > 0) {
            $form = $this->createForm('CrewCallBundle\Form\PersonEventType', $pre, ['people' => $people]);
        } else {
            $form = $this->createForm('CrewCallBundle\Form\PersonEventType', $pre);
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($pre);
            $em->flush($pre);

            if ($this->isRest($access)) {
                return new JsonResponse(array("status" => "OK"), Response::HTTP_CREATED);
            } else {
                if ($event->getParent())
                    return $this->redirectToRoute('event_show', array('id' => $event->getParent()->getId()));
                else
                    return $this->redirectToRoute('event_show', array('id' => $event->getId()));
            }
        }

        if ($this->isRest($access)) {
            return $this->render('event/_new_pfe.html.twig', array(
                'pre' => $pre,
                'event' => $event,
                'form' => $form->createView(),
            ));
        }
        if ($event->getParent())
            return $this->redirectToRoute('event_show', array('id' => $event->getParent()->getId()));
        else
            return $this->redirectToRoute('event_show', array('id' => $event->getId()));
    }

    /**
     * Removes a PersonRoleEvent entity.
     * Pure REST/AJAX.
     *
     * @Route("/{id}/remove_contact", name="event_remove_contact", methods={"GET", "DELETE", "POST"})
     */
    public function removeContactAction(Request $request, PersonRoleEvent $pfe, $access)
    {
        $event = $pfe->getEvent();
        $em = $this->getDoctrine()->getManager();
        $em->remove($pfe);
        $em->flush($pfe);
        if ($this->isRest($access)) {
            return new JsonResponse(array("status" => "OK"),
                Response::HTTP_OK);
        }
        if ($event->getParent())
            return $this->redirectToRoute('event_show', array('id' => $event->getParent()->getId()));
        else
            return $this->redirectToRoute('event_show', array('id' => $event->getId()));
    }

    /**
     * List all available contact info from location and organization.
     * Pure Ajax for now.
     *
     * @Route("/{id}/pick_contact_info", name="event_pick_contact_info", methods={"GET"})
     */
    public function getContactInfoAction(Request $request, Event $event, $access)
    {
        $sakonnin = $this->get('sakonnin.messages');

        $loc_context = [
            'system' => 'crewcall',
            'object_name' => 'location',
            'message_type' => 'Contact Info',
            'external_id' => $event->getLocation()->getId(),
        ];
        $loc_infos = $sakonnin->getMessagesForContext($loc_context);
        $org_context = [
            'system' => 'crewcall',
            'object_name' => 'organization',
            'message_type' => 'Contact Info',
            'external_id' => $event->getOrganization()->getId(),
        ];
        $org_infos = $sakonnin->getMessagesForContext($org_context);

        return $this->render('event/_add_contact_info_list.html.twig', array(
            'event' => $event,
            'loc_infos' => $loc_infos,
            'org_infos' => $org_infos,
        ));
    }

    /**
     * Sends messages to a batch of persons.
     *
     * @Route("/{id}/send_message", name="event_send_message", methods={"POST"})
     */
    public function sendMessageAction($access, Request $request, Event $event)
    {
        $sm = $this->get('sakonnin.messages');
        $body = $request->request->get('body');
        $subject = $request->request->get('subject') ?? "Message from CrewCall";

        $filter = [];
        if ($states = $request->request->get('states')) {
            if (!in_array("all", $states))
                $filter['states'] = $states;
        }

        if ($state = $request->request->get('state'))
            $filter['states'] = [$state];

        if ($function_id = $request->request->get('function_id'))
            $filter['function_ids'] = [$function_id];
        
        $people = new ArrayCollection();
        foreach ($event->getJobs($filter) as $j) {
            if (!$people->contains($j->getPerson()))
                $people->add($j->getPerson());
        }
        // Including sub events, unless.
        if (!$request->request->get('no_children')
            && !$request->get('no_children')) {
            foreach ($event->getChildren() as $child) {
                foreach ($child->getJobs($filter) as $j) {
                    if (!$people->contains($j->getPerson()))
                        $people->add($j->getPerson());
                }
            }
        }

        $person_contexts = array_map(function($person) {
            return [
                'system' => 'crewcall',
                'object_name' => 'person',
                'external_id' => $person->getId()
            ];
            }, $people->toArray());
        $message_type = $request->request->get('message_type');
        $sm->postMessage(array(
            'subject' => $subject,
            'body' => $body,
            'from' => $this->getParameter('system_emails_address'),
            'message_type' => $message_type,
            'to_type' => "INTERNAL",
            'from_type' => "EMAIL",
        ), $person_contexts);
        $status_text = "Sent '".$body."' to " . count($person_contexts) . " persons.";
        return new Response($status_text, Response::HTTP_OK);
    }

    /**
     * @Route("/search", name="event_search", methods={"GET"})
     */
    public function searchAction(Request $request, $access)
    {
        if (!$term = $request->query->get("term"))
            $term = $request->query->get("event");
                // Gotta be able to handle two-letter usernames.
        if (strlen($term) > 1) {
            $result = array();
            $em = $this->getDoctrine()->getManager();
            $qb = $em->createQueryBuilder();
            $qb->select('e')
                 ->from('CrewCallBundle:Event', 'e')
                ->where('lower(e.name) LIKE :term')
                ->setParameter('term', strtolower($term) . '%');

            if ($events = $qb->getQuery()->getResult()) {
                foreach ($events as $event) {
                    // TODO: Add full name.
                    $res = array(
                        'id' => $event->getId(),
                        'value' => (string)$event,
                        'label' => (string)$event,
                    );
                    $result[] = $res;
                }
            }
        } else {
            $result = "Too little information provided for a viable search";
        }

        if ($this->isRest($access)) {
            // Format for autocomplete.
            return $this->returnRestData($request, $result);
        }
    }

    /**
     * Finds and displays a event entity.
     *
     * @Route("/{id}/send_mail", name="event_send_as_mail", methods={"POST"})
     */
    public function sendMailAction(Request $request, Event $event)
    {
        $em = $this->getDoctrine()->getManager();
        $mailForm = $this->createSendMailForm($event);
        $mailForm->handleRequest($request);
        $fields = $request->get('fields');
        $params = $fields;
        $params['all'] = false;
        $params['event'] = $event;
        $params['state'] = $request->get('state');

        if ($mailForm->isSubmitted() && $mailForm->isValid()) {
            $fd = $mailForm->getData();
            $params['state'] = $fd['state'];
            $resp = $this->render('event/mailable.html.twig', $params);
            $html = $resp->getContent();
            $tmpdir = sys_get_temp_dir() . "/mpdf";
            $mpdf = new \Mpdf\Mpdf(['tempDir' => $tmpdir]);
            $mpdf->shrink_tables_to_fit = 1;
            $mpdf->WriteHTML($html);
            $pdf = $mpdf->Output('', 'S');

            $body = "Here is the staff list for " . $event->getName();
            $sm = $this->container->get('sakonnin.messages');
            $sm->postMessage([
                'subject' => $event->getName(),
                'body' => $body,
                'to' => $fd['email'],
                'from' => $this->getParameter('system_emails_address'),
                'message_type' => "List Sent",
                'to_type' => "EMAIL",
                'from_type' => "EMAIL",
                'attach_content' => $pdf,
                'attach_filename' => 'CrewList.pdf',
                'attach_content_type' => 'application/pdf'
                ],
                [
                    'system' => 'crewcall',
                    'object_name' => 'event',
                    'external_id' => $event->getId()
                ]
            );
            $params['message'] = "Mail sendt to " . $fd['email'];
        }

        $params['all'] = true;
        $params['mailform'] = $mailForm->createView();
        return $this->render('event/printable.html.twig', $params);
    }

    /**
     * Creates a form to delete a event entity.
     *
     * @param Event $event The event entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Event $event)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('event_delete', array('id' => $event->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Creates a form to confirm
     *
     * @param Event $event The event entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createStateForm(Event $event)
    {
        // It looks like should add a state here, but I am going to act
        // differently based on the state. And I am not ready to do that in
        // the event entity, yet. (Hmm, but the state handler..)
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('event_state', array('state' => "STATE", 'id' => $event->getId())))
            ->setMethod('POST')
            ->getForm()
        ;
    }
    /**
     * Creates a form to confirm
     *
     * @param Event $event The event entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createSendMailForm(Event $event, $state = '')
    {
        // It looks like should add a state here, but I am going to act
        // differently based on the state. And I am not ready to do that in
        // the event entity, yet. (Hmm, but the state handler..)
        return $this->createFormBuilder(null, array('allow_extra_fields' =>true))
            ->add('email', EmailType::class, array('label' => "E-mail",
                'required' => true))
            ->add('state', HiddenType::class, array('data' => $state,
                'required' => false))
            ->setAction($this->generateUrl('event_send_as_mail', array('id' => $event->getId())))
            ->setMethod('POST')
            ->getForm()
        ;
    }
}
