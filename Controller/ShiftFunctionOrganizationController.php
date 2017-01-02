<?php

namespace CrewCallBundle\Controller;

use CrewCallBundle\Entity\ShiftFunctionOrganization;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;

/**
 * Shiftfunctionorganization controller.
 *
 * @Route("/admin/{access}/shiftfunctionorganization", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class ShiftFunctionOrganizationController extends CommonController
{
    /**
     * Lists all shiftFunctionOrganization entities.
     *
     * @Route("/", name="shiftfunctionorganization_index")
     * @Method("GET")
     */
    public function indexAction(Request $request, $access)
    {
        $em = $this->getDoctrine()->getManager();
        if ($shiftfunction_id = $request->get('shiftfunction')) {
            $em = $this->getDoctrine()->getManager();
            if ($shiftFunction = $em->getRepository('CrewCallBundle:ShiftFunction')->find($shiftfunction_id)) {
                $shiftFunctionOrganizations = $shift->getShiftFunctions();
            }
        } else {
            $shiftFunctionOrganizations = $em->getRepository('CrewCallBundle:ShiftFunctionOrganization')->findAll();
        }

        // Again, ajax-centric.
        if ($this->isRest($access)) {
            return $this->render('shiftfunctionorganization/_index.html.twig', array(
                'shiftFunctionOrganizations' => $shiftFunctionOrganizations,
            ));
        }
        return $this->render('shiftfunctionorganization/index.html.twig', array(
            'shiftFunctionOrganizations' => $shiftFunctionOrganizations,
        ));
    }

    /**
     * Creates a new shiftFunctionOrganization entity.
     *
     * @Route("/new", name="shiftfunctionorganization_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, $access)
    {
        $shiftFunctionOrganization = new Shiftfunctionorganization();
        $form = $this->createForm('CrewCallBundle\Form\ShiftFunctionOrganizationType', $shiftFunctionOrganization);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($shiftFunctionOrganization);
            $em->flush($shiftFunctionOrganization);

            if ($this->isRest($access)) {
                return new JsonResponse(array("status" => "OK"), Response::HTTP_CREATED);
            } else { 
                return $this->redirectToRoute('shiftfunctionorganization_show', array('id' => $shiftFunctionOrganization->getId()));
            }
        }

        // If this has a shift set here, it's not an invalid create attempt.
        if ($shiftfunction_id = $request->get('shiftfunction')) {
            $em = $this->getDoctrine()->getManager();
            if ($shiftFunction = $em->getRepository('CrewCallBundle:ShiftFunction')->find($shiftfunction_id)) {
                $shiftFunctionOrganization->setShiftFunction($shiftFunction);
                $form->setData($shiftFunctionOrganization);
            }
        }
        if ($this->isRest($access)) {
            return $this->render('shiftfunctionorganization/_new.html.twig', array(
                'shiftFunctionOrganization' => $shiftFunctionOrganization,
                'form' => $form->createView(),
            ));
        }
        return $this->render('shiftfunctionorganization/new.html.twig', array(
            'shiftFunctionOrganization' => $shiftFunctionOrganization,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a shiftFunctionOrganization entity.
     *
     * @Route("/{id}", name="shiftfunctionorganization_show")
     * @Method("GET")
     */
    public function showAction(ShiftFunctionOrganization $shiftFunctionOrganization)
    {
        $deleteForm = $this->createDeleteForm($shiftFunctionOrganization);

        return $this->render('shiftfunctionorganization/show.html.twig', array(
            'shiftFunctionOrganization' => $shiftFunctionOrganization,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing shiftFunctionOrganization entity.
     *
     * @Route("/{id}/edit", name="shiftfunctionorganization_edit", defaults={"id" = 0})
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, ShiftFunctionOrganization $shiftFunctionOrganization, $access)
    {
        $deleteForm = $this->createDeleteForm($shiftFunctionOrganization);
        $editForm = $this->createForm('CrewCallBundle\Form\ShiftFunctionOrganizationType', $shiftFunctionOrganization);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('shiftfunctionorganization_show', array('id' => $shiftFunctionOrganization->getId()));
        }

        if ($this->isRest($access)) {
            return $this->render('shiftfunctionorganization/_edit.html.twig', array(
            'shiftFunctionOrganization' => $shiftFunctionOrganization,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
            ));
        }
        return $this->render('shiftfunctionorganization/edit.html.twig', array(
            'shiftFunctionOrganization' => $shiftFunctionOrganization,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a shiftFunctionOrganization entity.
     *
     * @Route("/{id}", name="shiftfunctionorganization_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, ShiftFunctionOrganization $shiftFunctionOrganization)
    {
        $form = $this->createDeleteForm($shiftFunctionOrganization);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($shiftFunctionOrganization);
            $em->flush($shiftFunctionOrganization);
        }

        return $this->redirectToRoute('shiftfunctionorganization_index');
    }

    /**
     * Creates a form to delete a shiftFunctionOrganization entity.
     *
     * @param ShiftFunctionOrganization $shiftFunctionOrganization The shiftFunctionOrganization entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(ShiftFunctionOrganization $shiftFunctionOrganization)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('shiftfunctionorganization_delete', array('id' => $shiftFunctionOrganization->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
