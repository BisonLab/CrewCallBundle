<?php

namespace CrewCallBundle\Controller;

use CrewCallBundle\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;

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
     * @Route("/", name="event_index")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        if ($request->get('past')) {
            $qb = $em->createQueryBuilder();
            $qb->select('e')
                 ->from('CrewCallBundle:Event', 'e')
                 ->where('e.end < :today')
                 ->setParameter('today', new \DateTime(), \Doctrine\DBAL\Types\Type::DATETIME);
            $events = $qb->getQuery()->getResult();
        } else {
            $qb = $em->createQueryBuilder();
            $qb->select('e')
                 ->from('CrewCallBundle:Event', 'e')
                 ->where('e.end > :yesterday')
                 ->setParameter('yesterday', new \DateTime('yesterday'), \Doctrine\DBAL\Types\Type::DATETIME);
            $events = $qb->getQuery()->getResult();
        }

        return $this->render('event/index.html.twig', array(
            'events' => $events,
        ));
    }

    /**
     * Creates a new event entity.
     *
     * @Route("/new", name="event_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $event = new Event();

        $form = $this->createForm('CrewCallBundle\Form\EventType', $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($event);
            $em->flush($event);

            return $this->redirectToRoute('event_show', array('id' => $event->getId()));
        }

        // If this has a parent set here, it's not an invalid create attempt.
        if ($parent_id = $request->get('parent')) {
            $em = $this->getDoctrine()->getManager();
            if ($parent = $em->getRepository('CrewCallBundle:Event')->find($parent_id)) {
                $event->setParent($parent);
                $event->setStart($parent->getStart());
                $event->setEnd($parent->getEnd());
                $event->setOrganization($parent->getOrganization());
                // TODO: Consider setting manager, location and organization
                // aswell. But not befire I've decided on wether I want to
                // inherit from the parent or not. And on which properties.
                $form->setData($event);
            }
        } elseif (!$form->isSubmitted()) {
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
     * @Route("/{id}", name="event_show")
     * @Method("GET")
     */
    public function showAction(Event $event)
    {
        $deleteForm = $this->createDeleteForm($event);
        $confirmForm = null;
        if (!$event->isDone())
            $confirmForm = $this->createConfirmForm($event)->createView();

        return $this->render('event/show.html.twig', array(
            'event' => $event,
            'delete_form' => $deleteForm->createView(),
            'confirm_form' => $confirmForm
        ));
    }

    /**
     * Displays a form to edit an existing event entity.
     *
     * @Route("/{id}/edit", name="event_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Event $event)
    {
        $deleteForm = $this->createDeleteForm($event);
        $editForm = $this->createForm('CrewCallBundle\Form\EventType', $event);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

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
     * @Route("/{id}/delete", name="event_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Event $event)
    {
        $form = $this->createDeleteForm($event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($event);
            $em->flush($event);
        }

        return $this->redirectToRoute('event_index');
    }

    /**
     * Sets "CONFIRMED" on the event and all shifts underneith.
     *
     * @Route("/{id}/confirm", name="event_confirm")
     * @Method("POST")
     */
    public function confirmAction(Request $request, Event $event)
    {
        $form = $this->createConfirmForm($event);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $event->setConfirmed();
            $em = $this->getDoctrine()->getManager();
            $em->flush();
        }
        return $this->redirectToRoute('event_show', array('id' => $event->getId()));
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
    private function createConfirmForm(Event $event)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('event_confirm', array('id' => $event->getId())))
            ->setMethod('POST')
            ->getForm()
        ;
    }
}
