<?php

namespace CrewCallBundle\Controller;

use CrewCallBundle\Entity\Organization;
use CrewCallBundle\Entity\PersonFunctionOrganization;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;

/**
 * Organization controller.
 *
 * @Route("/admin/{access}/organization", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class OrganizationController extends CommonController
{
    /**
     * Lists all organization entities.
     *
     * @Route("/", name="organization_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $organizations = $em->getRepository('CrewCallBundle:Organization')->findAll();

        return $this->render('organization/index.html.twig', array(
            'organizations' => $organizations,
        ));
    }

    /**
     * Creates a new organization entity.
     *
     * @Route("/new", name="organization_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $organization = new Organization();
        $form = $this->createForm('CrewCallBundle\Form\OrganizationType', $organization);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($organization);
            $em->flush($organization);

            return $this->redirectToRoute('organization_show', array('id' => $organization->getId()));
        }

        return $this->render('organization/new.html.twig', array(
            'organization' => $organization,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a organization entity.
     *
     * @Route("/{id}", name="organization_show")
     * @Method("GET")
     */
    public function showAction(Organization $organization)
    {
        $deleteForm = $this->createDeleteForm($organization);

        return $this->render('organization/show.html.twig', array(
            'delete_form' => $deleteForm->createView(),
            'organization' => $organization,
        ));
    }

    /**
     * Displays a form to edit an existing organization entity.
     *
     * @Route("/{id}/edit", name="organization_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Organization $organization)
    {
        $deleteForm = $this->createDeleteForm($organization);
        $editForm = $this->createForm('CrewCallBundle\Form\OrganizationType', $organization);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('organization_show', array('id' => $organization->getId()));
        }

        return $this->render('organization/edit.html.twig', array(
            'organization' => $organization,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a organization entity.
     *
     * @Route("/{id}", name="organization_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Organization $organization)
    {
        $form = $this->createDeleteForm($organization);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($organization);
            $em->flush($organization);
        }

        return $this->redirectToRoute('organization_index');
    }

    /**
     * Finds and displays the gedmo loggable history
     *
     * @Route("/{id}/log", name="organization_log")
     */
    public function showLogAction(Request $request, $access, $id)
    {
        return  $this->showLogPage($request,$access, "CrewCallBundle:Organization", $id);
    }

    /**
     * Creates a form to delete a organization entity.
     *
     * @param Organization $organization The organization entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Organization $organization)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('organization_delete', array('id' => $organization->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Creates a new personFunctionOrganization entity.
     * Pure REST/AJAX.
     *
     * @Route("/{id}/add_exitsting_person", name="organization_add_existing_person")
     * @Method({"GET", "POST"})
     */
    public function addExistingPersonAction(Request $request, Organization $organization, $access)
    {
        $em = $this->getDoctrine()->getManager();
        $fgroups = $this->getParameter('crewcall.functiongroups');
        $fparents = [];
        foreach ($fgroups['Organization'] as $f) { 
            $fparents[] = $em->getRepository('CrewCallBundle:FunctionEntity')
                ->findOneByName($f);
        }
        $pfo = new PersonFunctionOrganization();
        $pfo->setOrganization($organization);
        $form = $this->createForm('CrewCallBundle\Form\ExistingPersonOrganizationType', $pfo, array('parent_functions' => $fparents));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($pfo);
            $em->flush($pfo);

            if ($this->isRest($access)) {
                return new JsonResponse(array("status" => "OK"), Response::HTTP_CREATED);
            } else {
                return $this->redirectToRoute('organization_show', array('id' => $organization->getId()));
            }
        }

        if ($this->isRest($access)) {
            return $this->render('organization/_new_pfo.html.twig', array(
                'pfo' => $pfo,
                'organization' => $organization,
                'form' => $form->createView(),
            ));
        }
    }

    /**
     * Removes a personFunctionOrganization entity.
     * Pure REST/AJAX.
     *
     * @Route("/{id}/remove_person", name="organization_remove_person")
     * @Method({"GET", "DELETE", "POST"})
     */
    public function removePersonAction(Request $request, PersonFunctionOrganization $pfo, $access)
    {
        $organization = $pfo->getOrganization();
        $em = $this->getDoctrine()->getManager();
        $em->remove($pfo);
        $em->flush($pfo);
        if ($this->isRest($access)) {
            return new JsonResponse(array("status" => "OK"),
                Response::HTTP_OK);
        }
        return $this->redirectToRoute('organization_show',
            array('id' => $organization->getId()));
    }
}
