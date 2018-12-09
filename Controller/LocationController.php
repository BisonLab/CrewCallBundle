<?php

namespace CrewCallBundle\Controller;

use CrewCallBundle\Entity\Location;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;

/**
 * Location controller.
 *
 * @Route("/admin/{access}/location", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class LocationController extends CommonController
{
    /**
     * Lists all location entities.
     *
     * @Route("/", name="location_index", methods={"GET"})
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $locations = $em->getRepository('CrewCallBundle:Location')->findAll();

        return $this->render('location/index.html.twig', array(
            'locations' => $locations,
        ));
    }

    /**
     * Creates a new location entity.
     *
     * @Route("/new", name="location_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $location = new Location();
        $form = $this->createForm('CrewCallBundle\Form\LocationType', $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($location);
            $em->flush($location);

            return $this->redirectToRoute('location_show', array('id' => $location->getId()));
        }

        return $this->render('location/new.html.twig', array(
            'location' => $location,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a location entity.
     *
     * @Route("/{id}", name="location_show", methods={"GET"})
     */
    public function showAction(Location $location)
    {
        $deleteForm = $this->createDeleteForm($location);

        return $this->render('location/show.html.twig', array(
            'location' => $location,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing location entity.
     *
     * @Route("/{id}/edit", name="location_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, Location $location)
    {
        $deleteForm = $this->createDeleteForm($location);
        $editForm = $this->createForm('CrewCallBundle\Form\LocationType', $location);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('location_show', array('id' => $location->getId()));
        }

        return $this->render('location/edit.html.twig', array(
            'location' => $location,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a location entity.
     *
     * @Route("/{id}", name="location_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, Location $location)
    {
        $form = $this->createDeleteForm($location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($location);
            $em->flush($location);
        }

        return $this->redirectToRoute('location_index');
    }

    /**
     * Finds and displays the gedmo loggable history
     *
     * @Route("/{id}/log", name="location_log")
     */
    public function showLogAction(Request $request, $access, $id)
    {
        return  $this->showLogPage($request,$access, "CrewCallBundle:Location", $id);
    }

    /**
     * Creates a form to delete a location entity.
     *
     * @param Location $location The location entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Location $location)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('location_delete', array('id' => $location->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
