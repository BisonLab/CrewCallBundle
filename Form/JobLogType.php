<?php

namespace CrewCallBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use CrewCallBundle\Lib\ExternalEntityConfig;

class JobLogType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
           ->add('in', DateTimeType::class, array(
                'label' => "In",
                'date_widget' => "single_text",
                'time_widget' => "single_text",
                'attr' => array('autofocus' => true, 'tabindex' => 0)))
           ->add('out', DateTimeType::class, array(
                'label' => "Out",
                'date_widget' => "single_text",
                'time_widget' => "single_text",
                'attr' => array('tabindex' => 1)))
           ->add('break_minutes', IntegerType::class, array(
                'label' => "Break",
                'attr' => array('size'=> 3, 'tabindex' => 2)))
           ->add('job', EntityType::class,
                array('class' => 'CrewCallBundle:Job'))
           ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CrewCallBundle\Entity\JobLog',
            'allow_extra_fields' => true
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'crewcallbundle_joblog';
    }
}
