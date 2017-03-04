<?php

namespace CrewCallBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use CrewCallBundle\Entity\EmbeddableAddress;

class AddressType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('countryCode')
            ->add('addressLine1')
            ->add('addressLine2')
            ->add('postalCode')
            ->add('postalName')
            ->add('locality')
            ->add('dependentLocality')
            ->add('sortingCode')
            ->add('administrativeArea')
            ->add('locale')
            ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => EmbeddableAddress::class
        ));
    }
}
