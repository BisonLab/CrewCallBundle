<?php

namespace CrewCallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\Common\Collections\ArrayCollection;

use BisonLab\CommonBundle\Controller\CommonController as CommonController;
use CrewCallBundle\Entity\Event;
use CrewCallBundle\Entity\PersonFunctionEvent;

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

        $past = $upcoming = $ongoing = false;
        if ($request->get('past')) {
            $events = $eventrepo->findEvents(['past' => true,
                'parents_only' => true]);
            $past = true;
        } elseif ($request->get('upcoming')) {
            $events = $eventrepo->findEvents(['future' => true,
                'parents_only' => true]);
            $upcoming = true;
        } else {
            $events = $eventrepo->findEvents(['ongoing' => true,
                'parents_only' => true]);
            $ongoing = true;
        }

        if ($this->isRest($access)) {
            if ($access == "ajax")
                return $this->render('event/_index.html.twig', array(
                    'events'  => $events,
                    'past'    => $past,
                    'upcoming'=> $upcoming,
                    'ongoing' => $ongoing,
                ));
            else
                return $this->returnRestData($request, [
                    'events'  => $events,
                    'past'    => $past,
                    'upcoming'=> $upcoming,
                    'ongoing' => $ongoing,
                ]);
        }

        return $this->render('event/index.html.twig', array(
            'events'  => $events,
            'past'    => $past,
            'upcoming'=> $upcoming,
            'ongoing' => $ongoing,
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
        if ($parent_id = $request->get('parent')) {
            $em = $this->getDoctrine()->getManager();
            if ($parent = $em->getRepository('CrewCallBundle:Event')->find($parent_id)) {
                $event->setParent($parent);
                $event->setStart($parent->getStart());
                $event->setEnd($parent->getEnd());
                $event->setOrganization($parent->getOrganization());
                $event->setLocation($parent->getLocation());
                // TODO: Consider setting manager, location and organization
                // aswell. But not before I've decided on wether I want to
                // inherit from the parent or not. And on which properties.
                // (Org and loc are included, for now at least.)
                $form->setData($event);
            }
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
            return $this->render('event/printable.html.twig', array(
                'event' => $event,
                'state' => $request->get('state'),
            ));
        }

        $funcrepo = $em->getRepository('CrewCallBundle:FunctionEntity');
        $pfe = new PersonFunctionEvent();
        $pfe->setEvent($event);
        if ($contact = $funcrepo->findOneByName('Contact'))
            $pfe->setFunction($contact);
        // Gotta find all available persons
        // $persons = new ArrayCollection();
        // Just gettable from organization for now, have to add location later.
        $persons = $event->getOrganization()->getPersons();
        foreach ($event->getLocation()->getPersons() as $p) {
            if (!$persons->contains($p))
                $persons->add($p);
        }

        $add_contact_form = null;
        if (count($persons) > 0) {
            $add_contact_form = $this->createForm('CrewCallBundle\Form\PersonEventType', $pfe, ['persons' => $persons])->createView();
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
        $deleteForm = $this->createDeleteForm($event);
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
            'delete_form' => $deleteForm->createView(),
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

        if ($parent)
            return $this->redirectToRoute('event_show',
                array('id' => $parent->getId()));
        else
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
            $events = $this->container->get('crewcall.events');
            $clone = $events->cloneEvent($event, $clone);
            $em = $this->getDoctrine()->getManager();
            $em->persist($clone);
            $em->flush($clone);
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
     * Creates a new PersonFunctionEvent entity.
     * But it's only the Contact role here. Simplicity for now.
     * Pure REST/AJAX.
     *
     * @Route("/{id}/add_contact", name="event_add_contact", methods={"GET", "POST"})
     */
    public function addContactAction(Request $request, Event $event, $access)
    {
        $em = $this->getDoctrine()->getManager();
        $pfe = new PersonFunctionEvent();
        $pfe->setEvent($event);

        $persons = $event->getOrganization()->getPersons();
        foreach ($event->getLocation()->getPersons() as $p) {
            if (!$persons->contains($p))
                $persons->add($p);
        }
        $add_contact_form = null;
        if (count($persons) > 0) {
            $form = $this->createForm('CrewCallBundle\Form\PersonEventType', $pfe, ['persons' => $persons]);
        } else {
            $form = $this->createForm('CrewCallBundle\Form\PersonEventType', $pfe);
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($pfe);
            $em->flush($pfe);

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
                'pfe' => $pfe,
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
     * Removes a contactFunctionEvent entity.
     * Pure REST/AJAX.
     *
     * @Route("/{id}/remove_contact", name="event_remove_contact", methods={"GET", "DELETE", "POST"})
     */
    public function removeContactAction(Request $request, PersonFunctionEvent $pfe, $access)
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
        
        $persons = new ArrayCollection();
        foreach ($event->getJobs($filter) as $j) {
            if (!$persons->contains($j->getPerson()))
                $persons->add($j->getPerson());
        }
        $person_ids = array_map(function($person) {
                return $person->getId();
            }, $persons->toArray());
        $message_type = $request->request->get('message_type');
        $sm->postMessage(array(
            'subject' => $subject,
            'body' => $body,
            'to' => implode(",", $person_ids),
            'from' => $this->getParameter('system_emails_address'),
            'message_type' => $message_type,
            'to_type' => "INTERNAL",
            'from_type' => "INTERNAL",
        ));
        return new Response("Sent: " . $body, Response::HTTP_OK);
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
}
