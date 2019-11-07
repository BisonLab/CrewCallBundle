<?php

namespace CrewCallBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use FOS\UserBundle\Form\Type\UsernameFormType;

use CrewCallBundle\Lib\ExternalEntityConfig;

class NewPersonLocationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
           ->add('email', EmailType::class, array('label' => "E-mail", 'required' => false))
           ->add('first_name', TextType::class, array('label' => "First name", 'required' => true))
           ->add('last_name', TextType::class, array('label' => "Last name", 'required' => true))
           ->add('mobile_phone_number', TextType::class, array('label' => "Phone number", 'required' => true))
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
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'crewcallbundle_add_new_person_loc';
    }
}
