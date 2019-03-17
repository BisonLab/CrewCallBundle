<?php

namespace CrewCallBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use FOS\UserBundle\Form\Type\UsernameFormType;

use CrewCallBundle\Lib\ExternalEntityConfig;

class PersonEventType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
           ->add('person', UsernameFormType::class, array(
                    'label' => "Person",
                    'required' => true
                ))
           ->add('event', EntityType::class, array(
                    'class' => 'CrewCallBundle:Event',
                    'required' => true,
                ))
           ->add('function', EntityType::class, array(
                    'class' => 'CrewCallBundle:FunctionEntity',
                    'required' => true,
               ))
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CrewCallBundle\Entity\PersonFunctionEvent'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'crewcallbundle_pfe';
    }
}