<?php

namespace CrewCallBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;

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
            ->add('date_of_birth', BirthdayType::class, array('required' => false))
            ->add('diets', ChoiceType::class,array(
                'choices' => ExternalEntityConfig::getTypesAsChoicesFor('Person', 'Diet'),
                'expanded'  => true,
                'multiple'  => true,))
            ->add('mobile_phone_number')
            ->add('home_phone_number')
            ->add('state', ChoiceType::class, array('choices' => ExternalEntityConfig::getStatesAsChoicesFor('Person')))
            ->add('system_role', ChoiceType::class,
                array(
                    'expanded'  => true,
                    'multiple' =>  false,
                    'choices' => ExternalEntityConfig::getSystemRolesAsChoices(),
                    'preferred_choices' => 'ROLE_USER'
                )
            )
            // ->add('attributes')
            ->add('address', AddressType::class, ['address_elements' => $options['address_elements']])
            ->add('postal_address', AddressType::class, ['address_elements' => $options['address_elements']])
            ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CrewCallBundle\Entity\Person',
            'address_elements' => []
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
