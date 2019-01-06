<?php

namespace CrewCallBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ShiftOrganizationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
           ->add('amount', TextType::class, array('required' => true, 'attr' => array('size' => 3, 'pattern' => '[0-9]{1,3}')))
            ->add('shift')
            ->add('organization', EntityType::class,
                array('class' => 'CrewCallBundle:Organization',
                    'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('o')
                     ->where("o.state = 'ACTIVE'")
                     ->orderBy('o.name', 'ASC');
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
            'data_class' => 'CrewCallBundle\Entity\ShiftOrganization'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'crewcallbundle_shiftorganization';
    }


}
