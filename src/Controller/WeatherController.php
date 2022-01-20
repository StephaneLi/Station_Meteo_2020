<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Service\WeatherService;

class WeatherController extends AbstractController
{
    private $weatherService;
    private $defaultCityUri;

    public function __construct(WeatherService $weather)
    {
        $this->weatherService = $weather;
        $this->defaultCity = "toulouse";
    }

    /**
     * @Route("/weather-{uriVille}", name="weatherCity")
     * @Route("/weather", name="weather")
     * @Route("/", name="index")
     */
    public function index(Request $request, $uriVille = null)
    {
        $errors = [];

        $form = $this->createFormBuilder()
        ->add('ville', textType::class)
        ->add('submit', SubmitType::class)
        ->getForm()
        ; 
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $ville = $request->request->get('form')['ville'];
            $uriVille = $this->getFormatVille($ville);

            return $this->redirectToRoute('weatherCity', [
                'uriVille' => $uriVille,
            ]);
        }else{
            if($uriVille){
                //validation du texte saisie
                if(strlen($uriVille) < 3 || strlen($uriVille) > 100 ){
                    array_push ($errors, "Erreur lors de la saisie dans le formulaire !");
                    return $this->render('weather/errors.html.twig', [
                        'errors' => $errors,
                        'formMeteo' => $form->createView()
                    ]);
                }else{
                    try{
                        $data = $this->weatherService->getWeather($uriVille);
                    }catch(NotFoundHttpException $ex){
                        dump($ex->getMessage());
                        array_push ($errors, $ex->getMessage());
                        return $this->render('weather/errors.html.twig', [
                            'errors' => $errors,
                            'formMeteo' => $form->createView()
                        ]);
                    }
                    
                }
            }else{
                $data = $this->weatherService->getWeather($this->getFormatVille($this->defaultCity));                
            }

            //suppression des tirets dans le name de OpenWeather
            $data['ville'] = $this->getPublishVille($data['ville']);
            
            return $this->render('weather/index.html.twig', [
                'data' => $data,
                'formMeteo' => $form->createView()
            ]);
        }
    }

    private function skip_accents( $str, $charset='utf-8' ) { 
        $str = htmlentities( $str, ENT_NOQUOTES, $charset );        
        $str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
        $str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str );
        $str = preg_replace( '#&[^;]+;#', '', $str );        
        return $str;
    }

    public function getFormatVille ($ville){
        $ville = strtolower($ville);
        $ville = str_replace(' ', '-', $ville);
        $uriVille = $this->skip_accents( $ville );

        return $ville;
    }

    public function getPublishVille($villeNameOW){
        $ville = str_replace('-', ' ', $villeNameOW);
        return $ville;
    }
}
