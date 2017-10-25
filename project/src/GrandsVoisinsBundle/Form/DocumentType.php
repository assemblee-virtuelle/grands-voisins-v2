<?php

namespace GrandsVoisinsBundle\Form;

use GrandsVoisinsBundle\GrandsVoisinsConfig;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use VirtualAssembly\SemanticFormsBundle\Form\UriType;

class DocumentType extends AbstractForm
{
		var $fieldsAliases = [
			'http://assemblee-virtuelle.github.io/mmmfest/PAIR_temp.owl#preferedLabel' 			=> 'preferedLabel', # txt
			'http://assemblee-virtuelle.github.io/mmmfest/PAIR_temp.owl#alternativeLabel' 	=> 'alternativeLabel', # txt
			'http://assemblee-virtuelle.github.io/mmmfest/PAIR_temp.owl#description' 				=> 'description', # txt
			'http://assemblee-virtuelle.github.io/mmmfest/PAIR_temp.owl#comment' 						=> 'comment', # txt
			'http://assemblee-virtuelle.github.io/mmmfest/PAIR_temp.owl#aboutPage' 					=> 'aboutPage', # url
			'http://assemblee-virtuelle.github.io/mmmfest/PAIR_temp.owl#homePage' 					=> 'homePage', # url
			#'http://assemblee-virtuelle.github.io/mmmfest/PAIR_temp.owl#represents' 				=> 'represents', # sf (doc)
			'http://assemblee-virtuelle.github.io/mmmfest/PAIR_temp.owl#documents' 				=> 'documents', # sf (doc)
			'http://assemblee-virtuelle.github.io/mmmfest/PAIR_temp.owl#references' 				=> 'references', # sf (doc)
			'http://assemblee-virtuelle.github.io/mmmfest/PAIR_temp.owl#hasType' 						=> 'hasType', # sf (docType)
			'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'                               => 'type',
			'http://assemblee-virtuelle.github.io/grands-voisins-v2/gv.owl.ttl#thesaurus'        => 'thesaurus',

		];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // This will manage form specification.
        parent::buildForm($builder, $options);

        $this
					->add($builder, 'preferedLabel', TextType::class)
					->add($builder, 'alternativeLabel', TextType::class, ['required' => false,])
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
						'comment',
						TextType::class,
						[
							'required' => false,
						]
					)
					->add(
						$builder,
						'homePage',
						UrlType::class,
						[
							'required' => false,
						]
					)
					->add(
						$builder,
						'aboutPage',
						UrlType::class,
						[
							'required' => false,
						]
					)
					->add(
						$builder,
						'references',
						UriType::class,
						[
							'required'  => false,
							'lookupUrl' => $options['lookupUrlPerson'],
							'labelUrl'  => $options['lookupUrlLabel'],
							'rdfType'   => GrandsVoisinsConfig::URI_PAIR_DOCUMENT,
						]
					)
					->add(
						$builder,
						'documents',
						UriType::class,
						[
							'required'  => false,
							'lookupUrl' => $options['lookupUrlPerson'],
							'labelUrl'  => $options['lookupUrlLabel'],
							'rdfType'   =>  implode('|',GrandsVoisinsConfig::URI_ALL_EXCEPT_DOC_TYPE),
						]
					)
					->add(
						$builder,
						'hasType',
						UriType::class,
						[
							'required'  => false,
							'lookupUrl' => $options['lookupUrlPerson'],
							'labelUrl'  => $options['lookupUrlLabel'],
							'rdfType'   => GrandsVoisinsConfig::URI_PAIR_DOCUMENT_TYPE,
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
				;

        $builder->add('save', SubmitType::class, ['label' => 'Enregistrer']);
    }
}
