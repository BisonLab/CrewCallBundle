<?php

namespace CrewCallBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
    }

    /**
     * @Route("/global_search", name="global_search")
     */
    public function globalSearchAction(Request $request)
    {
        $value = $request->get('value');
        if (empty($value))
            return $this->redirect($this->generateUrl('homepage'));

        $em = $this->getDoctrine()->getManager();

        // For now, always web.
        $access = "web";
        $events = new \Doctrine\Common\Collections\ArrayCollection();
        $persons = new \Doctrine\Common\Collections\ArrayCollection();
        $locations = new \Doctrine\Common\Collections\ArrayCollection();
        $organizations = new \Doctrine\Common\Collections\ArrayCollection();

        foreach (array('name', 'description') as $field) {
            $result = $em->getRepository('CrewCallBundle:Event')
                        ->searchByField($field, trim($value));
            foreach ($result as $i) {
                if (!$events->contains($i))
                    $events->add($i);
            }
        }

        foreach (array('username', 'full_name', 'mobile_phone_number') as $field) {
            $result = $em->getRepository('CrewCallBundle:Person')
                        ->searchByField($field, trim($value));
            foreach ($result as $i) {
                if (!$persons->contains($i))
                    $persons->add($i);
            }
        }

        // A direct hit.
        if ($persons->count() == 1 && (string)$persons->first() == $value) {
            return $this->redirect($this->generateUrl('person_show',
                array('id' => $persons->first()->getId(), 'access' => $access)));
        }

        foreach (array('name', 'description') as $field) {
            $result = $em->getRepository('CrewCallBundle:Location')
                        ->searchByField($field, trim($value));
            foreach ($result as $i) {
                if (!$locations->contains($i))
                    $locations->add($i);
            }
        }

        foreach (array('name', 'organization_number') as $field) {
            $result = $em->getRepository('CrewCallBundle:Organization')
                        ->searchByField($field, trim($value));
            foreach ($result as $i) {
                if (!$organizations->contains($i))
                    $organizations->add($i);
            }
        }

        $zilch = $value;
        if ($events->count() > 0
                || $persons->count() > 0
                || $locations->count() > 0
                || $organizations->count() > 0)
            $zilch = false;

        return $this->render('CustomBundle:Default:globalSearchResult.html.twig',
            array(
                'value'  => $value,
                'zilch'  => $zilch,
                'events'  => $events,
                'persons'  => $persons,
                'locations'  => $locations,
                'organizations' => $organizations,
            ));
    }

    /**
     * @Route("/ping", name="ping")
     */
    public function pingAction(Request $request)
    {
        $response = $request->request->all();
        $response = array_merge($response, $request->query->all());
        $response['response'] = "ACK";
        $response['code'] = "42";
        return $this->returnRestData($request, $response);
    }
}
