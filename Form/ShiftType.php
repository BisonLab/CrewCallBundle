<?php

namespace CrewCallBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

use CrewCallBundle\Lib\ExternalEntityConfig;

class ShiftType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
           ->add('from_time', DateTimeType::class, array('date_widget' => "single_text", 'time_widget' => "single_text"))
           ->add('to_time', DateTimeType::class, array('date_widget' => "single_text", 'time_widget' => "single_text"))
           ->add('state', ChoiceType::class, array(
              'choices' => ExternalEntityConfig::getStatesAsChoicesFor('Shift')))
           ->add('event')
           ->add('location')
           ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CrewCallBundle\Entity\Shift'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'crewcallbundle_shift';
    }


}
