<?php

namespace GrandsVoisinsBundle\Controller;

use VirtualAssembly\SparqlBundle\Services\SparqlClient;
use GrandsVoisinsBundle\GrandsVoisinsConfig;
use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use VirtualAssembly\SemanticFormsBundle\Services\SemanticFormsClient;

class WebserviceController extends Controller
{
		const TYPE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';

		var $entitiesTabs = [
      GrandsVoisinsConfig::URI_FOAF_ORGANIZATION => [
        'name'   => 'Organisation',
        'plural' => 'Organisations',
        'icon'   => 'tower',
				'nameType' => 'organization'
      ],
      GrandsVoisinsConfig::URI_FOAF_PERSON       => [
        'name'   => 'Personne',
        'plural' => 'Personnes',
        'icon'   => 'user',
				'nameType' => 'person'
      ],
      GrandsVoisinsConfig::URI_FOAF_PROJECT      => [
        'name'   => 'Projet',
        'plural' => 'Projets',
        'icon'   => 'screenshot',
				'nameType' => 'projet'
      ],
      GrandsVoisinsConfig::URI_PURL_EVENT        => [
        'name'   => 'Event',
        'plural' => 'Events',
        'icon'   => 'calendar',
				'nameType' => 'event'
      ],
      GrandsVoisinsConfig::URI_FIPA_PROPOSITION  => [
        'name'   => 'Proposition',
        'plural' => 'Propositions',
        'icon'   => 'info-sign',
				'nameType' => 'proposition'
      ],
			GrandsVoisinsConfig::URI_PAIR_DOCUMENT  => [
				'name'   => 'Document',
				'plural' => 'Documents',
				'icon'   => 'folder-open',
				'nameType' => 'document'
			],
			GrandsVoisinsConfig::URI_PAIR_DOCUMENT_TYPE  => [
				'name'   => 'Type de document',
				'plural' => 'Types de document',
				'icon'   => 'pushpin',
				'nameType' => 'documenttype'
			],
    ];

    var $entitiesFilters = [
      GrandsVoisinsConfig::URI_FOAF_ORGANIZATION,
      GrandsVoisinsConfig::URI_FOAF_PERSON,
      GrandsVoisinsConfig::URI_FOAF_PROJECT,
      GrandsVoisinsConfig::URI_PURL_EVENT,
      GrandsVoisinsConfig::URI_FIPA_PROPOSITION,
      GrandsVoisinsConfig::URI_SKOS_THESAURUS,
			GrandsVoisinsConfig::URI_PAIR_DOCUMENT,
			GrandsVoisinsConfig::URI_PAIR_DOCUMENT_TYPE,
    ];

    public function __construct()
    {
        // We also need to type as property.
        foreach ($this->entitiesTabs as $key => $item) {
            $this->entitiesTabs[$key]['type'] = $key;
        }
    }

    public function parametersAction()
    {
        $cache = new FilesystemAdapter();
        $parameters = $cache->getItem('gv.webservice.parameters');

        //if (!$parameters->isHit()) {
            $user = $this->GetUser();

            // Get results.
            $results = $this->searchSparqlRequest(
              '',
              GrandsVoisinsConfig::URI_SKOS_THESAURUS
            );

            $thesaurus = [];
            foreach ($results as $item) {
                $thesaurus[] = [
                  'uri'   => $item['uri'],
                  'label' => $item['title'],
                ];
            }

            $access = $this
              ->getDoctrine()
              ->getManager()
              ->getRepository('GrandsVoisinsBundle:User')
              ->getAccessLevelString($user);

            $name = ($user != null)? $user->getUsername() : '';
            // If no internet, we use a cached version of services
            // placed int face_service folder.
            if ($this->container->hasParameter('no_internet')) {
                $output = ['no_internet' => 1];
            } else {
                $output = [
                  'access'       => $access,
                  'name'         => $name,
                  'buildings'    => GrandsVoisinsConfig::$buildings,
                  'entities'     => $this->entitiesTabs,
                  'thesaurus'    => $thesaurus,
                ];
            }

            $parameters->set($output);

            $cache->save($parameters);
        //}

        return new JsonResponse($parameters->get());
    }

