<?php

namespace GrandsVoisinsBundle\Form;

use GrandsVoisinsBundle\GrandsVoisinsConfig;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use VirtualAssembly\SemanticFormsBundle\Form\DbPediaType;
use VirtualAssembly\SemanticFormsBundle\Form\UriType;
use VirtualAssembly\SemanticFormsBundle\SemanticFormsBundle;

class ProfileType extends AbstractForm
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        // This will manage form specification.
        parent::buildForm($builder, $options);

        $this
            ->add($builder, 'givenName', TextType::class)
            ->add($builder, 'familyName', TextType::class)
            ->add(
                $builder,
                'homepage',
                UrlType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                $builder,
                'mbox',
                EmailType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                $builder,
                'phone',
                TextType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                $builder,
                'shortDescription',
                TextType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                $builder,
                'description',
                TextareaType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                $builder,
                'expertise',
                DbPediaType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                $builder,
                'topicInterest',
                DbPediaType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                $builder,
                'knows',
                UriType::class,
                [
                    'required'  => false,
                    'lookupUrl' => $options['lookupUrlPerson'],
                    'labelUrl'  => $options['lookupUrlLabel'],
                    'rdfType'   => GrandsVoisinsConfig::URI_FOAF_PERSON,
                ]
            )
            ->add(
                $builder,
                'slack',
                TextType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                $builder,
                'birthday',
                DateType::class,
                [
                    'required' => false,
                    'widget'   => 'choice',
                    'format' => 'dd/MM/yyyy',
                    'years' => range(date('Y') -150, date('Y')),
                ]
            )
            ->add(
                $builder,
                'postalCode',
                TextType::class,
                [
                    'required' => false,
                ]

            )
            ->add(
                $builder,
                'resourceNeeded',
                DbPediaType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                $builder,
                'resourceProposed',
                DbPediaType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                $builder,
                'thesaurus',
                UriType::class,
                [
                    'required'  => false,
                    'lookupUrl' => $options['lookupUrlPerson'],
                    'labelUrl'  => $options['lookupUrlLabel'],
                    'rdfType'   => GrandsVoisinsConfig::URI_SKOS_THESAURUS,
                ]
            )
            ->add(
                $builder,
                'city',
                DbPediaType::class,
                [
                    'required' => false,
                ]
            );

        $builder->add(
            'pictureName',
            FileType::class,
            [
                'data_class' => null,
                'required'   => false,
            ]
        );

        $builder->add('save', SubmitType::class, ['label' => 'Enregistrer']);
    }
}
