<?php

namespace CrewCallBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use CrewCallBundle\Lib\ExternalEntityConfig;

class ShiftType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
           ->add('start', DateTimeType::class, array(
                'label' => "Start",
                'date_widget' => "single_text",
                'time_widget' => "single_text"))
           ->add('end', DateTimeType::class, array(
                'label' => "End",
                'date_widget' => "single_text",
                'time_widget' => "single_text"))
           ->add('state', ChoiceType::class, array(
              'choices' => ExternalEntityConfig::getStatesAsChoicesFor('Shift')))
           // ->add('amount', IntegerType::class, array('required' => true, 'attr' => array('size' => 3)))
           ->add('amount', TextType::class, array('required' => true, 'attr' => array('size' => 3, 'pattern' => '[0-9]{1,3}')))
           ->add('function', EntityType::class,
               array('class' => 'CrewCallBundle:FunctionEntity',
                   'choices' => $options['functions'],
/*
                   'group_by' => 'parent.name',
                   'query_builder' => function(EntityRepository $er) use ($options) {
                   return $er->createQueryBuilder('f')
                    ->where("f.state = 'VISIBLE'")
                    ->andWhere("f.parent in (:parents)")
                    ->orderBy('f.name', 'ASC')
                    ->setParameter('parents', $options['parent_functions']);
                   },
*/
               ))
            ->add('event')
           ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'functions' => [],
            'parent_functions' => [],
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