    public function searchSparqlRequest($term, $type = GrandsVoisinsConfig::Multiple,$filter=null,$isBlocked = false)
    {
        $sfClient    = $this->container->get('semantic_forms.client');
        $arrayType = explode('|',$type);
        $arrayType = array_flip($arrayType);
        $typeOrganization = array_key_exists(GrandsVoisinsConfig::URI_FOAF_ORGANIZATION,$arrayType);
        $typePerson= array_key_exists(GrandsVoisinsConfig::URI_FOAF_PERSON,$arrayType);
        $typeProject= array_key_exists(GrandsVoisinsConfig::URI_FOAF_PROJECT,$arrayType);
        $typeEvent= array_key_exists(GrandsVoisinsConfig::URI_PURL_EVENT,$arrayType);
        $typeProposition= array_key_exists(GrandsVoisinsConfig::URI_FIPA_PROPOSITION,$arrayType);
        $typeThesaurus= array_key_exists(GrandsVoisinsConfig::URI_SKOS_THESAURUS,$arrayType);
				$typeDocument= array_key_exists(GrandsVoisinsConfig::URI_PAIR_DOCUMENT,$arrayType);
				$typeDocumentType= array_key_exists(GrandsVoisinsConfig::URI_PAIR_DOCUMENT_TYPE,$arrayType);

				$userLogged =  $this->getUser() != null;
        $sparqlClient = new SparqlClient();
        /** @var \VirtualAssembly\SparqlBundle\Sparql\sparqlSelect $sparql */
        $sparql = $sparqlClient->newQuery(SparqlClient::SPARQL_SELECT);
        /* requete génériques */
        $sparql->addPrefixes($sparql->prefixes)
					->addPrefix('default','http://assemblee-virtuelle.github.io/mmmfest/PAIR_temp.owl#')
            ->addSelect('?uri')
            ->addSelect('?type')
            ->addSelect('?image')
            ->addSelect('?desc')
            ->addSelect('?building');
        ($filter)? $sparql->addWhere('?uri','gvoi:thesaurus',$sparql->formatValue($filter,$sparql::VALUE_TYPE_URL),'?GR' ) : null;
        //($term != '*')? $sparql->addWhere('?uri','text:query',$sparql->formatValue($term,$sparql::VALUE_TYPE_TEXT),'?GR' ) : null;
        $sparql->addWhere('?uri','rdf:type', '?type','?GR')
            ->groupBy('?uri ?type ?title ?image ?desc ?building')
            ->orderBy($sparql::ORDER_ASC,'?title');
        $organizations =[];
        if($type == GrandsVoisinsConfig::Multiple || $typeOrganization ){
            $orgaSparql = clone $sparql;

            $orgaSparql->addSelect('?title')
                ->addWhere('?uri','rdf:type', $sparql->formatValue(GrandsVoisinsConfig::URI_FOAF_ORGANIZATION,$sparql::VALUE_TYPE_URL),'?GR')
                ->addWhere('?uri','foaf:name','?title','?GR')
                ->addOptional('?uri','foaf:img','?image','?GR')
                ->addOptional('?uri','foaf:status','?desc','?GR')
                ->addOptional('?uri','gvoi:building','?building','?GR');

            if($term)$orgaSparql->addFilter('contains( lcase(?title) , lcase("'.$term.'")) || contains( lcase(?desc)  , lcase("'.$term.'")) ');
            //dump($orgaSparql->getQuery());
            $results = $sfClient->sparql($orgaSparql->getQuery());
            $organizations = $sfClient->sparqlResultsValues($results);
        }

        $persons = [];
        if($type == GrandsVoisinsConfig::Multiple || $typePerson ){

            $personSparql = clone $sparql;
            $personSparql->addSelect('?familyName')
                ->addSelect('?givenName')
                ->addSelect('( COALESCE(?familyName, "") As ?result) (fn:concat(?givenName, " " , ?result) as ?title)')
                ->addWhere('?uri','rdf:type', $sparql->formatValue(GrandsVoisinsConfig::URI_FOAF_PERSON,$sparql::VALUE_TYPE_URL),'?GR')
                ->addWhere('?uri','foaf:givenName','?givenName','?GR')
                ->addOptional('?uri','foaf:img','?image','?GR')
                ->addOptional('?uri','foaf:status','?desc','?GR')
                ->addOptional('?uri','gvoi:building','?building','?GR')
                ->addOptional('?uri','foaf:familyName','?familyName','?GR')
                ->addOptional('?org','rdf:type','foaf:Organization','?GR')
                ->addOptional('?org','gvoi:building','?building','?GR');
            if($term)$personSparql->addFilter('contains( lcase(?givenName)+ " " + lcase(?familyName), lcase("'.$term.'")) || contains( lcase(?desc)  , lcase("'.$term.'")) || contains( lcase(?familyName)  , lcase("'.$term.'")) || contains( lcase(?givenName)  , lcase("'.$term.'")) ');
            $personSparql->groupBy('?givenName ?familyName');
            //dump($personSparql->getQuery());
            $results = $sfClient->sparql($personSparql->getQuery());
            $persons = $sfClient->sparqlResultsValues($results);

        }
        $projects = [];
        if($type == GrandsVoisinsConfig::Multiple || $typeProject ){
            $projectSparql = clone $sparql;
            $projectSparql->addSelect('?title')
                ->addWhere('?uri','rdf:type', $sparql->formatValue(GrandsVoisinsConfig::URI_FOAF_PROJECT,$sparql::VALUE_TYPE_URL),'?GR')
                ->addWhere('?uri','rdfs:label','?title','?GR')
                ->addOptional('?uri','foaf:img','?image','?GR')
                ->addOptional('?uri','foaf:status','?desc','?GR')
                ->addOptional('?uri','gvoi:building','?building','?GR');
            if($term)$projectSparql->addFilter('contains( lcase(?title) , lcase("'.$term.'")) || contains( lcase(?desc)  , lcase("'.$term.'")) ');
            $results = $sfClient->sparql($projectSparql->getQuery());
            $projects = $sfClient->sparqlResultsValues($results);

        }
        $events = [];
        if(($type == GrandsVoisinsConfig::Multiple || $typeEvent) && $userLogged){
            $eventSparql = clone $sparql;
            $eventSparql->addSelect('?title')
                ->addSelect('?start')
                ->addSelect('?end')
                ->addWhere('?uri','rdf:type', $sparql->formatValue(GrandsVoisinsConfig::URI_PURL_EVENT,$sparql::VALUE_TYPE_URL),'?GR')
                ->addWhere('?uri','rdfs:label','?title','?GR')
                ->addOptional('?uri','foaf:img','?image','?GR')
                ->addOptional('?uri','foaf:status','?desc','?GR')
                ->addOptional('?uri','gvoi:building','?building','?GR')
                ->addOptional('?uri','gvoi:eventBegin','?start','?GR')
                ->addOptional('?uri','gvoi:eventEnd','?end','?GR');
            if($term)$eventSparql->addFilter('contains( lcase(?title), lcase("'.$term.'")) || contains( lcase(?desc)  , lcase("'.$term.'")) ');
            $eventSparql->orderBy($sparql::ORDER_DESC,'?start')
                ->groupBy('?start')
                ->groupBy('?end');
            $results = $sfClient->sparql($eventSparql->getQuery());
            $events = $sfClient->sparqlResultsValues($results);

        }
        $propositions = [];
        if(($type == GrandsVoisinsConfig::Multiple || $typeProposition)&& $userLogged ){
            $propositionSparql = clone $sparql;
            $propositionSparql->addSelect('?title')
                ->addWhere('?uri','rdf:type', $sparql->formatValue(GrandsVoisinsConfig::URI_FIPA_PROPOSITION,$sparql::VALUE_TYPE_URL),'?GR')
                ->addWhere('?uri','rdfs:label','?title','?GR')
                ->addOptional('?uri','foaf:img','?image','?GR')
                ->addOptional('?uri','foaf:status','?desc','?GR');
            $propositionSparql->addOptional('?uri','gvoi:building','?building','?GR');
            if($term)$propositionSparql->addFilter('contains( lcase(?title)  , lcase("'.$term.'")) || contains( lcase(?desc)  , lcase("'.$term.'")) ');
            $results = $sfClient->sparql($propositionSparql->getQuery());
            $propositions = $sfClient->sparqlResultsValues($results);
        }

        $thematiques = [];
        if($type == GrandsVoisinsConfig::Multiple || $typeThesaurus ){
            $thematiqueSparql = clone $sparql;
            $thematiqueSparql->addSelect('?title')
                ->addWhere('?uri','rdf:type', $sparql->formatValue(GrandsVoisinsConfig::URI_SKOS_THESAURUS,$sparql::VALUE_TYPE_URL),'?GR')
                ->addWhere('?uri','skos:prefLabel','?title','?GR');
            if($term)$thematiqueSparql->addFilter('contains( lcase(?title) , lcase("'.$term.'"))');
            $results = $sfClient->sparql($thematiqueSparql->getQuery());
            $thematiques = $sfClient->sparqlResultsValues($results);
        }
				$documents = [];
				if(($type == GrandsVoisinsConfig::Multiple || $typeDocument)&& $userLogged ){
						$documentSparql = clone $sparql;
						$documentSparql->addSelect('?title')
							->addWhere('?uri','rdf:type', $sparql->formatValue(GrandsVoisinsConfig::URI_PAIR_DOCUMENT,$sparql::VALUE_TYPE_URL),'?GR')
							->addWhere('?uri','default:preferedLabel','?title','?GR')
							->addOptional('?uri','default:comment','?desc','?GR');
						//$documentSparql->addOptional('?uri','default:building','?building','?GR');
						if($term)$documentSparql->addFilter('contains( lcase(?title)  , lcase("'.$term.'")) || contains( lcase(?desc)  , lcase("'.$term.'")) ');
						$results = $sfClient->sparql($documentSparql->getQuery());
						$documents= $sfClient->sparqlResultsValues($results);
				}
				$documentTypes = [];
				if(($type == GrandsVoisinsConfig::Multiple || $typeDocumentType)&& !$isBlocked ){
						$documentTypeSparql = clone $sparql;
						$documentTypeSparql->addSelect('?title')
							->addWhere('?uri','rdf:type', $sparql->formatValue(GrandsVoisinsConfig::URI_PAIR_DOCUMENT_TYPE,$sparql::VALUE_TYPE_URL),'?GR')
							->addWhere('?uri','default:preferedLabel','?title','?GR')
							->addOptional('?uri','default:comment','?desc','?GR');
						//$documentTypeSparql->addOptional('?uri','default:building','?building','?GR');
						if($term)$documentTypeSparql->addFilter('contains( lcase(?title)  , lcase("'.$term.'")) || contains( lcase(?desc)  , lcase("'.$term.'")) ');
						$results = $sfClient->sparql($documentTypeSparql->getQuery());
						$documentTypes = $sfClient->sparqlResultsValues($results);
				}
        $results = [];

        while ($organizations || $persons || $projects
          || $events || $propositions || $thematiques || $documents || $documentTypes) {

						if (!empty($organizations)) {
                $results[] = array_shift($organizations);
            }
						else if (!empty($persons)) {
                $results[] = array_shift($persons);
            }
						else if (!empty($projects)) {
                $results[] = array_shift($projects);
            }
						else if (!empty($events)) {
                $results[] = array_shift($events);
            }
						else if (!empty($propositions)) {
                $results[] = array_shift($propositions);
            }
						else if (!empty($thematiques)) {
                $results[] = array_shift($thematiques);
            }
						else if (!empty($documents)) {
								$results[] = array_shift($documents);
						}
						else if  (!empty($documentTypes)) {
								$results[] = array_shift($documentTypes);
						}
        }

        return $results;
    }

