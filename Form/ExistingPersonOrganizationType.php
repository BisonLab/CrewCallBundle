<?php

namespace CrewCallBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use FOS\UserBundle\Form\Type\UsernameFormType;

use CrewCallBundle\Lib\ExternalEntityConfig;

class ExistingPersonOrganizationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
           ->add('person', UsernameFormType::class, array('label' => "Search with name, phone number or email address", 'required' => true))
           ->add('organization', EntityType::class,
               array('class' => 'CrewCallBundle:Organization'))
           ->add('role', EntityType::class,
               array('class' => 'CrewCallBundle:Role'))
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CrewCallBundle\Entity\PersonRoleOrganization'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'crewcallbundle_pfo';
    }
}
