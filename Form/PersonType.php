<?php

namespace CrewCallBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use CrewCallBundle\Lib\ExternalEntityConfig;
use CrewCallBundle\Form\AddressType;

class PersonType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('first_name')
            ->add('last_name')
            ->add('date_of_birth')
            ->add('mobile_phone_number')
            ->add('home_phone_number')
            ->add('state', ChoiceType::class, array('choices' => ExternalEntityConfig::getStatesAsChoicesFor('Person')))
            ->add('roles', ChoiceType::class,
                array(
                    'multiple' =>  true,
                    'choices' =>
                        array(
                            'Person' => 'ROLE_PERSON',
                            'Ordinary user' => 'ROLE_USER',
                            'Admin' => 'ROLE_ADMIN'
                            )
                )
            )
            // ->add('attributes')
            ->add('address', AddressType::class)
            ->add('postal_address', AddressType::class)
            ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CrewCallBundle\Entity\Person'
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
