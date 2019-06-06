<?php

namespace CrewCallBundle\Controller;

use CrewCallBundle\Entity\Shift;
use CrewCallBundle\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;
use CrewCallBundle\Lib\ExternalEntityConfig;

/**
 * Shift controller.
 *
 * @Route("/admin/{access}/shift", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class ShiftController extends CommonController
{
    /**
     * Lists all shift entities in an event.
     *
     * @Route("/", name="shift_index", methods={"GET"})
     */
    public function indexAction(Request $request, $access)
    {
        $em = $this->getDoctrine()->getManager();

        // If this has a event set here, it's not an invalid create attempt.
        if ($event_id = $request->get('event')) {
            $event = $em->getRepository('CrewCallBundle:Event')->find($event_id);
        }
        if (!$event)
            return $this->returnNotFound($request, "No event");

        // Again, ajax/html-centric. But maybe return json later.
        if ($this->isRest($access)) {
            return $this->render('shift/_index.html.twig', array(
                'event' => $event,
            ));
        }
        return $this->render('shift/index.html.twig', array(
            'event' => $event,
        ));
    }

    /**
     * Creates a new shift entity.
     *
     * @Route("/new", name="shift_new", methods={"GET", "POST"})
     */
    /*
     * This one is quite hacked together, but it's because I have to keep the
     * experimentation visible for later attempts at doing what I want to do.
     * Which is to return the form as HTML if it's ajax and web, and if it's
     * REST (apps you know..) Return JSON.
     *
     */
    public function newAction(Request $request, $access)
    {
        $shift = new Shift();
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm('CrewCallBundle\Form\ShiftType', $shift);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em->persist($shift);
                $em->flush($shift);
                if ($this->isRest($access)) {
                    return new JsonResponse(array("status" => "OK"),
                        Response::HTTP_CREATED);
                } else { 
                    return $this->redirectToRoute('shift_show',
                        array('id' => $shift->getId()));
                }
            } else {
                // The issue here is that I want to return this if it's a
                // /rest/ call, but not /ajax/. If AJAX, return a prettier
                // text.
                if ($this->isRest($access)) {
                    return $this->returnErrorResponse("Validation Error", 400, $this->handleFormErrors($form));
                }
            }
        }

        // If this has a event set here, it's not an invalid create attempt.
        if ($event_id = $request->get('event')) {
            if ($event = $em->getRepository('CrewCallBundle:Event')->find($event_id)) {
                $shift->setEvent($event);
                // Better have something to start with.
                $shift->setStart($event->getStart());
                // But ending at the end of the event is just too much. Let's
                // add 8 hours instead.
                $shift->setEnd($event->getStart()->modify('+8 hours'));
                $form->setData($shift);
            }
        }
        if ($from_shift = $request->get('from_shift')) {
            if ($fshift = $em->getRepository('CrewCallBundle:Shift')->find($from_shift)) {
                $shift->setEvent($fshift->getEvent());
                // Better have something to start with.
                $shift->setStart($fshift->getStart());
                $shift->setEnd($fshift->getEnd());
                $form->setData($shift);
            }
        }

        /*
         * Not sure yet how to handle pure REST, keep ajax for now.
         * (And I can start being annoyed by "isRest" which means both ajax
         * and rest.  (But I can test on accept-header and return the
         * _new-template if HTML is asked for. returnRest with a set template
         * does fix that part)
         */
        if ($this->isRest($access)) {
            return $this->render('shift/_new.html.twig', array(
                'shift' => $shift,
                'form' => $form->createView(),
            ));
        }

        return $this->render('shift/new.html.twig', array(
            'shift' => $shift,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a shift entity.
     *
     * @Route("/{id}", name="shift_show", methods={"GET"})
     */
    public function showAction(Shift $shift)
    {
        $deleteForm = $this->createDeleteForm($shift);

        return $this->render('shift/show.html.twig', array(
            'shift' => $shift,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing shift entity.
     *
     * @Route("/{id}/edit", name="shift_edit", defaults={"id" = 0}, methods={"GET", "POST"})
     */
    public function editAction(Request $request, Shift $shift, $access)
    {
        $em = $this->getDoctrine()->getManager();
        $editForm = $this->createForm('CrewCallBundle\Form\ShiftType', $shift);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('shift_show', array('id' => $shift->getId()));
        }

        if ($this->isRest($access)) {
            return $this->render('shift/_edit.html.twig', array(
                'shift' => $shift,
                'edit_form' => $editForm->createView()
            ));
        }

        return $this->render('shift/edit.html.twig', array(
            'shift' => $shift,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a shift entity.
     *
     * @Route("/{id}", name="shift_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, $access, Shift $shift)
    {
        // Bloody good question here, because CSRF.
        // This should add some sort of protection.
        if ($this->isRest($access)) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($shift);
            $em->flush($shift);
            return new JsonResponse(array("status" => "OK"),
                Response::HTTP_OK);
        }

        $form = $this->createDeleteForm($shift);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($shift);
            $em->flush($shift);
        }
        return $this->redirectToRoute('shift_index');
    }

    /**
     * Sets a (new) state on the shift.
     *
     * @Route("/{id}/state/{state}", name="shift_state", methods={"POST"})
     */
    public function stateAction(Request $request, Shift $shift, $state, $access)
    {
        if ($state != $shift->getState()) {
            $shift->setState($state);
            $em = $this->getDoctrine()->getManager();
            $em->flush($shift);
        }
        if ($this->isRest($access)) {
            return new JsonResponse(array("status" => "OK"),
                Response::HTTP_OK);
        }
        if (!$event_id = $request->request->get('event'))
            $event_id = $shift->getEvent()->getId();
        return $this->redirectToRoute('event_show', array(
            'id' => $event_id));
    }

    /**
     * Finds and displays the gedmo loggable history
     *
     * @Route("/{id}/log", name="shift_log")
     */
    public function showLogAction(Request $request, $access, $id)
    {
        return  $this->showLogPage($request,$access, "CrewCallBundle:Shift", $id);
    }

    /**
     * Creates a form to delete a shift entity.
     *
     * @param Shift $shift The shift entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Shift $shift)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('shift_delete', array('id' => $shift->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
