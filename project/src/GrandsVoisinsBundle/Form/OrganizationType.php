<?php

namespace GrandsVoisinsBundle\Form;

use GrandsVoisinsBundle\GrandsVoisinsConfig;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use VirtualAssembly\SemanticFormsBundle\Form\DbPediaType;
use VirtualAssembly\SemanticFormsBundle\Form\UriType;
use VirtualAssembly\SemanticFormsBundle\SemanticFormsBundle;

class OrganizationType extends AbstractForm
{


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // This will manage form specification.
        parent::buildForm($builder, $options);

        $this
          ->add($builder, 'name', TextType::class)
          ->add($builder, 'administrativeName', TextType::class)
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
              'shortDescription',
              TextType::class,
              [
                  'required' => false,
              ]
          )
          ->add(
            $builder,
            'proposedContribution',
            TextareaType::class,
            [
              'required' => false,
            ]
          )
          ->add(
            $builder,
            'realisedContribution',
            TextareaType::class,
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
            'conventionType',
            TextType::class,
            [
              'required' => false,
            ]
          )
          ->add(
            $builder,
            'employeesCount',
            NumberType::class,
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
            'mbox',
            EmailType::class,
            [
              'required' => false,
            ]
          )
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
            'facebook',
            UrlType::class,
            [
              'required' => false,
            ]
          )
          ->add(
            $builder,
            'twitter',
            UrlType::class,
            [
              'required' => false,
            ]
          )
          ->add(
            $builder,
            'linkedin',
            UrlType::class,
            [
              'required' => false,
            ]
          )
          ->add(
            $builder,
            'head',
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
              'OrganizationalCollaboration',
              UriType::class,
              [
                  'required'  => false,
                  'lookupUrl' => $options['lookupUrlPerson'],
                  'labelUrl'  => $options['lookupUrlLabel'],
                  'rdfType'   => GrandsVoisinsConfig::URI_FOAF_ORGANIZATION,
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
            'hasMember',
            UriType::class,
            [
              'required'  => false,
              'lookupUrl' => $options['lookupUrlPerson'],
              'labelUrl'  => $options['lookupUrlLabel'],
              'rdfType'   => implode('|',GrandsVoisinsConfig::URI_MIXTE_PERSON_ORGANIZATION),
            ]
          )
            ->add(
                $builder,
                'contributionType',
                TextType::class,
                [
                    'required' => false,
                ]
            )
					->add(
						$builder,
						'room',
						TextType::class,
						[
							'required' => false,
						]
					)
					->add(
						$builder,
						'building',
						ChoiceType::class,
						[
							'placeholder' => 'choisissez un batiment',
							'choices' => array_flip(GrandsVoisinsConfig::$buildingsSimple),
						]
					)
					->add(
						$builder,
						'documentedBy',
						UriType::class,
						[
							'required'  => false,
							'lookupUrl' => $options['lookupUrlPerson'],
							'labelUrl'  => $options['lookupUrlLabel'],
							'rdfType'   => GrandsVoisinsConfig::URI_PAIR_DOCUMENT,
						]
					)
				;

        //dump(array_flip($options['role']));
        //filter for field only available for aurore
        if(array_key_exists('ROLE_SUPER_ADMIN',array_flip($options['role']))){
        //if(contains('ROLE_SUPER_ADMIN',$opt1ions['role'])){
            $this
                ->add(
                    $builder,
                    'leavingDate',
                    DateType::class,
                    [
                        'required' => false,
                        'widget'   => 'choice',
													'placeholder' => array(
														'year' => 'Year', 'month' => 'Month', 'day' => 'Day'
													),
                        'format' => 'dd/MM/yyyy',
                    ]
                )
                ->add(
                    $builder,
                    'newLocation',
                    TextType::class,
                    [
                        'required' => false,
                    ]
                )
                ->add(
                    $builder,
                    'arrivalDate',
                    DateType::class,
                    [
                        'required' => false,
                        'widget'   => 'choice',
                        'format' => 'dd/MM/yyyy',
                        'years' => range(date('Y') -10, date('Y')+5),
                    ]
                )
                ->add(
                    $builder,
                    'status',
                    TextType::class,
                    [
                        'required' => false,
                    ]
                )
                ->add(
                    $builder,
                    'arrivalNumber',
                    TextType::class,
                    [
                        'required' => false,
                    ]
                )
                ->add(
                    $builder,
                    'insuranceStatus',
                    TextType::class,
                    [
                        'required' => false,
                    ]
                )
//                ->add(
//                    $builder,
//                    'haveBenefitOf',
//                    TextareaType::class,
//                    [
//                        'required' => false,
//                    ]
//                )
            ;
        }

        $builder->add(
          'organisationPicture',
          FileType::class,
          [
            'data_class' => null,
            'required'   => false,
          ]
        );

        $builder->add('save', SubmitType::class, ['label' => 'Enregistrer']);
    }
}
