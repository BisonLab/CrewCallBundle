<?php

namespace CrewCallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;

use CrewCallBundle\Lib\ExternalEntityConfig;
use CrewCallBundle\Entity\Role;

/**
 * Functionentity controller.
 *
 * @Route("/admin/{access}/role", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class RoleController extends CommonController
{
    /**
     * Lists all Role entities.
     *
     * @Route("/", name="role_index", methods={"GET"})
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $roles = $em->getRepository('CrewCallBundle:Role')->findAll();

        return $this->render('role/index.html.twig', array(
            'roles' => $roles,
        ));
    }

    /**
     * Creates a new Role entity.
     *
     * @Route("/new", name="role_new", methods={"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $role = new Role();
        $form = $this->createForm('CrewCallBundle\Form\RoleType', $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($role);
            $em->flush($role);

            return $this->redirectToRoute('role_show', array('id' => $role->getId()));
        }

        return $this->render('role/new.html.twig', array(
            'role' => $role,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Role entity.
     *
     * @Route("/{id}", name="role_show", methods={"GET"})
     */
    public function showAction(Role $Role)
    {
        $deleteForm = $this->createDeleteForm($Role);

        return $this->render('role/show.html.twig', array(
            'role' => $Role,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Role entity.
     *
     * @Route("/{id}/edit", name="role_edit", methods={"GET", "POST"})
     */
    public function editAction(Request $request, Role $Role)
    {
        $deleteForm = $this->createDeleteForm($Role);
        $editForm = $this->createForm('CrewCallBundle\Form\RoleType', $Role);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('role_show', array('id' => $Role->getId()));
        }

        return $this->render('role/edit.html.twig', array(
            'role' => $Role,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Role entity.
     *
     * @Route("/{id}", name="role_delete", methods={"DELETE"})
     */
    public function deleteAction(Request $request, Role $Role)
    {
        $form = $this->createDeleteForm($Role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($Role);
            $em->flush($Role);
        }
        return $this->redirectToRoute('role_index');
    }

    /**
     * Creates a form to delete a Role entity.
     *
     * @param Role $Role The Role entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Role $Role)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('role_delete', array('id' => $Role->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