    public function searchAction(Request $request)
    {
        // Search
        return new JsonResponse(
          (object)[
            'results' => $this->searchSparqlRequest(
              $request->get('t'),
              ''
              ,$request->get('f'),
							true
            ),
          ]
        );
    }

    public function fieldUriSearchAction(Request $request)
    {
        $output = [];
        // Get results.
        $results = $this->searchSparqlRequest($request->get('QueryString'),$request->get('rdfType'));
        // Transform data to match to uri field (uri => title).
        foreach ($results as $item) {
            $output[$item['uri']] = $item['title'];
        }

        return new JsonResponse((object)$output);
    }

    public function sparqlGetLabel($url, $uriType)
    {
        $sparqlClient = new SparqlClient();
        /** @var \VirtualAssembly\SparqlBundle\Sparql\sparqlSelect $sparql */
        $sparql = $sparqlClient->newQuery(SparqlClient::SPARQL_SELECT);
        $sparql->addPrefixes($sparql->prefixes)
					->addPrefix('default','http://assemblee-virtuelle.github.io/mmmfest/PAIR_temp.owl#')
            ->addSelect('?uri')
            ->addFilter('?uri = <'.$url.'>');
        switch ($uriType) {
            case GrandsVoisinsConfig::URI_FOAF_PERSON :
                $sparql->addSelect('( COALESCE(?familyName, "") As ?result)  (fn:concat(?givenName, " ", ?result) as ?label)')
                    ->addWhere('?uri','foaf:givenName','?givenName','?gr')
                    ->addOptional('?uri','foaf:familyName','?familyName','?gr');

                break;
            case GrandsVoisinsConfig::URI_FOAF_ORGANIZATION :
                $sparql->addSelect('?label')
                    ->addWhere('?uri','foaf:name','?label','?gr');

                break;
            case GrandsVoisinsConfig::URI_FOAF_PROJECT :
            case GrandsVoisinsConfig::URI_FIPA_PROPOSITION :
            case GrandsVoisinsConfig::URI_PURL_EVENT :
                $sparql->addSelect('?label')
                    ->addWhere('?uri','rdfs:label','?label','?gr');

                break;
            case GrandsVoisinsConfig::URI_SKOS_THESAURUS:
                $sparql->addSelect('?label')
                    ->addWhere('?uri','skos:prefLabel','?label','?gr');
                break;
						case GrandsVoisinsConfig::URI_PAIR_DOCUMENT :
						case GrandsVoisinsConfig::URI_PAIR_DOCUMENT_TYPE :
								$sparql->addSelect('?label')
									->addWhere('?uri','default:preferedLabel','?label','?gr');
								break;
						default:
                $sparql->addSelect('( COALESCE(?givenName, "") As ?result_1)')
                    ->addSelect('( COALESCE(?familyName, "") As ?result_2)')
                    ->addSelect('( COALESCE(?name, "") As ?result_3)')
                    ->addSelect('( COALESCE(?label_test, "") As ?result_4)')
                    ->addSelect('( COALESCE(?skos, "") As ?result_5)')
                    ->addSelect('( COALESCE(?preferedLabel, "") As ?result_6)')
                    ->addSelect('(fn:concat(?result_6,?result_5,?result_4,?result_3,?result_2, " ", ?result_1) as ?label)')
                    ->addWhere('?uri','rdf:type','?type','?gr')
                    ->addOptional('?uri','foaf:givenName','?givenName','?gr')
                    ->addOptional('?uri','foaf:familyName','?familyName','?gr')
                    ->addOptional('?uri','foaf:name','?name','?gr')
                    ->addOptional('?uri','rdfs:label','?label_test','?gr')
                    ->addOptional('?uri','skos:prefLabel','?skos','?gr')
                    ->addOptional('?uri','foaf:status','?desc','?gr')
                    ->addOptional('?uri','foaf:img','?image','?gr')
                    ->addOptional('?uri','default:preferedLabel','?preferedLabel','?gr')
                    ->addOptional('?uri','gvoi:building','?building','?gr');
                break;
        }


        $sfClient = $this->container->get('semantic_forms.client');
        // Count buildings.
        //dump($sparql->getQuery());
        $response = $sfClient->sparql($sparql->getQuery());
        if (isset($response['results']['bindings'][0]['label']['value'])) {
            return $response['results']['bindings'][0]['label']['value'];
        }

        return false;
    }

