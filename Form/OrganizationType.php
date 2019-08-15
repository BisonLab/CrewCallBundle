<?php

namespace CrewCallBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use CrewCallBundle\Form\AddressType;
use CrewCallBundle\Lib\ExternalEntityConfig;

class OrganizationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('organization_number')
            ->add('office_phone_number')
            ->add('office_email')
            ->add('state', ChoiceType::class, array(
              'label' => 'Status',
              'choices' => ExternalEntityConfig::getStatesAsChoicesFor('Organization')))
            // ->add('attributes')
            ->add('visit_address', AddressType::class, ['address_elements' => $options['address_elements']])
            ->add('postal_address', AddressType::class, ['address_elements' => $options['address_elements']])
           ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CrewCallBundle\Entity\Organization',
            'address_elements' => []
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'crewcallbundle_organization';
    }


}
