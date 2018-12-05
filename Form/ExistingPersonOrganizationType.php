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
           ->add('person', UsernameFormType::class, array('label' => "Person", 'required' => true))
           ->add('organization', EntityType::class,
               array('class' => 'CrewCallBundle:Organization'))
           ->add('function', EntityType::class,
               array('class' => 'CrewCallBundle:FunctionEntity',
                   'group_by' => 'parent.name',
                   'query_builder' => function(EntityRepository $er) use ($options) {
                   return $er->createQueryBuilder('f')
                    ->where("f.state = 'VISIBLE'")
                    ->andWhere("f.parent in (:parents)")
                    ->orderBy('f.name', 'ASC')
                    ->setParameter('parents', $options['parent_functions']);
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
            'parent_functions' => [],
            'data_class' => 'CrewCallBundle\Entity\PersonFunctionOrganization'
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