    public function fieldUriLabelAction(Request $request)
    {
        $label = $this->sparqlGetLabel(
          $request->get('uri'),
          GrandsVoisinsConfig::Multiple
        );

        return new JsonResponse(
          (object)['label' => $label]
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function detailAction(Request $request)
    {
        return new JsonResponse(
          (object)[
            'detail' => $this->requestPair($request->get('uri')),
          ]
        );
    }

    public function ressourceAction(Request $request){
        $uri                = $request->get('uri');
        $sfClient           = $this->container->get('semantic_forms.client');
        $nameRessource      = $sfClient->dbPediaLabel($uri);
        $sparqlClient = new SparqlClient();
        /** @var \VirtualAssembly\SparqlBundle\Sparql\sparqlSelect $sparql */
        $sparql = $sparqlClient->newQuery(SparqlClient::SPARQL_SELECT);
        $sparql->addPrefixes($sparql->prefixes)
					->addPrefix('default','http://assemblee-virtuelle.github.io/mmmfest/PAIR_temp.owl#')
            ->addSelect('?type')
            ->addSelect('?uri')
            ->addSelect('( COALESCE(?givenName, "") As ?result_1)')
            ->addSelect('( COALESCE(?familyName, "") As ?result_2)')
            ->addSelect('( COALESCE(?name, "") As ?result_3)')
            ->addSelect('( COALESCE(?label_test, "") As ?result_4)')
            ->addSelect('( COALESCE(?skos, "") As ?result_5)')
            ->addSelect('(fn:concat(?result_5,?result_4,?result_3,?result_2, " ", ?result_1) as ?title)')
            ->addOptional('?uri','foaf:givenName','?givenName','?gr')
            ->addOptional('?uri','foaf:familyName','?familyName','?gr')
            ->addOptional('?uri','foaf:name','?name','?gr')
            ->addOptional('?uri','rdfs:label','?label_test','?gr')
            ->addOptional('?uri','skos:prefLabel','?skos','?gr')
            ->addOptional('?uri','foaf:status','?desc','?gr')
            ->addOptional('?uri','foaf:img','?image','?gr')
            ->addOptional('?uri','gvoi:building','?building','?gr');
        $ressourcesNeeded = clone $sparql;
        $ressourcesNeeded->addWhere('?uri','gvoi:ressouceNeeded',$sparql->formatValue($uri,$sparql::VALUE_TYPE_URL),'?gr');

        $requests['ressourcesNeeded'] = $ressourcesNeeded->getQuery();
        $ressourcesProposed = clone $sparql;
        $ressourcesProposed->addWhere('?uri','gvoi:ressouceProposed',$sparql->formatValue($uri,$sparql::VALUE_TYPE_URL),'?gr');
        $requests['ressourcesProposed'] =$ressourcesProposed->getQuery();


        $filtered['name'] = $nameRessource;
        $filtered['uri'] = $uri;
        foreach ($requests as $key => $request){
            //dump($request);
            $results[$key]  = $sfClient->sparql($request);
            $results[$key] = is_array($results[$key]) ? $sfClient->sparqlResultsValues(
                $results[$key]
            ) : [];
            $filtered[$key] = $this->filter($results[$key]);
        }
        return new JsonResponse(
            (object)[
                'ressource' => $filtered,
            ]
        );
    }

    public function uriPropertiesFiltered($uri)
		{
				$sfClient = $this->container->get('semantic_forms.client');
				$properties = $sfClient->uriProperties($uri);
				$sfConf = $this->getConf(current($properties[self::TYPE]));
				$output = [];
				$user = $this->GetUser();
				$this
					->getDoctrine()
					->getManager()
					->getRepository('GrandsVoisinsBundle:User')
					->getAccessLevelString($user);
				if ($sfConf != null){
						foreach ($sfConf['fields'] as $field => $detail) {
								if ($detail['access'] === 'anonymous' ||
									$this->isGranted('ROLE_' . strtoupper($detail['access']))
								) {
										if (isset($properties[$field])) {
												$output[$detail['value']] = $properties[$field];
										}
								}
						}
				}
				else $output = $properties;

        return $output;
    }

    public function requestPair($uri)
    {
        $output     = [];
        $properties = $this->uriPropertiesFiltered($uri);
        $sfClient   = $this->container->get('semantic_forms.client');
        switch (current($properties['type'])) {
            // Orga.
            case  GrandsVoisinsConfig::URI_FOAF_ORGANIZATION:
                // Organization should be saved internally.
                $organization = $this->getDoctrine()->getRepository(
                  'GrandsVoisinsBundle:Organisation'
                )->findOneBy(
                  [
                    'sfOrganisation' => $uri,
                  ]
                );
                if(!is_null($organization))
                    $output['id'] = $organization->getId();
								if (isset($properties['topicInterest'])) {
										foreach ($properties['topicInterest'] as $uri) {
												$output['topicInterest'][] = [
													'uri'  => $uri,
													'name' => $sfClient->dbPediaLabel($uri),
												];
										}
								}
								$propertiesWithUri =[
									'head',
									'hasMember',
									'memberOf',
									'OrganizationalCollaboration',
									'made',
									'documentedBy',

								];
								$this->getData2($properties,$propertiesWithUri,$output);
                break;
            // Person.
            case  GrandsVoisinsConfig::URI_FOAF_PERSON:

                $query = " SELECT ?b WHERE { GRAPH ?G {<".$uri."> rdf:type foaf:Person . ?org rdf:type foaf:Organization . ?org gvoi:building ?b .} }";
                //dump($query);
                $buildingsResult = $sfClient->sparql($sfClient->prefixesCompiled . $query);
                $output['building'] = (isset($buildingsResult["results"]["bindings"][0])) ? $buildingsResult["results"]["bindings"][0]['b']['value'] : '';
                // Remove mailto: from email.
                if (isset($properties['mbox'])) {
                    $properties['mbox'] = preg_replace(
                      '/^mailto:/',
                      '',
                      current($properties['mbox'])
                    );
                }
                if (isset($properties['phone'])) {
                    // Remove tel: from phone
                    $properties['phone'] = preg_replace(
                      '/^tel:/',
                      '',
                      current($properties['phone'])
                    );
                }
								if (isset($properties['topicInterest'])) {
										foreach ($properties['topicInterest'] as $uri) {
												$output['topicInterest'][] = [
													'uri'  => $uri,
													'name' => $sfClient->dbPediaLabel($uri),
												];
										}
								}
								if (isset($properties['expertise'])) {
										foreach ($properties['expertise'] as $uri) {
												$output['expertise'][] = [
													'uri'  => $uri,
													'name' => $sfClient->dbPediaLabel($uri),
												];
										}
								}
								if (isset($properties['city'])) {
										foreach ($properties['city'] as $uri) {
												$output['city'] = [
													'uri'  => $uri,
													'name' => $sfClient->dbPediaLabel($uri),
												];
										}
								}
								$propertiesWithUri =[
									'memberOf',
									//'currentProject',
									'knows',
									'made',
									'headOf',
								];
								$this->getData2($properties,$propertiesWithUri,$output);
                break;
            // Project.
            case GrandsVoisinsConfig::URI_FOAF_PROJECT:
                if (isset($properties['mbox'])) {
                    $properties['mbox'] = preg_replace(
                      '/^mailto:/',
                      '',
                      current($properties['mbox'])
                    );
                }
								if (isset($properties['topicInterest'])) {
										foreach ($properties['topicInterest'] as $uri) {
												$output['topicInterest'][] = [
													'uri'  => $uri,
													'name' => $sfClient->dbPediaLabel($uri),
												];
										}
								}
								$propertiesWithUri =[
									'maker',
									'head',
									'documentedBy',

								];
								$this->getData2($properties,$propertiesWithUri,$output);


                break;
            // Event.
            case GrandsVoisinsConfig::URI_PURL_EVENT:
                if (isset($properties['mbox'])) {
                    $properties['mbox'] = preg_replace(
                      '/^mailto:/',
                      '',
                      current($properties['mbox'])
                    );
                }
                if (isset($properties['topicInterest'])) {
                    foreach ($properties['topicInterest'] as $uri) {
                        $output['topicInterest'][] = [
                          'uri'  => $uri,
                          'name' => $sfClient->dbPediaLabel($uri),
                        ];
                    }
                }
								$propertiesWithUri =[
									'maker',
									'documentedBy',
								];
								$this->getData2($properties,$propertiesWithUri,$output);

                break;
            // Proposition.
            case GrandsVoisinsConfig::URI_FIPA_PROPOSITION:
                if (isset($properties['mbox'])) {
                    $properties['mbox'] = preg_replace(
                      '/^mailto:/',
                      '',
                      current($properties['mbox'])
                    );
                }
                if (isset($properties['topicInterest'])) {
                    foreach ($properties['topicInterest'] as $uri) {
                        $output['topicInterest'][] = [
                          'uri'  => $uri,
                          'name' => $sfClient->dbPediaLabel($uri),
                        ];
                    }
                }
								$propertiesWithUri =[
									'maker',
									'documentedBy',

								];
								$this->getData2($properties,$propertiesWithUri,$output);

                break;
						case GrandsVoisinsConfig::URI_PAIR_DOCUMENT:
								if (isset($properties['description'])) {
										$properties['description'] = nl2br(current($properties['description']),false);
								}
								$propertiesWithUri = [
									'documents',
									'references',
									'referencesBy',
									'hasType'
								];
								$this->getData2($properties,$propertiesWithUri,$output);
								break;
						//document type
						case GrandsVoisinsConfig::URI_PAIR_DOCUMENT_TYPE:
								if (isset($properties['description'])) {
										$properties['description'] = nl2br(current($properties['description']),false);
								}
								$propertiesWithUri = [
									'typeOf'
								];
								//dump($properties);exit;
								$this->getData2($properties,$propertiesWithUri,$output);
								break;
        }
        if (isset($properties['resourceProposed'])) {
            foreach ($properties['resourceProposed'] as $uri) {
                $output['resourceProposed'][] = [
                  'uri'  => $uri,
                  'name' => $sfClient->dbPediaLabel($uri),
                ];
            }
        }
        if (isset($properties['resourceNeeded'])) {
            foreach ($properties['resourceNeeded'] as $uri) {
                $output['resourceNeeded'][] = [
                  'uri'  => $uri,
                  'name' => $sfClient->dbPediaLabel($uri),
                ];
            }
        }
        if (isset($properties['thesaurus'])) {
						foreach ($properties['thesaurus'] as $uri) {
								$result = [
									'uri' => $uri,
									'name' => $this->sparqlGetLabel($uri, GrandsVoisinsConfig::URI_SKOS_THESAURUS)
								];
								$output['thesaurus'][] = $result;
						}
        }
        if (isset($properties['description'])) {
            $properties['description'] = nl2br(current($properties['description']),false);
        }
        $output['properties'] = $properties;

        //dump($output);
        return $output;

    }

		private function getData2($properties,$tabFieldsAlias,&$output){
				$cacheTemp = [];
				foreach ($tabFieldsAlias as $alias) {
						if (isset($properties[$alias])) {
								foreach ($properties[$alias] as $uri) {
										if (array_key_exists($uri, $cacheTemp)) {
												$output[$alias][$this->entitiesTabs[$cacheTemp[$uri]['type']]['nameType']][] = $cacheTemp[$uri];
										} else {
												$component = $this->uriPropertiesFiltered($uri);
												$componentType = current($component['type']);
												$result = null;
												switch ($componentType) {
														case GrandsVoisinsConfig::URI_FOAF_PERSON:
																$result = [
																	'uri' => $uri,
																	'name' => ((current($component['givenName'])) ? current($component['givenName']) : "") . " " . ((array_key_exists('familyName',$component) && current($component['familyName'])) ? current($component['familyName']) : ""),
																	'image' => (!isset($component['image'])) ? '/common/images/no_avatar.jpg' : $component['image'],
																];
																$output[$alias][$this->entitiesTabs[$componentType]['nameType']][] = $result;
																break;
														case GrandsVoisinsConfig::URI_FOAF_ORGANIZATION:

																$result = [
																	'uri' => $uri,
																	'name' => ((current($component['name'])) ? current($component['name']) : ""),
																	'image' => (!isset($component['img'])) ? '/common/images/no_avatar.jpg' : $component['img'],
																];
																$output[$alias][$this->entitiesTabs[$componentType]['nameType']][] = $result;

														break;
														case GrandsVoisinsConfig::URI_PURL_EVENT:
														case GrandsVoisinsConfig::URI_FOAF_PROJECT:
														case GrandsVoisinsConfig::URI_FIPA_PROPOSITION:
														sort($component['label']);
																$result = [
																	'uri' => $uri,
																	'name' => ((end($component['label'])) ? end($component['label']) : "") ,
																	'image' => (!isset($component['image'])) ? '/common/images/no_avatar.jpg' : $component['image'],
																];
																$output[$alias][$this->entitiesTabs[$componentType]['nameType']][] = $result;
																break;
														break;
														case GrandsVoisinsConfig::URI_PAIR_DOCUMENT:
														case GrandsVoisinsConfig::URI_PAIR_DOCUMENT_TYPE:
																$result = [
																	'uri' => $uri,
																	'name' => ((current($component['preferedLabel'])) ? current($component['preferedLabel']) : ""),
																];
																$output[$alias][$this->entitiesTabs[$componentType]['nameType']][] = $result;
																break;
												}
												$cacheTemp[$uri] = $result;
												$cacheTemp[$uri]['type'] = $componentType;
										}
								}
						}
				}
		}

    /**
     * Filter only allowed types.
     * @param array $array
     * @return array
     */
    public function filter(Array $array){
        $filtered = [];
        foreach ($array as $result) {
            // Type is sometime missing.
            if (isset($result['type']) && in_array(
                $result['type'],
                $this->entitiesFilters
              )
            ) {
                $filtered[] = $result;
            }
        }

        return $filtered;
    }
		private function getConf($type){
				$conf = null;
				switch ($type){
						case GrandsVoisinsConfig::URI_FOAF_PERSON:
								$conf = $this->getParameter('profileConf');
								break;
						case GrandsVoisinsConfig::URI_FOAF_ORGANIZATION:
								$conf = $this->getParameter('organizationConf');
								break;
						case GrandsVoisinsConfig::URI_FOAF_PROJECT:
								$conf = $this->getParameter('projectConf');
								break;
						case GrandsVoisinsConfig::URI_PURL_EVENT:
								$conf = $this->getParameter('eventConf');
								break;
						case GrandsVoisinsConfig::URI_FIPA_PROPOSITION:
								$conf = $this->getParameter('propositionConf');
								break;
						case GrandsVoisinsConfig::URI_PAIR_DOCUMENT:
								$conf = $this->getParameter('documentConf');
								break;
						case GrandsVoisinsConfig::URI_PAIR_DOCUMENT_TYPE:
								$conf = $this->getParameter('documenttypeConf');
								break;
				}
				return $conf;
		}
}
