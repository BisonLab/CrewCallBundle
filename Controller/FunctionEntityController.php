<?php

namespace CrewCallBundle\Controller;

use CrewCallBundle\Entity\FunctionEntity;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;use Symfony\Component\HttpFoundation\Request;

/**
 * Functionentity controller.
 *
 * @Route("function")
 */
class FunctionEntityController extends Controller
{
    /**
     * Lists all functionEntity entities.
     *
     * @Route("/", name="function_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $functionEntities = $em->getRepository('CrewCallBundle:FunctionEntity')->findAll();

        return $this->render('functionentity/index.html.twig', array(
            'functionEntities' => $functionEntities,
        ));
    }

    /**
     * Creates a new functionEntity entity.
     *
     * @Route("/new", name="function_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $functionEntity = new Functionentity();
        $form = $this->createForm('CrewCallBundle\Form\FunctionEntityType', $functionEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($functionEntity);
            $em->flush($functionEntity);

            return $this->redirectToRoute('function_show', array('id' => $functionEntity->getId()));
        }

        return $this->render('functionentity/new.html.twig', array(
            'functionEntity' => $functionEntity,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a functionEntity entity.
     *
     * @Route("/{id}", name="function_show")
     * @Method("GET")
     */
    public function showAction(FunctionEntity $functionEntity)
    {
        $deleteForm = $this->createDeleteForm($functionEntity);

        return $this->render('functionentity/show.html.twig', array(
            'functionEntity' => $functionEntity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing functionEntity entity.
     *
     * @Route("/{id}/edit", name="function_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, FunctionEntity $functionEntity)
    {
        $deleteForm = $this->createDeleteForm($functionEntity);
        $editForm = $this->createForm('CrewCallBundle\Form\FunctionEntityType', $functionEntity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('function_edit', array('id' => $functionEntity->getId()));
        }

        return $this->render('functionentity/edit.html.twig', array(
            'functionEntity' => $functionEntity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a functionEntity entity.
     *
     * @Route("/{id}", name="function_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, FunctionEntity $functionEntity)
    {
        $form = $this->createDeleteForm($functionEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($functionEntity);
            $em->flush($functionEntity);
        }

        return $this->redirectToRoute('function_index');
    }

    /**
     * Creates a form to delete a functionEntity entity.
     *
     * @param FunctionEntity $functionEntity The functionEntity entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(FunctionEntity $functionEntity)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('function_delete', array('id' => $functionEntity->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
