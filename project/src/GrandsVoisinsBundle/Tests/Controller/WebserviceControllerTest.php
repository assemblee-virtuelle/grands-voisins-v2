<?php

namespace GrandsVoisinsBundle\Tests\Controller;


use GrandsVoisinsBundle\GrandsVoisinsConfig;
use VirtualAssembly\SemanticFormsBundle\SemanticFormsBundle;

class WebserviceControllerTest extends toolsTest
{
    var $entities = [
      GrandsVoisinsConfig::URI_FOAF_ORGANIZATION,
      GrandsVoisinsConfig::URI_FOAF_PERSON,
      GrandsVoisinsConfig::URI_FOAF_PROJECT,
      GrandsVoisinsConfig::URI_PURL_EVENT,
      GrandsVoisinsConfig::URI_FIPA_PROPOSITION,
    ];
    public function testWebserviceParameters(){
        //not logged
        $this->crawler = $this->client->request('GET', '/webservice/parameters');
        self::assertTrue(
          $this->client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
          )
        );
        $jsonResponse = json_decode($this->client->getResponse()->getContent(),true);
        self::assertArrayHasKey('access', $jsonResponse);
        self::assertEquals('anonymous', $jsonResponse['access']);

        self::assertArrayHasKey('name', $jsonResponse);
        self::assertEmpty($jsonResponse['name']);
        self::assertArrayHasKey('buildings', $jsonResponse);
        foreach ($jsonResponse['buildings'] as $building => $detail){
            self::assertArrayHasKey($building,GrandsVoisinsConfig::$buildingsExtended);
            self::assertArrayHasKey('title',$detail);
        }
        self::assertArrayHasKey('entities', $jsonResponse);
        foreach ($this->entities as $uri){
            self::assertArrayHasKey($uri,$jsonResponse['entities']);
            self::assertArrayHasKey('name',$jsonResponse['entities'][$uri]);
            self::assertArrayHasKey('plural',$jsonResponse['entities'][$uri]);
            self::assertArrayHasKey('icon',$jsonResponse['entities'][$uri]);
            self::assertArrayHasKey('type',$jsonResponse['entities'][$uri]);
        }
        self::assertArrayHasKey('thesaurus', $jsonResponse);
        self::assertGreaterThan(0,$jsonResponse['thesaurus']);
        foreach ($jsonResponse['thesaurus'] as $detail){
            self::assertArrayHasKey('uri',$detail);
            self::assertArrayHasKey('label',$detail);
        }
        //logged
        $this->testLogin();
        $this->crawler = $this->client->request('GET', '/webservice/parameters');
        self::assertTrue(
          $this->client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
          )
        );
        $jsonResponse = json_decode($this->client->getResponse()->getContent(),true);
        self::assertArrayHasKey('access', $jsonResponse);
        self::assertNotEquals('anonymous', $jsonResponse['access']);
        self::assertArrayHasKey('name', $jsonResponse);
        self::assertEquals($this->user, $jsonResponse['name']);
        $this->testLogout();
    }

    public function testWebserviceSearch(){
        $this->testLogin();
        $this->crawler = $this->client->request('GET', '/webservice/search',['t' =>"label"]);
        self::assertTrue(
          $this->client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
          )
        );
        $jsonResponse = json_decode($this->client->getResponse()->getContent(),true);
        self::assertArrayHasKey('results', $jsonResponse);
        self::assertNotEmpty($jsonResponse);
        self::assertNotEmpty($jsonResponse["results"]);
        $firstElem = $jsonResponse["results"][0];
        self::assertNotEmpty('results', $firstElem);
        self::assertArrayHasKey('title', $firstElem);
        self::assertArrayHasKey('type', $firstElem);
        self::assertArrayHasKey('uri', $firstElem);

    }

    public function testWebserviceFieldUriSearch(){
        $this->testLogin();
				$jsonResponse = $this->fieldUriSearch("label",$this->entities[2]);
        dump($jsonResponse);
        self::assertNotEmpty($jsonResponse);
        self::assertContains("label",reset($jsonResponse));
    }

    public function testWebserviceFieldUriLabel(){
        $this->testLogin();
        $term = "label";
				$jsonResponse = $this->fieldUriSearch($term,$this->entities[2]);

        $this->crawler = $this->client->request('GET', '/webservice/label/field-uri',['uri' =>array_flip($jsonResponse)[$term]]);
        self::assertTrue(
          $this->client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
          )
        );
        $jsonResponse = json_decode($this->client->getResponse()->getContent(),true);
        dump($jsonResponse);
        self::assertNotEmpty($jsonResponse);
        self::assertContains($term,reset($jsonResponse));
    }

    private function fieldUriSearch($term, $type){
			$this->crawler = $this->client->request('GET', '/webservice/search/field-uri',['QueryString' =>$term,'rdfType' =>$type]);
			self::assertTrue(
				$this->client->getResponse()->headers->contains(
					'Content-Type',
					'application/json'
				)
			);
			$jsonResponse = json_decode($this->client->getResponse()->getContent(),true);
			return $jsonResponse;
		}

}
