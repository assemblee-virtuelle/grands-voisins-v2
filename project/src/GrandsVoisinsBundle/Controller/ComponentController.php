<?php

namespace GrandsVoisinsBundle\Controller;


use GrandsVoisinsBundle\GrandsVoisinsConfig;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ComponentController extends Controller
{

    var $componentName = 'undefined';
    var $pluralName = 'undefined';
    var $sparqlPrefix = 'undefined';
		private $route = [
			'project' => 'projet',
			'event' => 'evenement',
			'proposition' => 'proposition',
			'document' => 'document',
			'documenttype' => 'documentType',
		];
    public function listAction()
    {
        /** @var  $sfClient \VirtualAssembly\SemanticFormsBundle\Services\SemanticFormsClient  */
        $sfClient = $this->container->get('semantic_forms.client');
        /** @var \VirtualAssembly\SparqlBundle\Services\SparqlClient $sparqlClient */
        $sparqlClient   = $this->container->get('sparqlbundle.client');

        $organisationEntity = $this->getDoctrine()->getManager()->getRepository(
          'GrandsVoisinsBundle:Organisation'
        );

        $organisation = $organisationEntity->find(
          $this->getUser()->getFkOrganisation()
        );

        $sparql = $sparqlClient->newQuery($sparqlClient::SPARQL_SELECT);
        $graphURI = $sparql->formatValue($organisation->getGraphURI(),$sparql::VALUE_TYPE_URL);
        $sparql->addPrefixes($sparql->prefixes)
					->addPrefix('default', 'http://assemblee-virtuelle.github.io/mmmfest/PAIR_temp.owl#')
            ->addSelect('?URI ?NAME')
            ->addWhere('?URI','rdf:type',$this->sparqlPrefix,$graphURI)
            ->addOptional('?URI','rdfs:label','?NAME',$graphURI)
        		->addOptional('?URI','default:preferedLabel','?NAME',$graphURI);
        $results = $sfClient->sparql($sparql->getQuery());

        $listContent = [];
        if (isset($results["results"]["bindings"])) {
            foreach ($results["results"]["bindings"] as $item) {
                $listContent[] = [
                  'uri'   => $item['URI']['value'],
                  'title' => $item['NAME']['value'],
                ];
            }
        }

        return $this->render(
          'GrandsVoisinsBundle:Component:'.$this->componentName.'List.html.twig',
          array(
            'componentName' => $this->componentName,
            'plural'        => $this->pluralName,
            'listContent'   => $listContent,
          )
        );
    }

    public function addAction(Request $request)
    {
        /** @var \GrandsVoisinsBundle\Services\Encryption $encryption */
        $encryption = $this->container->get('GrandsVoisinsBundle.encryption');
        /** @var $user \GrandsVoisinsBundle\Entity\User */
        $user         = $this->getUser();
        $sfClient     = $this->container->get('semantic_forms.client');
        $organisation = $this
          ->getDoctrine()
          ->getManager()
          ->getRepository('GrandsVoisinsBundle:Organisation')
          ->find($this->getUser()->getFkOrganisation());

        // Same as FormType::class
        $componentClassName = 'GrandsVoisinsBundle\Form\\'.ucfirst(
            $this->componentName
          ).'Type';

				$componentConf = $this->getParameter($this->componentName.'Conf');

        $form = $this->createForm(
          $componentClassName,
          null,
          [
            'login'                 => $user->getEmail(),
            'password'              => $encryption->decrypt($user->getSfUser()),
            'graphURI'              => $organisation->getGraphURI(),
            'client'                => $sfClient,
						'sfConf'               => $componentConf,
						'spec'                  => $componentConf['spec'],
            'values'                => $request->get('uri'),
            'lookupUrlLabel'        => $this->generateUrl(
              'webserviceFieldUriLabel'
            ),
            'lookupUrlPerson'       => $this->generateUrl(
              'webserviceFieldUriSearch'
            ),
            'lookupUrlOrganization' => $this->generateUrl(
              'webserviceFieldUriSearch'
            ),
          ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('info', 'Le contenu à bien été mis à jour.');

            return $this->redirectToRoute(
              strtolower($request->get('component')).'List'
            );
        }
        $image = ($form->has('image'))? $form->get('image')->getData() : null;
        // Fill form
        return $this->render(
          'GrandsVoisinsBundle:Component:'.$this->componentName.'Form.html.twig',
          array(
            'form' => $form->createView(),
            'image' => $image
          )
        );
    }
    public function removeAction(){
        /** @var  $sfClient \VirtualAssembly\SemanticFormsBundle\Services\SemanticFormsClient  */
        $sfClient = $this->container->get('semantic_forms.client');
        /** @var \VirtualAssembly\SparqlBundle\Services\SparqlClient $sparqlClient */
        $sparqlClient   = $this->container->get('sparqlbundle.client');
        $componentName = $_GET['componentName'];
        $sparql = $sparqlClient->newQuery($sparqlClient::SPARQL_DELETE);
				$sparqlDeux = clone $sparql;
        $uri = $sparql->formatValue($_GET['uri'],$sparql::VALUE_TYPE_URL);

        $sparql->addDelete($uri,'?P','?O','?gr')
            ->addWhere($uri,'?P','?O','?gr');
				$sparqlDeux->addDelete('?s','?PP',$uri,'?gr')
					->addWhere('?s','?PP',$uri,'?gr');

        $sfClient->update($sparql->getQuery());
        $sfClient->update($sparqlDeux->getQuery());

        return $this->redirect('/mon-compte/'.$this->route[$componentName]);
    }
}