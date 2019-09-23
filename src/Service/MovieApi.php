<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RedirectResponse;

class MovieApi
{

    protected $container;
    protected $params;
    protected $apiKey;

    public function __construct($container)
    {
        $this->container = $container;
        $this->params = $this->container->getParameter("movie_api");
        $this->apiKey = "?api_key=" . $this->params["key"];
    }

    public function authenticate()
    {
        $token = $this->createApiRequestToken();

        return $this->authenticateUser($token);
    }

    public function createApiRequestToken()
    {
        $ch = curl_init($this->params["request_token"] . $this->apiKey);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }

    public function authenticateUser($token)
    {
        return $this->params["authenticate_user"] . '/' . $token["request_token"] . "?redirect_to=" . $this->params["redirect_to"];
    }

    public function createApiSession($requestToken)
    {
        $ch = curl_init($this->params["create_session"] . $this->apiKey);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("request_token" => $requestToken)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        ));

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }

    public function getMovies($page)
    {
        $queryParams =  $this->apiKey . "&page={$page}";

        $ch = curl_init($this->params["popular_movies"] . $queryParams);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }
}