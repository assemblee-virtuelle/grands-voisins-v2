<?php
/**
 * Created by PhpStorm.
 * User: LaFaucheuse
 * Date: 21/03/2017
 * Time: 11:00
 */

namespace VirtualAssembly\SemanticFormsBundle\FormBuilder;


use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class FormBuilder
{
    var $specificProperty;
    var $formFactory;
    public function __construct($specificProperty,$formFactory)
    {
        $this->specificProperty =$specificProperty;
        $this->formFactory = $formFactory;
    }

    public function createForm(array $formBySF, $graphURI){
        $data = array();
        $form = $this->formFactory-> createNamedBuilder(null);
        $form->add('url',HiddenType::class,array('data' => $formBySF["subject"]));
        $form->add('uri',HiddenType::class,array('data' => $formBySF["subject"]));
        $form->add('graphURI',HiddenType::class,array('data' => $graphURI));


        $option = $this->specificProperty["options"];
        unset($this->specificProperty["options"]);

        $flippedArray= array_flip($this->specificProperty);

        $formTransformed = array();
        foreach ($formBySF["fields"] as $field){

            if(array_key_exists($field["property"],$formTransformed)){
                if(!is_array($formTransformed[$field["property"]]["value"])){;
                    $formTransformed[$field["property"]]["value"] = array($formTransformed[$field["property"]]["value"]);
                }
                array_push($formTransformed[$field["property"]]["value"],$field["value"]);
            }
            else{
                $formTransformed[$field["property"]]["label"] = $field["label"];
                $formTransformed[$field["property"]]["comment"] = $field["comment"];
                $formTransformed[$field["property"]]["value"] = $field["value"];
                $formTransformed[$field["property"]]["widgetType"] = $field["widgetType"];
                $formTransformed[$field["property"]]["htmlName"] = $field["htmlName"];
                $formTransformed[$field["property"]]["cardinality"] = $field["cardinality"];
            }
        }
        dump($formTransformed,$option,$flippedArray);
        foreach ($formTransformed as $key=>$field){
            $contentOfField = $this->getField($field,array_key_exists($key,$flippedArray)? $option[$flippedArray[$key]] : array());
            $form->add($contentOfField['label'],$contentOfField['type'],$contentOfField['option']);
        }
        $form->add('sav',SubmitType::class);
        return $form->getForm();
    }

    private function getField($field,$option =null){
        $contentOfField['label'] = urldecode($field['htmlName']);

        $type = array_key_exists('type',$option) ? $option['type'] : explode(' ',strtolower($field['widgetType']))[0];
        switch ($type){
            case 'email':
                $contentOfField['type'] = EmailType::class;
                $contentOfField['option'] = array('data' =>$field['value']);
                break;
            case 'uri':
                $contentOfField['type'] = UrlType::class;
                $contentOfField['option'] = array('data' =>$field['value']);
                break;
            case 'number':
                $contentOfField['type'] = NumberType::class;
                $contentOfField['option'] = array('data' =>$field['value']);
                break;
            case 'date':
                $contentOfField['type'] = DateType::class;
                $contentOfField['option'] = array('data' =>$field['value']);
                break;
            default:
                $contentOfField['type'] = TextType::class;
                $contentOfField['option'] = array('data' =>$field['value']);
        }
        return $contentOfField;
    }

}