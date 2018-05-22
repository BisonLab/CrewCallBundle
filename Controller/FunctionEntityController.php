<?php

namespace CrewCallBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;

use CrewCallBundle\Entity\FunctionEntity;
use CrewCallBundle\Entity\PersonFunction;

/**
 * Functionentity controller.
 *
 * @Route("/admin/{access}/function", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class FunctionEntityController extends CommonController
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
     * Lists all functionEntity entities in a pickable fasion
     *
     * @Route("/picker", name="function_picker")
     * @Method("GET")
     */
    public function pickerAction(Request $request, $access)
    {
        $em = $this->getDoctrine()->getManager();

        $functionEntities = $em->getRepository('CrewCallBundle:FunctionEntity')->findAll();

        $has_functions = $has_f_ids = array();
        $add_to = null;
        if ($person_id = $request->get('person_id')) {
            $person = $em->getRepository('CrewCallBundle:Person')->find($person_id);
            $update = "PersonFunction";
            $update_id = $person_id;
            foreach ($person->getPersonFunctions() as $hf) {
                $has_functions[] = $hf->getFunction();
                $has_f_ids[] = $hf->getFunction()->getId();
            }
        }

        $update_form = $this->createFormBuilder()
            ->setAction($this->generateUrl('picked_functions_updater',
                array('update' => $update, 'update_id' => $update_id)))
            ->setMethod('POST')
            ->getForm()
        ;

        $pickerparams = array(
            'functionEntities' => $functionEntities,
            'has_functions' => $has_functions,
            'has_f_ids' => $has_f_ids,
            'add_to' => $add_to,
            'update_form' => $update_form->createView(),
        );

        if ($this->isRest($access)) {
            return $this->render('functionentity/_picker.html.twig', $pickerparams);
        }
        return $this->render('functionentity/picker.html.twig', array(
            'pickerparams' => $pickerparams));
    }

    /**
     * Update the functions list on entities having them.
     *
     * @Route("/update_picked/{update}/{update_id}", name="picked_functions_updater")
     * @Method("POST")
     */
    public function updatePickedAction(Request $request, $update, $update_id)
    {
        $em = $this->getDoctrine()->getManager();
        // So, let's handle these based on what to update.
        if ($update == "PersonFunction") {
            $person = $em->getRepository('CrewCallBundle:Person')->find($update_id);
            $has_functions = $request->request->get('has_functions');
            $pfs = array();
            foreach ($person->getPersonFunctions() as $pf) { 
                if (!in_array($pf->getFunctionId(), $has_functions)) {
                    $em->remove($pf);
                }
                $pfs[] = $pf->getFunctionId();
            }
            foreach ($has_functions as $hf) {
                if (!in_array($hf, $pfs)) {
                    $function = $em->getRepository('CrewCallBundle:FunctionEntity')->find($hf);
                    $pf = new PersonFunction();
                    $pf->setPerson($person);
                    $pf->setFunction($function);
                    $pf->setFromDate(new \DateTime());
                    $em->persist($pf);
                }
            }
            $em->flush();
            return $this->redirectToRoute('person_show', array('id' => $update_id));
        }
        // Let the submitter decide what to do.
        return new JsonResponse(array("status" => "OK"), Response::HTTP_CREATED);
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

            return $this->redirectToRoute('function_show', array('id' => $functionEntity->getId()));
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
