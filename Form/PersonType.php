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
            ->add('state', ChoiceType::class, array('label' => 'Status', 'choices' => ExternalEntityConfig::getStatesAsChoicesFor('Person')))
           ->add('system_roles', ChoiceType::class,
                array(
                    'label' =>  'User type',
                    'multiple' =>  true,
                    'choices' => ExternalEntityConfig::getSystemRolesAsChoices('with_description'),
                )
            )
            ->add('address', AddressType::class, ['address_elements' => $options['address_elements']])
            ;

        if ($options['addressing_config']['use_postal_address']) {
            $builder
                ->add('postal_address', AddressType::class, ['address_elements' => $options['address_elements']])
            ;
        }
        $builder->remove('plainPassword');
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CrewCallBundle\Entity\Person',
            'address_elements' => [],
            'addressing_config' => []
        ));
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'crewcallbundle_person';
    }
}
