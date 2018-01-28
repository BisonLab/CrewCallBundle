<?php

namespace CrewCallBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use CrewCallBundle\Lib\ExternalEntityConfig;

class EventType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('start', DateTimeType::class, array('label' => "Start",'date_widget' => "single_text", 'time_widget' => "single_text"))
            ->add('end', DateTimeType::class, array('label' => "End",'date_widget' => "single_text", 'time_widget' => "single_text"))
            ->add('state', ChoiceType::class, array(
                'choices' => ExternalEntityConfig::getStatesAsChoicesFor('Event')))
            ->add('location', EntityType::class,
                array('class' => 'CrewCallBundle:Location',
                    'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('o')
                     ->orderBy('o.name', 'ASC');
                    },
                ))
            ->add('organization', EntityType::class,
                array('class' => 'CrewCallBundle:Organization',
                    'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('o')
                     ->orderBy('o.name', 'ASC');
                    },
                ))
            ->add('parent')
           ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CrewCallBundle\Entity\Event'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'crewcallbundle_event';
    }
}
