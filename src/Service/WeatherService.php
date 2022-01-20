<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use JMS\Serializer\SerializerInterface;

class WeatherService
{
    private $client;
    private $serializer;
    private $apiKey;

    public function __construct(HttpClientInterface $client, SerializerInterface $serializer, $apiKey)
    {
        $this->client = $client;
        $this->serializer = $serializer;
        $this->apiKey = $apiKey;
    }

    /**
     * @return array
     */
    public function getWeather($uri_ville):Array
    {
        //Reglage de l'heure en france
        date_default_timezone_set('Europe/Paris');

        //Requete Open Weather
        $url = 'https://api.openweathermap.org/data/2.5/weather?q='.$uri_ville.'&lang=fr&units=metric&appid=' . $this->apiKey;
        $response = $this->client->request('GET',$url);

        
        $statusCode = $response->getStatusCode();
        switch($statusCode){
            case 200:
                continue;
            break;
            case 404:
                throw new NotFoundHttpException("ERR ".$statusCode." : La ville que vous avez saisie n'a pas été trouvé !");
            break;
            case 403:
                throw new NotFoundHttpException("ERR ".$statusCode." : Vous n'êtes pas autorisé à consulter cette page !");
            break;

            default:
                throw new NotFoundHttpException("ERR ".$statusCode." : Une erreur c'est produite sur le serveur, veuillez réessayer ultérieurement.");
        }
            
        //Mise e form des datas
        $data = $this->serializer->deserialize($response->getContent(),'array', 'json');
        
        //Retourne un tableau de données
        return [
            'date' => date('d/m/Y'),
            'heure' => date('H:i'),
            'ville' => $data['name'],
            'description' => $data['weather'][0]['description'],
            'temperature' => round($data['main']['temp'], 1),
            'ressentie' => round($data['main']['feels_like'], 1),
            'pression' => $data['main']['pressure'], 
            'precipitation' => $data['weather'][0]['main'],
            'humidite' => $data['main']['humidity'],
            'vent' => $data['wind']['speed']*3.6,
            'icon' => $data['weather'][0]['icon'],
            'leve' => date("H",$data['sys']['sunrise'])."h".date("i",$data['sys']['sunrise']),
            'couche' => date("H",$data['sys']['sunset'])."h".date("i",$data['sys']['sunset'])
        ];
    }
}
