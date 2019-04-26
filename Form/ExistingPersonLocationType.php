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

class ExistingPersonLocationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
           ->add('person', UsernameFormType::class, array('label' => "Search person, name or email address", 'required' => true))
           ->add('location', EntityType::class,
               array('class' => 'CrewCallBundle:Location'))
           ->add('function', EntityType::class,
               array('class' => 'CrewCallBundle:FunctionEntity',
                   'query_builder' => function(EntityRepository $er) use ($options) {
                       $er->setReturnQb(true);
                       return $er->findByFunctionGroup('Location');
                   },
               ))
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CrewCallBundle\Entity\PersonFunctionLocation'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'crewcallbundle_pfl';
    }
}