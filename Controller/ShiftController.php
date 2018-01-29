<?php

namespace CrewCallBundle\Controller;

use CrewCallBundle\Entity\Shift;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;

/**
 * Shift controller.
 *
 * @Route("/admin/{access}/shift", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class ShiftController extends CommonController
{
    /**
     * Lists all shift entities.
     *
     * @Route("/", name="shift_index")
     * @Method("GET")
     */
    public function indexAction(Request $request, $access)
    {
        $em = $this->getDoctrine()->getManager();
        $shifts = array();
        // If this has a event set here, it's not an invalid create attempt.
        if ($event_id = $request->get('event')) {
            $em = $this->getDoctrine()->getManager();
            if ($event = $em->getRepository('CrewCallBundle:Event')->find($event_id)) {
                $shifts = $event->getShifts();
            }
        } else {
            // To be honest, I don't think having this at all is a good idea :=)
            // Who wants a list of absolutely all shifts? Gotta filter on 
            // something somehow.
            $shifts = $em->getRepository('CrewCallBundle:Shift')->findAll();
        }
        // Again, ajax-centric. But maybe return json later.
        if ($this->isRest($access)) {
            return $this->render('shift/_index.html.twig', array(
                'shifts' => $shifts
            ));
        }
        return $this->render('shift/index.html.twig', array(
            'shifts' => $shifts,
        ));
    }

    /**
     * Creates a new shift entity.
     *
     * @Route("/new", name="shift_new")
     * @Method({"GET", "POST"})
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
        $form = $this->createForm('CrewCallBundle\Form\ShiftType', $shift);
        // $this->handleForm($form, $request, $access);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($shift);
                $em->flush($shift);
                if ($this->isRest($access)) {
                    return new JsonResponse(array("status" => "OK"), Response::HTTP_CREATED);
                } else { 
                    return $this->redirectToRoute('shift_show', array('id' => $shift->getId()));
                }
            }
/*
 else {
                // The issue here is that I want to return this if it's a /rest/ call, but not /ajax/. If AJAX, return html form.
                if ($this->isRest($access)) {
                    return $this->returnErrorResponse("Validation Error", 400, $this->handleFormErrors($form));
                }
            }
*/
        }

        // If this has a event set here, it's not an invalid create attempt.
        if ($event_id = $request->get('event')) {
            $em = $this->getDoctrine()->getManager();
            if ($event = $em->getRepository('CrewCallBundle:Event')->find($event_id)) {
                $shift->setEvent($event);
                // Better have something to start with.
                $shift->setStart($event->getStart());
                $shift->setEnd($event->getEnd());
                // There are no Manager. Neither in shift, nor event.
                // $shift->setManager($event->getManager());
                $form->setData($shift);
            }
        }

        // Not sure yet how to handle pure REST, keep ajax for now.
        // (And I can start being annoyed by "isRest" which means both ajax and
        // rest. 
        // (But I can test on accept-header and return the _new-template if
        // HTML is asked for. returnRest with a set template does fix that part)
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
     * @Route("/{id}", name="shift_show")
     * @Method("GET")
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
     * @Route("/{id}/edit", name="shift_edit", defaults={"id" = 0})
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Shift $shift, $access)
    {
        $deleteForm = $this->createDeleteForm($shift);
        $editForm = $this->createForm('CrewCallBundle\Form\ShiftType', $shift);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('shift_show', array('id' => $shift->getId()));
        }

        if ($this->isRest($access)) {
            return $this->render('shift/_edit.html.twig', array(
                'shift' => $shift,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
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
     * @Route("/{id}", name="shift_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $access, Shift $shift)
    {
        // Bloody good question here, because CSRF. This should add some sort of protection.
        if ($this->isRest($access)) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($shiftFunction);
            $em->flush($shiftFunction);
            return new JsonResponse(array("status" => "OK"), Response::HTTP_OK);
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
