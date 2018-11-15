<?php

namespace CrewCallBundle\Controller;

use CrewCallBundle\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use BisonLab\CommonBundle\Controller\CommonController as CommonController;

/**
 * Summary / Summarizer controller.
 *
 * @Route("/admin/{access}/summary", defaults={"access" = "web"}, requirements={"web|rest|ajax"})
 */
class SummaryController extends CommonController
{
    /**
     * Summary with proper path.
     *
     * @Route("/entity/{entity}/entity_id/{id}", name="summary_show", methods={"GET"})
     */
    public function showAction(Request $request, $access, $entity, $id)
    {
        return $this->_show($request, $access, $entity, $id);
    }

    /**
     *
     * @Route("/", name="summary_show_get", methods={"GET"})
     */
    public function getAction(Request $request, $access)
    {
        if (!$entity = $request->get("entity"))
            return $this->returnNotFound($request, 'Unable to find entity.');
        if (!$id = $request->get("entity_id"))
            return $this->returnNotFound($request, 'Unable to find entity.');

        return $this->_show($request, $access, $entity, $id);
    }

    private function _show($request, $access, $entity, $id)
    {
        $em = $this->getDoctrine()->getManager();
        // Switch it.
        switch ($entity) {
            case 'person':
                $entity = $em->getRepository('CrewCallBundle:Person')->find($id);
                break;
            case 'event':
                $entity = $em->getRepository('CrewCallBundle:Event')->find($id);
                break;
            default:
                return $this->returnNotFound($request,'Unable to find class.');
                break;
        }

        if (!$entity) {
            return $this->returnNotFound($request, 'Unable to find entity.');
        }
        $summary = $this->get('crewcall.summarizer')->summarize($entity, $access);
        if ($this->isRest($access)) {
            return $this->returnRestData($request, $summary,
                array('html' => 'CrewCallBundle::summaryPopContent.html.twig'));
        }
    }

    /*
     * Showing the content of Gedmo Loggable. As a kinda summary.
     * (Rationalizing why it's even here)
     */

    /**
     *
     * @Route("/log", name="summary_show_log", methods={"GET"})
     */
    public function logSummaryAction(Request $request, $access)
    {
        if (!$entity = $request->get("entity"))
            return $this->returnNotFound($request, 'No entity.');
        if (!$id = $request->get("entity_id"))
            return $this->returnNotFound($request, 'No entity_id.');

        return $this->_showLogSummary($request, $access, $entity, $id);
    }

    private function _showLogSummary($request, $access, $entity, $id)
    {
        // Switch it.
        switch ($entity) {
            case 'shift':
                $class = 'CrewCallBundle:Shift';
                break;
            case 'organization':
                $class = 'CrewCallBundle:Organization';
                break;
            case 'location':
                $class = 'CrewCallBundle:Location';
                break;
            case 'person':
                $class = 'CrewCallBundle:Person';
                break;
            case 'event':
                $class = 'CrewCallBundle:Event';
                break;
            default:
                return $this->returnNotFound($request,'Unable to find class.');
                break;
        }
        return  $this->showLogPage($request,$access, $class, $id,
            ['html' => 'CrewCallBundle::summaryLogPopContent.html.twig']);
    }
}
