<?php

namespace CrewCallBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use CrewCallBundle\Lib\ExternalEntityConfig;
use CrewCallBundle\Form\AddressType;

class NewPersonType extends PersonType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        // Must have an initial function.
        $builder->add('function', EntityType::class,
               array('class' => 'CrewCallBundle:FunctionEntity',
                    'required' => true,
                    'mapped' => false,
                    'placeholder' => "Required",
                    'label' => "First function.",
                    'query_builder' => function(EntityRepository $er) use ($options) {
                       $er->setReturnQb(true);
                       return $er->findByFunctionGroup('Shift');
                   },
               ));
        if ($options['internal_organization_config']['allow_external_crew']) {
            $builder->add('role', EntityType::class,
               array('class' => 'CrewCallBundle:FunctionEntity',
                     'required' => true,
                     'mapped' => false,
                     'preferred_choices' => [$options['role']],
                     'label' => "Role.",
                     'query_builder' => function(EntityRepository $er) use ($options) {
                        $er->setReturnQb(true);
                        return $er->findByFunctionGroup('Organization');
                   },
                ))
                ->add('organization', EntityType::class,
                    array('class' => 'CrewCallBundle:Organization',
                     'required' => true,
                     'mapped' => false,
                     'preferred_choices' => [$options['organization']],
                     'label' => "Organization.",
                     ))
                ;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CrewCallBundle\Entity\Person',
            'internal_organization_config' => [],
            'addressing_config' => [],
            'address_elements' => [],
            'role' => null,
            'organization' => null
        ));
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';

        // Or for Symfony < 2.8
        // return 'fos_user_registration';
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'crewcallbundle_person';
    }
}
