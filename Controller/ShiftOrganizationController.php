<?php

namespace CrewCallBundle\Controller;

use CrewCallBundle\Entity\ShiftOrganization;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;

/**
 * Shiftorganization controller.
 *
 * @Route("/admin/{access}/shiftorganization", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class ShiftOrganizationController extends CommonController
{
    /**
     * Lists all shiftOrganization entities.
     *
     * @Route("/", name="shiftorganization_index", methods={"GET"})
     */
    public function indexAction(Request $request, $access)
    {
        $em = $this->getDoctrine()->getManager();
        if ($shift_id = $request->get('shift')) {
            $em = $this->getDoctrine()->getManager();
            if ($shift = $em->getRepository('CrewCallBundle:Shift')->find($shift_id)) {
                $shiftOrganizations = $shift;
            }
        } else {
            $shiftOrganizations = $em->getRepository('CrewCallBundle:ShiftOrganization')->findAll();
        }

        // Again, ajax-centric.
        if ($this->isRest($access)) {
            return $this->render('shiftorganization/_index.html.twig', array(
                'shiftOrganizations' => $shiftOrganizations,
            ));
        }
        return $this->render('shiftorganization/index.html.twig', array(
            'shiftOrganizations' => $shiftOrganizations,
        ));
    }

    /**
     * Creates a new shiftOrganization entity.
     *
     * @Route("/new", name="shiftorganization_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request, $access)
    {
        $shiftOrganization = new Shiftorganization();
        $form = $this->createForm('CrewCallBundle\Form\ShiftOrganizationType', $shiftOrganization);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($shiftOrganization);
            $em->flush($shiftOrganization);

            if ($this->isRest($access)) {
                return new JsonResponse(array("status" => "OK"), Response::HTTP_CREATED);
            } else { 
                return $this->redirectToRoute('shiftorganization_show', array('id' => $shiftOrganization->getId()));
            }
        }

        // If this has a shift set here, it's not an invalid create attempt.
        if ($shift_id = $request->get('shift')) {
            $em = $this->getDoctrine()->getManager();
            if ($shift = $em->getRepository('CrewCallBundle:Shift')->find($shift_id)) {
                $shiftOrganization->setShift($shift);
                $form->setData($shiftOrganization);
            }
        }
        if ($this->isRest($access)) {
            return $this->render('shiftorganization/_new.html.twig', array(
                'shiftOrganization' => $shiftOrganization,
                'form' => $form->createView(),
            ));
        }
        return $this->render('shiftorganization/new.html.twig', array(
            'shiftOrganization' => $shiftOrganization,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a shiftOrganization entity.
     *
     * @Route("/{id}", name="shiftorganization_show", methods={"GET"})
     */
    public function showAction(ShiftOrganization $shiftOrganization)
    {
        $deleteForm = $this->createDeleteForm($shiftOrganization);

        return $this->render('shiftorganization/show.html.twig', array(
            'shiftOrganization' => $shiftOrganization,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing shiftOrganization entity.
     *
     * @Route("/{id}/edit", name="shiftorganization_edit", defaults={"id" = 0}, methods={"GET", "POST"})
     */
    public function editAction(Request $request, ShiftOrganization $shiftOrganization, $access)
    {
        $deleteForm = $this->createDeleteForm($shiftOrganization);
        $editForm = $this->createForm('CrewCallBundle\Form\ShiftOrganizationType', $shiftOrganization);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            if ($this->isRest($access)) {
                // No content, well, sortof.
                return new JsonResponse(array("status" => "OK"), Response::HTTP_NO_CONTENT);
            } else {
                return $this->redirectToRoute('shiftorganization_show', array('id' => $shiftOrganization->getId()));
            }
        }

        if ($this->isRest($access)) {
            return $this->render('shiftorganization/_edit.html.twig', array(
            'shiftOrganization' => $shiftOrganization,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
            ));
        }
        return $this->render('shiftorganization/edit.html.twig', array(
            'shiftOrganization' => $shiftOrganization,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a shiftOrganization entity.
     *
     * @Route("/{id}", name="shiftorganization_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, ShiftOrganization $shiftOrganization, $access)
    {
        // If rest, no form.
        if ($this->isRest($access)) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($shiftOrganization);
            $em->flush($shiftOrganization);
            return new JsonResponse(array("status" => "OK"), Response::HTTP_NO_CONTENT);
        }

        $form = $this->createDeleteForm($shiftOrganization);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($shiftOrganization);
            $em->flush($shiftOrganization);
        }

        return $this->redirectToRoute('shiftorganization_index');
    }

    /**
     * Creates a form to delete a shiftOrganization entity.
     *
     * @param ShiftOrganization $shiftOrganization The shiftOrganization entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(ShiftOrganization $shiftOrganization)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('shiftorganization_delete', array('id' => $shiftOrganization->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
