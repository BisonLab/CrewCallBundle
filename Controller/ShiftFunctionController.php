<?php

namespace CrewCallBundle\Controller;

use CrewCallBundle\Entity\ShiftFunction;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;

/**
 * Shiftfunction controller.
 *
 * @Route("/admin/{access}/shiftfunction", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class ShiftFunctionController extends CommonController
{
    /**
     * Lists all shiftFunction entities.
     *
     * @Route("/", name="shiftfunction_index")
     * @Method("GET")
     */
    public function indexAction(Request $request, $access)
    {
        $em = $this->getDoctrine()->getManager();

        $shiftFunctions = $em->getRepository('CrewCallBundle:ShiftFunction')->findAll();
        $shiftFunctions = array();
        if ($shift_id = $request->get('shift')) {
            $em = $this->getDoctrine()->getManager();
            if ($shift = $em->getRepository('CrewCallBundle:Shift')->find($shift_id)) {
                $shiftFunctions = $shift->getShiftFunctions();
            }
        } else {
            // Gotta filter this too, on active or not completed or something.
            $shiftFunctions = $em->getRepository('CrewCallBundle:ShiftFunction')->findAll();
        }
        // Again, ajax-centric.
        if ($this->isRest($access)) {
            return $this->render('shiftfunction/_index.html.twig', array(
                'shiftFunctions' => $shiftFunctions
            ));
        }

        return $this->render('shiftfunction/index.html.twig', array(
            'shiftFunctions' => $shiftFunctions,
        ));
    }

    /**
     * Creates a new shiftFunction entity.
     *
     * @Route("/new", name="shiftfunction_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, $access)
    {
        $shiftFunction = new Shiftfunction();
        $form = $this->createForm('CrewCallBundle\Form\ShiftFunctionType', $shiftFunction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($shiftFunction);
            $em->flush($shiftFunction);

            if ($this->isRest($access)) {
                return new JsonResponse(array("status" => "OK"), Response::HTTP_CREATED);
            } else { 
                return $this->redirectToRoute('shiftfunction_show', array('id' => $shiftFunction->getId()));
            }
        }

        // If this has a shift set here, it's not an invalid create attempt.
        if ($shift_id = $request->get('shift')) {
            $em = $this->getDoctrine()->getManager();
            if ($shift = $em->getRepository('CrewCallBundle:Shift')->find($shift_id)) {
                $shiftFunction->setShift($shift);
                // Better have something to start with.
                $form->setData($shiftFunction);
            }
        }
        if ($this->isRest($access)) {
            return $this->render('shiftfunction/_new.html.twig', array(
                'shiftFunction' => $shiftFunction,
                'form' => $form->createView(),
            ));
        }
        return $this->render('shiftfunction/new.html.twig', array(
            'shiftFunction' => $shiftFunction,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a shiftFunction entity.
     *
     * @Route("/{id}", name="shiftfunction_show")
     * @Method("GET")
     */
    public function showAction(ShiftFunction $shiftFunction)
    {
        $deleteForm = $this->createDeleteForm($shiftFunction);

        return $this->render('shiftfunction/show.html.twig', array(
            'shiftFunction' => $shiftFunction,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing shiftFunction entity.
     *
     * @Route("/{id}/edit", name="shiftfunction_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, ShiftFunction $shiftFunction)
    {
        $deleteForm = $this->createDeleteForm($shiftFunction);
        $editForm = $this->createForm('CrewCallBundle\Form\ShiftFunctionType', $shiftFunction);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('shiftfunction_edit', array('id' => $shiftFunction->getId()));
        }

        return $this->render('shiftfunction/edit.html.twig', array(
            'shiftFunction' => $shiftFunction,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a shiftFunction entity.
     *
     * @Route("/{id}", name="shiftfunction_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, ShiftFunction $shiftFunction)
    {
        $form = $this->createDeleteForm($shiftFunction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($shiftFunction);
            $em->flush($shiftFunction);
        }

        return $this->redirectToRoute('shiftfunction_index');
    }

    /**
     * Creates a form to delete a shiftFunction entity.
     *
     * @param ShiftFunction $shiftFunction The shiftFunction entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(ShiftFunction $shiftFunction)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('shiftfunction_delete', array('id' => $shiftFunction->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
