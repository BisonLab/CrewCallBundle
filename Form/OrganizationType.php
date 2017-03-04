<?php

namespace CrewCallBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use CrewCallBundle\Form\AddressType;

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
            ->add('state')
            ->add('attributes')
            ->add('visit_address', AddressType::class)
            ->add('postal_address', AddressType::class)
           ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CrewCallBundle\Entity\Organization'
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
